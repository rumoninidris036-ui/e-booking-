<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BadmintonField;
use App\Models\User;

class BadmintonFieldPolicy
{
    public function view(User $user, BadmintonField $badmintonField): bool
    {
        return $user->hasRole('owner') && $badmintonField->owner_id === $user->id;
    }

    public function update(User $user, BadmintonField $badmintonField): bool
    {
        return $user->hasRole('owner') && $badmintonField->owner_id === $user->id;
    }

    public function delete(User $user, BadmintonField $badmintonField): bool
    {
        return $user->hasRole('owner') && $badmintonField->owner_id === $user->id;
    }
}
