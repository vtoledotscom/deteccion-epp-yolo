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
            ->whereBetween('event_confirmed_at', [$dateFrom, $dateTo])
            ->where('event_type', 'violation_started');

        $totalEvents = (clone $baseQuery)->count();

        $nonCompliantEvents = (clone $baseQuery)
            ->where('status', 'non_compliant')
            ->count();

        $humanPendingEvents = (clone $baseQuery)
            ->where('status', 'non_compliant')
            ->where('human_review_status', 'pending')
            ->count();

        $humanResolvedEvents = (clone $baseQuery)
            ->where('status', 'non_compliant')
            ->where('human_review_status', 'resolved')
            ->count();

        $latestEvents = EppEvent::query()
            ->with('evidence')
            ->whereBetween('event_confirmed_at', [$dateFrom, $dateTo])
            ->where('event_type', 'violation_started')
            ->orderByDesc('event_confirmed_at')
            ->limit(10)
            ->get();

        return view('dashboard', [
            'dateFrom' => $dateFrom->format('Y-m-d'),
            'dateTo' => $dateTo->format('Y-m-d'),
            'totalEvents' => $totalEvents,
            'nonCompliantEvents' => $nonCompliantEvents,
            'humanPendingEvents' => $humanPendingEvents,
            'humanResolvedEvents' => $humanResolvedEvents,
            'latestEvents' => $latestEvents,
        ]);
    }
}
