import 'package:flutter/material.dart';

import '../api.dart';
import '../models.dart';
import '../session.dart';
import '../theme.dart';
import '../widgets.dart';
import 'endorsements.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final session = SessionScope.of(context);

    return AsyncScreen<Profile>(
      load: () async {
        final json = await session.api.get('me') as Map<String, dynamic>;
        return Profile.fromJson(json['data'] as Map<String, dynamic>);
      },
      builder: (context, me, reload) {
        final colors = context.colors;
        final brand = Theme.of(context).colorScheme.primary;

        return [
          Column(
            children: [
              EmployeeAvatar(
                name: me.fullName,
                photoUrl: me.photoUrl,
                radius: 44,
              ),
              const SizedBox(height: 8),
              Text(
                me.fullName,
                style: const TextStyle(
                  fontSize: 17,
                  fontWeight: FontWeight.w600,
                ),
              ),
              Text(me.jobTitle, style: TextStyle(color: colors.textSecondary)),
            ],
          ),
          InfoCard(
            child: Column(
              children: [
                LabelValue('Employee No.', me.employeeNumber),
                const Divider(height: 12),
                LabelValue('Department', me.department),
                const Divider(height: 12),
                LabelValue('Branch', me.branch),
                const Divider(height: 12),
                LabelValue('Shift', '${me.shiftStart}–${me.shiftEnd}'),
                const Divider(height: 12),
                LabelValue('Login', me.email),
              ],
            ),
          ),
          if (me.canEndorse)
            InfoCard(
              onTap: () => Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => const EndorsementsScreen()),
              ),
              child: Row(
                children: [
                  Icon(Icons.verified_outlined, color: brand),
                  const SizedBox(width: 8),
                  const Expanded(
                    child: Text(
                      'Team endorsements',
                      style: TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                  const Icon(Icons.chevron_right),
                ],
              ),
            ),
          OutlinedButton(
            onPressed: session.signOut,
            child: const Text('Sign out'),
          ),
          Text(
            'TriStellar HRIS · Server ${apiUrl.replaceFirst(RegExp(r'^https?://'), '')}',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 12, color: colors.textSecondary),
          ),
        ];
      },
    );
  }
}
