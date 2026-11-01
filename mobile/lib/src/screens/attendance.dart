import 'package:flutter/material.dart';

import '../format.dart';
import '../models.dart';
import '../session.dart';
import '../theme.dart';
import '../widgets.dart';

const _status = {
  'present': ('Present', BadgeTone.green),
  'absent': ('Absent', BadgeTone.red),
  'incomplete': ('Missing log', BadgeTone.amber),
  'leave': ('On leave', BadgeTone.blue),
  'holiday': ('Holiday', BadgeTone.blue),
  'rest_day': ('Rest day', BadgeTone.gray),
};

/// The employee's own Work Hours Summary, one semi-monthly cut-off at a time.
class AttendanceScreen extends StatefulWidget {
  const AttendanceScreen({super.key});

  @override
  State<AttendanceScreen> createState() => _AttendanceScreenState();
}

class _AttendanceScreenState extends State<AttendanceScreen> {
  int _offset = 0;

  @override
  Widget build(BuildContext context) {
    final api = SessionScope.of(context).api;
    final range = cutoff(DateTime.now(), _offset);
    final brand = Theme.of(context).colorScheme.primary;

    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(8, 8, 8, 0),
          child: Row(
            children: [
              IconButton(
                tooltip: 'Previous cut-off',
                icon: Icon(Icons.chevron_left, color: brand),
                onPressed: () => setState(() => _offset--),
              ),
              Expanded(
                child: Column(
                  children: [
                    Text(
                      dateRange(range.from, range.to),
                      style: const TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    Text(
                      _offset == 0 ? 'Current cut-off' : 'Cut-off',
                      style: TextStyle(
                        fontSize: 12,
                        color: context.colors.textSecondary,
                      ),
                    ),
                  ],
                ),
              ),
              IconButton(
                tooltip: 'Next cut-off',
                icon: Icon(Icons.chevron_right, color: brand),
                onPressed: _offset >= 0
                    ? null
                    : () => setState(() => _offset++),
              ),
            ],
          ),
        ),
        Expanded(
          child: AsyncScreen<(List<DailyRecord>, WorkSummary)>(
            key: ValueKey(_offset),
            load: () async {
              final json = await api.get(
                'attendance',
                query: {'from': range.from, 'to': range.to},
              ) as Map<String, dynamic>;
              final meta = json['meta'] as Map<String, dynamic>;
              return (
                (json['data'] as List)
                    .map(
                      (item) =>
                          DailyRecord.fromJson(item as Map<String, dynamic>),
                    )
                    .toList(),
                WorkSummary.fromJson(meta['summary'] as Map<String, dynamic>),
              );
            },
            builder: (context, data, reload) {
              final (records, summary) = data;
              final missing = records
                  .where(
                    (r) => r.status == 'incomplete' || r.status == 'absent',
                  )
                  .length;

              return [
                InfoCard(
                  child: GridView.count(
                    crossAxisCount: 3,
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    childAspectRatio: 2,
                    children: [
                      StatTile('Worked', number(summary.daysWorked)),
                      StatTile('Paid leave', number(summary.paidLeaveDays)),
                      StatTile(
                        'Absent',
                        number(summary.daysAbsent + summary.unpaidLeaveDays),
                      ),
                      StatTile('Late', minutes(summary.lateMinutes)),
                      StatTile('Undertime', minutes(summary.undertimeMinutes)),
                      StatTile('OT', '${number(summary.overtimeHours)}h'),
                    ],
                  ),
                ),
                if (missing > 0)
                  InfoCard(
                    color: context.colors.warningSoft,
                    child: Text(
                      '$missing day(s) with missing logs or no attendance. If you were at work, file an '
                      'Unaccounted or Leave request before the 3rd / 18th, 12:00 NN.',
                    ),
                  ),
                for (final record in records.where(
                  (r) => r.status != 'not_employed',
                ))
                  _RecordCard(record),
              ];
            },
          ),
        ),
      ],
    );
  }
}

class _RecordCard extends StatelessWidget {
  const _RecordCard(this.record);

  final DailyRecord record;

  @override
  Widget build(BuildContext context) {
    final colors = context.colors;
    var (label, tone) =
        _status[record.status] ?? (record.status, BadgeTone.gray);

    if (record.status == 'leave') {
      label = record.paidLeaveDays > 0 ? 'Leave (paid)' : 'Leave (unpaid)';
    }

    final notes = [
      if (record.holiday != null) record.holiday!,
      if (record.daysWorked == 0.5) 'Half-day leave',
    ];

    return Opacity(
      opacity: record.isWorkDay ? 1 : 0.6,
      child: InfoCard(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    '${record.day}, ${formatDate(record.date, withYear: false)}',
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
                StatusBadge(label, tone: tone),
              ],
            ),
            if (record.timeIn != null || record.timeOut != null) ...[
              const SizedBox(height: 6),
              Wrap(
                spacing: 16,
                children: [
                  Text('In ${record.timeIn ?? '—'}'),
                  Text('Out ${record.timeOut ?? '—'}'),
                  if (record.lateMinutes > 0)
                    Text(
                      'Late ${minutes(record.lateMinutes)}',
                      style: TextStyle(color: colors.danger),
                    ),
                  if (record.undertimeMinutes > 0)
                    Text(
                      'UT ${minutes(record.undertimeMinutes)}',
                      style: TextStyle(color: colors.danger),
                    ),
                  if (record.overtimeHours > 0)
                    Text(
                      'OT ${number(record.overtimeHours)}h',
                      style: TextStyle(color: colors.success),
                    ),
                ],
              ),
            ],
            if (notes.isNotEmpty) ...[
              const SizedBox(height: 4),
              Text(
                notes.join(' · '),
                style: TextStyle(fontSize: 12, color: colors.textSecondary),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
