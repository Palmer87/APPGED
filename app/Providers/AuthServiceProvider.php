<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentComment;
use App\Models\Group;
use App\Models\MetadataDefinition;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Policies\AuditLogPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\DocumentCommentPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\GroupPolicy;
use App\Policies\MetadataDefinitionPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\TagPolicy;
use App\Policies\UserPolicy;
use App\Policies\WorkflowInstancePolicy;
use App\Policies\WorkflowPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionRegistrar;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Group::class => GroupPolicy::class,
        Organization::class => OrganizationPolicy::class,
        Document::class => DocumentPolicy::class,
        DocumentComment::class => DocumentCommentPolicy::class,
        Category::class => CategoryPolicy::class,
        Tag::class => TagPolicy::class,
        MetadataDefinition::class => MetadataDefinitionPolicy::class,
        AuditLog::class => AuditLogPolicy::class,
        Workflow::class => WorkflowPolicy::class,
        WorkflowInstance::class => WorkflowInstancePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
        Gate::policy(MetadataDefinition::class, MetadataDefinitionPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(Workflow::class, WorkflowPolicy::class);
        Gate::policy(WorkflowInstance::class, WorkflowInstancePolicy::class);
        Gate::policy(DocumentComment::class, DocumentCommentPolicy::class);

        // Super-admin has global access across all organisations
        Gate::before(function (User $user, $ability) {
            if ($user->organization_id) {
                app(PermissionRegistrar::class)->setPermissionsTeamId($user->organization_id);
            }

            if ($user->hasRole('super-admin')) {
                return true;
            }
        });
    }
}
