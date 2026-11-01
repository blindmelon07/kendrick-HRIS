/// Data returned by the TriStellar HRIS `/api/v1` endpoints.
library;

double _num(Object? value) => (value as num? ?? 0).toDouble();

class Option {
  const Option(this.value, this.label);

  factory Option.fromJson(Map<String, dynamic> json) =>
      Option('${json['value']}', '${json['label']}');

  final String value;
  final String label;
}

class Profile {
  Profile({
    required this.email,
    required this.employeeNumber,
    required this.fullName,
    required this.firstName,
    required this.jobTitle,
    required this.department,
    required this.branch,
    required this.shiftName,
    required this.shiftStart,
    required this.shiftEnd,
    required this.canEndorse,
    this.photoUrl,
  });

  factory Profile.fromJson(Map<String, dynamic> json) {
    final employee = json['employee'] as Map<String, dynamic>;
    final shift = employee['shift'] as Map<String, dynamic>;

    return Profile(
      email: json['email'] as String,
      employeeNumber: employee['employee_number'] as String,
      fullName: employee['full_name'] as String,
      firstName: employee['first_name'] as String,
      jobTitle: employee['job_title'] as String? ?? '',
      department: employee['department'] as String? ?? '',
      branch: employee['branch'] as String? ?? '',
      shiftName: shift['name'] as String,
      shiftStart: shift['start_time'] as String,
      shiftEnd: shift['end_time'] as String,
      photoUrl: json['photo_url'] as String?,
      canEndorse:
          (json['can'] as Map<String, dynamic>)['endorse_filings'] == true,
    );
  }

  final String email;
  final String employeeNumber;
  final String fullName;
  final String firstName;
  final String jobTitle;
  final String department;
  final String branch;
  final String shiftName;
  final String shiftStart;
  final String shiftEnd;
  final bool canEndorse;

  /// Authorized URL of the employee's photo (load with the API token), or null.
  final String? photoUrl;
}

class PayslipLine {
  const PayslipLine(this.label, this.amount);

  factory PayslipLine.fromJson(Map<String, dynamic> json) =>
      PayslipLine('${json['label']}', _num(json['amount']));

  final String label;
  final double amount;
}

class Payslip {
  Payslip({
    required this.id,
    required this.periodLabel,
    required this.payDate,
    required this.paymentMethod,
    required this.paymentMethodLabel,
    required this.grossPay,
    required this.totalDeductions,
    required this.netPay,
    this.daysWorked,
    this.daysAbsent,
    this.lateUndertimeMinutes,
    this.overtimeHours,
    this.earnings = const [],
    this.deductions = const [],
  });

  factory Payslip.fromJson(Map<String, dynamic> json) {
    final period = json['period'] as Map<String, dynamic>;
    final details = json['details'] as Map<String, dynamic>?;
    final attendance = details?['attendance'] as Map<String, dynamic>?;
    List<PayslipLine> lines(String key) =>
        ((details?[key] as List?) ?? const [])
            .map((line) => PayslipLine.fromJson(line as Map<String, dynamic>))
            .toList();

    return Payslip(
      id: json['id'] as int,
      periodLabel: period['label'] as String,
      payDate: period['pay_date'] as String,
      paymentMethod: json['payment_method'] as String,
      paymentMethodLabel: json['payment_method_label'] as String,
      grossPay: _num(json['gross_pay']),
      totalDeductions: _num(json['total_deductions']),
      netPay: _num(json['net_pay']),
      daysWorked: attendance == null ? null : _num(attendance['days_worked']),
      daysAbsent: attendance == null ? null : _num(attendance['days_absent']),
      lateUndertimeMinutes: attendance == null
          ? null
          : (attendance['late_undertime_minutes'] as num).toInt(),
      overtimeHours: attendance == null
          ? null
          : _num(attendance['overtime_hours']),
      earnings: lines('earnings'),
      deductions: lines('deductions'),
    );
  }

  final int id;
  final String periodLabel;
  final String payDate;
  final String paymentMethod;
  final String paymentMethodLabel;
  final double grossPay;
  final double totalDeductions;
  final double netPay;
  final double? daysWorked;
  final double? daysAbsent;
  final int? lateUndertimeMinutes;
  final double? overtimeHours;
  final List<PayslipLine> earnings;
  final List<PayslipLine> deductions;
}

class HomeSummary {
  HomeSummary({
    required this.todayLogs,
    required this.pendingFilings,
    this.endorsementsWaiting,
    this.latestPayslip,
  });

  factory HomeSummary.fromJson(Map<String, dynamic> json) => HomeSummary(
    todayLogs: ((json['today_logs'] as List?) ?? const [])
        .map((time) => '$time')
        .toList(),
    pendingFilings: (json['pending_filings'] as num).toInt(),
    endorsementsWaiting: (json['endorsements_waiting'] as num?)?.toInt(),
    latestPayslip: json['latest_payslip'] == null
        ? null
        : Payslip.fromJson(json['latest_payslip'] as Map<String, dynamic>),
  );

  final List<String> todayLogs;
  final int pendingFilings;
  final int? endorsementsWaiting;
  final Payslip? latestPayslip;
}

class DailyRecord {
  DailyRecord({
    required this.date,
    required this.day,
    required this.status,
    required this.isWorkDay,
    this.timeIn,
    this.timeOut,
    required this.lateMinutes,
    required this.undertimeMinutes,
    required this.overtimeHours,
    required this.daysWorked,
    required this.paidLeaveDays,
    this.holiday,
  });

  factory DailyRecord.fromJson(Map<String, dynamic> json) => DailyRecord(
    date: json['date'] as String,
    day: json['day'] as String,
    status: json['status'] as String,
    isWorkDay: json['is_work_day'] == true,
    timeIn: json['time_in'] as String?,
    timeOut: json['time_out'] as String?,
    lateMinutes: (json['late_minutes'] as num).toInt(),
    undertimeMinutes: (json['undertime_minutes'] as num).toInt(),
    overtimeHours: _num(json['overtime_hours']),
    daysWorked: _num(json['days_worked']),
    paidLeaveDays: _num(json['paid_leave_days']),
    holiday: json['holiday'] as String?,
  );

  final String date;
  final String day;
  final String status;
  final bool isWorkDay;
  final String? timeIn;
  final String? timeOut;
  final int lateMinutes;
  final int undertimeMinutes;
  final double overtimeHours;
  final double daysWorked;
  final double paidLeaveDays;
  final String? holiday;
}

class WorkSummary {
  WorkSummary({
    required this.daysWorked,
    required this.paidLeaveDays,
    required this.unpaidLeaveDays,
    required this.daysAbsent,
    required this.lateMinutes,
    required this.undertimeMinutes,
    required this.overtimeHours,
  });

  factory WorkSummary.fromJson(Map<String, dynamic> json) => WorkSummary(
    daysWorked: _num(json['days_worked']),
    paidLeaveDays: _num(json['paid_leave_days']),
    unpaidLeaveDays: _num(json['unpaid_leave_days']),
    daysAbsent: _num(json['days_absent']),
    lateMinutes: (json['late_minutes'] as num).toInt(),
    undertimeMinutes: (json['undertime_minutes'] as num).toInt(),
    overtimeHours: _num(json['overtime_hours']),
  );

  final double daysWorked;
  final double paidLeaveDays;
  final double unpaidLeaveDays;
  final double daysAbsent;
  final int lateMinutes;
  final int undertimeMinutes;
  final double overtimeHours;
}

class Filing {
  Filing({
    required this.id,
    required this.type,
    required this.typeLabel,
    required this.dateFrom,
    required this.dateTo,
    this.timeFrom,
    this.timeTo,
    required this.isHalfDay,
    required this.withPay,
    this.shift,
    required this.reason,
    required this.status,
    required this.statusLabel,
    this.remarks,
    required this.canWithdraw,
    this.employeeName,
    this.employeeNumber,
  });

  factory Filing.fromJson(Map<String, dynamic> json) {
    final employee = json['employee'] as Map<String, dynamic>?;

    return Filing(
      id: json['id'] as int,
      type: json['type'] as String,
      typeLabel: json['type_label'] as String,
      dateFrom: json['date_from'] as String,
      dateTo: json['date_to'] as String,
      timeFrom: json['time_from'] as String?,
      timeTo: json['time_to'] as String?,
      isHalfDay: json['is_half_day'] == true,
      withPay: json['with_pay'] == true,
      shift: json['shift'] as String?,
      reason: json['reason'] as String,
      status: json['status'] as String,
      statusLabel: json['status_label'] as String,
      remarks: json['remarks'] as String?,
      canWithdraw: json['can_withdraw'] == true,
      employeeName: employee?['full_name'] as String?,
      employeeNumber: employee?['employee_number'] as String?,
    );
  }

  final int id;
  final String type;
  final String typeLabel;
  final String dateFrom;
  final String dateTo;
  final String? timeFrom;
  final String? timeTo;
  final bool isHalfDay;
  final bool withPay;
  final String? shift;
  final String reason;
  final String status;
  final String statusLabel;
  final String? remarks;
  final bool canWithdraw;
  final String? employeeName;
  final String? employeeNumber;

  /// What the filing asks for, in one line.
  String get detail => switch (type) {
    'unaccounted' => [
      if (timeFrom != null) 'In $timeFrom',
      if (timeTo != null) 'Out $timeTo',
    ].join(' · '),
    'overtime' => '$timeFrom–$timeTo',
    'early_out' => 'Out at $timeFrom',
    'leave' =>
      '${isHalfDay ? 'Half day' : 'Whole day'} · ${withPay ? 'With pay' : 'Without pay'}',
    'shift_change' => shift ?? '',
    _ => '',
  };
}
