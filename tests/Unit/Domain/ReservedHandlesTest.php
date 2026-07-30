<?php

use App\Domain\Profiles\ReservedHandles;

test('the required handles are reserved', function (string $handle): void {
    expect(ReservedHandles::contains($handle))->toBeTrue();
})->with([
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
    'ADMIN',
]);

test('non-reserved handles remain available', function (): void {
    expect(ReservedHandles::contains('lance'))->toBeFalse()
        ->and(ReservedHandles::all())->toContain('admin');
});
