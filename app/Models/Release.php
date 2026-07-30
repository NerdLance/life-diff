<?php

namespace App\Models;

use App\Enums\ReleaseState;
use App\Enums\ReleaseType;
use App\Enums\RepositoryVisibility;
use Carbon\CarbonImmutable;
use Database\Factories\ReleaseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

/**
 * @property int $id
 * @property string $public_id
 * @property int $repository_id
 * @property string $version
 * @property ReleaseType $release_type
 * @property ReleaseState $state
 * @property string $title
 * @property string|null $body
 * @property RepositoryVisibility $visibility
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $edited_at
 * @property CarbonImmutable|null $deleted_at
 */
class Release extends Model
{
    /** @use HasFactory<ReleaseFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'version',
        'release_type',
        'state',
        'title',
        'body',
        'visibility',
        'published_at',
        'edited_at',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $release): void {
            if ($release->isDirty('public_id')) {
                throw new LogicException('Release public IDs are immutable.');
            }
        });
    }

    /**
     * @return array<string, class-string|string>
     */
    protected function casts(): array
    {
        return [
            'release_type' => ReleaseType::class,
            'state' => ReleaseState::class,
            'visibility' => RepositoryVisibility::class,
            'published_at' => 'datetime',
            'edited_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return BelongsTo<Repository, $this>
     */
    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    /**
     * @return HasMany<ChangeEntry, $this>
     */
    public function changeEntries(): HasMany
    {
        return $this->hasMany(ChangeEntry::class)->orderBy('sort_order');
    }

    public function isDraft(): bool
    {
        return $this->state === ReleaseState::Draft;
    }

    public function isPublished(): bool
    {
        return $this->state === ReleaseState::Published && $this->published_at !== null;
    }

    /**
     * @param  Builder<Release>  $query
     * @return Builder<Release>
     */
    public function scopeDrafts(Builder $query): Builder
    {
        return $query->where('state', ReleaseState::Draft);
    }

    /**
     * @param  Builder<Release>  $query
     * @return Builder<Release>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('state', ReleaseState::Published)
            ->whereNotNull('published_at');
    }

    /**
     * @param  Builder<Release>  $query
     * @return Builder<Release>
     */
    public function scopePubliclyListed(Builder $query): Builder
    {
        return $query
            ->published()
            ->where('visibility', RepositoryVisibility::Public)
            ->whereHas('repository', fn (Builder $query) => $query
                ->where('visibility', RepositoryVisibility::Public)
                ->whereNull('archived_at'));
    }

    /**
     * This narrows collection queries only. Policies still authorize individual resources.
     *
     * @param  Builder<Release>  $query
     * @return Builder<Release>
     */
    public function scopeVisibleTo(Builder $query, ?User $viewer): Builder
    {
        return $query->where(function (Builder $query) use ($viewer): void {
            if ($viewer !== null) {
                $query->whereHas('repository', fn (Builder $query) => $query->where('owner_id', $viewer->getKey()))
                    ->orWhere(function (Builder $query) use ($viewer): void {
                        $query->published()
                            ->whereIn('visibility', [
                                RepositoryVisibility::Unlisted,
                                RepositoryVisibility::Public,
                            ])
                            ->whereHas('repository', function (Builder $query) use ($viewer): void {
                                $query->where(function (Builder $query) use ($viewer): void {
                                    $query->where('owner_id', $viewer->getKey())->orWhereIn('visibility', [
                                        RepositoryVisibility::Unlisted,
                                        RepositoryVisibility::Public,
                                    ]);
                                });
                            });
                    });

                return;
            }

            $query->published()->where(function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('visibility', RepositoryVisibility::Unlisted)
                        ->whereHas('repository', fn (Builder $query) => $query->whereIn('visibility', [
                            RepositoryVisibility::Unlisted,
                            RepositoryVisibility::Public,
                        ]));
                })->orWhere(function (Builder $query): void {
                    $query->where('visibility', RepositoryVisibility::Public)
                        ->whereHas('repository', fn (Builder $query) => $query->where('visibility', RepositoryVisibility::Public));
                });
            });
        });
    }

    /**
     * @param  Builder<Release>  $query
     * @return Builder<Release>
     */
    public function scopeChronological(Builder $query): Builder
    {
        return $query
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }
}
