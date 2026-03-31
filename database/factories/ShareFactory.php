<?php

namespace Database\Factories;

use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShareFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shared_by' => User::factory(),
            'shared_with' => User::factory(),
            'shareable_type' => Note::class,
            'shareable_id' => Note::factory(),
            'status' => 'pending',
            'message' => fake()->optional()->sentence(),
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'accepted',
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'rejected',
        ]);
    }
}
