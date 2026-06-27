import 'package:flutter_test/flutter_test.dart';

import 'package:pattern_mobile/src/pattern_app.dart';

void main() {
  testWidgets('Pattern app shows animated login shell', (WidgetTester tester) async {
    await tester.pumpWidget(const PatternApp());
    await tester.pumpAndSettle();

    expect(find.text('Pattern Vacation Homes'), findsOneWidget);
    expect(find.text('Tenant'), findsOneWidget);
    expect(find.text('Owner'), findsOneWidget);
    expect(find.text('Continue'), findsOneWidget);

    await tester.tap(find.text('Owner'));
    await tester.pumpAndSettle();

    expect(find.text('Owner'), findsOneWidget);
  });
}
