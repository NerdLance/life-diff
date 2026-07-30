<?php

namespace App\Http\Controllers;

use App\Actions\Repositories\ArchiveRepository;
use App\Actions\Repositories\CreateRepository;
use App\Actions\Repositories\DeleteRepository;
use App\Actions\Repositories\RestoreRepository;
use App\Actions\Repositories\UpdateRepository;
use App\Http\Requests\Repositories\ArchiveRepositoryRequest;
use App\Http\Requests\Repositories\DeleteRepositoryRequest;
use App\Http\Requests\Repositories\RestoreRepositoryRequest;
use App\Http\Requests\Repositories\StoreRepositoryRequest;
use App\Http\Requests\Repositories\UpdateRepositoryRequest;
use App\Models\Release;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RepositoryController extends Controller
{
    public function index(): Response
    {
        $repositories = $this->repositoryQuery()->get();

        return Inertia::render('repositories/index', [
            'activeRepositories' => $repositories->filter->isActive()->map($this->repositoryListItem(...))->values(),
            'archivedRepositories' => $repositories->filter->isArchived()->map($this->repositoryListItem(...))->values(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Repository::class);

        return Inertia::render('repositories/create', [
            'repository' => [
                'name' => '',
                'slug' => '',
                'description' => '',
                'status' => 'stable',
                'visibility' => 'private',
            ],
        ]);
    }

    public function store(StoreRepositoryRequest $request, CreateRepository $createRepository): RedirectResponse
    {
        $repository = $createRepository($request->user(), $request->repositoryAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Repository created.')]);

        return to_route('repositories.show', $repository);
    }

    public function show(Repository $repository): Response
    {
        Gate::authorize('view', $repository);

        Gate::authorize('viewOwner', $repository);

        return Inertia::render('repositories/show', [
            'repository' => $this->repositoryDetail($repository),
            'drafts' => $repository->releases()->drafts()->latest('updated_at')->get()->map($this->releaseItem(...))->values(),
            'publishedReleases' => $repository->releases()->published()->chronological()->limit(20)->get()->map($this->releaseItem(...))->values(),
            'actions' => [
                'canUpdate' => Gate::allows('update', $repository),
                'canArchive' => Gate::allows('archive', $repository),
                'canRestore' => Gate::allows('restore', $repository),
                'canDelete' => Gate::allows('delete', $repository),
                'canCreateRelease' => Gate::allows('create', [Release::class, $repository]),
            ],
        ]);
    }

    public function edit(Repository $repository): Response
    {
        Gate::authorize('update', $repository);

        return Inertia::render('repositories/edit', [
            'repository' => $this->repositoryDetail($repository),
        ]);
    }

    public function update(UpdateRepositoryRequest $request, Repository $repository, UpdateRepository $updateRepository): RedirectResponse
    {
        $updateRepository($repository, $request->repositoryAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Repository updated.')]);

        return to_route('repositories.show', $repository);
    }

    public function archive(ArchiveRepositoryRequest $request, Repository $repository, ArchiveRepository $archiveRepository): RedirectResponse
    {
        $archiveRepository($repository);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Repository archived.')]);

        return to_route('repositories.show', $repository);
    }

    public function restore(RestoreRepositoryRequest $request, Repository $repository, RestoreRepository $restoreRepository): RedirectResponse
    {
        $restoreRepository($repository);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Repository restored.')]);

        return to_route('repositories.show', $repository);
    }

    public function destroy(DeleteRepositoryRequest $request, Repository $repository, DeleteRepository $deleteRepository): RedirectResponse
    {
        $deleteRepository($repository);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Repository deleted.')]);

        return to_route('repositories.index');
    }

    /** @return HasMany<Repository, User> */
    private function repositoryQuery(): HasMany
    {
        return request()->user()->ownedRepositories()
            ->withCount('releases')
            ->withMax(['releases as last_release_at' => fn ($query) => $query->published()], 'published_at')
            ->addSelect(['latest_version' => Release::query()
                ->select('version')
                ->whereColumn('repository_id', 'repositories.id')
                ->published()
                ->chronological()
                ->limit(1)])
            ->latest();
    }

    /** @return array{public_id: string, name: string, slug: string, status: string, visibility: string, archived_at: string|null, release_count: int, last_release_at: mixed, latest_version: mixed} */
    private function repositoryListItem(Repository $repository): array
    {
        return [
            'public_id' => $repository->public_id,
            'name' => $repository->name,
            'slug' => $repository->slug,
            'status' => $repository->status->value,
            'visibility' => $repository->visibility->value,
            'archived_at' => $repository->archived_at?->toDateString(),
            'release_count' => (int) $repository->getAttribute('releases_count'),
            'last_release_at' => $repository->getAttribute('last_release_at'),
            'latest_version' => $repository->getAttribute('latest_version'),
        ];
    }

    /** @return array{public_id: string, name: string, slug: string, description: string|null, status: string, visibility: string, archived_at: string|null} */
    private function repositoryDetail(Repository $repository): array
    {
        return [
            'public_id' => $repository->public_id,
            'name' => $repository->name,
            'slug' => $repository->slug,
            'description' => $repository->description,
            'status' => $repository->status->value,
            'visibility' => $repository->visibility->value,
            'archived_at' => $repository->archived_at?->toDateString(),
        ];
    }

    /** @return array{public_id: string, version: string, title: string, release_type: string, visibility: string, published_at: string|null, updated_at: string} */
    private function releaseItem(Release $release): array
    {
        return [
            'public_id' => $release->public_id,
            'version' => $release->version,
            'title' => $release->title,
            'release_type' => $release->release_type->value,
            'visibility' => $release->visibility->value,
            'published_at' => $release->published_at?->toDateString(),
            'updated_at' => $release->updated_at?->toDateString(),
        ];
    }
}
