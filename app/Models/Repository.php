<?php

namespace App\Models;

use App\Enums\ProfileStatus;
use App\Enums\RepositoryVisibility;
use Database\Factories\RepositoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property string $public_id
 * @property int $owner_id
 * @property string $name
 * @property string $normalized_name
 * @property string $slug
 * @property string|null $description
 * @property RepositoryVisibility $visibility
 * @property ProfileStatus $status
 * @property Carbon|null $archived_at
 * @property Carbon|null $deleted_at
 */
class Repository extends Model
{
    /** @use HasFactory<RepositoryFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'normalized_name',
        'slug',
        'description',
        'visibility',
        'status',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $repository): void {
            if ($repository->isDirty('public_id')) {
                throw new LogicException('Repository public IDs are immutable.');
            }
        });
    }

    /**
     * @return array<string, class-string|string>
     */
    protected function casts(): array
    {
        return [
            'visibility' => RepositoryVisibility::class,
            'status' => ProfileStatus::class,
            'archived_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<Release, $this>
     */
    public function releases(): HasMany
    {
        return $this->hasMany(Release::class);
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function isActive(): bool
    {
        return ! $this->isArchived();
    }

    /**
     * @param  Builder<Repository>  $query
     * @return Builder<Repository>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    /**
     * @param  Builder<Repository>  $query
     * @return Builder<Repository>
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('owner_id', $user->getKey());
    }

    /**
     * @param  Builder<Repository>  $query
     * @return Builder<Repository>
     */
    public function scopePubliclyListed(Builder $query): Builder
    {
        return $query
            ->active()
            ->where('visibility', RepositoryVisibility::Public);
    }

    /**
     * This narrows collection queries only. Policies still authorize individual resources.
     *
     * @param  Builder<Repository>  $query
     * @return Builder<Repository>
     */
    public function scopeVisibleTo(Builder $query, ?User $viewer): Builder
    {
        return $query->where(function (Builder $query) use ($viewer): void {
            if ($viewer !== null) {
                $query->where('owner_id', $viewer->getKey())->orWhereIn('visibility', [
                    RepositoryVisibility::Unlisted,
                    RepositoryVisibility::Public,
                ]);

                return;
            }

            $query->whereIn('visibility', [
                RepositoryVisibility::Unlisted,
                RepositoryVisibility::Public,
            ]);
        });
    }
}
