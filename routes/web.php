<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentCommentController;
use App\Http\Controllers\DocumentLifecycleController;
use App\Http\Controllers\DocumentPreviewController;
use App\Http\Controllers\DocumentShareController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Platform\PlatformAuditWebController;
use App\Http\Controllers\Platform\PlatformAuthWebController;
use App\Http\Controllers\Platform\PlatformDashboardWebController;
use App\Http\Controllers\Platform\PlatformInvoiceWebController;
use App\Http\Controllers\Platform\PlatformOrganizationWebController;
use App\Http\Controllers\Platform\PlatformPaymentWebController;
use App\Http\Controllers\Platform\PlatformPlanWebController;
use App\Http\Controllers\Platform\PlatformSettingWebController;
use App\Http\Controllers\Platform\PlatformSubscriptionWebController;
use App\Http\Controllers\Platform\PlatformSupportWebController;
use App\Http\Controllers\Platform\PlatformTrialWebController;
use App\Http\Controllers\Platform\PlatformUsageWebController;
use App\Http\Controllers\Platform\PlatformUserWebController;
use App\Http\Controllers\Web\AccessScopeWebController;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\CategoryWebController;
use App\Http\Controllers\Web\DepartmentWebController;
use App\Http\Controllers\Web\DirectionWebController;
use App\Http\Controllers\Web\DocumentTypeWebController;
use App\Http\Controllers\Web\DocumentWebController;
use App\Http\Controllers\Web\EnterpriseController;
use App\Http\Controllers\Web\FavoriteWebController;
use App\Http\Controllers\Web\FolderWebController;
use App\Http\Controllers\Web\MetadataWebController;
use App\Http\Controllers\Web\OnboardingController;
use App\Http\Controllers\Web\PricingWebController;
use App\Http\Controllers\Web\ProfileWebController;
use App\Http\Controllers\Web\PublicLandingController;
use App\Http\Controllers\Web\RecentWebController;
use App\Http\Controllers\Web\RegistrationWebController;
use App\Http\Controllers\Web\RoleWebController;
use App\Http\Controllers\Web\SearchWebController;
use App\Http\Controllers\Web\ServiceWebController;
use App\Http\Controllers\Web\ShareWebController;
use App\Http\Controllers\Web\SubscriptionWebController;
use App\Http\Controllers\Web\TagWebController;
use App\Http\Controllers\Web\UserWebController;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\WorkflowInstanceController;
use App\Http\Controllers\WorkflowStepController;
use Illuminate\Support\Facades\Route;

// Public SaaS Pages
Route::get('/', [PublicLandingController::class, 'index'])->name('home');
Route::get('/tarifs', [PricingWebController::class, 'index'])->name('tarifs');
Route::get('/pricing', [PricingWebController::class, 'index'])->name('pricing');
Route::get('/fonctionnalites', [PublicLandingController::class, 'features'])->name('features');
Route::get('/enterprise', [EnterpriseController::class, 'index'])->name('enterprise');
Route::post('/enterprise', [EnterpriseController::class, 'store'])->middleware('throttle:10,1')->name('enterprise.store');
Route::post('/enterprise/contact', [EnterpriseController::class, 'store'])->middleware('throttle:10,1')->name('enterprise.contact');
Route::get('/contact', [PublicLandingController::class, 'contact'])->name('contact');
Route::post('/contact', [PublicLandingController::class, 'storeContact'])->middleware('throttle:10,1')->name('contact.store');

// Inscription SaaS public (Compte -> Organisation -> Plan -> 14 jours d'essai)
Route::middleware('guest')->group(function () {
    // Parcours officiel V1 /inscription
    Route::get('/inscription', [RegistrationWebController::class, 'createAccount'])->name('inscription');
    Route::post('/inscription', [RegistrationWebController::class, 'storeAccount'])->middleware('throttle:10,1')->name('inscription.store');
    Route::get('/inscription/organisation', [RegistrationWebController::class, 'createOrganization'])->name('inscription.organisation');
    Route::post('/inscription/organisation', [RegistrationWebController::class, 'storeOrganization'])->middleware('throttle:10,1')->name('inscription.organisation.store');
    Route::get('/inscription/plan', [RegistrationWebController::class, 'createPlan'])->name('inscription.plan');
    Route::post('/inscription/plan', [RegistrationWebController::class, 'storePlan'])->middleware('throttle:10,1')->name('inscription.plan.store');

    // Alias et compatibilité ascendante /register
    Route::get('/register', fn () => redirect()->route('register.organization'))->name('register');
    Route::get('/register/organization', [RegistrationWebController::class, 'createOrganization'])->name('register.organization');
    Route::post('/register/organization', [RegistrationWebController::class, 'storeOrganization'])->middleware('throttle:10,1')->name('register.organization.store');
    Route::get('/register/admin', [RegistrationWebController::class, 'createAdmin'])->name('register.admin');
    Route::post('/register/admin', [RegistrationWebController::class, 'storeAdmin'])->middleware('throttle:10,1')->name('register.admin.store');
    Route::get('/register/plan', [RegistrationWebController::class, 'createPlan'])->name('register.plan');
    Route::post('/register/plan', [RegistrationWebController::class, 'storePlan'])->middleware('throttle:10,1')->name('register.plan.store');
});

Route::get('/login', [AuthWebController::class, 'create'])->name('login');
Route::post('/login', [AuthWebController::class, 'store'])->name('login.store');
Route::post('/logout', [AuthWebController::class, 'destroy'])->name('logout');

Route::middleware(['auth:web', 'org.active'])->group(function () {
    // Dashboard V1 & Onboarding
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
    Route::post('/dashboard/onboarding/dismiss', [OnboardingController::class, 'dismiss'])
        ->name('dashboard.onboarding.dismiss');

    // Document Lifecycle (Trash & Archive) - must precede {document} wildcard
    Route::get('/documents/trash', [DocumentLifecycleController::class, 'trash'])
        ->name('documents.trash.index');
    Route::post('/documents/trash/empty', [DocumentLifecycleController::class, 'emptyTrash'])
        ->name('documents.trash.empty');
    Route::get('/documents/archived', [DocumentLifecycleController::class, 'archived'])
        ->name('documents.archived.index');

    // Documents Web
    Route::get('/documents', [DocumentWebController::class, 'index'])->name('documents.index');
    Route::get('/documents/create', [DocumentWebController::class, 'create'])->name('documents.create');
    Route::post('/documents', [DocumentWebController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}', [DocumentWebController::class, 'show'])->name('documents.show');
    Route::get('/documents/{document}/edit', [DocumentWebController::class, 'edit'])->name('documents.edit');
    Route::put('/documents/{document}', [DocumentWebController::class, 'update'])->name('documents.update');
    Route::post('/documents/{document}/move', [DocumentWebController::class, 'move'])->name('documents.move');
    Route::get('/documents/{document}/download', [DocumentWebController::class, 'download'])->name('documents.download');
    Route::post('/documents/{document}/versions', [DocumentWebController::class, 'storeVersion'])->name('documents.versions.store');
    Route::get('/documents/{document}/versions/{version}/download', [DocumentWebController::class, 'downloadVersion'])->name('documents.versions.download');
    Route::post('/documents/{document}/versions/{version}/restore', [DocumentWebController::class, 'restoreVersion'])->name('documents.versions.restore');
    Route::post('/documents/{document}/ocr/retry', [DocumentWebController::class, 'retryOcr'])->name('documents.ocr.retry');

    // Folders Web
    Route::get('/folders', [FolderWebController::class, 'index'])->name('folders.index');
    Route::post('/folders', [FolderWebController::class, 'store'])->name('folders.store');
    Route::get('/folders/{folder}', [FolderWebController::class, 'show'])->name('folders.show');
    Route::put('/folders/{folder}', [FolderWebController::class, 'update'])->name('folders.update');
    Route::delete('/folders/{folder}', [FolderWebController::class, 'destroy'])->name('folders.destroy');

    // Departments (Legacy / Compatibility)
    Route::get('/departments', [DepartmentWebController::class, 'index'])->name('departments.index');
    Route::post('/departments', [DepartmentWebController::class, 'store'])->name('departments.store');
    Route::put('/departments/{department}', [DepartmentWebController::class, 'update'])->name('departments.update');
    Route::delete('/departments/{department}', [DepartmentWebController::class, 'destroy'])->name('departments.destroy');

    // Directions Web (Organization Structure V2)
    Route::get('/directions', [DirectionWebController::class, 'index'])->name('directions.index');
    Route::get('/admin/directions', [DirectionWebController::class, 'index'])->name('admin.directions.index');
    Route::post('/directions', [DirectionWebController::class, 'store'])->name('directions.store');
    Route::post('/admin/directions', [DirectionWebController::class, 'store'])->name('admin.directions.store');
    Route::put('/directions/{direction}', [DirectionWebController::class, 'update'])->name('directions.update');
    Route::put('/admin/directions/{direction}', [DirectionWebController::class, 'update'])->name('admin.directions.update');
    Route::delete('/directions/{direction}', [DirectionWebController::class, 'destroy'])->name('directions.destroy');
    Route::delete('/admin/directions/{direction}', [DirectionWebController::class, 'destroy'])->name('admin.directions.destroy');

    // Services Web (Organization Structure V2)
    Route::get('/services', [ServiceWebController::class, 'index'])->name('services.index');
    Route::get('/admin/services', [ServiceWebController::class, 'index'])->name('admin.services.index');
    Route::post('/services', [ServiceWebController::class, 'store'])->name('services.store');
    Route::post('/admin/services', [ServiceWebController::class, 'store'])->name('admin.services.store');
    Route::put('/services/{service}', [ServiceWebController::class, 'update'])->name('services.update');
    Route::put('/admin/services/{service}', [ServiceWebController::class, 'update'])->name('admin.services.update');
    Route::delete('/services/{service}', [ServiceWebController::class, 'destroy'])->name('services.destroy');
    Route::delete('/admin/services/{service}', [ServiceWebController::class, 'destroy'])->name('admin.services.destroy');
    Route::post('/services/{service}/users', [ServiceWebController::class, 'assignUser'])->name('services.users.assign');
    Route::delete('/services/{service}/users/{user}', [ServiceWebController::class, 'removeUser'])->name('services.users.remove');

    // Access Scopes Web (Périmètres d'accès V2)
    Route::get('/access-scopes', [AccessScopeWebController::class, 'index'])->name('access_scopes.index');
    Route::get('/admin/access-scopes', [AccessScopeWebController::class, 'index'])->name('admin.access_scopes.index');
    Route::post('/access-scopes', [AccessScopeWebController::class, 'store'])->name('access_scopes.store');
    Route::post('/admin/access-scopes', [AccessScopeWebController::class, 'store'])->name('admin.access_scopes.store');
    Route::delete('/access-scopes/{accessScope}', [AccessScopeWebController::class, 'destroy'])->name('access_scopes.destroy');
    Route::delete('/admin/access-scopes/{accessScope}', [AccessScopeWebController::class, 'destroy'])->name('admin.access_scopes.destroy');

    // Document Types Web
    Route::get('/document-types', [DocumentTypeWebController::class, 'index'])->name('document_types.index');
    Route::get('/admin/document-types', [DocumentTypeWebController::class, 'index'])->name('admin.document_types.index');
    Route::post('/document-types', [DocumentTypeWebController::class, 'store'])->name('document_types.store');
    Route::post('/admin/document-types', [DocumentTypeWebController::class, 'store'])->name('admin.document_types.store');
    Route::put('/document-types/{documentType}', [DocumentTypeWebController::class, 'update'])->name('document_types.update');
    Route::put('/admin/document-types/{documentType}', [DocumentTypeWebController::class, 'update'])->name('admin.document_types.update');
    Route::delete('/document-types/{documentType}', [DocumentTypeWebController::class, 'destroy'])->name('document_types.destroy');
    Route::delete('/admin/document-types/{documentType}', [DocumentTypeWebController::class, 'destroy'])->name('admin.document_types.destroy');
    Route::get('/document-types/{documentType}/metadata', [DocumentTypeWebController::class, 'metadata'])->name('document_types.metadata');
    Route::get('/admin/document-types/{documentType}/metadata', [DocumentTypeWebController::class, 'metadata'])->name('admin.document_types.metadata');
    Route::post('/document-types/{documentType}/metadata', [DocumentTypeWebController::class, 'syncMetadata'])->name('document_types.metadata.sync');
    Route::post('/admin/document-types/{documentType}/metadata', [DocumentTypeWebController::class, 'syncMetadata'])->name('admin.document_types.metadata.sync');

    // Search Web
    Route::get('/search', [SearchWebController::class, 'index'])->name('search.index');

    // Favorites & Recent Web
    Route::get('/favorites', [FavoriteWebController::class, 'index'])->name('favorites.index');
    Route::get('/recent', [RecentWebController::class, 'index'])->name('recent.index');

    // Shares overview
    Route::get('/shares', [ShareWebController::class, 'index'])->name('shares.index');

    // Admin Web: Categories, Tags, Metadata
    Route::get('/categories', [CategoryWebController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryWebController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [CategoryWebController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryWebController::class, 'destroy'])->name('categories.destroy');

    Route::get('/tags', [TagWebController::class, 'index'])->name('tags.index');
    Route::post('/tags', [TagWebController::class, 'store'])->name('tags.store');
    Route::put('/tags/{tag}', [TagWebController::class, 'update'])->name('tags.update');
    Route::delete('/tags/{tag}', [TagWebController::class, 'destroy'])->name('tags.destroy');

    Route::get('/metadata', [MetadataWebController::class, 'index'])->name('metadata.index');
    Route::post('/metadata', [MetadataWebController::class, 'store'])->name('metadata.store');
    Route::put('/metadata/{metadata}', [MetadataWebController::class, 'update'])->name('metadata.update');
    Route::delete('/metadata/{metadata}', [MetadataWebController::class, 'destroy'])->name('metadata.destroy');

    // Admin Web: Users & Roles
    Route::get('/users', [UserWebController::class, 'index'])->name('users.index');
    Route::get('/admin/users', [UserWebController::class, 'index'])->name('admin.users.index');
    Route::get('/users/create', [UserWebController::class, 'create'])->name('users.create');
    Route::get('/admin/users/create', [UserWebController::class, 'create'])->name('admin.users.create');
    Route::post('/users', [UserWebController::class, 'store'])->name('users.store');
    Route::post('/admin/users', [UserWebController::class, 'store'])->name('admin.users.store');
    Route::get('/users/{user}', [UserWebController::class, 'show'])->name('users.show');
    Route::get('/admin/users/{user}', [UserWebController::class, 'show'])->name('admin.users.show');
    Route::get('/users/{user}/edit', [UserWebController::class, 'edit'])->name('users.edit');
    Route::get('/admin/users/{user}/edit', [UserWebController::class, 'edit'])->name('admin.users.edit');
    Route::put('/users/{user}', [UserWebController::class, 'update'])->name('users.update');
    Route::put('/admin/users/{user}', [UserWebController::class, 'update'])->name('admin.users.update');
    Route::delete('/users/{user}', [UserWebController::class, 'destroy'])->name('users.destroy');
    Route::delete('/admin/users/{user}', [UserWebController::class, 'destroy'])->name('admin.users.destroy');
    Route::post('/users/{user}/toggle-status', [UserWebController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('/admin/users/{user}/toggle-status', [UserWebController::class, 'toggleStatus'])->name('admin.users.toggle-status');

    Route::get('/roles', [RoleWebController::class, 'index'])->name('roles.index');
    Route::get('/admin/roles', [RoleWebController::class, 'index'])->name('admin.roles.index');
    Route::get('/roles/create', [RoleWebController::class, 'create'])->name('roles.create');
    Route::get('/admin/roles/create', [RoleWebController::class, 'create'])->name('admin.roles.create');
    Route::post('/roles', [RoleWebController::class, 'store'])->name('roles.store');
    Route::post('/admin/roles', [RoleWebController::class, 'store'])->name('admin.roles.store');
    Route::get('/roles/{role}', [RoleWebController::class, 'show'])->name('roles.show');
    Route::get('/admin/roles/{role}', [RoleWebController::class, 'show'])->name('admin.roles.show');
    Route::get('/roles/{role}/edit', [RoleWebController::class, 'edit'])->name('roles.edit');
    Route::get('/admin/roles/{role}/edit', [RoleWebController::class, 'edit'])->name('admin.roles.edit');
    Route::put('/roles/{role}', [RoleWebController::class, 'update'])->name('roles.update');
    Route::put('/admin/roles/{role}', [RoleWebController::class, 'update'])->name('admin.roles.update');
    Route::delete('/roles/{role}', [RoleWebController::class, 'destroy'])->name('roles.destroy');
    Route::delete('/admin/roles/{role}', [RoleWebController::class, 'destroy'])->name('admin.roles.destroy');

    // Profile & Preferences
    Route::get('/profile', [ProfileWebController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileWebController::class, 'update'])->name('profile.update');
    Route::put('/profile/preferences', [ProfileWebController::class, 'updatePreferences'])->name('profile.preferences.update');

    // Document Favorites
    Route::post('/documents/{document}/favorite', [FavoriteController::class, 'toggle'])
        ->name('documents.favorite.toggle');

    // Document Preview
    Route::get('/documents/{document}/preview', [DocumentPreviewController::class, 'preview'])
        ->name('documents.preview');

    Route::get('/documents/{document}/versions/{version}/preview', [DocumentPreviewController::class, 'previewVersion'])
        ->name('documents.versions.preview');

    // Document Sharing
    Route::get('/documents/{document}/shares', [DocumentShareController::class, 'index'])
        ->name('documents.shares.index');

    Route::post('/documents/{document}/shares/user', [DocumentShareController::class, 'storeUserShare'])
        ->name('documents.shares.user.store');

    Route::post('/documents/{document}/shares/group', [DocumentShareController::class, 'storeGroupShare'])
        ->name('documents.shares.group.store');

    Route::delete('/documents/{document}/shares/{share}', [DocumentShareController::class, 'destroy'])
        ->name('documents.shares.destroy');

    // Document Lifecycle actions
    Route::post('/documents/{document}/archive', [DocumentLifecycleController::class, 'archive'])
        ->name('documents.archive');

    Route::post('/documents/{document}/unarchive', [DocumentLifecycleController::class, 'unarchive'])
        ->name('documents.unarchive');

    Route::delete('/documents/{document}', [DocumentLifecycleController::class, 'destroy'])
        ->name('documents.destroy');

    Route::post('/documents/{document}/restore', [DocumentLifecycleController::class, 'restore'])
        ->withTrashed()
        ->name('documents.restore');

    Route::delete('/documents/{document}/force', [DocumentLifecycleController::class, 'forceDestroy'])
        ->withTrashed()
        ->name('documents.force-destroy');

    // Audit & History
    Route::get('/audit-logs', [AuditLogController::class, 'index'])
        ->name('audit.logs.index');

    Route::get('/documents/{document}/history', [AuditLogController::class, 'documentHistory'])
        ->name('documents.history');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');

    Route::get('/notifications/unread', [NotificationController::class, 'unread'])
        ->name('notifications.unread');

    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');

    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.read_all');

    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])
        ->name('notifications.destroy');

    Route::get('/notifications/preferences', [NotificationController::class, 'preferences'])
        ->name('notifications.preferences.index');

    Route::put('/notifications/preferences', [NotificationController::class, 'updatePreferences'])
        ->name('notifications.preferences.update');

    // Workflows
    Route::get('/workflows', [WorkflowController::class, 'index'])->name('workflows.index');
    Route::post('/workflows', [WorkflowController::class, 'store'])->name('workflows.store');
    Route::get('/workflows/{workflow}', [WorkflowController::class, 'show'])->name('workflows.show');
    Route::put('/workflows/{workflow}', [WorkflowController::class, 'update'])->name('workflows.update');
    Route::delete('/workflows/{workflow}', [WorkflowController::class, 'destroy'])->name('workflows.destroy');

    // Workflow Steps
    Route::post('/workflows/{workflow}/steps', [WorkflowStepController::class, 'store'])->name('workflows.steps.store');
    Route::put('/workflow-steps/{step}', [WorkflowStepController::class, 'update'])->name('workflow_steps.update');
    Route::delete('/workflow-steps/{step}', [WorkflowStepController::class, 'destroy'])->name('workflow_steps.destroy');
    Route::post('/workflows/{workflow}/steps/reorder', [WorkflowStepController::class, 'reorder'])->name('workflows.steps.reorder');

    // Workflow Instances
    Route::get('/workflow-instances', [WorkflowInstanceController::class, 'index'])->name('workflow_instances.index');
    Route::get('/workflow-instances/{instance}', [WorkflowInstanceController::class, 'show'])->name('workflow_instances.show');
    Route::get('/workflow-instances/{instance}/history', [WorkflowInstanceController::class, 'history'])->name('workflow_instances.history');
    Route::post('/documents/{document}/workflows/{workflow}/start', [WorkflowInstanceController::class, 'start'])->name('workflow_instances.start');

    Route::post('/workflow-instances/{instance}/approve', [WorkflowInstanceController::class, 'approve'])->name('workflow_instances.approve');
    Route::post('/workflow-instances/{instance}/reject', [WorkflowInstanceController::class, 'reject'])->name('workflow_instances.reject');
    Route::post('/workflow-instances/{instance}/correction', [WorkflowInstanceController::class, 'requestCorrection'])->name('workflow_instances.correction');
    Route::post('/workflow-instances/{instance}/resubmit', [WorkflowInstanceController::class, 'resubmit'])->name('workflow_instances.resubmit');
    Route::post('/workflow-instances/{instance}/cancel', [WorkflowInstanceController::class, 'cancel'])->name('workflow_instances.cancel');

    // Document Comments
    Route::get('/documents/{document}/comments', [DocumentCommentController::class, 'index'])->name('documents.comments.index');
    Route::post('/documents/{document}/comments', [DocumentCommentController::class, 'store'])->name('documents.comments.store');
    Route::post('/documents/{document}/versions/{version}/comments', [DocumentCommentController::class, 'storeVersionComment'])->name('documents.versions.comments.store');
    Route::post('/comments/{comment}/reply', [DocumentCommentController::class, 'reply'])->name('comments.reply');
    Route::put('/comments/{comment}', [DocumentCommentController::class, 'update'])->name('comments.update');
    Route::delete('/comments/{comment}', [DocumentCommentController::class, 'destroy'])->name('comments.destroy');
    Route::post('/comments/{comment}/restore', [DocumentCommentController::class, 'restore'])->name('comments.restore')->withTrashed();

    // Organization Subscription & Billing
    Route::get('/settings/subscription', [SubscriptionWebController::class, 'index'])->name('subscription.show');
    Route::get('/subscription/choose', [SubscriptionWebController::class, 'choose'])->name('subscription.choose');
    Route::post('/settings/subscription/change-plan', [SubscriptionWebController::class, 'changePlan'])->name('subscription.change-plan');
    Route::post('/settings/subscription/cancel', [SubscriptionWebController::class, 'cancel'])->name('subscription.cancel');
    Route::post('/settings/subscription/resume', [SubscriptionWebController::class, 'resume'])->name('subscription.resume');
});

// ==========================================
// PLATFORM DOMAIN (SaaS Owner / Admin)
// ==========================================
Route::prefix('platform')->name('platform.')->group(function () {
    // Guest platform routes
    Route::middleware('guest:platform')->group(function () {
        Route::get('/login', [PlatformAuthWebController::class, 'create'])->name('login');
        Route::post('/login', [PlatformAuthWebController::class, 'store'])->name('login.store');
    });

    // Protected platform routes
    Route::middleware(['platform.auth'])->group(function () {
        Route::post('/logout', [PlatformAuthWebController::class, 'destroy'])->name('logout');

        // Dashboard (Accessible by all platform staff)
        Route::get('/', [PlatformDashboardWebController::class, 'index'])->name('dashboard');

        // Organizations, Users, Usage (Owner, Admin, Support)
        Route::middleware(['platform.role:platform_owner,platform_admin,platform_support'])->group(function () {
            Route::get('/organizations', [PlatformOrganizationWebController::class, 'index'])->name('organizations.index');
            Route::get('/organizations/{organization}', [PlatformOrganizationWebController::class, 'show'])->name('organizations.show');
            Route::post('/organizations/{organization}/suspend', [PlatformOrganizationWebController::class, 'suspend'])->name('organizations.suspend');
            Route::post('/organizations/{organization}/reactivate', [PlatformOrganizationWebController::class, 'reactivate'])->name('organizations.reactivate');
            Route::post('/organizations/{organization}/subscription', [PlatformOrganizationWebController::class, 'updateSubscription'])->name('organizations.subscription.update');

            Route::get('/usage', [PlatformUsageWebController::class, 'index'])->name('usage.index');
            Route::get('/users', [PlatformUserWebController::class, 'index'])->name('users.index');
        });

        // Plans, Subscriptions, Trials (Owner, Admin, Billing)
        Route::middleware(['platform.role:platform_owner,platform_admin,platform_billing'])->group(function () {
            Route::get('/plans', [PlatformPlanWebController::class, 'index'])->name('plans.index');
            Route::get('/plans/create', [PlatformPlanWebController::class, 'create'])->name('plans.create');
            Route::post('/plans', [PlatformPlanWebController::class, 'store'])->name('plans.store');
            Route::get('/plans/{plan}/edit', [PlatformPlanWebController::class, 'edit'])->name('plans.edit');
            Route::put('/plans/{plan}', [PlatformPlanWebController::class, 'update'])->name('plans.update');
            Route::post('/plans/{plan}/toggle-active', [PlatformPlanWebController::class, 'toggleActive'])->name('plans.toggle-active');

            Route::get('/subscriptions', [PlatformSubscriptionWebController::class, 'index'])->name('subscriptions.index');
            Route::get('/subscriptions/{subscription}', [PlatformSubscriptionWebController::class, 'show'])->name('subscriptions.show');
            Route::post('/subscriptions/{subscription}/cancel', [PlatformSubscriptionWebController::class, 'cancel'])->name('subscriptions.cancel');
            Route::post('/subscriptions/{subscription}/extend-trial', [PlatformSubscriptionWebController::class, 'extendTrial'])->name('subscriptions.extend-trial');
            Route::post('/subscriptions/{subscription}/end-trial', [PlatformSubscriptionWebController::class, 'endTrial'])->name('subscriptions.end-trial');

            Route::get('/trials', [PlatformTrialWebController::class, 'index'])->name('trials.index');
            Route::post('/trials/{organization}/extend', [PlatformTrialWebController::class, 'extend'])->name('trials.extend');
            Route::post('/trials/{organization}/end', [PlatformTrialWebController::class, 'end'])->name('trials.end');
            Route::post('/trials/{organization}/convert', [PlatformTrialWebController::class, 'convert'])->name('trials.convert');
        });

        // Payments & Invoices (Owner, Billing only)
        Route::middleware(['platform.role:platform_owner,platform_billing'])->group(function () {
            Route::get('/payments', [PlatformPaymentWebController::class, 'index'])->name('payments.index');
            Route::post('/payments', [PlatformPaymentWebController::class, 'store'])->name('payments.store');

            Route::get('/invoices', [PlatformInvoiceWebController::class, 'index'])->name('invoices.index');
            Route::get('/invoices/{invoice}', [PlatformInvoiceWebController::class, 'show'])->name('invoices.show');
            Route::post('/invoices/{invoice}/mark-paid', [PlatformInvoiceWebController::class, 'markPaid'])->name('invoices.mark-paid');
        });

        // Support (Owner, Admin, Support only)
        Route::middleware(['platform.role:platform_owner,platform_admin,platform_support'])->group(function () {
            Route::get('/support', [PlatformSupportWebController::class, 'index'])->name('support.index');
            Route::post('/support', [PlatformSupportWebController::class, 'store'])->name('support.store');
            Route::put('/support/{ticket}', [PlatformSupportWebController::class, 'update'])->name('support.update');
        });

        // Platform Audit (Owner, Admin only)
        Route::middleware(['platform.role:platform_owner,platform_admin'])->group(function () {
            Route::get('/audit', [PlatformAuditWebController::class, 'index'])->name('audit.index');
        });

        // Settings (Owner only)
        Route::middleware(['platform.role:platform_owner'])->group(function () {
            Route::get('/settings', [PlatformSettingWebController::class, 'index'])->name('settings.index');
            Route::put('/settings', [PlatformSettingWebController::class, 'update'])->name('settings.update');
        });
    });
});
