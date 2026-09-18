<?php

namespace App\Services;

use App\Enums\WorkflowApproverType;
use App\Models\Document;
use App\Models\DocumentComment;
use App\Models\DocumentOcr;
use App\Models\DocumentShare;
use App\Models\DocumentVersion;
use App\Models\Group;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use App\Notifications\DocumentArchivedNotification;
use App\Notifications\DocumentCommentedNotification;
use App\Notifications\DocumentOcrCompletedNotification;
use App\Notifications\DocumentOcrFailedNotification;
use App\Notifications\DocumentRestoredNotification;
use App\Notifications\DocumentSharedNotification;
use App\Notifications\DocumentShareRevokedNotification;
use App\Notifications\DocumentVersionCreatedNotification;
use App\Notifications\WorkflowApprovedNotification;
use App\Notifications\WorkflowCorrectionRequestedNotification;
use App\Notifications\WorkflowRejectedNotification;
use App\Notifications\WorkflowStepAssignedNotification;
use App\Notifications\WorkflowSubmittedNotification;
use Carbon\Carbon;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class NotificationService
{
    /**
     * Send a notification to a single user with strict multi-tenant validation.
     */
    public function notifyUser(User $target, Notification $notification, ?int $organizationId = null): void
    {
        // 1. Resolve expected organization
        $expectedOrgId = $organizationId;
        if ($expectedOrgId === null && isset($notification->document)) {
            $expectedOrgId = (int) $notification->document->organization_id;
        }

        // 2. Strict tenant check: target must belong to expected organization
        if ($expectedOrgId !== null && $target->organization_id !== $expectedOrgId) {
            return;
        }

        // 3. Do not self-notify the actor
        if (isset($notification->actor) && $notification->actor instanceof User && $target->id === $notification->actor->id) {
            return;
        }

        NotificationFacade::send($target, $notification);
    }

    /**
     * Send a notification to multiple users, ensuring deduplication, tenant isolation, and actor exclusion.
     *
     * @param  iterable<User>  $targets
     */
    public function notifyUsers(iterable $targets, Notification $notification, ?int $organizationId = null): void
    {
        $expectedOrgId = $organizationId;
        if ($expectedOrgId === null && isset($notification->document)) {
            $expectedOrgId = (int) $notification->document->organization_id;
        }

        $actorId = isset($notification->actor) && $notification->actor instanceof User
            ? $notification->actor->id
            : null;

        $validRecipients = [];

        foreach ($targets as $user) {
            if (! ($user instanceof User)) {
                continue;
            }

            // Exclude actor
            if ($actorId !== null && $user->id === $actorId) {
                continue;
            }

            // Strict tenant isolation
            if ($expectedOrgId !== null && $user->organization_id !== $expectedOrgId) {
                continue;
            }

            // Deduplicate by user id
            $validRecipients[$user->id] = $user;
        }

        if (! empty($validRecipients)) {
            NotificationFacade::send(array_values($validRecipients), $notification);
        }
    }

    /**
     * Notify user or group members when a document is shared.
     */
    public function notifyDocumentShared(Document $document, User $actor, User|Group $target, string $permission = 'view'): void
    {
        $notification = new DocumentSharedNotification($document, $actor, $permission);

        if ($target instanceof User) {
            $this->notifyUser($target, $notification, $document->organization_id);
        } elseif ($target instanceof Group) {
            if ($target->organization_id === $document->organization_id) {
                $target->loadMissing('users');
                $this->notifyUsers($target->users, $notification, $document->organization_id);
            }
        }
    }

    /**
     * Notify user or group members when a document share is revoked.
     */
    public function notifyDocumentShareRevoked(Document $document, User $actor, User|Group $target): void
    {
        $notification = new DocumentShareRevokedNotification($document, $actor);

        if ($target instanceof User) {
            $this->notifyUser($target, $notification, $document->organization_id);
        } elseif ($target instanceof Group) {
            if ($target->organization_id === $document->organization_id) {
                $target->loadMissing('users');
                $this->notifyUsers($target->users, $notification, $document->organization_id);
            }
        }
    }

    /**
     * Notify active share recipients and creator when a new version is created.
     */
    public function notifyDocumentVersionCreated(Document $document, User $actor, DocumentVersion $version): void
    {
        $recipients = $this->getDocumentInterestedUsers($document, $actor);
        $notification = new DocumentVersionCreatedNotification($document, $version, $actor);

        $this->notifyUsers($recipients, $notification, $document->organization_id);
    }

    /**
     * Notify active share recipients and creator when a document is archived.
     */
    public function notifyDocumentArchived(Document $document, User $actor): void
    {
        $recipients = $this->getDocumentInterestedUsers($document, $actor);
        $notification = new DocumentArchivedNotification($document, $actor);

        $this->notifyUsers($recipients, $notification, $document->organization_id);
    }

    /**
     * Notify active share recipients and creator when a document is restored from trash.
     */
    public function notifyDocumentRestored(Document $document, User $actor): void
    {
        $recipients = $this->getDocumentInterestedUsers($document, $actor);
        $notification = new DocumentRestoredNotification($document, $actor);

        $this->notifyUsers($recipients, $notification, $document->organization_id);
    }

    /**
     * Resolve users interested in events on a document (active share recipients and creator).
     *
     * @return array<int, User>
     */
    public function getDocumentInterestedUsers(Document $document, ?User $actor = null): array
    {
        $users = [];
        $actorId = $actor?->id;

        // 1. Find all active shares (not revoked, not expired)
        $now = Carbon::now();
        $shares = DocumentShare::where('document_id', $document->id)
            ->whereNull('revoked_at')
            ->where(function ($query) use ($now) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', $now);
            })
            ->with(['user', 'group.users'])
            ->get();

        foreach ($shares as $share) {
            if ($share->user && $share->user->organization_id === $document->organization_id) {
                if ($share->user->id !== $actorId) {
                    $users[$share->user->id] = $share->user;
                }
            } elseif ($share->group && $share->group->organization_id === $document->organization_id) {
                foreach ($share->group->users as $groupMember) {
                    if ($groupMember->organization_id === $document->organization_id && $groupMember->id !== $actorId) {
                        $users[$groupMember->id] = $groupMember;
                    }
                }
            }
        }

        // 2. Include document creator if different from actor and in same org
        if ($document->uploaded_by && $document->uploaded_by !== $actorId) {
            $creator = User::find($document->uploaded_by);
            if ($creator && $creator->organization_id === $document->organization_id) {
                $users[$creator->id] = $creator;
            }
        }

        return array_values($users);
    }

    /**
     * Resolve target users for a workflow step (user or group members in the same organization).
     *
     * @return array<int, User>
     */
    public function getStepApprovers(WorkflowStep $step): array
    {
        $users = [];

        if ($step->approver_type === WorkflowApproverType::User && $step->approverUser) {
            if ($step->approverUser->organization_id === $step->organization_id) {
                $users[$step->approverUser->id] = $step->approverUser;
            }
        } elseif ($step->approver_type === WorkflowApproverType::Group && $step->approverGroup) {
            $step->approverGroup->loadMissing('users');
            foreach ($step->approverGroup->users as $member) {
                if ($member->organization_id === $step->organization_id) {
                    $users[$member->id] = $member;
                }
            }
        }

        return array_values($users);
    }

    /**
     * Notify first approver(s) upon workflow start/submission.
     */
    public function notifyWorkflowSubmitted(WorkflowInstance $instance, User $actor, WorkflowStep $step): void
    {
        $approvers = $this->getStepApprovers($step);
        $notification = new WorkflowSubmittedNotification(
            document: $instance->document,
            actor: $actor,
            instance: $instance,
            step: $step
        );

        $this->notifyUsers($approvers, $notification, $instance->organization_id);
    }

    /**
     * Notify approver(s) of the next step.
     */
    public function notifyWorkflowStepAssigned(WorkflowInstance $instance, User $actor, WorkflowStep $step): void
    {
        $approvers = $this->getStepApprovers($step);
        $notification = new WorkflowStepAssignedNotification(
            document: $instance->document,
            actor: $actor,
            instance: $instance,
            step: $step
        );

        $this->notifyUsers($approvers, $notification, $instance->organization_id);
    }

    /**
     * Notify of workflow approval (step or final).
     */
    public function notifyWorkflowApproved(WorkflowInstance $instance, User $actor, ?WorkflowStep $step = null, bool $isFinal = false): void
    {
        $recipients = [];
        $instance->loadMissing(['startedBy', 'document.uploader']);

        if ($instance->startedBy && $instance->startedBy->organization_id === $instance->organization_id) {
            $recipients[$instance->startedBy->id] = $instance->startedBy;
        }

        if ($instance->document && $instance->document->uploader && $instance->document->uploader->organization_id === $instance->organization_id) {
            $recipients[$instance->document->uploader->id] = $instance->document->uploader;
        }

        $notification = new WorkflowApprovedNotification(
            document: $instance->document,
            actor: $actor,
            instance: $instance,
            step: $step,
            isFinal: $isFinal
        );

        $this->notifyUsers(array_values($recipients), $notification, $instance->organization_id);
    }

    /**
     * Notify of workflow rejection.
     */
    public function notifyWorkflowRejected(WorkflowInstance $instance, User $actor, string $comment, ?WorkflowStep $step = null): void
    {
        $recipients = [];
        $instance->loadMissing(['startedBy', 'document.uploader']);

        if ($instance->startedBy && $instance->startedBy->organization_id === $instance->organization_id) {
            $recipients[$instance->startedBy->id] = $instance->startedBy;
        }

        if ($instance->document && $instance->document->uploader && $instance->document->uploader->organization_id === $instance->organization_id) {
            $recipients[$instance->document->uploader->id] = $instance->document->uploader;
        }

        $notification = new WorkflowRejectedNotification(
            document: $instance->document,
            actor: $actor,
            instance: $instance,
            comment: $comment,
            step: $step
        );

        $this->notifyUsers(array_values($recipients), $notification, $instance->organization_id);
    }

    /**
     * Notify of workflow correction request.
     */
    public function notifyWorkflowCorrectionRequested(WorkflowInstance $instance, User $actor, string $comment, ?WorkflowStep $step = null): void
    {
        $recipients = [];
        $instance->loadMissing(['startedBy', 'document.uploader']);

        if ($instance->startedBy && $instance->startedBy->organization_id === $instance->organization_id) {
            $recipients[$instance->startedBy->id] = $instance->startedBy;
        }

        if ($instance->document && $instance->document->uploader && $instance->document->uploader->organization_id === $instance->organization_id) {
            $recipients[$instance->document->uploader->id] = $instance->document->uploader;
        }

        $notification = new WorkflowCorrectionRequestedNotification(
            document: $instance->document,
            actor: $actor,
            instance: $instance,
            comment: $comment,
            step: $step
        );

        $this->notifyUsers(array_values($recipients), $notification, $instance->organization_id);
    }

    /**
     * Notify interested users when a comment or reply is added to a document.
     */
    public function notifyDocumentCommented(DocumentComment $comment, User $actor): void
    {
        $document = $comment->document;
        if (! $document) {
            return;
        }

        $recipients = [];

        // 1. If it is a reply, notify the author of the parent comment
        if ($comment->parent_id && $comment->parent) {
            $parentAuthor = $comment->parent->user;
            if ($parentAuthor && $parentAuthor->organization_id === $document->organization_id && $parentAuthor->id !== $actor->id) {
                $recipients[$parentAuthor->id] = $parentAuthor;
            }
        }

        // 2. Add users interested in the document (shares + creator)
        $interestedUsers = $this->getDocumentInterestedUsers($document, $actor);
        foreach ($interestedUsers as $user) {
            $recipients[$user->id] = $user;
        }

        $commentPreview = mb_substr($comment->content, 0, 100);
        $notification = new DocumentCommentedNotification(
            document: $document,
            actor: $actor,
            commentPreview: $commentPreview
        );

        $this->notifyUsers(array_values($recipients), $notification, $document->organization_id);
    }

    /**
     * Notify document owner when OCR processing completes successfully.
     */
    public function notifyDocumentOcrCompleted(Document $document, DocumentOcr $ocr): void
    {
        $owner = $document->uploader ?? $document->creator;
        if (! $owner) {
            return;
        }

        $notification = new DocumentOcrCompletedNotification($document, $ocr);
        $this->notifyUser($owner, $notification, $document->organization_id);
    }

    /**
     * Notify document owner when OCR processing fails.
     */
    public function notifyDocumentOcrFailed(Document $document, DocumentOcr $ocr): void
    {
        $owner = $document->uploader ?? $document->creator;
        if (! $owner) {
            return;
        }

        $notification = new DocumentOcrFailedNotification($document, $ocr);
        $this->notifyUser($owner, $notification, $document->organization_id);
    }
}
