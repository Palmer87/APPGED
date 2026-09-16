<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentCommentController;
use App\Http\Controllers\DocumentLifecycleController;
use App\Http\Controllers\DocumentPreviewController;
use App\Http\Controllers\DocumentShareController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\WorkflowInstanceController;
use App\Http\Controllers\WorkflowStepController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', fn () => response()->json(['message' => 'Unauthenticated'], 401))->name('login');

Route::middleware(['auth'])->group(function () {
    // Dashboard V1
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

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

    // Document Lifecycle (Trash & Archive)
    Route::get('/documents/trash', [DocumentLifecycleController::class, 'trash'])
        ->name('documents.trash.index');

    Route::post('/documents/trash/empty', [DocumentLifecycleController::class, 'emptyTrash'])
        ->name('documents.trash.empty');

    Route::get('/documents/archived', [DocumentLifecycleController::class, 'archived'])
        ->name('documents.archived.index');

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
});
