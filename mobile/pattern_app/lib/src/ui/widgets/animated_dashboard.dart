import 'package:flutter/material.dart';

import '../../models/pattern_models.dart';
import '../../theme/pattern_theme.dart';

class AnimatedDashboard extends StatefulWidget {
  const AnimatedDashboard({
    super.key,
    required this.role,
    required this.data,
    required this.pageIndex,
  });

  final PortalRole role;
  final PatternDashboardData data;
  final int pageIndex;

  @override
  State<AnimatedDashboard> createState() => _AnimatedDashboardState();
}

class _AnimatedDashboardState extends State<AnimatedDashboard>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;
  bool _doorUnlocked = false;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 650),
    )..forward();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final title = switch (widget.pageIndex) {
      1 => widget.role == PortalRole.owner ? 'Owner Statement' : 'My Bookings',
      2 => widget.role == PortalRole.owner ? 'Payout Schedule' : 'Messages',
      3 => 'Profile',
      _ => widget.data.heroTitle,
    };

    return ListView(
      padding: const EdgeInsets.fromLTRB(18, 8, 18, 24),
      children: [
        _Staggered(
          controller: _controller,
          order: 0,
          child: _HeroCard(
            title: title,
            subtitle: widget.data.heroSubtitle,
            role: widget.role,
          ),
        ),
        const SizedBox(height: 16),
        _Staggered(
          controller: _controller,
          order: 1,
          child: GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: widget.data.metrics.length,
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2,
              childAspectRatio: 1.18,
              crossAxisSpacing: 12,
              mainAxisSpacing: 12,
            ),
            itemBuilder: (context, index) => _MetricCard(metric: widget.data.metrics[index]),
          ),
        ),
        const SizedBox(height: 18),
        if (widget.role == PortalRole.tenant) ...[
          _Staggered(
            controller: _controller,
            order: 2,
            child: _SmartLockSlider(
              unlocked: _doorUnlocked,
              onChanged: (value) => setState(() => _doorUnlocked = value),
            ),
          ),
          const SizedBox(height: 18),
        ],
        _SectionTitle(widget.role == PortalRole.owner ? 'Properties' : 'Stay'),
        const SizedBox(height: 10),
        ...widget.data.properties.asMap().entries.map((entry) {
          return _Staggered(
            controller: _controller,
            order: entry.key + 3,
            child: _PropertyCard(property: entry.value),
          );
        }),
        const SizedBox(height: 10),
        _SectionTitle(widget.role == PortalRole.owner ? 'Money & Updates' : 'Quick Actions'),
        const SizedBox(height: 10),
        ...widget.data.activities.asMap().entries.map((entry) {
          return _Staggered(
            controller: _controller,
            order: entry.key + 5,
            child: _ActivityCard(activity: entry.value),
          );
        }),
      ],
    );
  }
}

class _HeroCard extends StatelessWidget {
  const _HeroCard({
    required this.title,
    required this.subtitle,
    required this.role,
  });

  final String title;
  final String subtitle;
  final PortalRole role;

  @override
  Widget build(BuildContext context) {
    return Container(
      constraints: const BoxConstraints(minHeight: 190),
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF031329), Color(0xFF123B7A), PatternTheme.blue],
        ),
        borderRadius: BorderRadius.circular(28),
        boxShadow: const [
          BoxShadow(color: Color(0x26071A3B), blurRadius: 30, offset: Offset(0, 18)),
        ],
      ),
      child: Stack(
        children: [
          Positioned(
            right: -28,
            top: -22,
            child: TweenAnimationBuilder<double>(
              tween: Tween(begin: .75, end: 1),
              duration: const Duration(milliseconds: 900),
              curve: Curves.easeOutBack,
              builder: (context, scale, child) {
                return Transform.scale(scale: scale, child: child);
              },
              child: Container(
                width: 120,
                height: 120,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: .12),
                  shape: BoxShape.circle,
                ),
              ),
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              Text(
                role == PortalRole.owner ? 'OWNER PORTAL' : 'TENANT APP',
                style: const TextStyle(
                  color: Color(0xFFBFDBFE),
                  letterSpacing: 2,
                  fontWeight: FontWeight.w900,
                  fontSize: 11,
                ),
              ),
              const SizedBox(height: 12),
              Text(
                title,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 30,
                  height: 1.05,
                  fontWeight: FontWeight.w900,
                ),
              ),
              const SizedBox(height: 10),
              Text(
                subtitle,
                style: TextStyle(
                  color: Colors.white.withValues(alpha: .78),
                  height: 1.45,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _MetricCard extends StatelessWidget {
  const _MetricCard({required this.metric});

  final PatternMetric metric;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              metric.label.toUpperCase(),
              style: const TextStyle(
                color: Colors.blueGrey,
                fontSize: 11,
                fontWeight: FontWeight.w900,
                letterSpacing: 1.2,
              ),
            ),
            Text(
              metric.value,
              style: const TextStyle(
                color: PatternTheme.navy,
                fontSize: 24,
                fontWeight: FontWeight.w900,
              ),
            ),
            Text(
              metric.note,
              style: TextStyle(
                color: Colors.blueGrey.shade500,
                fontWeight: FontWeight.w600,
                fontSize: 12,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _PropertyCard extends StatelessWidget {
  const _PropertyCard({required this.property});

  final PatternProperty property;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: PatternTheme.line),
      ),
      child: Row(
        children: [
          Container(
            width: 52,
            height: 52,
            decoration: BoxDecoration(
              color: const Color(0xFFEFF6FF),
              borderRadius: BorderRadius.circular(18),
            ),
            child: const Icon(Icons.apartment_rounded, color: PatternTheme.blue),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  property.title,
                  style: const TextStyle(
                    color: PatternTheme.navy,
                    fontWeight: FontWeight.w900,
                    fontSize: 16,
                  ),
                ),
                const SizedBox(height: 4),
                Text(property.subtitle, style: TextStyle(color: Colors.blueGrey.shade500)),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                property.amount,
                style: const TextStyle(color: PatternTheme.navy, fontWeight: FontWeight.w900),
              ),
              const SizedBox(height: 6),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                decoration: BoxDecoration(
                  color: const Color(0xFFEFF6FF),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  property.status,
                  style: const TextStyle(
                    color: PatternTheme.blue,
                    fontSize: 11,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _SmartLockSlider extends StatelessWidget {
  const _SmartLockSlider({
    required this.unlocked,
    required this.onChanged,
  });

  final bool unlocked;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) {
    final activeColor = unlocked ? const Color(0xFF059669) : PatternTheme.blue;

    return GestureDetector(
      onHorizontalDragEnd: (details) {
        final velocity = details.primaryVelocity ?? 0;
        if (velocity > 120) {
          onChanged(true);
        } else if (velocity < -120) {
          onChanged(false);
        }
      },
      onTap: () => onChanged(!unlocked),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 260),
        curve: Curves.easeOut,
        height: 74,
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: activeColor.withValues(alpha: .10),
          borderRadius: BorderRadius.circular(28),
          border: Border.all(color: activeColor.withValues(alpha: .22)),
        ),
        child: Stack(
          alignment: Alignment.center,
          children: [
            AnimatedOpacity(
              duration: const Duration(milliseconds: 180),
              opacity: unlocked ? .0 : 1,
              child: const Text(
                'Swipe to unlock',
                style: TextStyle(
                  color: PatternTheme.navy,
                  fontWeight: FontWeight.w900,
                ),
              ),
            ),
            AnimatedOpacity(
              duration: const Duration(milliseconds: 180),
              opacity: unlocked ? 1 : .0,
              child: const Text(
                'Swipe back to lock',
                style: TextStyle(
                  color: PatternTheme.navy,
                  fontWeight: FontWeight.w900,
                ),
              ),
            ),
            AnimatedAlign(
              duration: const Duration(milliseconds: 260),
              curve: Curves.easeOutBack,
              alignment: unlocked ? Alignment.centerRight : Alignment.centerLeft,
              child: Container(
                width: 58,
                height: 58,
                decoration: BoxDecoration(
                  color: activeColor,
                  borderRadius: BorderRadius.circular(22),
                  boxShadow: [
                    BoxShadow(
                      color: activeColor.withValues(alpha: .28),
                      blurRadius: 18,
                      offset: const Offset(0, 10),
                    ),
                  ],
                ),
                child: Icon(
                  unlocked ? Icons.lock_open_rounded : Icons.lock_rounded,
                  color: Colors.white,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ActivityCard extends StatelessWidget {
  const _ActivityCard({required this.activity});

  final PatternActivity activity;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        leading: const CircleAvatar(
          backgroundColor: Color(0xFFEFF6FF),
          foregroundColor: PatternTheme.blue,
          child: Icon(Icons.arrow_outward_rounded),
        ),
        title: Text(activity.title, style: const TextStyle(fontWeight: FontWeight.w900)),
        subtitle: Text(activity.subtitle),
        trailing: Text(
          activity.amount,
          style: const TextStyle(color: PatternTheme.navy, fontWeight: FontWeight.w900),
        ),
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle(this.title);

  final String title;

  @override
  Widget build(BuildContext context) {
    return Text(
      title,
      style: const TextStyle(
        color: PatternTheme.navy,
        fontWeight: FontWeight.w900,
        fontSize: 18,
      ),
    );
  }
}

class _Staggered extends StatelessWidget {
  const _Staggered({
    required this.controller,
    required this.order,
    required this.child,
  });

  final AnimationController controller;
  final int order;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    final start = ((order * .08).clamp(0.0, .7) as num).toDouble();
    final animation = CurvedAnimation(
      parent: controller,
      curve: Interval(start, 1, curve: Curves.easeOutCubic),
    );

    return FadeTransition(
      opacity: animation,
      child: SlideTransition(
        position: Tween<Offset>(
          begin: const Offset(0, .08),
          end: Offset.zero,
        ).animate(animation),
        child: child,
      ),
    );
  }
}
