<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\StaffAttendance;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    /**
     * Fetch attendance records with date-range filtering for UI views.
     */
    public function index(Request $request)
    {
        $request->validate([
            'staff_id'   => 'nullable|exists:staff,id',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'filter'     => 'nullable|string|in:last_month,this_month,this_week',
        ]);

        $query = StaffAttendance::with('staff');

        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        if ($request->filter === 'last_month') {
            $query->whereBetween('date', [
                now()->subMonth()->startOfMonth()->toDateString(),
                now()->subMonth()->endOfMonth()->toDateString(),
            ]);
        } elseif ($request->filter === 'this_month') {
            $query->whereBetween('date', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ]);
        } elseif ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $attendances = $query->orderBy('date', 'desc')->get();

        return response()->json([
            'data' => $attendances,
        ], 200);
    }

    /**
     * Bulk store or update daily attendance entries submitted from the UI.
     */
    public function storeDaily(Request $request)
    {
        $request->validate([
            'date'                   => 'required|date_format:Y-m-d',
            'attendances'            => 'required|array|min:1',
            'attendances.*.staff_id' => 'required|exists:staff,id',
            'attendances.*.status'   => ['required', Rule::in(['present', 'absent', 'late', 'half_day', 'on_leave'])],
            'attendances.*.check_in' => 'nullable|string',
            'attendances.*.check_out' => 'nullable|string',
            'attendances.*.notes'    => 'nullable|string|max:500',
        ]);

        $recorded = [];

        foreach ($request->attendances as $record) {
            $attendance = StaffAttendance::updateOrCreate(
                [
                    'staff_id' => $record['staff_id'],
                    'date'     => $request->date,
                ],
                [
                    'status'    => $record['status'],
                    'check_in'  => $record['check_in'] ?? null,
                    'check_out' => $record['check_out'] ?? null,
                    'notes'     => $record['notes'] ?? null,
                ]
            );

            $recorded[] = $attendance;
        }

        return response()->json([
            'message' => 'Daily attendance recorded successfully',
            'data'    => $recorded,
        ], 200);
    }

    /**
     * Update a single attendance log entry.
     */
    public function update(Request $request, StaffAttendance $attendance)
    {
        $fields = $request->validate([
            'status'    => ['sometimes', 'required', Rule::in(['present', 'absent', 'late', 'half_day', 'on_leave'])],
            'check_in'  => 'nullable|string',
            'check_out' => 'nullable|string',
            'notes'     => 'nullable|string|max:500',
        ]);

        $attendance->update($fields);

        return response()->json([
            'message' => 'Attendance entry updated successfully',
            'data'    => $attendance,
        ], 200);
    }
}