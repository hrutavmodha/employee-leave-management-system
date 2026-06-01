<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Display the reports dashboard.
     */
    public function index()
    {
        $employeeStats = $this->reportService->getEmployeeReport();
        $departmentStats = $this->reportService->getDepartmentReport();
        $monthlyStats = $this->reportService->getMonthlyStats();

        return view('reports.index', compact('employeeStats', 'departmentStats', 'monthlyStats'));
    }
}
