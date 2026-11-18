# Nigerian Institution Catalogue, Eligibility and Join-First Onboarding

* **Author:** Goldfish review synthesis
* **Date:** 2026-11-10

## Overview

Five linked changes are proposed: (1) an imported Nigerian institution/course catalogue with user-submitted unverified entries, (2) multi-record user education profiles whose exact-normalised matches surface suggestions while proof-backed admin approval still governs joining, (3) de-emphasised group creation behind a request-and-verify form, (4) admin-managed WhatsApp invite links plus WhatsApp Business Cloud API messaging, and (5) join suggestions (dashboard top three, a "see all" page, ranking, reasons, new-group alerts, friends/classmates).

Six review lenses (design, codebase-details, zoom-out, comprehension, implementation-readiness, structural) examined the concept against the working code at `C:\xampp\htdocs\classof`. This brief records their findings, deduplicated and normalised. Severity is marked 🔴 Blocker, 🟡 Major, 🟢 Nit.

The single most important finding: **the concept is not currently implementable as written, because the codebase has no additive schema path, no settings storage, no notification/scheduling infrastructure, and no canonical identity for institutions or groups.** These are shared prerequisites, not per-ask details. The concept is also partly a **data-cleanup project** (mapping existing free-text affiliation to catalogue IDs) disguised as a feature.

Two reviewers (zoom-out, structural) dissent from the scope itself: both argue most of the user value ships with one new table, one query, and a copy change, and that the catalogue, policy engine, Cloud API and social graph are premature. That dissent is recorded in Alternatives Weighed.

**Revision after review:** the full five-ask scope is retained, but staged. Release 1 is deliberately narrow: the additive migration path, a Nigerian tertiary institution catalogue (institutions only), multiple education records, exact institution + graduation-year suggestions, institution-level association suggestions, and request-and-verify group creation. Faculty/department/programme matching, configurable verification modes, WhatsApp Cloud API delivery, new-group alerts and friends/classmates are deferred to later releases, after the base survives real use.

## Analysis

### 1. Institution and course catalogue

**1.1 🔴 No migration path exists for any new table or column.**
`database/schema.sql:10-43` drops and recreates all 30 tables; `database/install.php:60-76` splits the file on `;\s*(?:\r?\n|$)` and skips any chunk beginning with `--`. This is fine for demo data but not for a live `payments`/`coop_transactions`/`votes` ledger. There is no `migrations/` directory, no `schema_migrations` table, no rollback. Until an additive runner exists, none of the five asks can ship safely.
*Direction:* add `schema_migrations` plus a CLI `database/migrate.php` applying numbered additive SQL. Keep `schema.sql` in sync for fresh installs, and add each new table to the `DROP` block in child-first order.

**1.2 🔴 The documented fresh-install path is already broken, and it constrains catalogue loading.**
Verified in code: the first split chunk is the two header comment lines *plus* `CREATE DATABASE classof …;`, so line 70 (`str_starts_with($statement, '--')`) skips it. The next chunk is `USE classof`, which fails on a fresh server because the DSN has no `dbname` (`install.php:41`). Reset works only because the database already exists. Separately, the splitter corrupts any statement whose value contains `;` followed by a newline — so catalogue INSERTs must never live in `schema.sql`.
*Direction:* fix the splitter (strip comment lines before splitting, or execute statement-by-statement), and load catalogue data through a dedicated CLI importer, not `schema.sql`.

**1.3 🔴 Free text is not matchable, and the demo shows why.**
`alumni_groups.institution`, `faculty`, `department` are `VARCHAR(160)` free text with no normalised key and no index (`schema.sql:93-95`, keys are only `type`/`scope`/`status` at `:110-112`). The seed is internally consistent on the `institution` value (`"University of Lagos"` at `seed.php:132,159,171,184`; `"King's College, Lagos"` at `:119,198`), but display names already diverge from it: group names embed the abbreviation "UNILAG" (`seed.php:175`) and drop the comma (`"King's College Lagos Old Boys Association"`, `:195`). `utf8mb4_unicode_ci` handles case and accents, not punctuation, abbreviations or "Faculty of" prefixes. Any exact-normalised rule built on these strings will miss most real input, and seed department text is `"Department of Computer Science"` (`:186`) where a JAMB-style canonical name would be `"Computer Science"`.
*Direction:* matching must be ID-based (`institution_id`, `faculty_unit_id`, `department_unit_id`), with a stored `name_key` normaliser (trim, collapse whitespace, lowercase, fold accents, strip leading `Faculty of`/`Department of`/`The`) plus an `institution_aliases` table for abbreviations. Free text stays as a display fallback only.

**1.4 🔴 The catalogue's data model, source, licence and owner are undefined.**
No table, file or format exists, and there is no Composer/CSV library or general HTTP fetch layer (`app/Services/Payments/HttpClient.php` is payment-specific). Unresolved: institutions→faculties→departments→programmes cardinality (are departments global or institution-scoped? do programmes attach to departments or institutions?); mandatory fields; ownership (federal/state/private) and state; provenance, licence, attribution and snapshot date; and mapping into the existing seven type strings — note the enum value is `'college'`, labelled "College of education" (`Group.php:16`, `schema.sql:91`).
*Direction:* write DDL plus three sample rows per table, name the dataset and licence, store `source_url`/`dataset_version`/`imported_at`, and record a per-run import summary (inserted/updated/skipped/rejected) in `catalogue_import_runs` surfaced in the admin console.

**1.5 🟡 The catalogue as described is tertiary-only and conflicts with the product's own types and demo.**
`Group::TYPES` includes primary, secondary, polytechnic, college and vocational (`Group.php:11-19`), and the demo includes a primary school and a secondary old-boys association (`seed.php:116,142,195`). Primary/secondary schools have no faculty, department or JAMB programme; polytechnics and colleges use schools, not faculties. A form that forces the university shape pushes garbage into faculty/department fields for other institution types.
*Direction:* state a coverage table (level → catalogue-backed or free text → which sub-units exist). Make the education record type-driven: which unit fields are required depends on institution type. `association` and `other` are never catalogue-matched.

**1.6 🟡 No uniqueness invariant on groups, so suggestions amplify duplicates and slugs are squatted.**
There is no unique constraint on (institution, scope, unit, year). `uniqueSlug()` appends `-1`, `-2` and reserves the slug across all statuses (`Group.php:80-92`) — a rejected or archived request permanently burns a canonical slug, and creation has no duplicate check (`GroupController::store`, `:37-52`). Retrofitting uniqueness after launch means merging groups that already hold payments, welfare, elections and co-op ledgers — effectively impossible.
*Direction:* define a canonical key and check for an existing equivalent group *before* accepting a request, routing the user to "request to join". Release slugs on rejection/archival.

**1.7 🟡 An "unverified institution" is either a global namespace or unusable.**
If it is a global row, it is an unauthenticated write into a shared namespace (typos, duplicates, squatting). If it is per-user, it cannot anchor matching. While pending it cannot anchor group verification or suggestions, stranding that user. There is no dedupe/similarity check and no rate limit on `Group::create`.
*Direction:* unverified institutions are `pending` global rows with `created_by`, a similarity check against existing names/aliases, per-user rate limiting, an admin merge/rename flow, and a visible queue status for the submitter. Education records may reference a pending institution; group creation and suggestions require `verified`.

**1.8 🟢 Programme/course has no matching consumer.**
No group is programme-scoped — `scope` has no `programme` value — so a programme field can never match, and a JAMB course list exists only to populate a dropdown nobody filters on. JAMB names programmes, not departments, so mapping them is itself a curation project.
*Direction:* treat programme as display-only on the education record, or cut it from v1.

**1.9 🟢 Naming.**
Do not name tables `nigerian_*`: the platform ships nine currencies (`Group.php:270-273`) and describes itself as multi-tenant. Use `institutions.country_code` and scope only the first import to NG. `group.country` should be derived from the institution and stop being accepted as free text (`schema.sql:97`, `groups/create.php:83`).

### 2. Education records and matching rules

**2.1 🔴 Combined class+faculty/department groups are not representable.**
`alumni_groups.scope` is a single ENUM `institution|class|department|faculty|association|other` (`schema.sql:92`, `Group.php:26-33`). Today a `class` group *may* also carry `faculty`/`department` (the form exposes them, `groups/create.php:62-72`; `groupPayload()` persists them, `GroupController.php:380-395`) with no defined semantics, and `descriptor()` for `class` ignores both, rendering only "Class of YYYY" (`Group.php:258`). So a combined group's specificity is invisible, unrankable, and its matching requirement is undefined.
*Direction:* do not add combinatorial enum values. Define matching as "every populated affiliation attribute must match" (institution always; graduation year, faculty, department when non-null), and store a computed `specificity` integer at verification time to rank on. Update `descriptor()` to render every qualifier.

**2.2 🟡 `association` and `other` have no eligibility rule but are first-class today.**
The demo's King's College Old Boys Association is `scope='association'` with an institution and no year (`seed.php:195-204`) — likely the highest-value suggestion for those alumni. `other` is fully custom.
*Direction:* define rules for all six scopes. Recommend `association` matches at institution level but requires a verified institution; `other` is never auto-suggested.

**2.3 🟡 "Exact-normalised matching" and the "set"/"class" year axis are undefined, and the word "eligible" is already taken.**
In Nigerian secondary usage "set" is the leaving cohort, so a repeated year creates an off-by-one. The concept uses four names for one axis ("set", "class", "year group", "graduation year"). Additionally, "eligible" already means "active member permitted to vote" in elections (`elections/show.php:236`, `Election.php:129-133`), so "eligible groups" collides.
*Direction:* one canonical field, label "Year your set left school (the year you finished)", tertiary variant "year you graduated". Use "Groups that match your education" and a "Matched" badge; reserve "eligible" for ballots.

**2.4 🔴 Worked example — the UNIBEN six-group case.**
User **E**: institution UNIBEN, faculty Faculty of Science, department Computer Science, programme B.Sc. Computer Science, graduation year 2016.

| Group | Stored scope / fields | Rule applied | E matches? |
| --- | --- | --- | --- |
| G1 UNIBEN Alumni Association | institution | institution | Yes |
| G2 UNIBEN Class of 2016 | class + year 2016 | institution + year | Yes |
| G3 Faculty of Science, UNIBEN — Alumni | faculty | institution + faculty | Yes |
| G4 Department of Computer Science, UNIBEN — Alumni | department | institution + department | Yes |
| G5 Class of 2016 · Faculty of Science, UNIBEN | class + year + faculty | institution + year + faculty | Yes |
| G6 UNIBEN Old Students Association | association | undefined (recommend institution-level) | Yes, if rule defined |

Cross-checks that expose why "exact" matters:
- User **F** (UNIBEN, Faculty of Science, **Political Science**, 2016): G1, G2, G3, G5, G6 match; G4 does **not** (department differs). Ranking must put G5 above G2/G3.
- User **G** (UNIBEN, Faculty of Science, Computer Science, **2015**): G1, G3, G4, G6 match; G2 and G5 do **not** (year differs).
- Because no group is programme-scoped, G4 matches a Computer Science *and* a Microbiology user in the same department — programme is stored but never matched (see 1.8).

This table is the eligibility matrix the implementer needs; it must be finalised in writing before any query is coded (2.5).

**2.5 🔴 There is no eligibility matrix and no service boundary.**
The concept gives three different matching models in one paragraph ("match on institution", "require the corresponding unit", "combined … require all details") without stating, per scope *and* per filled-field combination, what is required, matched, or ignored, or the ranking weights. Eligibility is needed by `DashboardController`, `HomeController::explore`, `GroupController::join`, `MemberController::approve` and admin review. Written inline it will exist in five places with five normalisation behaviours. Models in this codebase are table-access only (`Group.php`, `Membership.php`).
*Direction:* produce one printed matrix (one row per scope, columns = required / match-if-present / ignored, with a weight), and implement a single `app/Services/EligibilityService.php` returning `{group_id, matched, reasons[], missing[]}`. No eligibility SQL outside the service. The only existing domain-service precedent is `CoopService`.

**2.6 🔴 The current validator cannot carry multi-record education, and `old()` on nested rows will break.**
`Validator::apply` coerces non-scalars to `''` for `min`/`max`/`in` but treats a non-empty array as non-empty (`Validator.php:50-51`), so `max:160` on a nested field passes silently; `Controller::validate()` returns only declared top-level keys (`Controller.php:148-153`); and views call `e(old('field'))`, where `e()` casts to string (`helpers.php:18-21`), so an array triggers an "Array to string conversion" that the global handler turns into a 500.
*Direction:* use one record per request (`POST /profile/education`, `POST /profile/education/{id}/delete`) rather than nested `education[]` arrays, and add an indexed `old_nested()` helper. Do not extend `Validator` ad hoc.

**2.7 🟡 Education records have no verification state, no ownership model, and cannot reuse membership proof.**
All evidence currently lives on the membership row (`proof_type/reference/file`, `schema.sql:124-131`) and is streamed by `ProofController::show()` which authorises owner-or-group-manager after looking up `memberships.proof_file` (`ProofController.php:20-23`). Education claims are user-level and cross-group; a group admin verifying one would gain authority that unlocks other groups (separation-of-duties break). There is also no stated precedence between "education profile" and "proof document", which will surface as inconsistent admin decisions.
*Direction:* new `education_records` (user-owned, `status ENUM('unverified','pending','verified','rejected')`, reviewer columns) and a separate document path authorised owner-or-platform-verifier. State explicitly that an education record is never proof — it only improves suggestion ranking. Group admins keep approving membership, not education.

### 3. Eligibility, proof, privacy and configurable verification

**3.1 🔴 "Auto-approve umbrella groups only" hands the member directory to self-declared claims.**
The product's stated guarantee is proof-backed approval (`README.md:7,20-21`). Active membership grants the member list including email (`Membership::list` selects `u.email, u.phone, u.occupation`), and the member page shows email and phone to any active member. Auto-approving an institution group on an education claim alone both exposes PII and unlocks co-op enrolment, welfare and voting for unverified people. The current settings screen promises the opposite (`groups/settings.php:128`: "always require proof and admin approval").
*Direction:* settle this before designing the settings screen. Recommended: `verified` membership (full access) must be separate from a lesser "declared affiliation" tier with no directory, co-op or voting. At minimum require verified email plus an anti-harvest rate limit. "Umbrella" is also undefined — replace with the existing scope name, e.g. "auto-approve institution-wide (all years) groups only", and state whether `association` is included.

**3.2 🔴 The four policy options are not one setting, and there is nowhere to store them.**
`config/config.php:39-91` is read-only env; `Config` has no setter; there is no settings table among the 30 and no admin settings route. The options are also orthogonal ("flag unverified claims" and "auto-approve umbrella groups" can both be on), not a four-value enum, and "verify education profile before requesting" is unfinished — before requesting *what*?
*Direction:* add `platform_settings` (key, value, type, `updated_by`, `updated_at`) with a cached typed accessor, an `/admin/settings` page, and audit on change. Model as named keys (`education.gate_joining`, `education.flag_unverified`, `education.auto_approve_institution_scope`) rather than one exclusive enum. Define precedence against per-group `join_policy` (`schema.sql:103`): group `invite` always wins.

**3.3 🔴 Invites bypass every policy and even group state.**
`GroupController::acceptInvite()` (`:230-295`) validates the token and the signed-in email, then writes `status='active'`, `proof_type='invitation'` with no proof and no check of the group's status or visibility. `Invite::findByToken()` joins `alumni_groups` without a status filter. Any "verify before requesting" or "auto-approve only" policy is false the moment an invite exists.
*Direction:* decide and document invite behaviour per policy; reject invites whose group is not `active`; if the policy requires verification, an invite should create a `pending` membership, not `active`.

**3.4 🟡 A join request has nowhere to record the eligibility claim.**
`memberships` stores only proof columns (`schema.sql:124-133`); the join form collects only those (`groups/join.php:45-74`); `Membership::approve()` only flips status (`Membership.php:120-133`); and the review screen selects only user profile fields (`MemberController.php:240-256`). So a reviewer cannot see which institution/year/programme the applicant claimed, whether it matched, or whether the claim is verified.
*Direction:* add `memberships.education_record_id`, `claim_match`, `claim_summary` snapshotted at request time (so later profile edits don't rewrite history), surfaced on the review screen; never used to auto-approve. Record policy overrides in the audit log.

**3.5 🟡 The join form's relationship to the education record is unspecified.**
If the education record already holds institution/programme/year, it is unclear which join fields are pre-filled, which remain mandatory, and what the reviewer sees. Keep "proof" defined inline (ID, certificate, transcript, admission letter) and show the reviewer "claimed education" beside "submitted proof".

**3.6 🟡 "Verified education profile" collides with the existing "verified" badge, and has no message for already-approved members.**
An active member is told "Your membership is verified" (`members/show.php:184`) and "Proof verified" (`:7`). Onboarding that then asks them to "verify your education profile" reads as lost verification or a redundant check. "Unverified claim" then sounds like an accusation.
*Direction:* give the facts different words: membership → "Approved" / "Approved by admin"; education → "Education confirmed" / "Not yet confirmed". Write the already-approved case explicitly.

**3.7 🟡 "Unverified institution" and "unverified claim" are two different objects.**
Name them separately: "Unlisted institution — pending catalogue review" vs "Education record not confirmed". Specify the reviewer (platform admin), the queue screen, the submitter-facing status text, and what happens on rejection.

**3.8 🟡 Two review queues with no tooling, and a conflict of interest in the demo.**
`platform_role` is a two-value enum; `AdminController` offers only user/group status toggles (`:16-111`); proof review is per-group, not a platform queue. The seed's platform admin owns UNILAG Class of 2016 and the institution association and is a KC admin (`seed.php:138,164,238`) — the verifier is also a beneficiary.
*Direction:* add a scoped `moderator` role, a filtered review queue with ageing and bulk actions, `verified_by`/`verified_at` on groups and education records, old→new audit diffs, and a rule that a reviewer cannot verify a group they own or belong to.

### 4. Onboarding, suggestions, notifications and the group-creation request path

**4.1 🔴 "Suggestions from signup data" is impossible — signup captures no education data.**
Registration collects name, email, phone, password and redirects to the dashboard with a "create a group" message (`AuthController.php:58-85`); `email_verified_at` exists but is never set (`schema.sql:57`); the profile is one flat form (`ProfileController.php:42-56`, `profile/edit.php:24-74`) with no education section.
*Direction:* decide and state the onboarding path (post-signup step feeding the same education write path, or a first-dashboard prompt), whether it is skippable, and update the post-registration copy. Change the wording to "education records added after signup"; signup stays four fields.

**4.2 🟡 Suggestions must exclude private/invite-only groups or every card is a dead end.**
`joinConfirm()` and `join()` reject `visibility='private'` or `join_policy='invite'` (`GroupController.php:129-131,155-158`), and `Group::explore` filters `visibility='public'` (`Group.php:120`). Matching on education alone would surface the seeded private Greenfield group (`seed.php:141-153`) and any invite-only group, leaking their existence and dead-ending.
*Direction:* one shared query builder for both "top three" and "see all": eligible = `status='active' AND visibility='public' AND join_policy='approval'`, not already member/pending/rejected.

**4.3 🟡 "See all eligible groups" duplicates Explore and creates a fourth discovery surface.**
Explore already exists with keyword + type + scope filters and a guest and signed-in template (`HomeController.php:32-63`). Adding dashboard top-three, a "see all" page, sidebar "Explore groups", and alerts gives four ways to reach an unjoined group.
*Direction:* make matching a mode on `/explore` (`?for=you`) implemented in the signed-in view, with reason badges; dashboard gets one compact card linking there. Do not add a sidebar item.

**4.4 🔴 Alerts need infrastructure that does not exist.**
There is no `notifications` table, no badge/read-state, no queue or scheduler, and `Mailer::send()` always appends to `storage/logs/mail.log` (`Mailer.php:11-16`). "New matching group" alerts also conflict with change 3: a new group is invisible until admin-verified. `users.last_login_at` is login-only, so "new since last visit" has no reliable cursor.
*Direction:* v1 is a derived in-app badge using a new `users.last_seen_at`, computed on read; alert only after a group is verified, capped per user, with an outbox for later delivery. Never alert on pending groups.

**4.5 🟡 Ranking and alert copy are unspecified.**
"More specific first" has no weights or tie-breakers. Recommended ladder: class+department/class+faculty > department > faculty > class > institution-wide > association, with a stored `specificity` and stable tie-breaks (member_count, created_at). Read "when a new matching group is **approved**", not "created".

**4.6 🟡 "Friends/classmates" has no data model and is the biggest privacy risk in the brief.**
There is no friendship/connection table; the only cross-user helper `User::search()` returns email/occupation platform-wide (`User.php:62-75`). Building friend suggestions therefore means either restating the main suggestion (co-membership/same education) or contact/phone upload matching — a consent problem under the Nigeria Data Protection Act 2023, on top of an unverified optional `users.phone`.
*Direction:* scope this to "people with the same confirmed education record who are already on Classof", surfaced inside the matching page, opt-in, with name + avatar + shared institution/year only. No contact upload. Otherwise cut it.

**4.7 🔴 Group creation has no lifecycle state, and a pending group cannot be viewed by its own requester.**
`alumni_groups.status` is only `active|archived` (`schema.sql:104`); `Group::create()` hardcodes `'active'` (`Group.php:73`); and `Controller::findGroup()` 404s any non-active group for non-platform-admins (`Controller.php:24-26`), so the requester cannot see their own request. `AdminController::groupStatus()` flips any non-active status back to `active`, bypassing verification, and `Group::paginate()` has no status filter, so there is no queue.
*Direction:* add `verification_status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'verified'` (existing rows stay discoverable; the ~20 `status = "active"` queries are untouched), filter it only in explore/suggestions/request views, extend `findGroup()` to allow the owner to view their own pending group, and add explicit admin transitions plus a queue with counts. Separate `group_requests` from `alumni_groups` so a rejected request does not burn a slug (see 1.6).

**4.8 🔴 Creation is promoted in five to seven surfaces; de-emphasis is the real work.**
Sidebar (`layouts/app.php:57-59`), topbar (`:97`), dashboard header (`dashboard/index.php:14`), dashboard empty state (`:40`), Explore header and empty state (`groups/explore.php:10,39`), public homepage hero and steps (`home/index.php:21,149-151,203`), and the post-registration flash (`AuthController.php:82`). `GET /groups/create` must 404, redirect or become admin-only, or the ask is contradicted.
*Direction:* list every surface as acceptance criteria. Keep one entry point (`/profile/group-requests`) plus one contextual link from the Explore empty state ("Can't find your school? Request it"). Change the homepage CTA to "Find your alumni group".

**4.9 🟡 There is no account-settings area, and "settings" means three different scopes.**
Account has only `/profile` (three routes); group settings exist (`/groups/{slug}/settings`); platform settings do not. The concept says "under account settings" and "configurable in settings" in different senses.
*Direction:* fix the hierarchy and ban the bare word "settings" in the spec: account = `/profile` sub-pages (`/profile/education`, `/profile/group-requests`); group = a card in group settings; platform = `/admin/settings`. Do not create a singleton `/settings` route.

**4.10 🔴 Route ordering and pagination are ready to bite.**
The router iterates in registration order with `([^/]+)` per placeholder (`Router.php:37-85`); `/groups/create` resolves only because it precedes `/groups/{slug}` (`routes/web.php:50` vs `:55`). Any new literal `/groups/...` after line 55 is swallowed as a slug, and `/admin/groups/{id}/status` would swallow `/admin/groups/requests`. `Group::explore()` has no offset, so a "see all" page has no pager.
*Direction:* put no new static segments under `/groups/`; declare literal segments before parameterised ones; add `offset` + `countEligible()` or a dedicated page method, reusing the admin pagination pattern. Consider splitting the single 183-line route file.

**4.11 🟡 Suggestion-driven volume exposes per-group query fan-out.**
`DashboardController` loops `Group::forUser()` twice, one dues query per group, then co-op lookups with savings/shares/loan per group (`DashboardController.php:27-77`); `layouts/app.php:4` runs `Group::forUser` again on every authenticated page. At 2 groups nobody notices; at 10 it is 20+ queries per load. `Membership::nextNumber()` uses `COUNT(*)+1` with no unique key on `membership_no` (`Membership.php:74-83`), so burst joins can collide on visible member numbers.
*Direction:* set-based queries (`WHERE group_id IN (...)`), memoise `Group::forUser` per request, drop member counts from the nav query, add `UNIQUE(group_id, membership_no)` with retry. Recommend no suggestion cache in v1 and document the query budget.

**4.12 🟡 No JSON endpoints or fetch exist for catalogue cascades.**
`public/assets/js/app.js` contains no `fetch`/`XMLHttpRequest`; the only scope logic is hidden-field toggling. Dependent selects (institution → faculty → department) need a decision.
*Direction:* prefer server-rendered multi-step selection (`?institution_id=` query param) for v1 over new JSON routes and JS.

### 5. WhatsApp integration

**5.1 🔴 "Integration" cannot mean reading, posting inside, or joining private WhatsApp groups — and the concept already says so, but the copy must match.**
The app can store an invite link and call the Cloud API to message an opted-in phone. It cannot read group messages, post into a group, or auto-join. The homepage currently advertises "no WhatsApp polling" (`home/index.php:86`), which would self-contradict.
*Direction:* write the contract in the UI: "Anyone with this WhatsApp link can join the WhatsApp group. It does not add anyone to Classof." Keep "official" out of user copy.

**5.2 🔴 Invite links are credentials and the current surfaces leak them.**
Public group pages are visible to signed-out and non-member users (`groups/public.php:43-58`), and join/approval is the product's whole trust model. An invite link rendered there lets anyone join the real WhatsApp group with no proof. Multiple labelled channels also need per-channel audience — an exco channel must not be visible to plain members — which the four-role set cannot express as a label.
*Direction:* `group_channels` (group_id, label, invite_url, `visible_to ENUM('members','manager')`, `is_primary`, `last_verified_at`, `revoked_at`); render only to authorised active members; validate `https` + host allowlist (`chat.whatsapp.com`/`wa.me`) — note `Validator::url` does not restrict scheme; add `rel="noopener"`; audit every reveal and change; show a "last verified" date and rotation reminder. Keep `label` and `visible_to` separate axes.

**5.3 🔴 Cloud API has none of its prerequisites, and no consent model.**
`users.phone` is nullable, non-unique, unverified and unnormalised (`schema.sql:50`); there is no opt-in/opt-out field, no `phone_e164`, no credential store beyond `.env`, no template registry, no queue/scheduler/worker, no `notifications` table, and no webhook route except payments (`routes/web.php:174-176`). One platform WhatsApp Business number is also a cross-tenant single point of failure: one admin's blast or a spike in reports degrades the number for every group. Note the CSRF exemption for `/webhooks` means signature verification is entirely the app's responsibility.
*Direction:* add `phone_e164`, `phone_verified_at`, `whatsapp_opt_in_at`, `whatsapp_opt_out_at`; per-channel audience and per-group send quota; an `outbox`/`notification_deliveries` table (idempotency key unique, attempts, last_error) written before the HTTP call; a CLI worker via cron; and `GET/POST /webhooks/whatsapp` with `X-Hub-Signature-256` verification. Decide platform-WABA vs per-group WABA and document the blast radius. Make "share the invite link" the default; treat Cloud API as opt-in, budgeted, digest-only.

**5.4 🟡 Reminders and announcements duplicate existing features.**
`announcements` already exists with pinning and a dashboard feed. Cloud API sending would split the record of truth and become the app's only real outbound channel while email remains log-only.
*Direction:* define reminders explicitly (trigger, audience, CLI invocation) or scope v1 to invite links only. Do not build labelled channels until groups ask.

### 6. Migration, backfill, admin workload, cost and operations

**6.1 🔴 The backfill is the project, and it is unspecified.**
Existing groups store free-text affiliation (`seed.php:119,132,145,159,171,184,198`) with no catalogue IDs. Without a mapping pass, every current group is unmatched and the headline "top three suggestions" renders empty for the demo accounts — which is how reviewers will judge the feature.
*Direction:* state the migration: a one-off idempotent admin backfill mapping the seven seeded groups (and any live rows) to catalogue records by normalised key, with unmatched rows flagged "institution not in catalogue" and never suggested. Report counts of matched/unmatched.

**6.2 🔴 Nothing persists the eligibility decision, so verification and disputes cannot be audited.**
The match exists only in a transient SQL result. On-call cannot answer "why was this suggested?" or "why was this claim rejected?", and support escalations on disputed rejections are unresolvable after catalogue edits.
*Direction:* persist a match snapshot (education_record_id, matched dimensions, rank, policy, dataset version) and emit structured log lines with user/group/education ids.

**6.3 🟡 Three new queues on one boolean admin, with no telemetry.**
Institution review, education verification and group verification are added on top of membership review, all assigned to a single `admin` boolean, with no ageing/SLA, no bulk action, no failure metrics and only file logging.
*Direction:* scoped roles, queue filters with ageing, structured counters on each transition, and a small admin health panel.

**6.4 🟡 Operational risks not covered by feature tests.**
- `mail.log` is append-only plaintext containing reset and invite tokens with no rotation; a fan-out launch multiplies it. Treat it as sensitive and move to a real transport.
- Proof re-submission deletes the previous file (`GroupController.php:204-224`), so a reviewer mid-review gets a 404; keep prior versions when reviewers multiply.
- `leave`/`remove` delete the membership row and proof (`GroupController.php:398-413`, `MemberController.php:166-168`), destroying the history that would justify future suggestions. Soft-delete instead.
- `Membership::proofState()` treats any active membership with `reviewed_at` as verified (`Membership.php:148-159`), including invites — distinguish "verified by evidence" from "admitted by invite".
- `audit_logs.description` and `rejection_reason` are `VARCHAR(255)` (`schema.sql:598`); verification notes can exceed it. Use TEXT, and never put invite/WhatsApp URLs or evidence references into the description.
- `slugify()` strips non-ASCII, so same-named institutions in different states collide; decide the unique key is normalised name + state.
- No tests or CI; each slice needs explicit manual acceptance steps.

**6.5 🟢 Cost and volume.**
Cloud API conversation costs and template approvals are external and recurring; a national dataset plus permanent curation is an operational commitment that outlives the feature. Both need a named owner before they are built.

## Goals vs. Non-Goals

* **In-Scope Goals:**
  - Make joining existing groups the primary path: onboarding, education records, matched-group suggestions, and proof-backed approval unchanged as the gate.
  - Represent Nigerian institutions and their units with stable identifiers, and let users pick from them; allow an unlisted institution as a pending record for review.
  - Store multiple education records per user and compute exact-normalised matches per a written matrix, with reasons and deterministic ranking.
  - Move group creation to a lower-visibility request-and-verify path with an explicit lifecycle state and admin queue.
  - Store admin-managed WhatsApp invite links per group and per audience label, shown only to authorised members.
  - Provide a configurable verification policy stored in the database, editable by a platform admin, defaulting to today's behaviour.
  - Provide the shared prerequisites: additive migrations, a settings store, and an outbox/worker for any asynchronous delivery.

* **Out-of-Scope Non-Goals:**
  - Reading, posting inside, or automatically joining private WhatsApp groups.
  - Fuzzy or scored/semantic matching as the eligibility mechanism.
  - Group creation open to any signed-in user, or admin-only creation.
  - Contact/phone-upload friend matching, or any global people search.
  - Real-time notification delivery by push or email in v1.
  - Rewriting the group, membership, dues, welfare, election or co-op modules; all new work is additive.

## Alternatives Weighed

Rejected options are recorded as decided, with reviewer dissent where it is material.

1. **Catalogue source.** Open user-submitted catalogue vs curated seed vs public dataset import vs small verified pilot. **Chosen: public dataset import** (with user-submitted unverified records for gaps). *Dissent:* zoom-out argues none of these is needed for launch because a catalogue with no group to join produces no suggestion; it recommends deferring the catalogue and solving name variance with a small `institutions` + aliases table only when measured.
2. **Matching mechanism.** Strict normalised exact matching vs fuzzy matching vs scored suggestions. **Chosen: exact normalised.** *Caveat:* "exact" is undefinable on free-text columns; it only works if it is ID-based on a stored normalised key (1.3). Fuzzy was rejected because false positives are tolerable (admin approval still gates) and recall matters more than precision.
3. **WhatsApp mechanism.** Share-links-only vs Cloud API notifications vs unofficial bridge. **Chosen: deep links plus Cloud API.** *Caveat:* the unofficial bridge was correctly rejected (fragile, against terms); Cloud API depends on infrastructure that must be built first (5.3). Zoom-out recommends share-links-only for v1.
4. **Group creation.** By anyone vs verified representatives vs admin-only vs request-and-verify. **Chosen: request-and-verify.** *Caveat:* this requires a lifecycle state and a review queue that do not exist (4.7).
5. **Education records.** Single record vs multiple records vs one-primary vs one-tertiary-one-secondary. **Chosen: multiple records.** *Caveat:* the concept never says whether suggestions are per-record or the union, whether one is "primary", or whether the same school can appear twice; this must be specified (2.5).
6. **Course catalogue shape.** Flat programme list vs four-level hierarchy vs no course catalogue vs national list guide. **Chosen: four-level hierarchy with the national list as naming guide.** *Dissent:* structural and implementation-readiness recommend institutions-only for v1, deferring faculties/departments/programmes; zoom-out notes programme has no matching consumer (1.8). The national list is authoritative for programme naming only, not institution names or accreditation; the fallback for absent polytechnic programmes must be named.
7. **Suggestion presentation.** Top three + See all + new-group alerts + friends/classmates. **Chosen: all four.** *Dissent:* zoom-out and implementation-readiness recommend cutting alerts (no notification infrastructure), cutting friends/classmates (no social graph, privacy risk), and folding "See all" into the existing Explore page as a mode.
8. **Verification policy.** All options available in settings. **Chosen: all options.** *Dissent:* zoom-out notes three of four modes are cosmetic and that "auto-approve umbrella groups only" contradicts the README's proof-backed promise; the design reviewer flags it as a PII exposure (3.1). Recommended revision: ship one default value first (`suggest but still verify`), then add flags as independent booleans.

## Key Decisions & Staging for Next Steps

The sequence reuses the existing app. It does not rewrite groups, memberships, dues, welfare, elections or the co-op. Steps 0 and 1 are prerequisites; nothing after step 2 is safe before them.

**Release slicing (first release is narrow).**

| Release | Ships | Explicitly deferred |
| --- | --- | --- |
| **Release 1 — join-first base** | Steps 0, 1, 2 (institutions only), 3 (education records), 4 (institution/year/association matching), 7 (group request-and-verify plus creation de-emphasis). The existing proof-and-approval flow stays unchanged. | Faculty/department/programme catalogue, combined-unit matching, policy modes, WhatsApp Cloud API, alerts, friends/classmates. |
| **Release 2 — richer affiliation** | Steps 5 (faculties/departments/programmes), 6 (settings and verification policy), 9 (WhatsApp invite links), 8 (in-app new-group notice). | Cloud API delivery, friends/classmates. |
| **Release 3 — channels and social** | Steps 10 (Cloud API outbox and webhook) and 11 (same-record classmates). | Nothing from the five asks; later phases only. |

Release 1 must still deliver the UNIBEN institution-wide and class-of-2004 matches; faculty, department and combined groups show a clear “affiliation details are not yet catalogue-backed” state rather than a wrong match. Release 2 completes the six-group example.

**Step 0 — Write the two specs (critical path, before any code).**
- Normalisation specification: exact `name_key` rules, abbreviation/alias policy, and how existing free text maps.
- Eligibility matrix: one row per scope and per filled-field combination (required / match-if-present / ignored / weight), with the UNIBEN six-group example (2.4) as the test case.
- Terminology glossary fixing three colliding terms: catalogue verification (institution row state), education confirmation (user claim state), membership approval (join evidence). Ban "eligible", "verified" (for profiles) and "umbrella" in user copy.

**Step 1 — Additive schema path and install fix.**
`database/schema_migrations` + `database/migrate.php`; fix the `install.php` splitter and fresh-install database creation; keep `schema.sql` in sync. Every new column nullable or defaulted. This unblocks everything else.

**Step 2 — Ask 1a: institutions only.**
`institutions` table with normalised key, type/ownership/state, `country_code`; a CLI importer; a one-off backfill mapping the seven seeded groups; an admin verification queue with counts and old→new audit. Catalogue appears in a select; no behaviour change yet.

**Step 3 — Ask 2a: education records.**
`education_records` (user-owned, multiple, unverified), profile card, per-record routes, and an optional post-signup step writing to the same path. Server-rendered selects, no cascade JS.

**Step 4 — Ask 5a: matching and suggestions.**
`EligibilityService` plus `Group::suggestFor($userId, $limit)` with the documented exclusions; dashboard top-three card and `/explore?for=you`; reasons rendered from matched fields. Read-only, advisory, no cache, no alerts. Validate the matrix against seeded groups before anything depends on it.

**Step 5 — Ask 1b: faculties/departments/programmes.**
Widen the catalogue; programme is display-only. Must land before any policy that gates on "profile completeness".

**Step 6 — Ask 2b: policy storage and gating.**
`platform_settings`, `/admin/settings`, `Setting` accessor, audit. Ship one default value first, then add flags as independent booleans with defined precedence against group `join_policy`. Do this after suggestions exist so the policy has something to describe.

**Step 7 — Ask 3: group requests and de-emphasis.**
`group_requests` table, `/profile/group-requests`, `/admin/group-requests`, `verification_status` column, explicit admin transitions, and the full creation-CTA inventory (4.8). Resolve invite behaviour per policy (3.3).

**Step 8 — Ask 5b: in-app new-group notice.**
`users.last_seen_at`; a derived badge only after a group is verified. No email/push.

**Step 9 — Ask 4a: WhatsApp invite links only.**
`group_channels` with audience, host allowlist, `last_verified_at`, `revoked_at`, members-only rendering. Technically independent; can ship any time after step 7.

**Step 10 — Ask 4b: WhatsApp Cloud API.**
Outbox + CLI worker + idempotency + `X-Hub-Signature-256` webhook + templates + opt-in + quotas. Last, because it needs WABA and template approval and the repo has no scheduler, queue or credential store.

**Step 11 — Ask 5c: friends/classmates.**
Only after eligibility is live, scoped to "same confirmed education record, already on Classof", opt-in, no contact upload. Otherwise cut.

**Open questions (must be answered before the dependent step).**
1. Exact `name_key` normalisation rules and which aliases are maintained (step 0).
2. Do suggestions union across all education records, and is one "primary"? (step 0).
3. Does the loosest policy mode reduce member access, or is it withdrawn? (step 6, blocks 3.1).
4. Which actor verifies institutions, education claims and group affiliations, and can they verify a group they belong to? (steps 2, 6, 7).
5. Platform-owned WABA or per-group numbers, and who owns templates and opt-outs? (step 10).
6. Is the catalogue mandatory for Nigerian groups only, and what do foreign groups and foreign alumni do (1.9)?
7. Does `association` auto-match at institution level, and does "auto-approve umbrella" include it (2.2, 3.1)?
8. Are the seven seeded groups grandfathered as verified (4.7, 6.1)?

Release 1 blocks on questions 1, 2, 4, 6 and 8. Questions 3, 5 and 7 block later releases only.

**Measurable success criteria.**
- A new user can register, add one education record, and see at least the institution-wide group for that institution within one page load, with a stated reason.
- The UNIBEN six-group example (2.4) produces the exact expected match sets for users E, F and G, asserted by a scripted check.
- No private or invite-only group appears in any suggestion or alert (query test).
- Existing demo data resets cleanly via a fresh install, and every seeded group is mapped or explicitly flagged unmatched.
- A group created through the request form cannot be discovered by anyone except its requester until an admin verifies it, and verification is recorded with reviewer and timestamp.
- A stored WhatsApp link is visible only to authorised active members; an unauthenticated request for a group page returns no link.
- No suggestion or alert computation adds queries to `layouts/app.php`; the dashboard stays within a documented query budget at 10 memberships.
- Policy default equals today's behaviour: with no settings changed, a join request still requires proof and admin approval.
