<?php

namespace App\Http\Controllers;

use App\Models\EppEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->input('date_from')
            ? Carbon::parse($request->input('date_from'))->startOfDay()
            : now()->subDays(7)->startOfDay();

        $dateTo = $request->input('date_to')
            ? Carbon::parse($request->input('date_to'))->endOfDay()
            : now()->endOfDay();

        $baseQuery = EppEvent::query()
            ->whereBetween('event_confirmed_at', [$dateFrom, $dateTo]);

        $totalEvents = (clone $baseQuery)->count();

        $startedViolations = (clone $baseQuery)
            ->where('event_type', 'violation_started')
            ->count();

        $resolvedViolations = (clone $baseQuery)
            ->where('event_type', 'violation_resolved')
            ->count();

        $openViolations = EppEvent::query()
            ->where('event_type', 'violation_started')
            ->whereNull('resolved_by_event_id')
            ->count();

        $latestEvents = EppEvent::query()
            ->with('evidence')
            ->whereBetween('event_confirmed_at', [$dateFrom, $dateTo])
            ->orderByDesc('event_confirmed_at')
            ->limit(10)
            ->get();

        return view('dashboard', [
            'dateFrom' => $dateFrom->format('Y-m-d'),
            'dateTo' => $dateTo->format('Y-m-d'),
            'totalEvents' => $totalEvents,
            'startedViolations' => $startedViolations,
            'resolvedViolations' => $resolvedViolations,
            'openViolations' => $openViolations,
            'latestEvents' => $latestEvents,
        ]);
    }
}