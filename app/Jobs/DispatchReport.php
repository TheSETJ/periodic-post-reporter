<?php

namespace App\Jobs;

use App\Enums\ReportRunStatus;
use App\Mail\PostsReport;
use App\Models\ReportRun;
use App\Repositories\Contracts\PostSearchRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DispatchReport implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private int $runId,
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(
        PostSearchRepositoryInterface $posts
    ): void {
        DB::transaction(function () use ($posts) {
            $run = ReportRun::lockForUpdate()->find($this->runId);

            if (!$run || $run->status !== ReportRunStatus::PROCESSING) {
                return;
            }

            try {
                $schedule = $run->reportSchedule;

                $histogram = $posts->countByDay($schedule->keywords, $run->period_start, $run->period_end);

                Mail::to($schedule->user)->send(
                    new PostsReport($schedule->title, $run->period_start, $run->period_end, $histogram)
                );

                $run->update(['status' => ReportRunStatus::SENT]);
            } catch (\Throwable $e) {
                Log::error('Report dispatch failed', ['run_id' => $run->id, 'error' => $e->getMessage()]);

                $run->update(['status' => ReportRunStatus::FAILED]);
            }
        });
    }
}
