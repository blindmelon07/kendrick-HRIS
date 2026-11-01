import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:hris/src/updater.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

MockClient releaseServer(Map<String, dynamic>? release) => MockClient(
  (request) async => release == null
      ? http.Response('Not Found', 404)
      : http.Response.bytes(utf8.encode(jsonEncode(release)), 200),
);

const latest = {
  'version': '1.1.0',
  'build': 2,
  'min_build': 1,
  'url': 'https://example.test/downloads/TriStellar-HRIS.apk?v=2',
  'sha256': 'abc123',
  'notes': 'Profile photos and in-app updates.',
};

void main() {
  test('offers a newer build', () async {
    final release = await fetchAvailableUpdate(
      1,
      client: releaseServer(latest),
    );

    expect(release?.version, '1.1.0');
    expect(release?.sha256, 'abc123');
    expect(release?.isRequiredFor(1), isFalse);
  });

  test('stays quiet when the installed build is current', () async {
    expect(
      await fetchAvailableUpdate(2, client: releaseServer(latest)),
      isNull,
    );
  });

  test('marks the update as required below the minimum build', () async {
    final release = await fetchAvailableUpdate(
      1,
      client: releaseServer({...latest, 'build': 3, 'min_build': 3}),
    );

    expect(release?.isRequiredFor(1), isTrue);
  });

  test('ignores a missing release file', () async {
    expect(await fetchAvailableUpdate(1, client: releaseServer(null)), isNull);
  });

  test('requests the release file without caching', () async {
    late Uri requested;
    final client = MockClient((request) async {
      requested = request.url;
      expect(request.headers['Cache-Control'], 'no-cache');
      return http.Response.bytes(utf8.encode(jsonEncode(latest)), 200);
    });

    await fetchAvailableUpdate(1, client: client);

    expect(requested.path, '/downloads/latest.json');
    expect(requested.queryParameters, contains('t'));
  });
}
