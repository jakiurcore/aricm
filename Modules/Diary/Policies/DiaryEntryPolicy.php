<?php

namespace Modules\Diary\Policies;

use Modules\Accounts\Models\User;
use Modules\Diary\Models\DiaryEntry;
use Modules\Diary\Services\DiaryService;

class DiaryEntryPolicy
{
    public function __construct(protected DiaryService $diary) {}

    public function viewAny(User $user): bool
    {
        return $user->can('diary.monitor') || $user->can('diary.manage');
    }

    public function view(User $user, DiaryEntry $entry): bool
    {
        return $this->viewAny($user) || $this->diary->isOwner($user, $entry);
    }

    public function update(User $user, DiaryEntry $entry): bool
    {
        return $this->diary->canEdit($user, $entry);
    }

    public function delete(User $user, DiaryEntry $entry): bool
    {
        return $this->diary->canDelete($user, $entry);
    }
}
