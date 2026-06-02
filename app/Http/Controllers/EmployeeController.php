<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use App\Services\LeaveCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class EmployeeController extends Controller
{
    protected $calculationService;

    public function __construct(LeaveCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    /**
     * Display a listing of employees.
     */
    public function index()
    {
        $employees = User::with(['department', 'manager'])->get();
        return view('employees.index', compact('employees'));
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create()
    {
        $departments = Department::all();
        $managers = User::whereIn('role', ['Manager', 'HR/Admin'])->get();
        return view('employees.create', compact('departments', 'managers'));
    }

    /**
     * Store a newly created employee in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', 'in:Employee,Manager,HR/Admin'],
            'department_id' => ['required', 'exists:departments,id'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'designation' => ['required', 'string', 'max:255'],
            'joining_date' => ['required', 'date'],
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'department_id' => $request->department_id,
            'manager_id' => $request->manager_id,
            'designation' => $request->designation,
            'joining_date' => $request->joining_date,
            'status' => 'Active',
        ]);

        // Automatically initialize leave balances for the new employee
        $this->calculationService->initializeBalances($user);

        return redirect()->route('employees.index')->with('success', 'Employee created and leave balances initialized.');
    }

    /**
     * Remove the specified employee from storage.
     */
    public function destroy(User $employee)
    {
        // Prevent deleting themselves through this route
        if ($employee->id === auth()->id()) {
            return redirect()->route('employees.index')->with('error', 'You cannot delete yourself through Employee Management.');
        }

        // Only allow deleting lower-level employees (HR/Admin cannot be deleted by other admins unless needed, or just let them delete anyone else)
        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }
}
