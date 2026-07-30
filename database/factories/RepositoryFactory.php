<?php

namespace Database\Factories;

use App\Enums\ProfileStatus;
use App\Enums\RepositoryVisibility;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Repository>
 */
class RepositoryFactory extends Factory
{
    protected $model = Repository::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->bothify('Repository ###');

        return [
            'owner_id' => User::factory(),
            'name' => $name,
            'normalized_name' => Str::lower($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'description' => fake()->sentence(),
            'visibility' => RepositoryVisibility::Private,
            'status' => ProfileStatus::Stable,
            'archived_at' => null,
        ];
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => RepositoryVisibility::Private,
        ]);
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => RepositoryVisibility::Public,
        ]);
    }

    public function unlisted(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => RepositoryVisibility::Unlisted,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
        ]);
    }
}
