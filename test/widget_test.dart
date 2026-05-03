import 'dart:io';
import 'package:flutter_test/flutter_test.dart';
import 'package:academia_vault/main.dart';
import 'package:hive_flutter/hive_flutter.dart';

void main() {
  setUpAll(() async {
    // Initialize Hive for testing
    final tempDir = Directory.systemTemp.createTempSync();
    Hive.init(tempDir.path);
    await Hive.openBox('knowledge_vault');
  });

  testWidgets('App load smoke test', (WidgetTester tester) async {
    // Build our app and trigger a frame.
    // We use pump() instead of pumpAndSettle() because of infinite animations.
    await tester.pumpWidget(const AcademiaVault());
    await tester.pump(const Duration(seconds: 1));

    // Verify that the app title is present.
    expect(find.text('ACADEMIA VAULT'), findsOneWidget);
  });
}
