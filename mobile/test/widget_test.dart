import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hris/main.dart';
import 'package:hris/src/api.dart';
import 'package:hris/src/format.dart';
import 'package:hris/src/models.dart';
import 'package:hris/src/session.dart';
import 'package:hris/src/theme.dart';
import 'package:hris/src/widgets.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

/// JSON response encoded as UTF-8, like the Laravel server sends.
http.Response jsonResponse(Object body, int status) => http.Response.bytes(
  utf8.encode(jsonEncode(body)),
  status,
  headers: {'content-type': 'application/json; charset=utf-8'},
);

void main() {
  setUp(() => FlutterSecureStorage.setMockInitialValues({}));

  group('format', () {
    test('peso adds thousands separators and a minus for deductions', () {
      expect(peso(15984.21), '₱15,984.21');
      expect(peso(600), '₱600.00');
      expect(peso(-1341.85), '−₱1,341.85');
    });

    test('dates and minutes read naturally', () {
      expect(formatDate('2026-10-05'), 'Oct 5, 2026');
      expect(dateRange('2026-11-03', '2026-11-04'), 'Nov 3 – Nov 4, 2026');
      expect(minutes(74), '1h 14m');
      expect(minutes(0), '—');
      expect(number(0.5), '0.5');
      expect(number(12), '12');
    });

    test('cut-offs step across months and years', () {
      expect(cutoff(DateTime(2026, 10, 31)), (
        from: '2026-10-16',
        to: '2026-10-31',
      ));
      expect(cutoff(DateTime(2026, 10, 31), -1), (
        from: '2026-10-01',
        to: '2026-10-15',
      ));
      expect(cutoff(DateTime(2026, 1, 10), -1), (
        from: '2025-12-16',
        to: '2025-12-31',
      ));
      expect(cutoff(DateTime(2026, 2, 20)), (
        from: '2026-02-16',
        to: '2026-02-28',
      ));
    });
  });

  test('filing details describe the request', () {
    final filing = Filing.fromJson({
      'id': 1,
      'type': 'leave',
      'type_label': 'Leave',
      'date_from': '2026-11-03',
      'date_to': '2026-11-04',
      'is_half_day': false,
      'with_pay': true,
      'reason': 'Family event',
      'status': 'pending',
      'status_label': 'Pending',
      'can_withdraw': true,
    });

    expect(filing.detail, 'Whole day · With pay');
  });

  test('validation errors from the API are exposed per field', () async {
    final api = ApiClient(
      client: MockClient(
        (request) async => jsonResponse(({
          'message': 'The given data was invalid.',
          'errors': {
            'email': ['These credentials do not match our records.'],
          },
        }), 422),
      ),
    );

    await expectLater(
      api.post('auth/token', {
        'email': 'x@y.z',
        'password': 'no',
        'device_name': 'test',
      }),
      throwsA(
        isA<ApiException>().having(
          (e) => e.field('email'),
          'email error',
          'These credentials do not match our records.',
        ),
      ),
    );
  });

  testWidgets('avatar loads the photo with the token, or shows initials', (
    tester,
  ) async {
    final session = Session(api: ApiClient()..token = 'abc');

    Widget wrap(Widget child) => SessionScope(
      session: session,
      child: MaterialApp(
        theme: buildTheme(Brightness.light),
        home: Scaffold(body: child),
      ),
    );

    await tester.pumpWidget(
      wrap(const EmployeeAvatar(name: 'Dela Cruz, Juan K.')),
    );
    expect(find.text('DJ'), findsOneWidget);

    await tester.pumpWidget(
      wrap(
        const EmployeeAvatar(
          name: 'Dela Cruz, Juan K.',
          photoUrl: 'https://example.test/api/v1/me/photo?v=1',
        ),
      ),
    );
    final avatar = tester.widget<CircleAvatar>(find.byType(CircleAvatar));
    final image = avatar.foregroundImage! as NetworkImage;

    expect(image.url, 'https://example.test/api/v1/me/photo?v=1');
    expect(image.headers, {'Authorization': 'Bearer abc'});
  });

  testWidgets('shows the sign-in screen when no one is signed in', (
    tester,
  ) async {
    final session = Session(
      api: ApiClient(client: MockClient((_) async => http.Response('{}', 401))),
    );
    await session.restore();

    await tester.pumpWidget(TriStellarApp(session: session));
    await tester.pumpAndSettle();

    expect(find.text('Employee Kiosk'), findsOneWidget);
    expect(find.widgetWithText(FilledButton, 'Sign in'), findsOneWidget);
  });

  testWidgets('signing in stores the token and opens the home tab', (
    tester,
  ) async {
    final client = MockClient((request) async {
      final path = request.url.path;

      if (path.endsWith('auth/token')) {
        return jsonResponse(({'token': 'abc'}), 201);
      }

      if (path.endsWith('/home')) {
        return jsonResponse(({
          'data': {
            'today_logs': ['07:58'],
            'pending_filings': 1,
            'endorsements_waiting': null,
            'latest_payslip': null,
          },
        }), 200);
      }

      return jsonResponse(({
        'data': {
          'name': 'Juan Dela Cruz',
          'email': 'employee@tristellar.test',
          'employee': {
            'id': 2,
            'employee_number': 'TS-00002',
            'full_name': 'Dela Cruz, Juan K.',
            'first_name': 'Juan',
            'job_title': 'Warehouse Staff',
            'department': 'Operations',
            'branch': 'Trece Martires',
            'shift': {
              'name': 'Rank & File (7:00–4:00)',
              'start_time': '07:00',
              'end_time': '16:00',
            },
          },
          'can': {'endorse_filings': false},
        },
      }), 200);
    });

    final session = Session(api: ApiClient(client: client));
    await session.restore();
    await tester.pumpWidget(TriStellarApp(session: session));
    await tester.pumpAndSettle();

    await tester.enterText(
      find.byType(TextField).at(0),
      'employee@tristellar.test',
    );
    await tester.enterText(find.byType(TextField).at(1), 'password');
    await tester.tap(find.widgetWithText(FilledButton, 'Sign in'));
    await tester.pumpAndSettle();

    expect(session.isSignedIn, isTrue);
    expect(find.textContaining('Juan'), findsWidgets);
    expect(find.text('07:58'), findsOneWidget);
    expect(find.text('1 awaiting approval'), findsOneWidget);
  });
}
