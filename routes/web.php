<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\PublicRepositoryController;
use App\Http\Controllers\ReleaseController;
use App\Http\Controllers\RepositoryController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'show'])->name('dashboard');

    Route::get('repositories', [RepositoryController::class, 'index'])->name('repositories.index');
    Route::get('repositories/create', [RepositoryController::class, 'create'])->name('repositories.create');
    Route::post('repositories', [RepositoryController::class, 'store'])->name('repositories.store');
    Route::get('repositories/{repository:public_id}', [RepositoryController::class, 'show'])->name('repositories.show');
    Route::get('repositories/{repository:public_id}/edit', [RepositoryController::class, 'edit'])->name('repositories.edit');
    Route::patch('repositories/{repository:public_id}', [RepositoryController::class, 'update'])->name('repositories.update');
    Route::post('repositories/{repository:public_id}/archive', [RepositoryController::class, 'archive'])->name('repositories.archive');
    Route::post('repositories/{repository:public_id}/restore', [RepositoryController::class, 'restore'])->name('repositories.restore');
    Route::delete('repositories/{repository:public_id}', [RepositoryController::class, 'destroy'])->name('repositories.destroy');
    Route::get('repositories/{repository:public_id}/releases/create', [ReleaseController::class, 'create'])->name('repositories.releases.create');
    Route::post('repositories/{repository:public_id}/releases', [ReleaseController::class, 'store'])->name('repositories.releases.store');
    Route::get('releases/{release:public_id}/edit', [ReleaseController::class, 'edit'])->name('releases.edit');
    Route::patch('releases/{release:public_id}', [ReleaseController::class, 'update'])->name('releases.update');
    Route::delete('releases/{release:public_id}', [ReleaseController::class, 'destroy'])->name('releases.destroy');
});

Route::get('/@{user:handle}', [PublicProfileController::class, 'show'])->name('profiles.show');
Route::get('/@{user:handle}/{repository:slug}', [PublicRepositoryController::class, 'show'])
    ->scopeBindings()
    ->name('public.repositories.show');

require __DIR__.'/settings.php';
