<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

class RoleHome
{
    public static function routeNameFor(?User $user): string
    {
        if ($user?->hasRole('admin') === true) {
            return 'admin.dashboard';
        }

        if ($user?->hasRole('owner') === true) {
            return 'owner.dashboard';
        }

        return 'public.fields.index';
    }

    public static function urlFor(?User $user, bool $absolute = true): string
    {
        return route(self::routeNameFor($user), absolute: $absolute);
    }
}
