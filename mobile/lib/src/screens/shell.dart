import 'package:flutter/material.dart';

import 'attendance.dart';
import 'filings.dart';
import 'home.dart';
import 'payslips.dart';
import 'profile.dart';

/// Bottom-tab layout for signed-in employees.
class Shell extends StatefulWidget {
  const Shell({super.key});

  @override
  State<Shell> createState() => ShellState();
}

class ShellState extends State<Shell> {
  int _index = 0;

  /// Lets other screens switch tabs (e.g. Home → Attendance).
  void showTab(int index) => setState(() => _index = index);

  static const _titles = [
    'Home',
    'Attendance',
    'Filings',
    'Payslips',
    'Profile',
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: _index == 0 ? null : AppBar(title: Text(_titles[_index])),
      // Each tab rebuilds when selected so its data refreshes.
      body: SafeArea(
        top: _index == 0,
        child: switch (_index) {
          0 => HomeScreen(key: UniqueKey()),
          1 => const AttendanceScreen(),
          2 => FilingsScreen(key: UniqueKey()),
          3 => PayslipsScreen(key: UniqueKey()),
          _ => const ProfileScreen(),
        },
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: showTab,
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.home_outlined),
            selectedIcon: Icon(Icons.home),
            label: 'Home',
          ),
          NavigationDestination(
            icon: Icon(Icons.calendar_month_outlined),
            selectedIcon: Icon(Icons.calendar_month),
            label: 'Attendance',
          ),
          NavigationDestination(
            icon: Icon(Icons.description_outlined),
            selectedIcon: Icon(Icons.description),
            label: 'Filings',
          ),
          NavigationDestination(
            icon: Icon(Icons.payments_outlined),
            selectedIcon: Icon(Icons.payments),
            label: 'Payslips',
          ),
          NavigationDestination(
            icon: Icon(Icons.account_circle_outlined),
            selectedIcon: Icon(Icons.account_circle),
            label: 'Profile',
          ),
        ],
      ),
    );
  }
}
