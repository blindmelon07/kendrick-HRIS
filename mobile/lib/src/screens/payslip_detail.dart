import 'package:flutter/material.dart';

import '../format.dart';
import '../models.dart';
import '../session.dart';
import '../theme.dart';
import '../widgets.dart';

class PayslipDetailScreen extends StatelessWidget {
  const PayslipDetailScreen({super.key, required this.id});

  final int id;

  @override
  Widget build(BuildContext context) {
    final api = SessionScope.of(context).api;

    return Scaffold(
      appBar: AppBar(title: const Text('Payslip')),
      body: AsyncScreen<Payslip>(
        load: () async {
          final json = await api.get('payslips/$id') as Map<String, dynamic>;
          return Payslip.fromJson(json['data'] as Map<String, dynamic>);
        },
        builder: (context, slip, reload) {
          final colors = context.colors;

          return [
            InfoCard(
              color: colors.brandSoft,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'NET PAY',
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.primary,
                      fontWeight: FontWeight.w600,
                      fontSize: 13,
                    ),
                  ),
                  Text(
                    peso(slip.netPay),
                    style: const TextStyle(
                      fontSize: 30,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  Text(
                    '${slip.periodLabel} · Paid ${formatDate(slip.payDate)}',
                    style: TextStyle(color: colors.textSecondary),
                  ),
                  Text(
                    slip.paymentMethod == 'atm'
                        ? 'Credited to your BDO payroll account'
                        : 'Released in cash',
                    style: TextStyle(fontSize: 12, color: colors.textSecondary),
                  ),
                ],
              ),
            ),
            InfoCard(
              child: GridView.count(
                crossAxisCount: 2,
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                childAspectRatio: 2.6,
                children: [
                  StatTile('Days worked', number(slip.daysWorked ?? 0)),
                  StatTile('Absent', number(slip.daysAbsent ?? 0)),
                  StatTile(
                    'Late / UT',
                    minutes(slip.lateUndertimeMinutes ?? 0),
                  ),
                  StatTile('Overtime', '${number(slip.overtimeHours ?? 0)}h'),
                ],
              ),
            ),
            const SectionTitle('Earnings'),
            InfoCard(
              child: Column(
                children: [
                  for (final line in slip.earnings)
                    LabelValue(line.label, peso(line.amount)),
                  const Divider(height: 16),
                  LabelValue('Gross pay', peso(slip.grossPay), strong: true),
                ],
              ),
            ),
            const SectionTitle('Deductions'),
            InfoCard(
              child: Column(
                children: [
                  if (slip.deductions.isEmpty)
                    Align(
                      alignment: Alignment.centerLeft,
                      child: Text(
                        'No deductions this cut-off.',
                        style: TextStyle(color: colors.textSecondary),
                      ),
                    ),
                  for (final line in slip.deductions)
                    LabelValue(line.label, peso(line.amount)),
                  const Divider(height: 16),
                  LabelValue(
                    'Total deductions',
                    peso(slip.totalDeductions),
                    strong: true,
                  ),
                ],
              ),
            ),
          ];
        },
      ),
    );
  }
}
