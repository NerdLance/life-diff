<?php

namespace App\Policies;

use App\Domain\Repositories\VisibilityCeiling;
use App\Enums\ReleaseState;
use App\Enums\RepositoryVisibility;
use App\Models\Release;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReleasePolicy
{
    public function view(?User $viewer, Release $release): Response|bool
    {
        $repository = $release->repository;

        if ($release->trashed() || $repository === null || $repository->trashed()) {
            return Response::denyAsNotFound();
        }

        if ($viewer !== null && $repository->owner->is($viewer)) {
            return true;
        }

        if (! $release->isPublished()
            || $repository->visibility === RepositoryVisibility::Private
            || $release->visibility === RepositoryVisibility::Private
            || ! VisibilityCeiling::allows($repository->visibility, $release->visibility)) {
            return Response::denyAsNotFound();
        }

        return true;
    }

    public function create(User $user, Repository $repository): bool
    {
        return ! $repository->trashed()
            && $repository->isActive()
            && $repository->owner->is($user);
    }

    public function update(User $user, Release $release): bool
    {
        return $this->ownsWritableRelease($user, $release);
    }

    public function publish(User $user, Release $release): bool
    {
        return $this->ownsWritableRelease($user, $release)
            && $release->state === ReleaseState::Draft
            && VisibilityCeiling::allows($release->repository->visibility, $release->visibility);
    }

    public function delete(User $user, Release $release): bool
    {
        return $this->ownsWritableRelease($user, $release);
    }

    private function ownsWritableRelease(User $user, Release $release): bool
    {
        $repository = $release->repository;

        return ! $release->trashed()
            && $repository !== null
            && ! $repository->trashed()
            && $repository->isActive()
            && $repository->owner->is($user);
    }
}
