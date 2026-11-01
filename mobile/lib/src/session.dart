import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'api.dart';

const _tokenKey = 'hris-token';

/// Holds the signed-in employee's API token (kept in the Android Keystore / iOS Keychain).
class Session extends ChangeNotifier {
  Session({ApiClient? api, FlutterSecureStorage? storage})
    : api = api ?? ApiClient(),
      _storage = storage ?? const FlutterSecureStorage() {
    this.api.onUnauthorized = _forget;
  }

  final ApiClient api;
  final FlutterSecureStorage _storage;

  bool _isLoading = true;
  bool get isLoading => _isLoading;
  bool get isSignedIn => api.token != null;

  Future<void> restore() async {
    try {
      api.token = await _storage.read(key: _tokenKey);
    } catch (_) {
      api.token = null;
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<void> signIn(String email, String password) async {
    final result = await api.post('auth/token', {
      'email': email.trim(),
      'password': password,
      'device_name': '${defaultTargetPlatform.name} app',
    }) as Map<String, dynamic>;

    api.token = result['token'] as String;
    await _storage.write(key: _tokenKey, value: api.token);
    notifyListeners();
  }

  Future<void> signOut() async {
    try {
      await api.delete('auth/token');
    } catch (_) {
      // Signing out locally is enough if the server cannot be reached.
    }

    await _forget();
  }

  Future<void> _forget() async {
    api.token = null;
    await _storage.delete(key: _tokenKey);
    notifyListeners();
  }
}

/// Makes the [Session] available to every screen.
class SessionScope extends InheritedNotifier<Session> {
  const SessionScope({
    super.key,
    required Session session,
    required super.child,
  }) : super(notifier: session);

  static Session of(BuildContext context) =>
      context.dependOnInheritedWidgetOfExactType<SessionScope>()!.notifier!;
}
