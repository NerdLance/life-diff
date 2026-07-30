<?php

namespace App\Http\Controllers;

use App\Models\Release;
use App\Models\Repository;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function show(): Response
    {
        $user = request()->user();

        return Inertia::render('dashboard', [
            'repositories' => $user->ownedRepositories()->active()->latest()->get()->map($this->repositoryItem(...))->values(),
            'drafts' => Release::query()->drafts()->whereHas('repository', fn (Builder $query) => $query->where('owner_id', $user->id))->with('repository:id,public_id,name')->latest('updated_at')->get()->map($this->releaseItem(...))->values(),
            'recentPublishedReleases' => Release::query()->published()->whereHas('repository', fn (Builder $query) => $query->where('owner_id', $user->id))->with('repository:id,public_id,name')->chronological()->limit(6)->get()->map($this->releaseItem(...))->values(),
        ]);
    }

    /** @return array{public_id: string, name: string, status: string, visibility: string} */
    private function repositoryItem(Repository $repository): array
    {
        return [
            'public_id' => $repository->public_id,
            'name' => $repository->name,
            'status' => $repository->status->value,
            'visibility' => $repository->visibility->value,
        ];
    }

    /** @return array{public_id: string, version: string, title: string, release_type: string, published_at: string|null, updated_at: string, repository: array{public_id: string, name: string}} */
    private function releaseItem(Release $release): array
    {
        return [
            'public_id' => $release->public_id,
            'version' => $release->version,
            'title' => $release->title,
            'release_type' => $release->release_type->value,
            'published_at' => $release->published_at?->toDateString(),
            'updated_at' => $release->updated_at?->toDateString(),
            'repository' => [
                'public_id' => $release->repository->public_id,
                'name' => $release->repository->name,
            ],
        ];
    }
}
