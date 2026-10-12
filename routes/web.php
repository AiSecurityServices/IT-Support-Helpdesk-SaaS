<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\RemoteSessionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SlaController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TimeEntryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Marketing website
|--------------------------------------------------------------------------
*/
Route::get('/', [MarketingController::class, 'home'])->name('home');
Route::get('/features', [MarketingController::class, 'features'])->name('features');
Route::get('/use-cases', [MarketingController::class, 'useCases'])->name('use-cases');
Route::get('/pricing', [MarketingController::class, 'pricing'])->name('pricing');
Route::get('/about', [MarketingController::class, 'about'])->name('about');
Route::get('/contact', [MarketingController::class, 'contact'])->name('contact');
Route::post('/contact', [MarketingController::class, 'contactStore'])->name('contact.store')->middleware('throttle:10,1');
Route::get('/faq', [MarketingController::class, 'faq'])->name('faq');
Route::get('/security', [MarketingController::class, 'security'])->name('security');
Route::get('/resources', [MarketingController::class, 'resources'])->name('resources');
Route::get('/privacy', [MarketingController::class, 'privacy'])->name('privacy');
Route::get('/terms', [MarketingController::class, 'terms'])->name('terms');
Route::get('/sitemap.xml', [MarketingController::class, 'sitemap'])->name('sitemap');

// Stripe webhook (no auth; signature-verified, CSRF-exempt via VerifyCsrfToken).
Route::post('/billing/webhook', [BillingController::class, 'webhook'])->name('billing.webhook');

// Mail provider bounce/complaint receiver (shared-secret verified).
Route::post('/webhooks/mail', [\App\Http\Controllers\MailWebhookController::class, 'store'])->name('webhooks.mail');

// Public readiness probe (no secrets, no PII) for uptime monitors.
Route::get('/up', [\App\Http\Controllers\HealthController::class, 'show'])->name('health');

require __DIR__.'/auth.php';

// TOTP challenge (guest: pending second step only).
Route::middleware('guest')->group(function () {
    Route::get('/totp/challenge', [\App\Http\Controllers\Auth\TwoFactorController::class, 'challenge'])->name('totp.challenge');
    Route::post('/totp/challenge', [\App\Http\Controllers\Auth\TwoFactorController::class, 'verifyChallenge'])->name('totp.verify')->middleware('throttle:10,1');
});

// Two-factor management (any verified user incl. super-admins without a tenant).
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/totp/setup', [\App\Http\Controllers\Auth\TwoFactorController::class, 'setup'])->name('totp.setup');
    Route::post('/totp/enable', [\App\Http\Controllers\Auth\TwoFactorController::class, 'enable'])->name('totp.enable');
    Route::get('/totp/recovery', [\App\Http\Controllers\Auth\TwoFactorController::class, 'recovery'])->name('totp.recovery');
    Route::post('/totp/recovery', [\App\Http\Controllers\Auth\TwoFactorController::class, 'regenerateCodes'])->name('totp.regenerate');
    Route::delete('/totp', [\App\Http\Controllers\Auth\TwoFactorController::class, 'disable'])->name('totp.disable');
});

/*
|--------------------------------------------------------------------------
| Authenticated tenant workspace
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/search', [SearchController::class, 'index'])->name('search');

    // Profile
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile', [\App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');

    // Tickets
    Route::get('/tickets/{queue?}', [TicketController::class, 'index'])
        ->where('queue', 'mine|unassigned|team|urgent|overdue|awaiting_customer|awaiting_internal|resolved|closed|all')
        ->name('tickets.index');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::post('/tickets/bulk', [TicketController::class, 'bulk'])->name('tickets.bulk');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::get('/tickets/{ticket}/edit', [TicketController::class, 'edit'])->name('tickets.edit');
    Route::put('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
    Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');
    Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
    Route::post('/tickets/{ticket}/note', [TicketController::class, 'note'])->name('tickets.note');
    Route::post('/tickets/{ticket}/transition', [TicketController::class, 'transition'])->name('tickets.transition');
    Route::post('/tickets/{ticket}/assign', [TicketController::class, 'assign'])->name('tickets.assign');
    Route::post('/tickets/{ticket}/watch', [TicketController::class, 'watch'])->name('tickets.watch');
    Route::delete('/tickets/{ticket}/watch', [TicketController::class, 'unwatch'])->name('tickets.unwatch');
    Route::post('/tickets/{ticket}/merge', [TicketController::class, 'merge'])->name('tickets.merge');
    Route::post('/tickets/{ticket}/rate', [TicketController::class, 'rate'])->name('tickets.rate');
    Route::post('/tickets/{ticket}/time', [TimeEntryController::class, 'store'])->name('tickets.time.store');
    Route::post('/tickets/{ticket}/timer', [TimeEntryController::class, 'start'])->name('tickets.timer.start');
    Route::post('/time/{entry}/stop', [TimeEntryController::class, 'stop'])->name('time.stop');
    Route::delete('/time/{entry}', [TimeEntryController::class, 'destroy'])->name('time.destroy');

    // Customers
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/export', [CustomerController::class, 'export'])->name('customers.export');
    Route::post('/customers/import', [CustomerController::class, 'import'])->name('customers.import');
    Route::post('/customers/{customer}/restore', [CustomerController::class, 'restore'])->name('customers.restore');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    Route::post('/customers/{customer}/contacts', [CustomerController::class, 'storeContact'])->name('customers.contacts.store');
    Route::delete('/customers/{customer}/contacts/{contact}', [CustomerController::class, 'destroyContact'])->name('customers.contacts.destroy');

    // Teams & technicians
    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
    Route::put('/teams/{team}', [TeamController::class, 'update'])->name('teams.update');
    Route::post('/teams/{team}/members', [TeamController::class, 'members'])->name('teams.members');
    Route::delete('/teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
    Route::get('/technicians', [TechnicianController::class, 'index'])->name('technicians.index');
    Route::get('/technicians/{technician}', [TechnicianController::class, 'show'])->name('technicians.show');
    Route::put('/technicians/{technician}', [TechnicianController::class, 'update'])->name('technicians.update');
    Route::post('/technicians/invite', [TechnicianController::class, 'invite'])->name('technicians.invite');
    Route::post('/technicians/{technician}/deactivate', [TechnicianController::class, 'deactivate'])->name('technicians.deactivate');

    // Assets
    Route::get('/assets', [AssetController::class, 'index'])->name('assets.index');
    Route::get('/assets/create', [AssetController::class, 'create'])->name('assets.create');
    Route::post('/assets', [AssetController::class, 'store'])->name('assets.store');
    Route::get('/assets/export', [AssetController::class, 'export'])->name('assets.export');
    Route::post('/assets/import', [AssetController::class, 'import'])->name('assets.import');
    Route::get('/assets/{asset}', [AssetController::class, 'show'])->name('assets.show');
    Route::get('/assets/{asset}/edit', [AssetController::class, 'edit'])->name('assets.edit');
    Route::put('/assets/{asset}', [AssetController::class, 'update'])->name('assets.update');
    Route::delete('/assets/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy');
    Route::post('/assets/{asset}/notes', [AssetController::class, 'note'])->name('assets.note');

    // SLA
    Route::get('/sla', [SlaController::class, 'index'])->name('sla.index')->middleware('plan:sla');
    Route::post('/sla', [SlaController::class, 'store'])->name('sla.store')->middleware('plan:sla');
    Route::put('/sla/{policy}', [SlaController::class, 'update'])->name('sla.update')->middleware('plan:sla');
    Route::post('/sla/holidays', [SlaController::class, 'storeHoliday'])->name('sla.holidays.store')->middleware('plan:sla');
    Route::delete('/sla/holidays/{holiday}', [SlaController::class, 'destroyHoliday'])->name('sla.holidays.destroy')->middleware('plan:sla');

    // Remote support
    Route::get('/remote', [RemoteSessionController::class, 'index'])->name('remote.index');
    Route::post('/remote', [RemoteSessionController::class, 'store'])->name('remote.store');
    Route::post('/remote/{session}/consent', [RemoteSessionController::class, 'consent'])->name('remote.consent');
    Route::post('/remote/{session}/complete', [RemoteSessionController::class, 'complete'])->name('remote.complete');

    // Service invoicing
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index')->middleware('plan:invoicing');
    Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create')->middleware('plan:invoicing');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store')->middleware('plan:invoicing');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show')->middleware('plan:invoicing');
    Route::post('/invoices/{invoice}/issue', [InvoiceController::class, 'issue'])->name('invoices.issue')->middleware('plan:invoicing');
    Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment'])->name('invoices.payments')->middleware('plan:invoicing');
    Route::post('/invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void')->middleware('plan:invoicing');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf')->middleware('plan:invoicing');
    Route::post('/invoices/{invoice}/email', [InvoiceController::class, 'email'])->name('invoices.email')->middleware('plan:invoicing');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/tickets', [ReportController::class, 'tickets'])->name('reports.tickets');
    Route::get('/reports/sla', [ReportController::class, 'sla'])->name('reports.sla');
    Route::get('/reports/workload', [ReportController::class, 'workload'])->name('reports.workload');
    Route::get('/reports/financial', [ReportController::class, 'financial'])->name('reports.financial');
    Route::get('/reports/assets', [ReportController::class, 'assets'])->name('reports.assets');
    Route::get('/reports/activity', [ReportController::class, 'activity'])->name('reports.activity');

    // SaaS subscription (tenant billing — separate from service invoices)
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/checkout/{plan}', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('/billing/success', [BillingController::class, 'success'])->name('billing.success');
    Route::post('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
    Route::get('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');

    // Workspace settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/organisation', [SettingsController::class, 'updateOrganisation'])->name('settings.organisation');
    Route::put('/settings/branding', [SettingsController::class, 'updateBranding'])->name('settings.branding');
    Route::put('/settings/hours', [SettingsController::class, 'updateWorkingHours'])->name('settings.hours');
    Route::put('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications');
    Route::put('/settings/invoice', [SettingsController::class, 'updateInvoice'])->name('settings.invoice');
    Route::put('/settings/security', [SettingsController::class, 'updateSecurity'])->name('settings.security');
    Route::put('/settings/features', [SettingsController::class, 'updateFeatures'])->name('settings.features');
    Route::get('/settings/mail', [\App\Http\Controllers\MailTestController::class, 'preview'])->name('settings.mail.preview');
    Route::post('/settings/mail/test', [\App\Http\Controllers\MailTestController::class, 'send'])->name('settings.mail.send');
    Route::post('/settings/categories', [SettingsController::class, 'storeCategory'])->name('settings.categories.store');
    Route::delete('/settings/categories/{category}', [SettingsController::class, 'destroyCategory'])->name('settings.categories.destroy');
    Route::post('/settings/departments', [SettingsController::class, 'storeDepartment'])->name('settings.departments.store');
    Route::post('/settings/skills', [SettingsController::class, 'storeSkill'])->name('settings.skills.store');
    Route::post('/settings/replies', [SettingsController::class, 'storeReply'])->name('settings.replies.store');
    Route::delete('/settings/replies/{reply}', [SettingsController::class, 'destroyReply'])->name('settings.replies.destroy');
    Route::post('/settings/fields', [SettingsController::class, 'storeCustomField'])->name('settings.fields.store');
    Route::post('/settings/webhooks', [SettingsController::class, 'storeWebhook'])->name('settings.webhooks.store');
    Route::delete('/settings/webhooks/{webhook}', [SettingsController::class, 'destroyWebhook'])->name('settings.webhooks.destroy');

    // Attachments + notifications
    Route::get('/attachments/{attachment}', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');

    // API credentials + service accounts
    Route::get('/settings/tokens', [\App\Http\Controllers\ApiTokenController::class, 'index'])->name('settings.tokens');
    Route::post('/settings/tokens', [\App\Http\Controllers\ApiTokenController::class, 'store'])->name('settings.tokens.store');
    Route::delete('/settings/tokens/{tokenId}', [\App\Http\Controllers\ApiTokenController::class, 'revoke'])->name('settings.tokens.revoke');
    Route::post('/settings/service-accounts', [\App\Http\Controllers\ApiTokenController::class, 'storeServiceAccount'])->name('settings.service-accounts.store');
    Route::post('/technicians/invitations/{invitation}/revoke', [TechnicianController::class, 'revokeInvitation'])->name('technicians.invitations.revoke');

    // Privacy: personal export + tenant closure.
    Route::get('/privacy/export', [\App\Http\Controllers\PrivacyController::class, 'exportMine'])->name('privacy.export');
    Route::post('/settings/close-account', [\App\Http\Controllers\PrivacyController::class, 'closeTenant'])->name('settings.close');
    Route::get('/settings/export', [\App\Http\Controllers\PrivacyController::class, 'exportTenant'])->name('settings.export');

    // Knowledge base (staff)
    Route::get('/kb', [\App\Http\Controllers\KnowledgeBaseController::class, 'index'])->name('kb.index');
    Route::get('/kb/create', [\App\Http\Controllers\KnowledgeBaseController::class, 'create'])->name('kb.create');
    Route::post('/kb', [\App\Http\Controllers\KnowledgeBaseController::class, 'store'])->name('kb.store');
    Route::get('/kb/suggest', [\App\Http\Controllers\KnowledgeBaseController::class, 'suggest'])->name('kb.suggest');
    Route::get('/kb/{article}', [\App\Http\Controllers\KnowledgeBaseController::class, 'show'])->name('kb.show');
    Route::get('/kb/{article}/edit', [\App\Http\Controllers\KnowledgeBaseController::class, 'edit'])->name('kb.edit');
    Route::put('/kb/{article}', [\App\Http\Controllers\KnowledgeBaseController::class, 'update'])->name('kb.update');
    Route::delete('/kb/{article}', [\App\Http\Controllers\KnowledgeBaseController::class, 'destroy'])->name('kb.destroy');
    Route::post('/kb/{article}/transition', [\App\Http\Controllers\KnowledgeBaseController::class, 'transition'])->name('kb.transition');
    Route::post('/kb/{article}/feedback', [\App\Http\Controllers\KnowledgeBaseController::class, 'feedback'])->name('kb.feedback');
    Route::post('/kb/categories', [\App\Http\Controllers\KnowledgeBaseController::class, 'storeCategory'])->name('kb.categories.store');

    // Service catalogue + requests
    Route::get('/catalog', [\App\Http\Controllers\ServiceCatalogController::class, 'index'])->name('catalog.index');
    Route::post('/catalog', [\App\Http\Controllers\ServiceCatalogController::class, 'store'])->name('catalog.store');
    Route::put('/catalog/{item}', [\App\Http\Controllers\ServiceCatalogController::class, 'update'])->name('catalog.update');
    Route::delete('/catalog/{item}', [\App\Http\Controllers\ServiceCatalogController::class, 'destroy'])->name('catalog.destroy');
    Route::post('/catalog/{item}/request', [\App\Http\Controllers\ServiceCatalogController::class, 'storeRequest'])->name('catalog.request.store');
    Route::get('/requests/{serviceRequest}', [\App\Http\Controllers\ServiceCatalogController::class, 'show'])->name('catalog.requests.show');
    Route::post('/requests/{serviceRequest}/decide', [\App\Http\Controllers\ServiceCatalogController::class, 'decide'])->name('catalog.requests.decide');
    Route::post('/requests/{serviceRequest}/tasks', [\App\Http\Controllers\ServiceCatalogController::class, 'storeTask'])->name('catalog.requests.tasks.store');
    Route::post('/tasks/{task}/complete', [\App\Http\Controllers\ServiceCatalogController::class, 'completeTask'])->name('catalog.tasks.complete');
    Route::post('/requests/{serviceRequest}/fulfil', [\App\Http\Controllers\ServiceCatalogController::class, 'fulfil'])->name('catalog.requests.fulfil');
    Route::post('/requests/{serviceRequest}/confirm', [\App\Http\Controllers\ServiceCatalogController::class, 'confirm'])->name('catalog.requests.confirm');

    // Problem / Change / Major incidents
    Route::get('/problems', [\App\Http\Controllers\ProblemController::class, 'index'])->name('problems.index');
    Route::post('/problems', [\App\Http\Controllers\ProblemController::class, 'store'])->name('problems.store');
    Route::get('/problems/{problem}', [\App\Http\Controllers\ProblemController::class, 'show'])->name('problems.show');
    Route::put('/problems/{problem}', [\App\Http\Controllers\ProblemController::class, 'update'])->name('problems.update');
    Route::post('/problems/{problem}/tickets', [\App\Http\Controllers\ProblemController::class, 'linkTicket'])->name('problems.tickets.link');
    Route::get('/changes', [\App\Http\Controllers\ChangeController::class, 'index'])->name('changes.index');
    Route::post('/changes', [\App\Http\Controllers\ChangeController::class, 'store'])->name('changes.store');
    Route::get('/changes/{change}', [\App\Http\Controllers\ChangeController::class, 'show'])->name('changes.show');
    Route::put('/changes/{change}', [\App\Http\Controllers\ChangeController::class, 'update'])->name('changes.update');
    Route::post('/changes/{change}/submit', [\App\Http\Controllers\ChangeController::class, 'submit'])->name('changes.submit');
    Route::post('/changes/{change}/decide', [\App\Http\Controllers\ChangeController::class, 'decide'])->name('changes.decide');
    Route::post('/changes/{change}/implement', [\App\Http\Controllers\ChangeController::class, 'implement'])->name('changes.implement');
    Route::post('/changes/{change}/tickets', [\App\Http\Controllers\ChangeController::class, 'linkTicket'])->name('changes.tickets.link');
    Route::get('/incidents', [\App\Http\Controllers\MajorIncidentController::class, 'index'])->name('incidents.index');
    Route::post('/incidents', [\App\Http\Controllers\MajorIncidentController::class, 'store'])->name('incidents.store');
    Route::get('/incidents/{incident}', [\App\Http\Controllers\MajorIncidentController::class, 'show'])->name('incidents.show');
    Route::post('/incidents/{incident}/tickets', [\App\Http\Controllers\MajorIncidentController::class, 'linkTicket'])->name('incidents.tickets.link');
    Route::post('/incidents/{incident}/updates', [\App\Http\Controllers\MajorIncidentController::class, 'update'])->name('incidents.updates.store');
    Route::post('/incidents/{incident}/transition', [\App\Http\Controllers\MajorIncidentController::class, 'transition'])->name('incidents.transition');

    // CMDB relationships + impact
    Route::get('/cmdb', [\App\Http\Controllers\CmdbController::class, 'index'])->name('cmdb.index');
    Route::post('/cmdb', [\App\Http\Controllers\CmdbController::class, 'store'])->name('cmdb.store');
    Route::delete('/cmdb/{relationship}', [\App\Http\Controllers\CmdbController::class, 'destroy'])->name('cmdb.destroy');
    Route::get('/cmdb/impact/{type}/{id}', [\App\Http\Controllers\CmdbController::class, 'impact'])->name('cmdb.impact');

    // Automation
    Route::get('/automation', [\App\Http\Controllers\AutomationController::class, 'index'])->name('automation.index');
    Route::post('/automation', [\App\Http\Controllers\AutomationController::class, 'store'])->name('automation.store');
    Route::put('/automation/{rule}', [\App\Http\Controllers\AutomationController::class, 'update'])->name('automation.update');
    Route::delete('/automation/{rule}', [\App\Http\Controllers\AutomationController::class, 'destroy'])->name('automation.destroy');
    Route::post('/automation/{rule}/dry-run', [\App\Http\Controllers\AutomationController::class, 'dryRun'])->name('automation.dry-run');

    // Customer portal
    Route::prefix('portal')->name('portal.')->group(function () {
        Route::get('/', [PortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/tickets', [PortalController::class, 'tickets'])->name('tickets');
        Route::get('/tickets/create', [PortalController::class, 'create'])->name('tickets.create');
        Route::post('/tickets', [PortalController::class, 'store'])->name('tickets.store');
        Route::get('/tickets/{ticket}', [PortalController::class, 'show'])->name('tickets.show');
        Route::post('/tickets/{ticket}/reply', [PortalController::class, 'reply'])->name('tickets.reply');
        Route::get('/assets', [PortalController::class, 'assets'])->name('assets');
        Route::get('/invoices', [PortalController::class, 'invoices'])->name('invoices');
        Route::get('/invoices/{invoice}', [PortalController::class, 'invoiceShow'])->name('invoices.show');
        Route::get('/profile', [PortalController::class, 'profile'])->name('profile');
        Route::put('/profile', [PortalController::class, 'updateProfile'])->name('profile.update');
        Route::get('/kb', [\App\Http\Controllers\KnowledgeBaseController::class, 'portalIndex'])->name('kb');
        Route::get('/kb/{article}', [\App\Http\Controllers\KnowledgeBaseController::class, 'portalShow'])->name('kb.show');
        Route::get('/services', [\App\Http\Controllers\ServiceCatalogController::class, 'index'])->name('services');
        Route::post('/services/{item}/request', [\App\Http\Controllers\ServiceCatalogController::class, 'storeRequest'])->name('services.request');
        Route::get('/requests/{serviceRequest}', [\App\Http\Controllers\ServiceCatalogController::class, 'show'])->name('requests.show');
        Route::post('/requests/{serviceRequest}/confirm', [\App\Http\Controllers\ServiceCatalogController::class, 'confirm'])->name('requests.confirm');
    });
});

/*
|--------------------------------------------------------------------------
| Platform super administration
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/tenants', [AdminController::class, 'tenants'])->name('tenants');
    Route::get('/tenants/{tenant}', [AdminController::class, 'tenantShow'])->name('tenants.show');
    Route::post('/tenants/{tenant}/suspend', [AdminController::class, 'suspend'])->name('tenants.suspend');
    Route::post('/tenants/{tenant}/reactivate', [AdminController::class, 'reactivate'])->name('tenants.reactivate');
    Route::get('/plans', [AdminController::class, 'plans'])->name('plans');
    Route::put('/plans/{plan}', [AdminController::class, 'updatePlan'])->name('plans.update');
    Route::get('/audit', [AdminController::class, 'audit'])->name('audit');
    Route::get('/health', [AdminController::class, 'health'])->name('health');
    Route::get('/impersonation', [\App\Http\Controllers\ImpersonationController::class, 'index'])->name('impersonation');
    Route::post('/impersonation/grant', [\App\Http\Controllers\ImpersonationController::class, 'grant'])->name('impersonation.grant');
    Route::post('/impersonation/{grant}/assume', [\App\Http\Controllers\ImpersonationController::class, 'assume'])->name('impersonation.assume');
    Route::post('/impersonation/{grant}/revoke', [\App\Http\Controllers\ImpersonationController::class, 'revoke'])->name('impersonation.revoke');
});

// Stop impersonation (available to the assumed session itself).
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/impersonation/stop', [\App\Http\Controllers\ImpersonationController::class, 'stop'])->name('impersonation.stop');
});
