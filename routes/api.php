<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DocumentCommentController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\DocumentOcrController;
use App\Http\Controllers\Api\V1\DocumentShareController;
use App\Http\Controllers\Api\V1\DocumentTypeController;
use App\Http\Controllers\Api\V1\DocumentVersionController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\FolderController;
use App\Http\Controllers\Api\V1\MetadataController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\RecentDocumentController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\WorkflowController;
use App\Http\Controllers\Api\V1\WorkflowInstanceController;
use App\Http\Middleware\EnsureApiTeamContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Legacy / Debug route
|--------------------------------------------------------------------------
*/
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| API Version 1 Routes
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {

    // Public authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('api.v1.auth.login');
    });

    // Authenticated Sanctum routes with tenant team context
    Route::middleware(['auth:sanctum', EnsureApiTeamContext::class])->group(function () {

        // Auth
        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me'])->name('api.v1.auth.me');
            Route::post('/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
            Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('api.v1.auth.logout_all');
        });

        // Organizations
        Route::prefix('organizations')->group(function () {
            Route::get('/current', [OrganizationController::class, 'current'])->name('api.v1.organizations.current');
        });

        // Document Types
        Route::prefix('document-types')->group(function () {
            Route::get('/{id}/metadata', [DocumentTypeController::class, 'metadata'])->name('api.v1.document_types.metadata');
            Route::post('/{id}/metadata', [DocumentTypeController::class, 'syncMetadata'])->name('api.v1.document_types.metadata.sync');
        });
        Route::apiResource('document-types', DocumentTypeController::class)->names('api.v1.document_types');

        // Folders
        Route::apiResource('folders', FolderController::class)->names('api.v1.folders');

        // Documents
        Route::prefix('documents')->group(function () {
            Route::get('/{document}/download', [DocumentController::class, 'download'])->name('api.v1.documents.download');
            Route::get('/{document}/preview', [DocumentController::class, 'preview'])->name('api.v1.documents.preview');
            Route::get('/{document}/history', [DocumentController::class, 'history'])->name('api.v1.documents.history');

            // Document Versions
            Route::get('/{document}/versions', [DocumentVersionController::class, 'index'])->name('api.v1.documents.versions.index');
            Route::post('/{document}/versions', [DocumentVersionController::class, 'store'])->name('api.v1.documents.versions.store');
            Route::get('/{document}/versions/{version}/download', [DocumentVersionController::class, 'download'])->name('api.v1.documents.versions.download');
            Route::post('/{document}/versions/{version}/restore', [DocumentVersionController::class, 'restore'])->name('api.v1.documents.versions.restore');

            // Document Favorite toggle shorthand
            Route::post('/{document}/favorite', [FavoriteController::class, 'toggle'])->name('api.v1.documents.favorite.toggle');

            // Document OCR
            Route::get('/{document}/ocr', [DocumentOcrController::class, 'show'])->name('api.v1.documents.ocr.show');
            Route::post('/{document}/ocr/retry', [DocumentOcrController::class, 'retry'])->name('api.v1.documents.ocr.retry');
        });
        Route::apiResource('documents', DocumentController::class)->names('api.v1.documents');

        // Search
        Route::get('/search', [SearchController::class, 'index'])->name('api.v1.search');

        // Categories & Tags
        Route::apiResource('categories', CategoryController::class)->names('api.v1.categories');
        Route::apiResource('tags', TagController::class)->names('api.v1.tags');

        // Custom Metadata
        Route::prefix('metadata')->group(function () {
            Route::get('/definitions', [MetadataController::class, 'definitions'])->name('api.v1.metadata.definitions.index');
            Route::post('/definitions', [MetadataController::class, 'storeDefinition'])->name('api.v1.metadata.definitions.store');
            Route::get('/definitions/{definition}', [MetadataController::class, 'showDefinition'])->name('api.v1.metadata.definitions.show');
            Route::put('/definitions/{definition}', [MetadataController::class, 'updateDefinition'])->name('api.v1.metadata.definitions.update');
            Route::delete('/definitions/{definition}', [MetadataController::class, 'destroyDefinition'])->name('api.v1.metadata.definitions.destroy');

            Route::get('/documents/{document}', [MetadataController::class, 'getDocumentMetadata'])->name('api.v1.metadata.documents.get');
            Route::put('/documents/{document}', [MetadataController::class, 'updateDocumentMetadata'])->name('api.v1.metadata.documents.update');
        });

        // Favorites
        Route::prefix('favorites')->group(function () {
            Route::get('/', [FavoriteController::class, 'index'])->name('api.v1.favorites.index');
            Route::post('/documents/{document}', [FavoriteController::class, 'toggle'])->name('api.v1.favorites.toggle');
        });

        // Recent Documents
        Route::get('/recent', [RecentDocumentController::class, 'index'])->name('api.v1.recent.index');

        // Document Shares
        Route::prefix('shares')->group(function () {
            Route::get('/documents/{document}', [DocumentShareController::class, 'index'])->name('api.v1.shares.index');
            Route::post('/documents/{document}/user', [DocumentShareController::class, 'storeUserShare'])->name('api.v1.shares.user.store');
            Route::post('/documents/{document}/group', [DocumentShareController::class, 'storeGroupShare'])->name('api.v1.shares.group.store');
            Route::delete('/documents/{document}/{share}', [DocumentShareController::class, 'destroy'])->name('api.v1.shares.destroy');
        });

        // Workflows & Instances
        Route::prefix('workflows')->group(function () {
            Route::get('/', [WorkflowController::class, 'index'])->name('api.v1.workflows.index');
            Route::post('/', [WorkflowController::class, 'store'])->name('api.v1.workflows.store');
            Route::get('/{workflow}', [WorkflowController::class, 'show'])->name('api.v1.workflows.show');
            Route::post('/documents/{document}/start/{workflow}', [WorkflowController::class, 'start'])->name('api.v1.workflows.start');

            // Instances
            Route::get('/instances', [WorkflowInstanceController::class, 'index'])->name('api.v1.workflow_instances.index');
            Route::get('/instances/{instance}', [WorkflowInstanceController::class, 'show'])->name('api.v1.workflow_instances.show');
            Route::post('/instances/{instance}/approve', [WorkflowInstanceController::class, 'approve'])->name('api.v1.workflow_instances.approve');
            Route::post('/instances/{instance}/reject', [WorkflowInstanceController::class, 'reject'])->name('api.v1.workflow_instances.reject');
            Route::post('/instances/{instance}/cancel', [WorkflowInstanceController::class, 'cancel'])->name('api.v1.workflow_instances.cancel');
        });

        // Document Comments
        Route::prefix('comments')->group(function () {
            Route::get('/documents/{document}', [DocumentCommentController::class, 'index'])->name('api.v1.comments.index');
            Route::post('/documents/{document}', [DocumentCommentController::class, 'store'])->name('api.v1.comments.store');
            Route::post('/{comment}/reply', [DocumentCommentController::class, 'reply'])->name('api.v1.comments.reply');
            Route::put('/{comment}', [DocumentCommentController::class, 'update'])->name('api.v1.comments.update');
            Route::delete('/{comment}', [DocumentCommentController::class, 'destroy'])->name('api.v1.comments.destroy');
        });

        // Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('api.v1.notifications.index');
            Route::get('/unread', [NotificationController::class, 'unread'])->name('api.v1.notifications.unread');
            Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('api.v1.notifications.read_all');
            Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('api.v1.notifications.mark_as_read');
            Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('api.v1.notifications.destroy');
        });
    });
});
