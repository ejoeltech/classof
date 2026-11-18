<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AnnouncementController;
use App\Controllers\AssistantController;
use App\Controllers\AuthController;
use App\Controllers\CatalogueController;
use App\Controllers\ClassmateController;
use App\Controllers\CoopController;
use App\Controllers\CoopDividendController;
use App\Controllers\CoopFineController;
use App\Controllers\CoopLoanController;
use App\Controllers\CoopMeetingController;
use App\Controllers\DashboardController;
use App\Controllers\DuesController;
use App\Controllers\EducationController;
use App\Controllers\ElectionController;
use App\Controllers\EventController;
use App\Controllers\GroupController;
use App\Controllers\GroupRequestController;
use App\Controllers\GroupSiteController;
use App\Controllers\HomeController;
use App\Controllers\MemberController;
use App\Controllers\OnboardingController;
use App\Controllers\PaymentController;
use App\Controllers\ProfileController;
use App\Controllers\ProofController;
use App\Controllers\SiteChatController;
use App\Controllers\TreasuryController;
use App\Controllers\WelfareController;
use App\Controllers\WhatsAppController;

/** @var App\Core\Router $router */

/* ---------------------------------------------------------------- Public */
if (defined('GROUP_SITE_SUBDOMAIN')) {
    $router->get('/', [GroupSiteController::class, 'showByHost']);
} else {
    $router->get('/', [HomeController::class, 'index']);
}

$router->get('/explore', [HomeController::class, 'explore']);
$router->get('/site/{subdomain}', [GroupSiteController::class, 'show']);
$router->post('/site/{subdomain}/chat', [SiteChatController::class, 'chat']);

/* ------------------------------------------------------------------ Auth */
$router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [AuthController::class, 'login'], ['guest']);
$router->get('/register', [AuthController::class, 'showRegister'], ['guest']);
$router->post('/register', [AuthController::class, 'register'], ['guest']);
$router->post('/logout', [AuthController::class, 'logout'], ['auth']);
$router->get('/forgot-password', [AuthController::class, 'showForgot'], ['guest']);
$router->post('/forgot-password', [AuthController::class, 'sendReset'], ['guest']);
$router->get('/reset-password/{token}', [AuthController::class, 'showReset'], ['guest']);
$router->post('/reset-password', [AuthController::class, 'resetPassword'], ['guest']);

/* ------------------------------------------------------------- Dashboard */
$router->get('/dashboard', [DashboardController::class, 'index'], ['auth']);
$router->post('/assistant/chat', [AssistantController::class, 'chat'], ['auth']);
$router->get('/onboarding/education', [OnboardingController::class, 'show'], ['auth']);
$router->post('/onboarding/skip', [OnboardingController::class, 'skip'], ['auth']);
$router->get('/profile', [ProfileController::class, 'edit'], ['auth']);
$router->post('/profile', [ProfileController::class, 'update'], ['auth']);
$router->post('/profile/password', [ProfileController::class, 'password'], ['auth']);
$router->post('/profile/whatsapp', [ProfileController::class, 'updateWhatsapp'], ['auth']);
$router->get('/profile/education', [EducationController::class, 'index'], ['auth']);
$router->post('/profile/education', [EducationController::class, 'store'], ['auth']);
$router->post('/profile/education/{id}/delete', [EducationController::class, 'destroy'], ['auth']);
$router->get('/profile/institutions/request', [EducationController::class, 'requestInstitutionForm'], ['auth']);
$router->post('/profile/institutions/request', [EducationController::class, 'storeInstitutionRequest'], ['auth']);
$router->get('/profile/group-requests', [GroupRequestController::class, 'index'], ['auth']);
$router->post('/profile/group-requests', [GroupRequestController::class, 'store'], ['auth']);
$router->get('/classmates', [ClassmateController::class, 'index'], ['auth']);
$router->post('/classmates/opt-in', [ClassmateController::class, 'toggle'], ['auth']);

/* --------------------------------------------------------- Catalogue API */
$router->get('/catalogue/faculties', [CatalogueController::class, 'faculties'], ['auth']);
$router->get('/catalogue/departments', [CatalogueController::class, 'departments'], ['auth']);

/* ---------------------------------------------------------------- Groups */
$router->get('/groups/create', [GroupRequestController::class, 'redirectCreate'], ['auth']);
$router->get('/join/{slug}', [GroupController::class, 'joinConfirm'], ['auth']);
$router->post('/join/{slug}', [GroupController::class, 'join'], ['auth']);
$router->get('/invite/{token}', [GroupController::class, 'acceptInvite'], ['auth']);
$router->get('/groups/{slug}', [GroupController::class, 'show'], ['auth']);
$router->get('/groups/{slug}/site', [GroupSiteController::class, 'edit'], ['auth']);
$router->post('/groups/{slug}/site', [GroupSiteController::class, 'update'], ['auth']);
$router->get('/groups/{slug}/site/preview', [GroupSiteController::class, 'preview'], ['auth']);
$router->get('/groups/{slug}/settings', [GroupController::class, 'settings'], ['auth']);
$router->post('/groups/{slug}/settings', [GroupController::class, 'updateSettings'], ['auth']);
$router->post('/groups/{slug}/channels', [GroupController::class, 'storeChannel'], ['auth']);
$router->post('/groups/{slug}/channels/{id}', [GroupController::class, 'updateChannel'], ['auth']);
$router->post('/groups/{slug}/channels/{id}/revoke', [GroupController::class, 'revokeChannel'], ['auth']);
$router->post('/groups/{slug}/leave', [GroupController::class, 'leave'], ['auth']);

/* --------------------------------------------------------------- Members */
$router->get('/groups/{slug}/members', [MemberController::class, 'index'], ['auth']);
$router->post('/groups/{slug}/members/{id}/approve', [MemberController::class, 'approve'], ['auth']);
$router->post('/groups/{slug}/members/{id}/reject', [MemberController::class, 'reject'], ['auth']);
$router->post('/groups/{slug}/members/{id}/role', [MemberController::class, 'updateRole'], ['auth']);
$router->post('/groups/{slug}/members/{id}/remove', [MemberController::class, 'remove'], ['auth']);
$router->get('/groups/{slug}/members/invite', [MemberController::class, 'inviteForm'], ['auth']);
$router->post('/groups/{slug}/members/invite', [MemberController::class, 'invite'], ['auth']);
$router->get('/groups/{slug}/members/{id}', [MemberController::class, 'show'], ['auth']);

/* ------------------------------------------------------- Proof documents */
$router->get('/proofs/{file}', [ProofController::class, 'show'], ['auth']);

/* --------------------------------------------------------- Announcements */
$router->get('/groups/{slug}/announcements', [AnnouncementController::class, 'index'], ['auth']);
$router->post('/groups/{slug}/announcements', [AnnouncementController::class, 'store'], ['auth']);
$router->post('/groups/{slug}/announcements/{id}/delete', [AnnouncementController::class, 'destroy'], ['auth']);

/* ---------------------------------------------------------------- Events */
$router->get('/groups/{slug}/events', [EventController::class, 'index'], ['auth']);
$router->post('/groups/{slug}/events', [EventController::class, 'store'], ['auth']);
$router->get('/groups/{slug}/events/{id}', [EventController::class, 'show'], ['auth']);
$router->get('/groups/{slug}/events/{id}/calendar', [EventController::class, 'calendar']);
$router->post('/groups/{slug}/events/{id}/rsvp', [EventController::class, 'rsvp'], ['auth']);
$router->post('/groups/{slug}/events/{id}/delete', [EventController::class, 'destroy'], ['auth']);

/* ------------------------------------------------------------------ Dues */
$router->get('/groups/{slug}/dues', [DuesController::class, 'index'], ['auth']);
$router->post('/groups/{slug}/dues', [DuesController::class, 'store'], ['auth']);
$router->get('/groups/{slug}/dues/{id}', [DuesController::class, 'show'], ['auth']);
$router->post('/groups/{slug}/dues/{id}/close', [DuesController::class, 'close'], ['auth']);

/* --------------------------------------------------------------- Welfare */
$router->get('/groups/{slug}/welfare', [WelfareController::class, 'index'], ['auth']);
$router->get('/groups/{slug}/welfare/create', [WelfareController::class, 'create'], ['auth']);
$router->post('/groups/{slug}/welfare', [WelfareController::class, 'store'], ['auth']);
$router->get('/groups/{slug}/welfare/{id}', [WelfareController::class, 'show'], ['auth']);
$router->post('/groups/{slug}/welfare/{id}/close', [WelfareController::class, 'close'], ['auth']);

/* ------------------------------------------------------------- Elections */
$router->get('/groups/{slug}/elections', [ElectionController::class, 'index'], ['auth']);
$router->post('/groups/{slug}/elections', [ElectionController::class, 'store'], ['auth']);
$router->get('/groups/{slug}/elections/{id}', [ElectionController::class, 'show'], ['auth']);
$router->post('/groups/{slug}/elections/{id}/vote', [ElectionController::class, 'vote'], ['auth']);
$router->get('/groups/{slug}/elections/{id}/results', [ElectionController::class, 'results'], ['auth']);
$router->get('/groups/{slug}/elections/{id}/manage', [ElectionController::class, 'manage'], ['auth']);
$router->post('/groups/{slug}/elections/{id}/positions', [ElectionController::class, 'addPosition'], ['auth']);
$router->post('/groups/{slug}/elections/{id}/positions/{positionId}/delete', [ElectionController::class, 'deletePosition'], ['auth']);
$router->post('/groups/{slug}/elections/{id}/positions/{positionId}/candidates', [ElectionController::class, 'addCandidate'], ['auth']);
$router->post('/groups/{slug}/elections/{id}/candidates/{candidateId}/delete', [ElectionController::class, 'deleteCandidate'], ['auth']);
$router->post('/groups/{slug}/elections/{id}/status', [ElectionController::class, 'setStatus'], ['auth']);

/* --------------------------------------------------------------- Treasury */
$router->get('/groups/{slug}/treasury', [TreasuryController::class, 'index'], ['auth']);
$router->post('/groups/{slug}/treasury/record', [TreasuryController::class, 'recordCash'], ['auth']);
$router->get('/groups/{slug}/treasury/export', [TreasuryController::class, 'export'], ['auth']);

/* ----------------------------------------------------------- Cooperative */
$router->get('/groups/{slug}/coop', [CoopController::class, 'index'], ['auth']);
$router->post('/groups/{slug}/coop/enable', [CoopController::class, 'enable'], ['auth']);
$router->get('/groups/{slug}/coop/settings', [CoopController::class, 'settings'], ['auth']);
$router->post('/groups/{slug}/coop/settings', [CoopController::class, 'updateSettings'], ['auth']);
$router->get('/groups/{slug}/coop/members', [CoopController::class, 'members'], ['auth']);
$router->post('/groups/{slug}/coop/join', [CoopController::class, 'join'], ['auth']);
$router->post('/groups/{slug}/coop/members/{id}/approve', [CoopController::class, 'approveMember'], ['auth']);
$router->post('/groups/{slug}/coop/members/{id}/reject', [CoopController::class, 'rejectMember'], ['auth']);
$router->post('/groups/{slug}/coop/members/{id}/role', [CoopController::class, 'setMemberRole'], ['auth']);
$router->post('/groups/{slug}/coop/members/{id}/remove', [CoopController::class, 'removeMember'], ['auth']);
$router->get('/groups/{slug}/coop/me', [CoopController::class, 'me'], ['auth']);
$router->get('/groups/{slug}/coop/me/export', [CoopController::class, 'exportStatement'], ['auth']);
$router->get('/groups/{slug}/coop/transactions', [CoopController::class, 'transactions'], ['auth']);
$router->post('/groups/{slug}/coop/transactions', [CoopController::class, 'recordTransaction'], ['auth']);
$router->get('/groups/{slug}/coop/transactions/export', [CoopController::class, 'exportTransactions'], ['auth']);

/* Co-op loans */
$router->get('/groups/{slug}/coop/loans', [CoopLoanController::class, 'index'], ['auth']);
$router->post('/groups/{slug}/coop/loans', [CoopLoanController::class, 'store'], ['auth']);
$router->get('/groups/{slug}/coop/loans/{id}', [CoopLoanController::class, 'show'], ['auth']);
$router->post('/groups/{slug}/coop/loans/{id}/guarantee', [CoopLoanController::class, 'guarantee'], ['auth']);
$router->post('/groups/{slug}/coop/loans/{id}/approve', [CoopLoanController::class, 'approve'], ['auth']);
$router->post('/groups/{slug}/coop/loans/{id}/reject', [CoopLoanController::class, 'reject'], ['auth']);
$router->post('/groups/{slug}/coop/loans/{id}/disburse', [CoopLoanController::class, 'disburse'], ['auth']);
$router->post('/groups/{slug}/coop/loans/{id}/repayment', [CoopLoanController::class, 'recordRepayment'], ['auth']);
$router->get('/groups/{slug}/coop/products', [CoopLoanController::class, 'products'], ['auth']);
$router->post('/groups/{slug}/coop/products', [CoopLoanController::class, 'storeProduct'], ['auth']);
$router->post('/groups/{slug}/coop/products/{id}/toggle', [CoopLoanController::class, 'toggleProduct'], ['auth']);
$router->post('/groups/{slug}/coop/products/{id}/delete', [CoopLoanController::class, 'deleteProduct'], ['auth']);

/* Co-op fines */
$router->get('/groups/{slug}/coop/fines', [CoopFineController::class, 'index'], ['auth']);
$router->post('/groups/{slug}/coop/fines', [CoopFineController::class, 'store'], ['auth']);
$router->post('/groups/{slug}/coop/fines/{id}/paid', [CoopFineController::class, 'markPaid'], ['auth']);
$router->post('/groups/{slug}/coop/fines/{id}/waive', [CoopFineController::class, 'waive'], ['auth']);

/* Co-op meetings */
$router->get('/groups/{slug}/coop/meetings', [CoopMeetingController::class, 'index'], ['auth']);
$router->post('/groups/{slug}/coop/meetings', [CoopMeetingController::class, 'store'], ['auth']);
$router->get('/groups/{slug}/coop/meetings/{id}', [CoopMeetingController::class, 'show'], ['auth']);
$router->post('/groups/{slug}/coop/meetings/{id}/attendance', [CoopMeetingController::class, 'attendance'], ['auth']);
$router->post('/groups/{slug}/coop/meetings/{id}/complete', [CoopMeetingController::class, 'complete'], ['auth']);
$router->post('/groups/{slug}/coop/meetings/{id}/fine-absent', [CoopMeetingController::class, 'fineAbsent'], ['auth']);
$router->post('/groups/{slug}/coop/meetings/{id}/cancel', [CoopMeetingController::class, 'cancel'], ['auth']);

/* Co-op dividends */
$router->get('/groups/{slug}/coop/dividends', [CoopDividendController::class, 'index'], ['auth']);
$router->post('/groups/{slug}/coop/dividends', [CoopDividendController::class, 'store'], ['auth']);
$router->get('/groups/{slug}/coop/dividends/{id}', [CoopDividendController::class, 'show'], ['auth']);
$router->post('/groups/{slug}/coop/dividends/{id}/distribute', [CoopDividendController::class, 'distribute'], ['auth']);

/* --------------------------------------------------------------- Payments */
$router->post('/payments/initiate', [PaymentController::class, 'initiate'], ['auth']);
$router->get('/payments/{id}/instructions', [PaymentController::class, 'instructions'], ['auth']);
$router->post('/payments/{id}/claim', [PaymentController::class, 'claim'], ['auth']);
$router->get('/payments/{id}/callback', [PaymentController::class, 'callback'], ['auth']);
$router->post('/payments/{id}/confirm', [PaymentController::class, 'confirm'], ['auth']);
$router->post('/payments/{id}/reject', [PaymentController::class, 'reject'], ['auth']);

/* --------------------------------------------------------------- Webhooks */
$router->post('/webhooks/paystack', [PaymentController::class, 'paystackWebhook']);
$router->post('/webhooks/stripe', [PaymentController::class, 'stripeWebhook']);
$router->get('/webhooks/whatsapp', [WhatsAppController::class, 'verify']);
$router->post('/webhooks/whatsapp', [WhatsAppController::class, 'receive']);

/* --------------------------------------------------------- Platform admin */
$router->get('/admin', [AdminController::class, 'index'], ['admin']);
$router->get('/admin/users', [AdminController::class, 'users'], ['admin']);
$router->post('/admin/users/{id}/status', [AdminController::class, 'userStatus'], ['admin']);
$router->get('/admin/groups', [AdminController::class, 'groups'], ['admin']);
$router->post('/admin/groups/{id}/status', [AdminController::class, 'groupStatus'], ['admin']);
$router->get('/admin/institutions', [AdminController::class, 'institutions'], ['admin']);
$router->post('/admin/institutions/{id}/status', [AdminController::class, 'institutionStatus'], ['admin']);
$router->get('/admin/education', [AdminController::class, 'education'], ['admin']);
$router->post('/admin/education/{id}/status', [AdminController::class, 'educationStatus'], ['admin']);
$router->get('/admin/group-requests', [AdminController::class, 'groupRequests'], ['admin']);
$router->post('/admin/group-requests/{id}/status', [AdminController::class, 'groupRequestStatus'], ['admin']);
$router->get('/admin/settings', [AdminController::class, 'settings'], ['admin']);
$router->post('/admin/settings', [AdminController::class, 'updateSettings'], ['admin']);
$router->get('/admin/whatsapp', [AdminController::class, 'whatsapp'], ['admin']);
$router->post('/admin/whatsapp/{id}/retry', [AdminController::class, 'retryMessage'], ['admin']);
$router->post('/admin/whatsapp/{id}/cancel', [AdminController::class, 'cancelMessage'], ['admin']);
