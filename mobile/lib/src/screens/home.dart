import 'package:flutter/material.dart';

import '../format.dart';
import '../models.dart';
import '../session.dart';
import '../theme.dart';
import '../widgets.dart';
import 'endorsements.dart';
import 'new_filing.dart';
import 'payslip_detail.dart';
import 'shell.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final api = SessionScope.of(context).api;

    return AsyncScreen<(HomeSummary, Profile)>(
      load: () async {
        final results = await Future.wait([api.get('home'), api.get('me')]);
        return (
          HomeSummary.fromJson(results[0]['data'] as Map<String, dynamic>),
          Profile.fromJson(results[1]['data'] as Map<String, dynamic>),
        );
      },
      builder: (context, data, reload) {
        final (summary, me) = data;
        final brand = Theme.of(context).colorScheme.primary;
        final shell = context.findAncestorStateOfType<ShellState>();

        return [
          Row(
            children: [
              Image.asset('assets/wordmark.png', height: 36),
              const SizedBox(width: 8),
              const StatusBadge('HRIS', tone: BadgeTone.brand),
            ],
          ),
          Row(
            children: [
              EmployeeAvatar(
                name: me.fullName,
                photoUrl: me.photoUrl,
                radius: 26,
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '${greeting(DateTime.now())}, ${me.firstName}',
                      style: Theme.of(context).textTheme.headlineSmall
                          ?.copyWith(fontWeight: FontWeight.w700),
                    ),
                    Text(
                      longDate(DateTime.now()),
                      style: TextStyle(color: context.colors.textSecondary),
                    ),
                  ],
                ),
              ),
            ],
          ),
          InfoCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _Heading(
                  icon: Icons.schedule,
                  text: "Today's logs",
                  color: brand,
                ),
                const SizedBox(height: 8),
                if (summary.todayLogs.isEmpty)
                  Text(
                    'No punches yet today. Tap your company ID at the biometric reader.',
                    style: TextStyle(color: context.colors.textSecondary),
                  )
                else
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      for (var i = 0; i < summary.todayLogs.length; i++)
                        _LogChip(
                          time: summary.todayLogs[i],
                          label: i == 0
                              ? 'In'
                              : (i == summary.todayLogs.length - 1
                                    ? 'Out'
                                    : 'Break'),
                        ),
                    ],
                  ),
                const SizedBox(height: 8),
                Text(
                  'Shift: ${me.shiftName}',
                  style: TextStyle(
                    fontSize: 12,
                    color: context.colors.textSecondary,
                  ),
                ),
              ],
            ),
          ),
          if (summary.endorsementsWaiting != null)
            InfoCard(
              onTap: () async {
                await Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => const EndorsementsScreen()),
                );
                reload();
              },
              child: Row(
                children: [
                  Expanded(
                    child: _Heading(
                      icon: Icons.verified_outlined,
                      text: 'Team filings to endorse',
                      color: brand,
                    ),
                  ),
                  StatusBadge(
                    '${summary.endorsementsWaiting}',
                    tone: summary.endorsementsWaiting! > 0
                        ? BadgeTone.amber
                        : BadgeTone.gray,
                  ),
                  const Icon(Icons.chevron_right),
                ],
              ),
            ),
          const SectionTitle('Quick actions'),
          Row(
            children: [
              Expanded(
                child: FilledButton(
                  onPressed: () async {
                    final filed = await Navigator.of(context).push<bool>(
                      MaterialPageRoute(
                        builder: (_) => const NewFilingScreen(),
                        fullscreenDialog: true,
                      ),
                    );
                    if (filed == true) reload();
                  },
                  child: const Text('File a request'),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: OutlinedButton(
                  onPressed: () => shell?.showTab(1),
                  child: const Text('My attendance'),
                ),
              ),
            ],
          ),
          InfoCard(
            onTap: () => shell?.showTab(2),
            child: Row(
              children: [
                Expanded(
                  child: _Heading(
                    icon: Icons.description_outlined,
                    text: 'My filings',
                    color: brand,
                  ),
                ),
                Text(
                  summary.pendingFilings > 0
                      ? '${summary.pendingFilings} awaiting approval'
                      : 'None pending',
                  style: TextStyle(color: context.colors.textSecondary),
                ),
              ],
            ),
          ),
          InfoCard(
            onTap: summary.latestPayslip == null
                ? null
                : () => Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) =>
                          PayslipDetailScreen(id: summary.latestPayslip!.id),
                    ),
                  ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _Heading(
                  icon: Icons.payments_outlined,
                  text: 'Latest payslip',
                  color: brand,
                ),
                const SizedBox(height: 8),
                if (summary.latestPayslip case final payslip?) ...[
                  Text(
                    peso(payslip.netPay),
                    style: const TextStyle(
                      fontSize: 28,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  Text(
                    '${payslip.periodLabel} · ${payslip.paymentMethodLabel}',
                    style: TextStyle(color: context.colors.textSecondary),
                  ),
                ] else
                  Text(
                    'No released payslips yet.',
                    style: TextStyle(color: context.colors.textSecondary),
                  ),
              ],
            ),
          ),
        ];
      },
    );
  }
}

class _Heading extends StatelessWidget {
  const _Heading({required this.icon, required this.text, required this.color});

  final IconData icon;
  final String text;
  final Color color;

  @override
  Widget build(BuildContext context) => Row(
    children: [
      Icon(icon, color: color, size: 22),
      const SizedBox(width: 8),
      Flexible(
        child: Text(
          text,
          style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w600),
        ),
      ),
    ],
  );
}

class _LogChip extends StatelessWidget {
  const _LogChip({required this.time, required this.label});

  final String time;
  final String label;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
    decoration: BoxDecoration(
      color: context.colors.border.withValues(alpha: 0.5),
      borderRadius: BorderRadius.circular(12),
    ),
    child: Column(
      children: [
        Text(
          time,
          style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w600),
        ),
        Text(
          label,
          style: TextStyle(fontSize: 12, color: context.colors.textSecondary),
        ),
      ],
    ),
  );
}
