import 'package:flutter/material.dart';

import '../api.dart';
import '../models.dart';
import '../session.dart';
import '../theme.dart';
import '../widgets.dart';
import 'filing_card.dart';

/// Dept. Head review of their team's pending filings.
class EndorsementsScreen extends StatefulWidget {
  const EndorsementsScreen({super.key});

  @override
  State<EndorsementsScreen> createState() => _EndorsementsScreenState();
}

class _EndorsementsScreenState extends State<EndorsementsScreen> {
  int? _busyId;

  Future<void> _decide(
    ApiClient api,
    Filing filing,
    String decision,
    Future<void> Function() reload,
  ) async {
    setState(() => _busyId = filing.id);

    try {
      await api.put('endorsements/${filing.id}', {'decision': decision});
      if (mounted) {
        showMessage(
          context,
          decision == 'endorse' ? 'Endorsed to HR.' : 'Filing denied.',
        );
      }
      await reload();
    } on ApiException catch (exception) {
      if (mounted) showMessage(context, exception.message);
    } finally {
      if (mounted) setState(() => _busyId = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    final api = SessionScope.of(context).api;

    return Scaffold(
      appBar: AppBar(title: const Text('Endorsements')),
      body: AsyncScreen<List<Filing>>(
        load: () async {
          final json = await api.get('endorsements') as Map<String, dynamic>;
          return (json['data'] as List)
              .map((item) => Filing.fromJson(item as Map<String, dynamic>))
              .toList();
        },
        builder: (context, filings, reload) => [
          Text(
            'Endorsed filings go to the HR Time Keeper for final approval. Denied filings are returned to the employee.',
            style: TextStyle(color: context.colors.textSecondary),
          ),
          if (filings.isEmpty)
            const EmptyState(
              title: 'All caught up',
              message: 'No team filings are waiting for your endorsement.',
            )
          else
            for (final filing in filings)
              FilingCard(
                filing: filing,
                showEmployee: true,
                actions: [
                  FilledButton(
                    style: FilledButton.styleFrom(
                      minimumSize: const Size(0, 40),
                    ),
                    onPressed: _busyId == null
                        ? () => _decide(api, filing, 'endorse', reload)
                        : null,
                    child: const Text('Endorse'),
                  ),
                  TextButton(
                    style: TextButton.styleFrom(
                      foregroundColor: context.colors.danger,
                      backgroundColor: context.colors.dangerSoft,
                      minimumSize: const Size(0, 40),
                    ),
                    onPressed: _busyId == null
                        ? () => _decide(api, filing, 'deny', reload)
                        : null,
                    child: const Text('Deny'),
                  ),
                ],
              ),
        ],
      ),
    );
  }
}
