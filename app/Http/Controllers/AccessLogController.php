<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\AccessLog;
use Illuminate\Http\Request;
use App\Http\Resources\AccessLogResource;

class AccessLogController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to'   => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $logs = AccessLog::with(['users', 'zone'])
            ->whereBetween('created_at', [
                Carbon::parse($data['from'])->startOfDay(),
                Carbon::parse($data['to'])->endOfDay(),
            ])
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['logs' => AccessLogResource::collection($logs)]);
    }
}
