<?php

declare(strict_types=1);

namespace App\Services\Lead;

use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\User;

final class LeadTimelineService
{
    /**
     * Create a new note for a Lead.
     */
    public function createNote(User $user, Lead $lead, string $content, bool $isPinned = false): LeadNote
    {
        /** @var LeadNote $note */
        $note = LeadNote::create([
            'lead_id' => $lead->getKey(),
            'user_id' => $user->getKey(),
            'content' => trim($content),
            'is_pinned' => $isPinned,
        ]);

        return $note;
    }

    /**
     * Toggle pinned status of a note.
     */
    public function togglePinNote(User $user, LeadNote $note): bool
    {
        $this->authorizeNoteModification($user, $note);

        $note->is_pinned = ! $note->is_pinned;
        $note->save();

        return $note->is_pinned;
    }

    /**
     * Delete a note.
     */
    public function deleteNote(User $user, LeadNote $note): bool
    {
        $this->authorizeNoteModification($user, $note);

        return (bool) $note->delete();
    }

    /**
     * Check authorization before editing/deleting/pinning note.
     */
    private function authorizeNoteModification(User $user, LeadNote $note): void
    {
        $superAdminRole = (string) config('crm.rbac.super_admin_role', 'super-admin');
        if ($note->user_id === $user->id || $user->hasRole($superAdminRole) || $user->hasRole('admin') || $user->can('users.manage')) {
            return;
        }

        abort(403, 'Bạn không có quyền thao tác trên ghi chú này.');
    }
}
