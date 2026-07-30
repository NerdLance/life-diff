<?php

namespace App\Policies;

use App\Enums\RepositoryVisibility;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RepositoryPolicy
{
    public function view(?User $viewer, Repository $repository): Response|bool
    {
        if ($repository->trashed()) {
            return Response::denyAsNotFound();
        }

        if ($viewer !== null && $repository->owner->is($viewer)) {
            return true;
        }

        return $repository->visibility === RepositoryVisibility::Private
            ? Response::denyAsNotFound()
            : true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function viewOwner(User $user, Repository $repository): Response|bool
    {
        return ! $repository->trashed() && $repository->owner->is($user)
            ? true
            : Response::denyAsNotFound();
    }

    public function update(User $user, Repository $repository): bool
    {
        return $this->ownsActiveRepository($user, $repository);
    }

    public function archive(User $user, Repository $repository): bool
    {
        return $this->ownsActiveRepository($user, $repository);
    }

    public function restore(User $user, Repository $repository): bool
    {
        return ! $repository->trashed()
            && $repository->isArchived()
            && $repository->owner->is($user);
    }

    public function delete(User $user, Repository $repository): bool
    {
        return ! $repository->trashed() && $repository->owner->is($user);
    }

    private function ownsActiveRepository(User $user, Repository $repository): bool
    {
        return ! $repository->trashed()
            && $repository->isActive()
            && $repository->owner->is($user);
    }
}
