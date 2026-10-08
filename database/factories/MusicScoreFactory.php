<?php

namespace Database\Factories;

use App\Models\MusicScore;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MusicScore> */
final class MusicScoreFactory extends Factory
{
    protected $model = MusicScore::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'content' => ['sections' => []],
        ];
    }
}
