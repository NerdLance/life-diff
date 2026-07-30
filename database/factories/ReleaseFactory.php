<?php

namespace Database\Factories;

use App\Enums\ReleaseState;
use App\Enums\ReleaseType;
use App\Enums\RepositoryVisibility;
use App\Models\Release;
use App\Models\Repository;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Release>
 */
class ReleaseFactory extends Factory
{
    protected $model = Release::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'repository_id' => Repository::factory(),
            'version' => sprintf(
                '%d.%d.%d',
                fake()->numberBetween(0, 999),
                fake()->numberBetween(0, 999),
                fake()->unique()->numberBetween(0, 9999),
            ),
            'release_type' => ReleaseType::Patch,
            'state' => ReleaseState::Draft,
            'title' => fake()->sentence(4),
            'body' => fake()->optional()->paragraph(),
            'visibility' => RepositoryVisibility::Private,
            'published_at' => null,
            'edited_at' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => ReleaseState::Draft,
            'published_at' => null,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => ReleaseState::Published,
            'published_at' => now(),
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => RepositoryVisibility::Private,
        ]);
    }

    public function public(): static
    {
        return $this
            ->published()
            ->for(Repository::factory()->public())
            ->state(fn (array $attributes) => [
                'visibility' => RepositoryVisibility::Public,
            ]);
    }

    public function unlisted(): static
    {
        return $this
            ->published()
            ->for(Repository::factory()->unlisted())
            ->state(fn (array $attributes) => [
                'visibility' => RepositoryVisibility::Unlisted,
            ]);
    }
}
