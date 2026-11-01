<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PayrollAdjustment;
use App\Models\Payslip;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayslipController extends Controller
{
    public function show(Request $request, Payslip $payslip): Response
    {
        $user = $request->user();
        $isOwner = $payslip->employee_id === $user->employee?->id;

        abort_unless($user->can('manage-payroll') || ($isOwner && $payslip->payrollPeriod->isFinalized()), 403);

        $payslip->load([
            'payrollPeriod',
            'employee:id,employee_number,first_name,last_name,middle_name,suffix,job_title,department_id,branch_id,pay_type,rate',
            'employee.department:id,name',
            'employee.branch:id,name',
        ]);

        return Inertia::render('payroll/payslip', [
            'payslip' => $payslip,
            'adjustments' => PayrollAdjustment::query()
                ->where('payroll_period_id', $payslip->payroll_period_id)
                ->where('employee_id', $payslip->employee_id)
                ->get(['id', 'category', 'description', 'amount']),
            'company' => config('app.name'),
        ]);
    }
}
