export type UserRole =
    | 'admin'
    | 'hr_timekeeper'
    | 'hr_payroll'
    | 'finance'
    | 'employee';

export type User = {
    id: number;
    name: string;
    email: string;
    role: UserRole;
};

export type Auth = {
    user: User | null;
    employee: {
        id: number;
        employee_number: string;
        full_name: string;
    } | null;
    can: {
        manageUsers: boolean;
        manageEmployees: boolean;
        manageTimekeeping: boolean;
        managePayroll: boolean;
        endorseFilings: boolean;
    };
};
