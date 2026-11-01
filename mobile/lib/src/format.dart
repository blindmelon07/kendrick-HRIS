const _months = [
  'Jan',
  'Feb',
  'Mar',
  'Apr',
  'May',
  'Jun',
  'Jul',
  'Aug',
  'Sep',
  'Oct',
  'Nov',
  'Dec',
];
const _weekdays = [
  'Monday',
  'Tuesday',
  'Wednesday',
  'Thursday',
  'Friday',
  'Saturday',
  'Sunday',
];

/// 15984.21 → "₱15,984.21"; negative values keep a leading minus.
String peso(double value) {
  final negative = value < 0;
  final fixed = value.abs().toStringAsFixed(2);
  final parts = fixed.split('.');
  final whole = parts[0].replaceAllMapped(
    RegExp(r'\B(?=(\d{3})+(?!\d))'),
    (_) => ',',
  );

  return '${negative ? '−' : ''}₱$whole.${parts[1]}';
}

/// "2026-10-05" → "Oct 5, 2026" (or "Oct 5" without the year).
String formatDate(String? value, {bool withYear = true}) {
  if (value == null || value.length < 10) {
    return '—';
  }

  final parts = value.substring(0, 10).split('-').map(int.parse).toList();

  return '${_months[parts[1] - 1]} ${parts[2]}${withYear ? ', ${parts[0]}' : ''}';
}

String dateRange(String from, String to) => from == to
    ? formatDate(from)
    : '${formatDate(from, withYear: false)} – ${formatDate(to)}';

/// 74 → "1h 14m"; 0 → "—".
String minutes(int value) {
  if (value == 0) {
    return '—';
  }

  final hours = value ~/ 60;
  final rest = value % 60;

  return hours > 0
      ? '${hours}h ${rest.toString().padLeft(2, '0')}m'
      : '${rest}m';
}

/// 12.0 → "12"; 0.5 → "0.5".
String number(double value) => value == value.roundToDouble()
    ? value.toInt().toString()
    : value.toString();

String isoDate(DateTime date) =>
    '${date.year}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';

String longDate(DateTime date) =>
    '${_weekdays[date.weekday - 1]}, ${_months[date.month - 1]} ${date.day}';

/// The semi-monthly cut-off (1st–15th or 16th–end) containing [date], shifted by [offset] cut-offs.
({String from, String to}) cutoff(DateTime date, [int offset = 0]) {
  final absolute = (date.month - 1) * 2 + (date.day <= 15 ? 0 : 1) + offset;
  final year = date.year + (absolute / 24).floor();
  final index =
      absolute % 24; // Dart's % is never negative for a positive divisor.

  final month = index ~/ 2 + 1;
  final firstHalf = index.isEven;
  final lastDay = DateTime(year, month + 1, 0).day;

  return (
    from: isoDate(DateTime(year, month, firstHalf ? 1 : 16)),
    to: isoDate(DateTime(year, month, firstHalf ? 15 : lastDay)),
  );
}

String greeting(DateTime now) => now.hour < 12
    ? 'Good morning'
    : now.hour < 18
    ? 'Good afternoon'
    : 'Good evening';
