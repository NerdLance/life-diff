<?php

namespace App\Models;

use App\Enums\ChangeType;
use Database\Factories\ChangeEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $release_id
 * @property ChangeType $change_type
 * @property string $content
 * @property int $sort_order
 */
class ChangeEntry extends Model
{
    /** @use HasFactory<ChangeEntryFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'change_type',
        'content',
        'sort_order',
    ];

    /**
     * @return array<string, class-string|string>
     */
    protected function casts(): array
    {
        return [
            'change_type' => ChangeType::class,
        ];
    }

    /**
     * @return BelongsTo<Release, $this>
     */
    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class);
    }
}
