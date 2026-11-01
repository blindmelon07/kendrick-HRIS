import 'package:flutter/material.dart';

import '../api.dart';
import '../models.dart';
import '../session.dart';
import '../theme.dart';
import '../widgets.dart';
import 'filing_card.dart';
import 'new_filing.dart';

/// The employee's own filings, with withdraw for pending ones.
class FilingsScreen extends StatelessWidget {
  const FilingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final api = SessionScope.of(context).api;

    return AsyncScreen<List<Filing>>(
      load: () async {
        final json = await api.get('filings') as Map<String, dynamic>;
        return (json['data'] as List)
            .map((item) => Filing.fromJson(item as Map<String, dynamic>))
            .toList();
      },
      builder: (context, filings, reload) => [
        FilledButton.icon(
          icon: const Icon(Icons.add),
          label: const Text('New filing'),
          onPressed: () async {
            final filed = await Navigator.of(context).push<bool>(
              MaterialPageRoute(
                builder: (_) => const NewFilingScreen(),
                fullscreenDialog: true,
              ),
            );
            if (filed == true) reload();
          },
        ),
        if (filings.isEmpty)
          const EmptyState(
            title: 'No filings yet',
            message: 'Missing logs, leaves, overtime, early outs and shift changes you file will show here.',
          )
        else
          for (final filing in filings)
            FilingCard(
              filing: filing,
              actions: [
                if (filing.canWithdraw)
                  TextButton(
                    style: TextButton.styleFrom(
                      foregroundColor: context.colors.danger,
                      backgroundColor: context.colors.dangerSoft,
                    ),
                    onPressed: () => _withdraw(context, api, filing, reload),
                    child: const Text('Withdraw'),
                  ),
              ],
            ),
      ],
    );
  }

  Future<void> _withdraw(
    BuildContext context,
    ApiClient api,
    Filing filing,
    Future<void> Function() reload,
  ) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Withdraw filing'),
        content: const Text('Withdraw this pending filing?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Withdraw'),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    try {
      await api.delete('filings/${filing.id}');
      await reload();
    } on ApiException catch (exception) {
      if (context.mounted) showMessage(context, exception.message);
    }
  }
}
