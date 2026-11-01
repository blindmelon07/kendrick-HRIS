import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;

/// Address of the TriStellar HRIS server. Override at build time with
/// `--dart-define=API_URL=https://your-site.example`.
const apiUrl = String.fromEnvironment(
  'API_URL',
  defaultValue: 'https://mistyrose-rook-580675.hostingersite.com',
);

class ApiException implements Exception {
  ApiException(this.message, {this.status = 0, this.errors = const {}});

  final String message;
  final int status;
  final Map<String, List<String>> errors;

  /// First validation message for a field, if any.
  String? field(String name) {
    final messages = errors[name];
    return messages == null || messages.isEmpty ? null : messages.first;
  }

  @override
  String toString() => message;
}

/// Thin client for the Laravel `/api/v1` endpoints.
class ApiClient {
  ApiClient({http.Client? client, this.onUnauthorized})
    : _client = client ?? http.Client();

  final http.Client _client;

  /// Called when the server rejects the stored token, so the app can sign out.
  void Function()? onUnauthorized;

  String? token;

  Future<dynamic> get(String path, {Map<String, String>? query}) =>
      _send('GET', path, query: query);

  Future<dynamic> post(String path, Object body) =>
      _send('POST', path, body: body);

  Future<dynamic> put(String path, Object body) =>
      _send('PUT', path, body: body);

  Future<dynamic> delete(String path) => _send('DELETE', path);

  Future<dynamic> _send(
    String method,
    String path, {
    Map<String, String>? query,
    Object? body,
  }) async {
    final uri = Uri.parse('$apiUrl/api/v1/$path')
        .replace(queryParameters: query);
    final request = http.Request(method, uri)
      ..headers.addAll({
        'Accept': 'application/json',
        if (body != null) 'Content-Type': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      });

    if (body != null) {
      request.body = jsonEncode(body);
    }

    final http.Response response;

    try {
      response = await http.Response.fromStream(
        await _client.send(request).timeout(const Duration(seconds: 30)),
      );
    } on http.ClientException {
      throw ApiException(
        'Cannot reach the HRIS server. Check your internet connection.',
      );
    } on TimeoutException {
      throw ApiException(
        'The HRIS server took too long to respond. Please try again.',
      );
    }

    if (response.statusCode == 204) {
      return null;
    }

    dynamic payload;

    try {
      payload = jsonDecode(utf8.decode(response.bodyBytes));
    } catch (_) {
      payload = null;
    }

    if (response.statusCode >= 400) {
      if (response.statusCode == 401 && token != null) {
        onUnauthorized?.call();
      }

      final map = payload is Map<String, dynamic>
          ? payload
          : const <String, dynamic>{};
      final errors = <String, List<String>>{};

      if (map['errors'] is Map) {
        (map['errors'] as Map).forEach((key, value) {
          errors['$key'] = value is List
              ? value.map((item) => '$item').toList()
              : ['$value'];
        });
      }

      throw ApiException(
        map['message'] as String? ?? 'Request failed (${response.statusCode}).',
        status: response.statusCode,
        errors: errors,
      );
    }

    return payload;
  }
}
