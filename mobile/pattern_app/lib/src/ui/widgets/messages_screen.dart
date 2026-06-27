import 'package:flutter/material.dart';

import '../../theme/pattern_theme.dart';

class MessagesScreen extends StatelessWidget {
  const MessagesScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(18, 8, 18, 24),
      children: [
        Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(26),
            border: Border.all(color: PatternTheme.line),
          ),
          child: const Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Support Center',
                style: TextStyle(
                  color: PatternTheme.navy,
                  fontSize: 22,
                  fontWeight: FontWeight.w900,
                ),
              ),
              SizedBox(height: 8),
              Text(
                'Chat with Pattern support, maintenance, and guest services.',
                style: TextStyle(color: Colors.blueGrey, height: 1.5),
              ),
            ],
          ),
        ),
        const SizedBox(height: 14),
        const _MessageTile(
          title: 'Guest support',
          body: 'Your request has been received. Our team will update you shortly.',
          time: 'Now',
        ),
        const _MessageTile(
          title: 'Payment team',
          body: 'Your latest payment record is available in the app.',
          time: 'Yesterday',
        ),
      ],
    );
  }
}

class _MessageTile extends StatelessWidget {
  const _MessageTile({
    required this.title,
    required this.body,
    required this.time,
  });

  final String title;
  final String body;
  final String time;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: ListTile(
        contentPadding: const EdgeInsets.all(14),
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.w900)),
        subtitle: Padding(
          padding: const EdgeInsets.only(top: 6),
          child: Text(body),
        ),
        trailing: Text(time, style: const TextStyle(fontSize: 11, color: Colors.blueGrey)),
      ),
    );
  }
}
