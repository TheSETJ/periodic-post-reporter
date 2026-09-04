<?php

namespace Database\Factories;

use App\Enums\ReportPeriod;
use App\Enums\ReportRunStatus;
use App\Models\ReportRun;
use App\Models\ReportSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportRun>
 */
class ReportRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'report_schedule_id' => ReportSchedule::factory(),
            'status' => ReportRunStatus::PROCESSING,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ReportRun $run) {
            $schedule = $run->reportSchedule;

            [$run->period_start, $run->period_end] = match ($schedule->period) {
                ReportPeriod::DAILY => [now()->startOfDay(), now()->endOfDay()],
                ReportPeriod::WEEKLY => [now()->startOfWeek(), now()->endOfWeek()],
            };
        });
    }

    public function send(): static
    {
        return $this->state(['status' => ReportRunStatus::SENT]);
    }

    public function failed(): static
    {
        return $this->state(['status' => ReportRunStatus::FAILED]);
    }
}
