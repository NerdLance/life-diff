<?php

namespace App\Http\Controllers;

use App\Models\Release;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PublicReleaseController extends Controller
{
    public function show(Release $release): Response
    {
        Gate::authorize('view', $release);

        $repository = $release->repository;
        $owner = $repository->owner;

        return Inertia::render('releases/public-show', [
            'profile' => [
                'display_name' => $owner->display_name ?? $owner->name,
                'handle' => $owner->handle,
            ],
            'repository' => [
                'name' => $repository->name,
                'slug' => $repository->slug,
            ],
            'release' => $this->releaseDetail($release),
            'copyLink' => route('public.releases.show', $release),
        ]);
    }

    /** @return array{public_id: string, version: string, release_type: string, title: string, body: string|null, published_at: string, edited_at: string|null, change_entries: list<array{change_type: string, content: string}>} */
    private function releaseDetail(Release $release): array
    {
        $changeEntries = [];

        foreach ($release->changeEntries()->get() as $changeEntry) {
            $changeEntries[] = [
                'change_type' => $changeEntry->change_type->value,
                'content' => $changeEntry->content,
            ];
        }

        return [
            'public_id' => $release->public_id,
            'version' => $release->version,
            'release_type' => $release->release_type->value,
            'title' => $release->title,
            'body' => $release->body,
            'published_at' => $release->published_at->toIso8601String(),
            'edited_at' => $release->edited_at?->toIso8601String(),
            'change_entries' => $changeEntries,
        ];
    }
}
