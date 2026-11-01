import 'package:flutter/material.dart';

import '../format.dart';
import '../models.dart';
import '../session.dart';
import '../theme.dart';
import '../widgets.dart';
import 'payslip_detail.dart';

/// Payslips from finalized payroll periods.
class PayslipsScreen extends StatelessWidget {
  const PayslipsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final api = SessionScope.of(context).api;

    return AsyncScreen<List<Payslip>>(
      load: () async {
        final json = await api.get('payslips') as Map<String, dynamic>;
        return (json['data'] as List)
            .map((item) => Payslip.fromJson(item as Map<String, dynamic>))
            .toList();
      },
      builder: (context, payslips, reload) => payslips.isEmpty
          ? const [
              EmptyState(
                title: 'No payslips yet',
                message: 'Payslips appear here once payroll for the cut-off is finalized.',
              ),
            ]
          : [
              for (final payslip in payslips)
                InfoCard(
                  onTap: () => Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => PayslipDetailScreen(id: payslip.id),
                    ),
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              payslip.periodLabel,
                              style: const TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                            Text(
                              'Pay date ${formatDate(payslip.payDate)} · ${payslip.paymentMethodLabel}',
                              style: TextStyle(
                                fontSize: 12,
                                color: context.colors.textSecondary,
                              ),
                            ),
                          ],
                        ),
                      ),
                      Text(
                        peso(payslip.netPay),
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      const Icon(Icons.chevron_right),
                    ],
                  ),
                ),
            ],
    );
  }
}
