import 'package:flutter/material.dart';

import 'theme/pattern_theme.dart';
import 'ui/login_screen.dart';

class PatternApp extends StatelessWidget {
  const PatternApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'Pattern',
      theme: PatternTheme.light(),
      home: const LoginScreen(),
    );
  }
}
