<?php

namespace App\Console\Commands;

use App\Enums\ReportPeriod;
use App\Enums\ReportRunStatus;
use App\Jobs\DispatchReport;
use App\Mail\PostsReport;
use App\Models\ReportSchedule;
use App\Repositories\Contracts\PostSearchRepositoryInterface;
use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class DispatchReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dispatch-reports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch periodic reports';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();

        $this->dispatchReports(
            ReportPeriod::DAILY,
            $now->startOfDay(),
            $now->endOfDay()
        );

        $this->dispatchReports(
            ReportPeriod::WEEKLY,
            $now->copy()->subWeek()->startOfDay(),
            $now->endOfDay()
        );
    }

    private function dispatchReports(ReportPeriod $period, Carbon $start, Carbon $end)
    {
        $this->output->info("Dispatching {$period->value} reports...");

        ReportSchedule::where('period', $period->value)->cursor()->each(function ($schedule) use ($start, $end) {
            try {
                $run = $schedule->reportRuns()->firstOrCreate(
                    ['period_start' => $start, 'period_end' => $end],
                    ['status' => ReportRunStatus::PROCESSING],
                );
            } catch (QueryException $e) {
                return;
            }

            DispatchReport::dispatchUnless($run->status !== ReportRunStatus::PROCESSING, $run->id);
        });
    }
}
