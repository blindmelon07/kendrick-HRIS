import 'package:flutter/material.dart';

import '../format.dart';
import '../models.dart';
import '../theme.dart';
import '../widgets.dart';

class FilingCard extends StatelessWidget {
  const FilingCard({
    super.key,
    required this.filing,
    this.showEmployee = false,
    this.actions = const [],
  });

  final Filing filing;
  final bool showEmployee;
  final List<Widget> actions;

  @override
  Widget build(BuildContext context) {
    final muted = TextStyle(color: context.colors.textSecondary);

    return InfoCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (showEmployee && filing.employeeName != null)
            Text(
              '${filing.employeeName} · ${filing.employeeNumber}',
              style: muted.copyWith(fontSize: 13, fontWeight: FontWeight.w600),
            ),
          Row(
            children: [
              Expanded(
                child: Text(
                  filing.typeLabel,
                  style: const TextStyle(
                    fontSize: 17,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              StatusBadge(
                filing.statusLabel,
                tone: StatusBadge.forFiling(filing.status),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(dateRange(filing.dateFrom, filing.dateTo)),
          if (filing.detail.isNotEmpty)
            Text(filing.detail, style: muted.copyWith(fontSize: 12)),
          const SizedBox(height: 4),
          Text(filing.reason, style: muted),
          if (filing.remarks != null) ...[
            const SizedBox(height: 4),
            Text(
              'Remarks: ${filing.remarks}',
              style: const TextStyle(fontSize: 12),
            ),
          ],
          if (actions.isNotEmpty) ...[
            const SizedBox(height: 8),
            Row(
              children: [
                for (final action in actions)
                  Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: action,
                  ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}
