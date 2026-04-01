<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FileFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->lexify('????????').'.bin';

        return [
            'user_id' => User::factory(),
            'original_name' => $name,
            'stored_name' => $name,
            'path' => 'files/'.$name,
            'size' => fake()->numberBetween(1024, 10 * 1024 * 1024),
            'mime_type' => fake()->mimeType(),
        ];
    }
}
