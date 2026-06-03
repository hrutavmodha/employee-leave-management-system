<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    /**
     * Display a listing of leave types.
     */
    public function index()
    {
        $leaveTypes = \Illuminate\Support\Facades\Cache::remember('leave_types.all', 3600, function () {
            return LeaveType::all();
        });
        return view('leave_types.index', compact('leaveTypes'));
    }

    /**
     * Show the form for creating a new leave type.
     */
    public function create()
    {
        return view('leave_types.create');
    }

    /**
     * Store a newly created leave type in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:leave_types',
            'allowed_days' => 'required|integer|min:0',
            'carry_forward' => 'boolean',
            'description' => 'nullable|string',
        ]);

        LeaveType::create([
            'name' => $request->name,
            'allowed_days' => $request->allowed_days,
            'carry_forward' => $request->has('carry_forward'),
            'description' => $request->description,
        ]);

        return redirect()->route('leave-types.index')->with('success', 'Leave type created successfully.');
    }

    /**
     * Show the form for editing the specified leave type.
     */
    public function edit(LeaveType $leaveType)
    {
        return view('leave_types.edit', compact('leaveType'));
    }

    /**
     * Update the specified leave type in storage.
     */
    public function update(Request $request, LeaveType $leaveType)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:leave_types,name,' . $leaveType->id,
            'allowed_days' => 'required|integer|min:0',
            'carry_forward' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $leaveType->update([
            'name' => $request->name,
            'allowed_days' => $request->allowed_days,
            'carry_forward' => $request->has('carry_forward'),
            'description' => $request->description,
        ]);

        return redirect()->route('leave-types.index')->with('success', 'Leave type updated successfully.');
    }

    /**
     * Remove the specified leave type from storage.
     */
    public function destroy(LeaveType $leaveType)
    {
        $leaveType->delete();
        return redirect()->route('leave-types.index')->with('success', 'Leave type deleted successfully.');
    }
}
