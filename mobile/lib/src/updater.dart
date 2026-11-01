import 'dart:convert';
import 'dart:io' show Platform;

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:ota_update/ota_update.dart';
import 'package:package_info_plus/package_info_plus.dart';

import 'api.dart';
import 'theme.dart';

/// Published next to the APK by `release.ps1`.
const releaseInfoUrl = '$apiUrl/downloads/latest.json';

/// A published app version, from `latest.json`.
class AppRelease {
  const AppRelease({
    required this.version,
    required this.build,
    required this.minBuild,
    required this.url,
    required this.sha256,
    required this.notes,
  });

  factory AppRelease.fromJson(Map<String, dynamic> json) => AppRelease(
    version: '${json['version']}',
    build: (json['build'] as num).toInt(),
    minBuild: (json['min_build'] as num?)?.toInt() ?? 0,
    url: '${json['url']}',
    sha256: json['sha256'] as String?,
    notes: json['notes'] as String? ?? '',
  );

  final String version;
  final int build;

  /// Installed builds older than this must update before using the app.
  final int minBuild;
  final String url;
  final String? sha256;
  final String notes;

  bool isNewerThan(int installedBuild) => build > installedBuild;

  bool isRequiredFor(int installedBuild) => installedBuild < minBuild;
}

/// Fetches `latest.json` and returns it when it is newer than the installed build.
Future<AppRelease?> fetchAvailableUpdate(
  int installedBuild, {
  http.Client? client,
}) async {
  final uri = Uri.parse(
    releaseInfoUrl,
  ).replace(queryParameters: {'t': '${DateTime.now().millisecondsSinceEpoch}'});
  final response = await (client ?? http.Client())
      .get(uri, headers: {'Cache-Control': 'no-cache'})
      .timeout(const Duration(seconds: 15));

  if (response.statusCode != 200) {
    return null;
  }

  final release = AppRelease.fromJson(
    jsonDecode(utf8.decode(response.bodyBytes)) as Map<String, dynamic>,
  );

  return release.isNewerThan(installedBuild) ? release : null;
}

/// Checks for a newer APK when the app starts or returns to the foreground
/// (at most once an hour) and offers a one-tap update. Android only.
class UpdateChecker extends StatefulWidget {
  const UpdateChecker({super.key, required this.child});

  final Widget child;

  @override
  State<UpdateChecker> createState() => _UpdateCheckerState();
}

class _UpdateCheckerState extends State<UpdateChecker>
    with WidgetsBindingObserver {
  DateTime? _lastCheck;
  bool _dialogOpen = false;

  static bool get _supported => !kIsWeb && Platform.isAndroid;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    WidgetsBinding.instance.addPostFrameCallback((_) => _check());
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _check();
    }
  }

  Future<void> _check() async {
    final now = DateTime.now();

    if (!_supported ||
        _dialogOpen ||
        (_lastCheck != null &&
            now.difference(_lastCheck!) < const Duration(hours: 1))) {
      return;
    }

    _lastCheck = now;

    try {
      final installed =
          int.tryParse((await PackageInfo.fromPlatform()).buildNumber) ?? 0;
      final release = await fetchAvailableUpdate(installed);

      if (release != null && mounted) {
        _dialogOpen = true;
        await showDialog<void>(
          context: context,
          barrierDismissible: !release.isRequiredFor(installed),
          builder: (_) => UpdateDialog(
            release: release,
            required: release.isRequiredFor(installed),
          ),
        );
        _dialogOpen = false;
      }
    } catch (_) {
      // No connection or no release info yet: try again on the next resume.
      _lastCheck = null;
    }
  }

  @override
  Widget build(BuildContext context) => widget.child;
}

/// "Update available" prompt that downloads the APK, verifies its checksum and
/// opens Android's installer.
class UpdateDialog extends StatefulWidget {
  const UpdateDialog({
    super.key,
    required this.release,
    required this.required,
  });

  final AppRelease release;
  final bool required;

  @override
  State<UpdateDialog> createState() => _UpdateDialogState();
}

class _UpdateDialogState extends State<UpdateDialog> {
  bool _downloading = false;
  double? _progress;
  String? _error;

  void _update() {
    setState(() {
      _downloading = true;
      _progress = null;
      _error = null;
    });

    try {
      OtaUpdate()
          .execute(
            widget.release.url,
            destinationFilename: 'TriStellar-HRIS.apk',
            sha256checksum: widget.release.sha256,
          )
          .listen((event) {
            if (!mounted) return;

            switch (event.status) {
              case OtaStatus.DOWNLOADING:
                setState(
                  () => _progress =
                      (double.tryParse(event.value ?? '') ?? 0) / 100,
                );
              case OtaStatus.INSTALLING:
              case OtaStatus.INSTALLATION_DONE:
                if (!widget.required) Navigator.of(context).pop();
              case OtaStatus.PERMISSION_NOT_GRANTED_ERROR:
                _fail(
                  'Allow "Install unknown apps" for TriStellar HRIS in your phone settings, then try again.',
                );
              case OtaStatus.CHECKSUM_ERROR:
                _fail('The download was damaged. Please try again.');
              case OtaStatus.CANCELED:
                _fail('The update was canceled.');
              default:
                _fail(
                  'The update could not be downloaded. Check your connection and try again.',
                );
            }
          });
    } catch (_) {
      _fail('The update could not be started. Please try again.');
    }
  }

  void _fail(String message) => setState(() {
    _downloading = false;
    _error = message;
  });

  @override
  Widget build(BuildContext context) {
    final release = widget.release;
    final muted = TextStyle(color: context.colors.textSecondary);

    return PopScope(
      canPop: !widget.required && !_downloading,
      child: AlertDialog(
        title: Text(widget.required ? 'Update required' : 'Update available'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Version ${release.version} of TriStellar HRIS is ready.'),
            if (widget.required) ...[
              const SizedBox(height: 4),
              Text(
                'This update is needed to keep using the app.',
                style: muted,
              ),
            ],
            if (release.notes.isNotEmpty) ...[
              const SizedBox(height: 12),
              const Text(
                "What's new",
                style: TextStyle(fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 4),
              Text(release.notes, style: muted),
            ],
            if (_downloading) ...[
              const SizedBox(height: 16),
              LinearProgressIndicator(value: _progress == 0 ? null : _progress),
              const SizedBox(height: 6),
              Text(
                _progress == null || _progress == 0
                    ? 'Starting download…'
                    : 'Downloading… ${((_progress ?? 0) * 100).round()}%',
                style: muted,
              ),
            ],
            if (_error != null) ...[
              const SizedBox(height: 12),
              Text(_error!, style: TextStyle(color: context.colors.danger)),
            ],
          ],
        ),
        actions: [
          if (!widget.required && !_downloading)
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Later'),
            ),
          FilledButton(
            style: FilledButton.styleFrom(minimumSize: const Size(0, 44)),
            onPressed: _downloading ? null : _update,
            child: Text(_error == null ? 'Update now' : 'Try again'),
          ),
        ],
      ),
    );
  }
}
