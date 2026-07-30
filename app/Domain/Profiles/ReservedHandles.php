<?php

namespace App\Domain\Profiles;

final class ReservedHandles
{
    /**
     * @var list<string>
     */
    private const VALUES = [
        'admin',
        'api',
        'app',
        'auth',
        'dashboard',
        'help',
        'login',
        'logout',
        'register',
        'settings',
        'support',
    ];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return self::VALUES;
    }

    public static function contains(string $handle): bool
    {
        return in_array(strtolower($handle), self::VALUES, true);
    }
}
