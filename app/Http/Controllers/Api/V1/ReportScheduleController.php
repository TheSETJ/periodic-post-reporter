<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateReportScheduleRequest;
use App\Http\Resources\Api\V1\ReportScheduleResource;
use Illuminate\Http\Request;
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

    public function index(Request $request)
    {
        $user = $request->user();
        $limit = $request->integer('limit', 10);
        $period = $request->string('period');

        $paginated = $user->reportSchedules()
            ->when($request->has('period'), fn ($query) => $query->where('period', $period))
            ->latest()
            ->latest('id')
            ->paginate($limit);

        return response()->json([
            'report_schedules' => ReportScheduleResource::collection($paginated),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }
}
