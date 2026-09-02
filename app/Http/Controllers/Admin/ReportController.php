<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\Report;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    /**
     * Fetch Revenue-only reports with date filtering.
     * Query Params: ?period=today|this_week|this_month|this_year|custom&start_date=YYYY-MM-DD&end_date=YYYY-MM-DD
     */
    public function revenue(Request $request)
    {
        $query = Report::where('type', 'revenue');
        $this->applyDateFilter($query, $request);

        $reports = $query->latest()->get();
        $totalRevenue = round($reports->sum('amount'), 2);

        return response()->json([
            'status'        => 'success',
            'type'          => 'revenue',
            'period'        => $request->query('period', 'all'),
            'total_revenue' => $totalRevenue,
            'count'         => $reports->count(),
            'data'          => $reports,
        ], 200);
    }

    /**
     * Fetch Cost-only reports with date filtering.
     * Query Params: ?period=today|this_week|this_month|this_year|custom&start_date=YYYY-MM-DD&end_date=YYYY-MM-DD
     */
    public function cost(Request $request)
    {
        $query = Report::where('type', 'cost');
        $this->applyDateFilter($query, $request);

        $reports = $query->latest()->get();
        $totalCost = round($reports->sum('amount'), 2);

        return response()->json([
            'status'     => 'success',
            'type'       => 'cost',
            'period'     => $request->query('period', 'all'),
            'total_cost' => $totalCost,
            'count'      => $reports->count(),
            'data'       => $reports,
        ], 200);
    }

    /**
     * Fetch Net Summary (Revenue, Cost, Net Profit/Loss) with date filtering.
     */
    public function summary(Request $request)
    {
        $query = Report::query();
        $this->applyDateFilter($query, $request);

        $reports = $query->latest()->get();

        $totalRevenue = round($reports->where('type', 'revenue')->sum('amount'), 2);
        $totalCost    = round($reports->where('type', 'cost')->sum('amount'), 2);
        $netProfit    = round($totalRevenue - $totalCost, 2);

        return response()->json([
            'status'        => 'success',
            'period'        => $request->query('period', 'all'),
            'total_revenue' => $totalRevenue,
            'total_cost'    => $totalCost,
            'net_profit'    => $netProfit,
            'count'         => $reports->count(),
            'data'          => $reports,
        ], 200);
    }

    /**
     * Helper method to parse and apply date ranges.
     */
    private function applyDateFilter($query, Request $request)
    {
        $period = $request->query('period');

        switch ($period) {
            case 'today':
                $query->whereDate('created_at', Carbon::today());
                break;

            case 'this_week':
                $query->whereBetween('created_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek(),
                ]);
                break;

            case 'this_month':
                $query->whereMonth('created_at', Carbon::now()->month)
                      ->whereYear('created_at', Carbon::now()->year);
                break;

            case 'this_year':
                $query->whereYear('created_at', Carbon::now()->year);
                break;

            case 'custom':
                $request->validate([
                    'start_date' => 'required|date_format:Y-m-d',
                    'end_date'   => 'required|date_format:Y-m-d|after_or_equal:start_date',
                ]);

                $startDate = Carbon::parse($request->query('start_date'))->startOfDay();
                $endDate   = Carbon::parse($request->query('end_date'))->endOfDay();

                $query->whereBetween('created_at', [$startDate, $endDate]);
                break;

            default:
                // If specific start_date/end_date passed without explicit period=custom
                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $startDate = Carbon::parse($request->query('start_date'))->startOfDay();
                    $endDate   = Carbon::parse($request->query('end_date'))->endOfDay();
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }
                break;
        }
    }
}