import 'package:flutter/material.dart';

import 'api.dart';
import 'session.dart';
import 'theme.dart';

/// Loads data for a screen and shows loading, error and pull-to-refresh states.
class AsyncScreen<T> extends StatefulWidget {
  const AsyncScreen({super.key, required this.load, required this.builder});

  final Future<T> Function() load;
  final List<Widget> Function(
    BuildContext context,
    T data,
    Future<void> Function() reload,
  )
  builder;

  @override
  State<AsyncScreen<T>> createState() => AsyncScreenState<T>();
}

class AsyncScreenState<T> extends State<AsyncScreen<T>> {
  T? _data;
  String? _error;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    reload();
  }

  Future<void> reload() async {
    try {
      final data = await widget.load();
      if (mounted) {
        setState(() {
          _data = data;
          _error = null;
        });
      }
    } on ApiException catch (exception) {
      if (mounted) setState(() => _error = exception.message);
    } catch (_) {
      if (mounted) setState(() => _error = 'Something went wrong.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final data = _data;

    if (data == null) {
      if (_loading) {
        return const Center(child: CircularProgressIndicator());
      }

      return ErrorState(
        message: _error ?? 'Something went wrong.',
        onRetry: () {
          setState(() => _loading = true);
          reload();
        },
      );
    }

    return RefreshIndicator(
      onRefresh: reload,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
        physics: const AlwaysScrollableScrollPhysics(),
        children: _spaced(widget.builder(context, data, reload)),
      ),
    );
  }

  List<Widget> _spaced(List<Widget> children) => [
    for (var i = 0; i < children.length; i++) ...[
      if (i > 0) const SizedBox(height: 12),
      children[i],
    ],
  ];
}

class ErrorState extends StatelessWidget {
  const ErrorState({super.key, required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) => Center(
    child: Padding(
      padding: const EdgeInsets.all(32),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            Icons.warning_amber_rounded,
            size: 36,
            color: context.colors.warning,
          ),
          const SizedBox(height: 12),
          Text(message, textAlign: TextAlign.center),
          const SizedBox(height: 16),
          OutlinedButton(onPressed: onRetry, child: const Text('Try again')),
        ],
      ),
    ),
  );
}

class EmptyState extends StatelessWidget {
  const EmptyState({super.key, required this.title, this.message});

  final String title;
  final String? message;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 48, horizontal: 24),
    child: Column(
      children: [
        Text(
          title,
          style: Theme.of(context).textTheme.titleMedium
              ?.copyWith(fontWeight: FontWeight.w600),
        ),
        if (message != null) ...[
          const SizedBox(height: 8),
          Text(
            message!,
            textAlign: TextAlign.center,
            style: TextStyle(color: context.colors.textSecondary),
          ),
        ],
      ],
    ),
  );
}

/// A bordered card with consistent padding; tappable when [onTap] is set.
class InfoCard extends StatelessWidget {
  const InfoCard({super.key, required this.child, this.onTap, this.color});

  final Widget child;
  final VoidCallback? onTap;
  final Color? color;

  @override
  Widget build(BuildContext context) => Card(
    color: color,
    shape: color == null
        ? null
        : RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
            side: BorderSide(color: color!),
          ),
    clipBehavior: Clip.antiAlias,
    child: InkWell(
      onTap: onTap,
      child: Padding(padding: const EdgeInsets.all(16), child: child),
    ),
  );
}

enum BadgeTone { green, amber, red, blue, brand, gray }

class StatusBadge extends StatelessWidget {
  const StatusBadge(this.label, {super.key, this.tone = BadgeTone.gray});

  final String label;
  final BadgeTone tone;

  static BadgeTone forFiling(String status) => switch (status) {
    'pending' => BadgeTone.amber,
    'endorsed' => BadgeTone.blue,
    'approved' => BadgeTone.green,
    'denied' => BadgeTone.red,
    _ => BadgeTone.gray,
  };

  @override
  Widget build(BuildContext context) {
    final colors = context.colors;
    final (background, foreground) = switch (tone) {
      BadgeTone.green => (colors.successSoft, colors.success),
      BadgeTone.amber => (colors.warningSoft, colors.warning),
      BadgeTone.red => (colors.dangerSoft, colors.danger),
      BadgeTone.blue => (colors.infoSoft, colors.info),
      BadgeTone.brand => (
        colors.brandSoft,
        Theme.of(context).colorScheme.primary,
      ),
      BadgeTone.gray => (colors.border, colors.textSecondary),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: foreground,
          fontSize: 12,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}

class SectionTitle extends StatelessWidget {
  const SectionTitle(this.text, {super.key});

  final String text;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.only(top: 8),
    child: Text(
      text.toUpperCase(),
      style: TextStyle(
        color: context.colors.textSecondary,
        fontSize: 12,
        fontWeight: FontWeight.w600,
        letterSpacing: 0.6,
      ),
    ),
  );
}

/// Label on the left, value on the right.
class LabelValue extends StatelessWidget {
  const LabelValue(this.label, this.value, {super.key, this.strong = false});

  final String label;
  final String value;
  final bool strong;

  @override
  Widget build(BuildContext context) {
    final weight = strong ? FontWeight.w700 : FontWeight.w400;

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        children: [
          Expanded(
            child: Text(
              label,
              style: TextStyle(
                fontWeight: weight,
                color: strong ? null : context.colors.textSecondary,
              ),
            ),
          ),
          const SizedBox(width: 16),
          Text(
            value,
            style: TextStyle(
              fontWeight: weight,
              fontFeatures: const [FontFeature.tabularFigures()],
            ),
          ),
        ],
      ),
    );
  }
}

/// Small centered stat: value above, label below.
class StatTile extends StatelessWidget {
  const StatTile(this.label, this.value, {super.key});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => Column(
    children: [
      Text(
        value,
        style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w600),
      ),
      const SizedBox(height: 2),
      Text(
        label,
        style: TextStyle(fontSize: 12, color: context.colors.textSecondary),
      ),
    ],
  );
}

/// The employee's photo (loaded with the API token), or their initials when there is none.
class EmployeeAvatar extends StatelessWidget {
  const EmployeeAvatar({
    super.key,
    required this.name,
    this.photoUrl,
    this.radius = 20,
  });

  final String name;
  final String? photoUrl;
  final double radius;

  /// "Dela Cruz, Juan K." → "DJ": first letter of the surname and of the first name
  /// (multi-word surnames like Dela Cruz count as one).
  String get _initials {
    final parts = name.contains(',')
        ? name.split(',').map((part) => part.trim())
        : name.split(RegExp(r'\s+'));

    return parts
        .where((part) => part.isNotEmpty)
        .take(2)
        .map((part) => part[0].toUpperCase())
        .join();
  }

  @override
  Widget build(BuildContext context) {
    final token = SessionScope.of(context).api.token;
    final url = photoUrl;

    return CircleAvatar(
      radius: radius,
      backgroundColor: context.colors.brandSoft,
      foregroundImage: url == null
          ? null
          : NetworkImage(url, headers: {'Authorization': 'Bearer $token'}),
      // Falls back to the initials if the photo cannot be loaded.
      onForegroundImageError: url == null ? null : (_, _) {},
      child: Text(
        _initials,
        style: TextStyle(
          fontSize: radius * 0.7,
          fontWeight: FontWeight.w700,
          color: Theme.of(context).colorScheme.primary,
        ),
      ),
    );
  }
}

void showMessage(BuildContext context, String message) =>
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(message)));
