import 'package:flutter/material.dart';

import '../models/pattern_models.dart';
import '../services/pattern_api.dart';
import '../theme/pattern_theme.dart';
import 'widgets/animated_dashboard.dart';
import 'widgets/messages_screen.dart';

class MobileShell extends StatefulWidget {
  const MobileShell({super.key, required this.role});

  final PortalRole role;

  @override
  State<MobileShell> createState() => _MobileShellState();
}

class _MobileShellState extends State<MobileShell> {
  final _api = const PatternApi();
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final owner = widget.role == PortalRole.owner;
    final tabs = owner
        ? const ['Owner', 'Statement', 'Payouts', 'Profile']
        : const ['Stay', 'Bookings', 'Messages', 'Profile'];

    return Scaffold(
      appBar: AppBar(
        title: Text(tabs[_index]),
        leading: Padding(
          padding: const EdgeInsets.only(left: 12),
          child: Container(
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: PatternTheme.line),
            ),
            child: const Center(
              child: Text('P', style: TextStyle(fontWeight: FontWeight.w900)),
            ),
          ),
        ),
        actions: [
          IconButton(
            onPressed: () {},
            icon: const Icon(Icons.notifications_none_rounded),
          ),
        ],
      ),
      body: AnimatedSwitcher(
        duration: const Duration(milliseconds: 280),
        switchInCurve: Curves.easeOut,
        switchOutCurve: Curves.easeIn,
        child: _body(owner),
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (value) => setState(() => _index = value),
        destinations: owner
            ? const [
                NavigationDestination(icon: Icon(Icons.home_work_outlined), label: 'Owner'),
                NavigationDestination(icon: Icon(Icons.receipt_long_outlined), label: 'Statement'),
                NavigationDestination(icon: Icon(Icons.payments_outlined), label: 'Payouts'),
                NavigationDestination(icon: Icon(Icons.person_outline), label: 'Profile'),
              ]
            : const [
                NavigationDestination(icon: Icon(Icons.home_outlined), label: 'Stay'),
                NavigationDestination(icon: Icon(Icons.calendar_month_outlined), label: 'Bookings'),
                NavigationDestination(icon: Icon(Icons.chat_bubble_outline_rounded), label: 'Messages'),
                NavigationDestination(icon: Icon(Icons.person_outline), label: 'Profile'),
              ],
      ),
    );
  }

  Widget _body(bool owner) {
    if (_index == 2 && !owner) {
      return const MessagesScreen();
    }

    return FutureBuilder<PatternDashboardData>(
      future: _api.dashboard(widget.role),
      builder: (context, snapshot) {
        final data = snapshot.data;
        if (data == null) {
          return const Center(child: CircularProgressIndicator());
        }
        return AnimatedDashboard(
          key: ValueKey('${widget.role}-$_index'),
          role: widget.role,
          data: data,
          pageIndex: _index,
        );
      },
    );
  }
}
