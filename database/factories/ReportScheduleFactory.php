<?php

namespace Database\Factories;

use App\Enums\ReportPeriod;
use App\Models\ReportSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportSchedule>
 */
class ReportScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'period' => fake()->randomElement(ReportPeriod::cases()),
            'keywords' => fake()->words(3),
            'user_id' => User::factory(),
        ];
    }
}
