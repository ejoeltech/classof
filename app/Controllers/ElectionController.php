<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Guard;
use App\Core\Request;
use App\Models\Election;
use App\Models\ElectionCandidate;
use App\Models\ElectionPosition;
use App\Models\Membership;
use App\Models\Vote;

final class ElectionController extends Controller
{
    public function index(string $slug): void
    {
        [$group, $membership] = $this->groupContext($slug);

        $elections = Election::forGroup((int) $group['id']);

        foreach ($elections as &$election) {
            $election['turnout'] = Election::turnout((int) $election['id'], (int) $group['id']);
            $election['my_votes'] = count(Election::votedPositionIds((int) $election['id'], (int) user()['id']));
        }
        unset($election);

        $this->view('elections/index', [
            'group' => $group,
            'membership' => $membership,
            'isManager' => Guard::canManage($membership),
            'elections' => $elections,
        ]);
    }

    public function store(string $slug): void
    {
        [$group] = $this->groupContext($slug, ['owner', 'admin', 'exco']);

        $data = $this->validate([
            'title' => 'required|min:5|max:180',
            'description' => 'max:3000',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date',
        ]);

        $starts = strtotime((string) $data['starts_at']);
        $ends = strtotime((string) $data['ends_at']);

        if ($starts === false || $ends === false) {
            $this->fail(['starts_at' => 'Enter valid voting dates.']);
        }

        if ($ends <= $starts) {
            $this->fail(['ends_at' => 'Voting must close after it opens.']);
        }

        $id = Election::create([
            'group_id' => (int) $group['id'],
            'title' => $data['title'],
            'description' => $data['description'] ?: null,
            'starts_at' => date('Y-m-d H:i:s', $starts),
            'ends_at' => date('Y-m-d H:i:s', $ends),
            'created_by' => (int) user()['id'],
        ]);

        $this->audit((int) $group['id'], 'election.created', 'Created the election "' . $data['title'] . '"', 'election', $id);
        $this->flashSuccess('Election created as a draft. Add positions and candidates, then open voting.');
        $this->redirect('groups/' . $slug . '/elections/' . $id . '/manage');
    }

    public function show(string $slug, string $id): void
    {
        [$group, $membership] = $this->groupContext($slug);
        $election = $this->findElection((int) $group['id'], (int) $id);
        $isManager = Guard::canManage($membership);

        if ($election['status'] === 'draft' && !$isManager) {
            \App\Core\Response::abort(403, 'This election has not been published yet.');
        }

        $positions = ElectionPosition::forElection((int) $election['id']);
        $votedPositions = Election::votedPositionIds((int) $election['id'], (int) user()['id']);
        $live = Election::isLive($election);
        $canSeeResults = $election['status'] === 'published' || $isManager;

        foreach ($positions as &$position) {
            $position['candidates'] = ElectionCandidate::forPosition((int) $position['id']);
            $position['i_voted'] = in_array((int) $position['id'], $votedPositions, true);
        }
        unset($position);

        $results = $canSeeResults && in_array($election['status'], ['closed', 'published'], true)
            ? Vote::results((int) $election['id'])
            : [];

        $this->view('elections/show', [
            'group' => $group,
            'membership' => $membership,
            'isManager' => $isManager,
            'election' => $election,
            'positions' => $positions,
            'live' => $live,
            'votedPositions' => $votedPositions,
            'results' => $results,
            'turnout' => Election::turnout((int) $election['id'], (int) $group['id']),
            'hasVotedAny' => $votedPositions !== [],
        ]);
    }

    public function vote(string $slug, string $id): void
    {
        [$group] = $this->groupContext($slug);
        $election = $this->findElection((int) $group['id'], (int) $id);
        $userId = (int) user()['id'];

        if (!Election::isLive($election)) {
            $this->flashError('Voting is not open for this election.');
            $this->redirect('groups/' . $slug . '/elections/' . $id);
        }

        $positions = ElectionPosition::forElection((int) $election['id']);
        $votedPositions = Election::votedPositionIds((int) $election['id'], $userId);
        $cast = 0;
        $alreadyVoted = 0;

        foreach ($positions as $position) {
            $positionId = (int) $position['id'];

            if (in_array($positionId, $votedPositions, true)) {
                $alreadyVoted++;
                continue;
            }

            $candidateId = (int) Request::input('candidate_' . $positionId, 0);

            if ($candidateId === 0) {
                continue;
            }

            $candidate = Database::fetch(
                'SELECT * FROM election_candidates WHERE id = ? AND position_id = ? AND status = "approved" LIMIT 1',
                [$candidateId, $positionId]
            );

            if ($candidate === null) {
                continue;
            }

            if (Vote::cast((int) $election['id'], $positionId, $candidateId, $userId)) {
                $cast++;
            }
        }

        if ($cast === 0) {
            $this->flashError($alreadyVoted > 0
                ? 'You have already voted for every position on this ballot.'
                : 'Select at least one candidate before submitting your ballot.');
            $this->redirect('groups/' . $slug . '/elections/' . $id);
        }

        $this->audit((int) $group['id'], 'vote.cast', 'Cast a secret ballot in "' . $election['title'] . '"', 'election', (int) $election['id']);
        $this->flashSuccess('Your ballot has been recorded. Thank you for voting.');

        if ($election['status'] === 'published') {
            $this->redirect('groups/' . $slug . '/elections/' . $id . '/results');
        }

        $this->redirect('groups/' . $slug . '/elections/' . $id);
    }

    public function results(string $slug, string $id): void
    {
        [$group, $membership] = $this->groupContext($slug);
        $election = $this->findElection((int) $group['id'], (int) $id);
        $isManager = Guard::canManage($membership);

        if ($election['status'] !== 'published' && !$isManager) {
            $this->flashError('Results have not been published yet.');
            $this->redirect('groups/' . $slug . '/elections/' . $id);
        }

        $this->view('elections/results', [
            'group' => $group,
            'membership' => $membership,
            'isManager' => $isManager,
            'election' => $election,
            'results' => Vote::results((int) $election['id']),
            'turnout' => Election::turnout((int) $election['id'], (int) $group['id']),
        ]);
    }

    public function manage(string $slug, string $id): void
    {
        [$group, $membership] = $this->groupContext($slug, ['owner', 'admin']);
        $election = $this->findElection((int) $group['id'], (int) $id);

        $positions = ElectionPosition::forElection((int) $election['id']);

        foreach ($positions as &$position) {
            $position['candidates'] = ElectionCandidate::forPosition((int) $position['id']);
        }
        unset($position);

        $this->view('elections/manage', [
            'group' => $group,
            'membership' => $membership,
            'election' => $election,
            'positions' => $positions,
            'members' => Membership::list((int) $group['id'], ['status' => 'active']),
            'turnout' => Election::turnout((int) $election['id'], (int) $group['id']),
        ]);
    }

    public function addPosition(string $slug, string $id): void
    {
        [$group] = $this->groupContext($slug, ['owner', 'admin']);
        $election = $this->findElection((int) $group['id'], (int) $id);

        if (!in_array($election['status'], ['draft', 'open'], true)) {
            $this->flashError('Positions cannot be changed once an election is closed.');
            $this->redirect('groups/' . $slug . '/elections/' . $id . '/manage');
        }

        $data = $this->validate([
            'title' => 'required|min:2|max:120',
            'description' => 'max:255',
            'seats' => 'integer',
        ]);

        $sort = (int) Database::value(
            'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM election_positions WHERE election_id = ?',
            [(int) $election['id']],
            1
        );

        ElectionPosition::create(
            (int) $election['id'],
            (string) $data['title'],
            $data['description'] ?: null,
            max(1, (int) ($data['seats'] ?: 1)),
            $sort
        );

        $this->audit((int) $group['id'], 'election.position_added', 'Added position "' . $data['title'] . '"', 'election', (int) $election['id']);
        $this->flashSuccess('Position added.');
        $this->redirect('groups/' . $slug . '/elections/' . $id . '/manage');
    }

    public function deletePosition(string $slug, string $id, string $positionId): void
    {
        [$group] = $this->groupContext($slug, ['owner', 'admin']);
        $election = $this->findElection((int) $group['id'], (int) $id);
        $position = $this->findPosition((int) $election['id'], (int) $positionId);

        if (!in_array($election['status'], ['draft', 'open'], true)) {
            $this->flashError('Positions cannot be changed once voting has closed.');
            $this->redirect('groups/' . $slug . '/elections/' . $id . '/manage');
        }

        ElectionPosition::delete((int) $position['id']);
        $this->audit((int) $group['id'], 'election.position_deleted', 'Removed position "' . $position['title'] . '"', 'election', (int) $election['id']);
        $this->flashSuccess('Position removed.');
        $this->redirect('groups/' . $slug . '/elections/' . $id . '/manage');
    }

    public function addCandidate(string $slug, string $id, string $positionId): void
    {
        [$group] = $this->groupContext($slug, ['owner', 'admin']);
        $election = $this->findElection((int) $group['id'], (int) $id);
        $position = $this->findPosition((int) $election['id'], (int) $positionId);

        if (!in_array($election['status'], ['draft', 'open'], true)) {
            $this->flashError('Candidates cannot be changed once voting has closed.');
            $this->redirect('groups/' . $slug . '/elections/' . $id . '/manage');
        }

        $data = $this->validate([
            'user_id' => 'required|integer',
            'manifesto' => 'max:3000',
        ]);

        $membership = Membership::forUser((int) $group['id'], (int) $data['user_id']);

        if ($membership === null || $membership['status'] !== 'active') {
            $this->fail(['user_id' => 'Choose an active member of this group.']);
        }

        $exists = Database::fetch(
            'SELECT id FROM election_candidates WHERE position_id = ? AND user_id = ? LIMIT 1',
            [(int) $position['id'], (int) $data['user_id']]
        );

        if ($exists !== null) {
            $this->flashError('That member is already a candidate for this position.');
            $this->redirect('groups/' . $slug . '/elections/' . $id . '/manage');
        }

        ElectionCandidate::create((int) $position['id'], (int) $data['user_id'], (string) ($data['manifesto'] ?? ''));

        $this->audit((int) $group['id'], 'election.candidate_added', 'Added a candidate for "' . $position['title'] . '"', 'election', (int) $election['id']);
        $this->flashSuccess('Candidate added.');
        $this->redirect('groups/' . $slug . '/elections/' . $id . '/manage');
    }

    public function deleteCandidate(string $slug, string $id, string $candidateId): void
    {
        [$group] = $this->groupContext($slug, ['owner', 'admin']);
        $election = $this->findElection((int) $group['id'], (int) $id);
        $candidate = ElectionCandidate::find((int) $candidateId);

        if (!in_array($election['status'], ['draft', 'open'], true)) {
            $this->flashError('Candidates cannot be changed once voting has closed.');
            $this->redirect('groups/' . $slug . '/elections/' . $id . '/manage');
        }

        if ($candidate === null || (int) $candidate['election_id'] !== (int) $election['id']) {
            \App\Core\Response::abort(404, 'That candidate does not exist.');
        }

        ElectionCandidate::delete((int) $candidate['id']);
        $this->audit((int) $group['id'], 'election.candidate_removed', 'Removed a candidate', 'election', (int) $election['id']);
        $this->flashSuccess('Candidate removed.');
        $this->redirect('groups/' . $slug . '/elections/' . $id . '/manage');
    }

    public function setStatus(string $slug, string $id): void
    {
        [$group] = $this->groupContext($slug, ['owner', 'admin']);
        $election = $this->findElection((int) $group['id'], (int) $id);
        $action = Request::string('action');

        if ($action === 'open') {
            if ($election['status'] !== 'draft') {
                $this->flashError('Only a draft election can be opened.');
                $this->redirect('groups/' . $slug . '/elections/' . $id . '/manage');
            }

            $count = (int) Database::value(
                'SELECT COUNT(*) FROM election_candidates c
                 INNER JOIN election_positions p ON p.id = c.position_id
                 WHERE p.election_id = ?',
                [(int) $election['id']],
                0
            );

            if ($count === 0) {
                $this->flashError('Add at least one candidate before opening voting.');
                $this->redirect('groups/' . $slug . '/elections/' . $id . '/manage');
            }

            if (strtotime((string) $election['ends_at']) < time()) {
                $this->flashError('The voting end date is in the past. Update the election dates first.');
                $this->redirect('groups/' . $slug . '/elections/' . $id . '/manage');
            }

            if (strtotime((string) $election['starts_at']) > time()) {
                Database::update('elections', ['starts_at' => date('Y-m-d H:i:s')], ['id' => (int) $election['id']]);
            }

            Election::setStatus((int) $election['id'], 'open');
            $this->audit((int) $group['id'], 'election.opened', 'Opened voting for "' . $election['title'] . '"', 'election', (int) $election['id']);
            $this->flashSuccess('Voting is now open. Members can cast their ballots.');
            $this->redirect('groups/' . $slug . '/elections/' . $id . '/manage');
        }

        if ($action === 'close') {
            if ($election['status'] !== 'open') {
                $this->flashError('Only an open election can be closed.');
                $this->redirect('groups/' . $slug . '/elections/' . $id . '/manage');
            }

            Election::setStatus((int) $election['id'], 'closed');
            $this->audit((int) $group['id'], 'election.closed', 'Closed voting for "' . $election['title'] . '"', 'election', (int) $election['id']);
            $this->flashSuccess('Voting closed. Review the results, then publish them.');
            $this->redirect('groups/' . $slug . '/elections/' . $id . '/manage');
        }

        if ($action === 'publish') {
            if ($election['status'] !== 'closed') {
                $this->flashError('Close voting before publishing results.');
                $this->redirect('groups/' . $slug . '/elections/' . $id . '/manage');
            }

            Election::setStatus((int) $election['id'], 'published');
            $this->audit((int) $group['id'], 'election.published', 'Published results for "' . $election['title'] . '"', 'election', (int) $election['id']);
            $this->flashSuccess('Results published to all members.');
            $this->redirect('groups/' . $slug . '/elections/' . $id . '/results');
        }

        $this->flashError('Unknown election action.');
        $this->redirect('groups/' . $slug . '/elections/' . $id . '/manage');
    }

    /** @return array<string, mixed> */
    private function findElection(int $groupId, int $electionId): array
    {
        $election = Election::find($electionId);

        if ($election === null || (int) $election['group_id'] !== $groupId) {
            \App\Core\Response::abort(404, 'That election does not exist.');
        }

        return $election;
    }

    /** @return array<string, mixed> */
    private function findPosition(int $electionId, int $positionId): array
    {
        $position = ElectionPosition::find($positionId);

        if ($position === null || (int) $position['election_id'] !== $electionId) {
            \App\Core\Response::abort(404, 'That position does not exist.');
        }

        return $position;
    }
}
