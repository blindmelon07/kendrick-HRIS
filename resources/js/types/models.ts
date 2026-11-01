export type Option = { value: string; label: string };

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

export type NamedRecord = { id: number; name: string };

export type EmployeeSummary = {
    id: number;
    employee_number: string;
    first_name: string;
    last_name: string;
    middle_name: string | null;
    suffix: string | null;
    full_name: string;
    photo_url?: string | null;
};

export type Employee = EmployeeSummary & {
    user_id: number | null;
    birth_date: string | null;
    gender: string | null;
    civil_status: string | null;
    mobile_number: string | null;
    email: string | null;
    address: string | null;
    branch_id: number;
    department_id: number;
    shift_id: number;
    level: string;
    job_title: string;
    employee_status: string;
    employment_status: string;
    date_hired: string;
    date_separated: string | null;
    pay_type: string;
    rate: string;
    payment_method: string;
    bank_account_number: string | null;
    sss_number: string | null;
    philhealth_number: string | null;
    pagibig_number: string | null;
    tin: string | null;
    has_statutory_deductions: boolean;
    custom_contributions: boolean;
    sss_contribution: string;
    philhealth_contribution: string;
    pagibig_contribution: string;
    emergency_contact_name: string | null;
    emergency_contact_relationship: string | null;
    emergency_contact_number: string | null;
    department?: NamedRecord;
    branch?: NamedRecord;
    shift?: Shift;
    user?: { id: number; email: string; role: string } | null;
};

export type Shift = {
    id: number;
    name: string;
    start_time: string;
    end_time: string;
    break_minutes: number;
    work_days: number[];
    employees_count?: number;
};

export type Filing = {
    id: number;
    employee_id: number;
    type: string;
    date_from: string;
    date_to: string;
    time_from: string | null;
    time_to: string | null;
    is_half_day: boolean;
    with_pay: boolean;
    shift_id: number | null;
    reason: string;
    status: string;
    endorsed_at: string | null;
    reviewed_at: string | null;
    remarks: string | null;
    created_at: string;
    employee?: EmployeeSummary & { department?: NamedRecord };
    shift?: NamedRecord | null;
    endorser?: NamedRecord | null;
    reviewer?: NamedRecord | null;
    ready_for_review?: boolean;
};

export type DailyRecord = {
    date: string;
    day: string;
    status:
        | 'present'
        | 'absent'
        | 'incomplete'
        | 'leave'
        | 'holiday'
        | 'rest_day'
        | 'not_employed';
    shift: string;
    is_work_day: boolean;
    time_in: string | null;
    time_out: string | null;
    late_minutes: number;
    undertime_minutes: number;
    overtime_hours: number;
    days_worked: number;
    paid_leave_days: number;
    unpaid_leave_days: number;
    holiday: string | null;
    holiday_type: string | null;
};

export type WorkSummary = {
    days_worked: number;
    paid_leave_days: number;
    unpaid_leave_days: number;
    days_absent: number;
    late_minutes: number;
    undertime_minutes: number;
    overtime_hours: number;
};

export type PayrollPeriod = {
    id: number;
    start_date: string;
    end_date: string;
    pay_date: string;
    half: 'first' | 'second';
    status: 'draft' | 'processed' | 'finalized';
    label: string;
    processed_at: string | null;
    finalized_at: string | null;
    payslips_count?: number;
    payslips_sum_net_pay?: string | null;
};

export type Payslip = {
    id: number;
    payroll_period_id: number;
    employee_id: number;
    payment_method: string;
    days_worked: string;
    days_absent: string;
    late_minutes: number;
    undertime_minutes: number;
    overtime_hours: string;
    basic_pay: string;
    holiday_pay: string;
    overtime_pay: string;
    per_biyahe_pay: string;
    other_earnings: string;
    gross_pay: string;
    absence_deduction: string;
    late_undertime_deduction: string;
    sss_contribution: string;
    philhealth_contribution: string;
    pagibig_contribution: string;
    loan_deductions: string;
    other_deductions: string;
    total_deductions: string;
    net_pay: string;
    loan_breakdown: { loan_id: number; type: string; amount: number }[] | null;
    employee?: EmployeeSummary & {
        bank_account_number?: string | null;
        job_title?: string;
        pay_type?: string;
        rate?: string;
        department?: NamedRecord;
        branch?: NamedRecord;
    };
    payroll_period?: PayrollPeriod;
};

export type PayrollAdjustment = {
    id: number;
    employee_id: number;
    category: string;
    description: string;
    amount: string;
    employee?: EmployeeSummary;
};

export type Loan = {
    id: number;
    employee_id: number;
    type: string;
    reference_number: string | null;
    principal: string;
    amortization: string;
    balance: string;
    start_date: string;
    employee?: EmployeeSummary;
};
