import 'package:flutter/material.dart';

import '../models/pattern_models.dart';
import '../theme/pattern_theme.dart';
import 'mobile_shell.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen>
    with SingleTickerProviderStateMixin {
  PortalRole _role = PortalRole.tenant;
  late final AnimationController _controller;
  late final Animation<double> _fade;
  late final Animation<Offset> _slide;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 850),
    )..forward();
    _fade = CurvedAnimation(parent: _controller, curve: Curves.easeOut);
    _slide = Tween<Offset>(
      begin: const Offset(0, .08),
      end: Offset.zero,
    ).animate(CurvedAnimation(parent: _controller, curve: Curves.easeOutCubic));
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: FadeTransition(
          opacity: _fade,
          child: SlideTransition(
            position: _slide,
            child: ListView(
              padding: const EdgeInsets.fromLTRB(20, 28, 20, 24),
              children: [
                const _BrandMark(),
                const SizedBox(height: 28),
                Text(
                  'Pattern Vacation Homes',
                  style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                        color: PatternTheme.navy,
                        fontWeight: FontWeight.w900,
                        height: 1.05,
                      ),
                ),
                const SizedBox(height: 10),
                Text(
                  'A mobile app for stays, statements, payouts, door access, and support.',
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        color: Colors.blueGrey.shade600,
                        height: 1.5,
                        fontWeight: FontWeight.w600,
                      ),
                ),
                const SizedBox(height: 28),
                _RolePicker(
                  role: _role,
                  onChanged: (role) => setState(() => _role = role),
                ),
                const SizedBox(height: 18),
                const TextField(
                  keyboardType: TextInputType.emailAddress,
                  decoration: InputDecoration(
                    labelText: 'Email',
                    prefixIcon: Icon(Icons.mail_outline_rounded),
                  ),
                ),
                const SizedBox(height: 12),
                const TextField(
                  obscureText: true,
                  decoration: InputDecoration(
                    labelText: 'Password',
                    prefixIcon: Icon(Icons.lock_outline_rounded),
                  ),
                ),
                const SizedBox(height: 22),
                FilledButton(
                  onPressed: () {
                    Navigator.of(context).pushReplacement(
                      PageRouteBuilder<void>(
                        transitionDuration: const Duration(milliseconds: 450),
                        pageBuilder: (_, animation, __) {
                          return FadeTransition(
                            opacity: animation,
                            child: MobileShell(role: _role),
                          );
                        },
                      ),
                    );
                  },
                  child: const Text('Continue'),
                ),
                const SizedBox(height: 14),
                TextButton(
                  onPressed: () {},
                  child: const Text('Forgot password?'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _BrandMark extends StatelessWidget {
  const _BrandMark();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 72,
      height: 72,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        boxShadow: const [
          BoxShadow(color: Color(0x16071A3B), blurRadius: 28, offset: Offset(0, 12)),
        ],
      ),
      child: const Center(
        child: Text(
          'P',
          style: TextStyle(
            color: PatternTheme.navy,
            fontSize: 34,
            fontWeight: FontWeight.w900,
          ),
        ),
      ),
    );
  }
}

class _RolePicker extends StatelessWidget {
  const _RolePicker({
    required this.role,
    required this.onChanged,
  });

  final PortalRole role;
  final ValueChanged<PortalRole> onChanged;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(5),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: PatternTheme.line),
      ),
      child: Row(
        children: [
          _RoleButton(
            label: 'Tenant',
            selected: role == PortalRole.tenant,
            onTap: () => onChanged(PortalRole.tenant),
          ),
          _RoleButton(
            label: 'Owner',
            selected: role == PortalRole.owner,
            onTap: () => onChanged(PortalRole.owner),
          ),
        ],
      ),
    );
  }
}

class _RoleButton extends StatelessWidget {
  const _RoleButton({
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 220),
          curve: Curves.easeOut,
          height: 44,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: selected ? PatternTheme.blue : Colors.transparent,
            borderRadius: BorderRadius.circular(16),
          ),
          child: Text(
            label,
            style: TextStyle(
              color: selected ? Colors.white : PatternTheme.navy,
              fontWeight: FontWeight.w900,
            ),
          ),
        ),
      ),
    );
  }
}
