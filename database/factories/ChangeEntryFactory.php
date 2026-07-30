<?php

namespace Database\Factories;

use App\Enums\ChangeType;
use App\Models\ChangeEntry;
use App\Models\Release;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChangeEntry>
 */
class ChangeEntryFactory extends Factory
{
    protected $model = ChangeEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'release_id' => Release::factory(),
            'change_type' => ChangeType::Added,
            'content' => fake()->sentence(),
            'sort_order' => 0,
        ];
    }
}
