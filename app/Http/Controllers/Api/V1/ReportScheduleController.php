<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateReportScheduleRequest;
use App\Http\Resources\Api\V1\ReportScheduleResource;
use Illuminate\Http\Response;

class ReportScheduleController extends Controller
{
    public function store(CreateReportScheduleRequest $request)
    {
        $user = $request->user();

        $reportSchedule = $user->reportSchedules()->create(
            $request->validated()
        );

        return response()->json([
            'report_schedule' => ReportScheduleResource::make($reportSchedule),
        ], Response::HTTP_CREATED);
    }
}
