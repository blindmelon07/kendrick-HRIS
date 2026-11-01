import 'package:flutter/material.dart';

import '../api.dart';
import '../format.dart';
import '../models.dart';
import '../session.dart';
import '../theme.dart';
import '../widgets.dart';

const _guidance = {
  'unaccounted': 'Missing time in or out — field work, approved WFH, or the reader was offline.',
  'leave': 'Half-day leave counts as 0.5 day worked + 0.5 day leave. Extra hours beyond the half day are not paid.',
  'overtime': 'Work beyond your shift. Per Biyahe drivers cannot claim OT on a trip day.',
  'early_out':
      'Leaving before the end of your shift. Undertime is still deducted.',
  'shift_change': 'Work a different schedule for the day.',
};

/// Form for Unaccounted, Leave, Overtime, Early Out and Shift Change requests.
/// Pops with `true` once a filing is submitted.
class NewFilingScreen extends StatefulWidget {
  const NewFilingScreen({super.key});

  @override
  State<NewFilingScreen> createState() => _NewFilingScreenState();
}

class _NewFilingScreenState extends State<NewFilingScreen> {
  final _reason = TextEditingController();

  List<Option> _types = const [];
  List<Option> _shifts = const [];
  String? _loadError;

  String _type = 'unaccounted';
  DateTime _dateFrom = DateTime.now();
  DateTime _dateTo = DateTime.now();
  TimeOfDay? _timeFrom;
  TimeOfDay? _timeTo;
  bool _isHalfDay = false;
  bool _withPay = true;
  String? _shiftId;

  ApiException? _error;
  bool _submitting = false;

  bool get _isLeave => _type == 'leave';
  bool get _multiDay => _isLeave && !_isHalfDay;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_types.isEmpty && _loadError == null) _loadOptions();
  }

  @override
  void dispose() {
    _reason.dispose();
    super.dispose();
  }

  Future<void> _loadOptions() async {
    try {
      final json = await SessionScope.of(
        context,
      ).api.get('filings') as Map<String, dynamic>;
      final meta = json['meta'] as Map<String, dynamic>;
      List<Option> options(String key) => (meta[key] as List)
          .map((item) => Option.fromJson(item as Map<String, dynamic>))
          .toList();

      setState(() {
        _types = options('types');
        _shifts = options('shifts');
      });
    } on ApiException catch (exception) {
      setState(() => _loadError = exception.message);
    }
  }

  String _time(TimeOfDay? time) => time == null
      ? ''
      : '${time.hour.toString().padLeft(2, '0')}:${time.minute.toString().padLeft(2, '0')}';

  Future<void> _submit() async {
    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      await SessionScope.of(context).api.post('filings', {
        'type': _type,
        'date_from': isoDate(_dateFrom),
        'date_to': isoDate(_multiDay ? _dateTo : _dateFrom),
        'time_from': _timeFrom == null ? null : _time(_timeFrom),
        'time_to': _timeTo == null ? null : _time(_timeTo),
        'is_half_day': _isLeave && _isHalfDay,
        'with_pay': _isLeave && _withPay,
        'shift_id': _type == 'shift_change' ? _shiftId : null,
        'reason': _reason.text.trim(),
      });

      if (mounted) {
        showMessage(context, 'Filing submitted for approval.');
        Navigator.of(context).pop(true);
      }
    } on ApiException catch (exception) {
      if (mounted) {
        setState(() {
          _error = exception;
          _submitting = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('New Filing')),
      body: _loadError != null
          ? ErrorState(
              message: _loadError!,
              onRetry: () {
                setState(() => _loadError = null);
                _loadOptions();
              },
            )
          : _types.isEmpty
          ? const Center(child: CircularProgressIndicator())
          : SafeArea(child: _form(context)),
    );
  }

  Widget _form(BuildContext context) {
    final muted = TextStyle(fontSize: 12, color: context.colors.textSecondary);

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        const _Label('Type'),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [
            for (final option in _types)
              ChoiceChip(
                label: Text(option.label),
                selected: _type == option.value,
                showCheckmark: false,
                onSelected: (_) => setState(() {
                  _type = option.value;
                  _error = null;
                }),
              ),
          ],
        ),
        const SizedBox(height: 6),
        Text(_guidance[_type] ?? '', style: muted),
        _FieldError(_error?.field('type')),
        const SizedBox(height: 16),
        Row(
          children: [
            Expanded(
              child: _PickerField(
                label: _multiDay ? 'From' : 'Date',
                value: formatDate(isoDate(_dateFrom)),
                error: _error?.field('date_from'),
                onTap: () async {
                  final picked = await _pickDate(_dateFrom);
                  if (picked != null) setState(() => _dateFrom = picked);
                },
              ),
            ),
            if (_multiDay) ...[
              const SizedBox(width: 8),
              Expanded(
                child: _PickerField(
                  label: 'To',
                  value: formatDate(isoDate(_dateTo)),
                  error: _error?.field('date_to'),
                  onTap: () async {
                    final picked = await _pickDate(_dateTo);
                    if (picked != null) setState(() => _dateTo = picked);
                  },
                ),
              ),
            ],
          ],
        ),
        if (_isLeave) ...[
          const SizedBox(height: 8),
          SwitchListTile(
            contentPadding: EdgeInsets.zero,
            title: const Text('Half day'),
            value: _isHalfDay,
            onChanged: (value) => setState(() => _isHalfDay = value),
          ),
          SwitchListTile(
            contentPadding: EdgeInsets.zero,
            title: const Text('With pay (subject to HR approval)'),
            value: _withPay,
            onChanged: (value) => setState(() => _withPay = value),
          ),
        ],
        if (_type == 'unaccounted' || _type == 'overtime') ...[
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: _PickerField(
                  label: _type == 'unaccounted' ? 'Time in' : 'Start',
                  value: _time(_timeFrom).isEmpty ? '--:--' : _time(_timeFrom),
                  error: _error?.field('time_from'),
                  onTap: () async {
                    final picked = await _pickTime(_timeFrom);
                    if (picked != null) setState(() => _timeFrom = picked);
                  },
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _PickerField(
                  label: _type == 'unaccounted' ? 'Time out' : 'End',
                  value: _time(_timeTo).isEmpty ? '--:--' : _time(_timeTo),
                  error: _error?.field('time_to'),
                  onTap: () async {
                    final picked = await _pickTime(_timeTo);
                    if (picked != null) setState(() => _timeTo = picked);
                  },
                ),
              ),
            ],
          ),
        ],
        if (_type == 'early_out') ...[
          const SizedBox(height: 16),
          _PickerField(
            label: 'Time out',
            value: _time(_timeFrom).isEmpty ? '--:--' : _time(_timeFrom),
            error: _error?.field('time_from'),
            onTap: () async {
              final picked = await _pickTime(_timeFrom);
              if (picked != null) setState(() => _timeFrom = picked);
            },
          ),
        ],
        if (_type == 'shift_change') ...[
          const SizedBox(height: 16),
          const _Label('Shift'),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              for (final shift in _shifts)
                ChoiceChip(
                  label: Text(shift.label),
                  selected: _shiftId == shift.value,
                  showCheckmark: false,
                  onSelected: (_) => setState(() => _shiftId = shift.value),
                ),
            ],
          ),
          _FieldError(_error?.field('shift_id')),
        ],
        const SizedBox(height: 16),
        TextField(
          controller: _reason,
          minLines: 3,
          maxLines: 5,
          onChanged: (_) => setState(() {}),
          decoration: InputDecoration(
            labelText: 'Reason',
            hintText: 'Explain briefly',
            errorText: _error?.field('reason'),
          ),
        ),
        if (_error != null && _error!.errors.isEmpty) ...[
          const SizedBox(height: 8),
          Text(_error!.message, style: TextStyle(color: context.colors.danger)),
        ],
        const SizedBox(height: 24),
        FilledButton(
          onPressed: _submitting || _reason.text.trim().isEmpty
              ? null
              : _submit,
          child: _submitting
              ? const SizedBox(
                  width: 22,
                  height: 22,
                  child: CircularProgressIndicator(strokeWidth: 2.5),
                )
              : const Text('Submit filing'),
        ),
        const SizedBox(height: 12),
        Text(
          'Goes to your Dept. Head first, then to the HR Time Keeper.',
          textAlign: TextAlign.center,
          style: muted,
        ),
      ],
    );
  }

  Future<DateTime?> _pickDate(DateTime initial) {
    final now = DateTime.now();
    return showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(now.year - 1),
      lastDate: DateTime(now.year + 1),
    );
  }

  Future<TimeOfDay?> _pickTime(TimeOfDay? initial) => showTimePicker(
    context: context,
    initialTime: initial ?? TimeOfDay.now(),
    builder: (context, child) => MediaQuery(
      data: MediaQuery.of(context).copyWith(alwaysUse24HourFormat: true),
      child: child!,
    ),
  );
}

class _Label extends StatelessWidget {
  const _Label(this.text);

  final String text;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.only(bottom: 8),
    child: Text(
      text,
      style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
    ),
  );
}

class _FieldError extends StatelessWidget {
  const _FieldError(this.message);

  final String? message;

  @override
  Widget build(BuildContext context) => message == null
      ? const SizedBox.shrink()
      : Padding(
          padding: const EdgeInsets.only(top: 4),
          child: Text(
            message!,
            style: TextStyle(fontSize: 12, color: context.colors.danger),
          ),
        );
}

/// Read-only field that opens a date or time picker.
class _PickerField extends StatelessWidget {
  const _PickerField({
    required this.label,
    required this.value,
    required this.onTap,
    this.error,
  });

  final String label;
  final String value;
  final VoidCallback onTap;
  final String? error;

  @override
  Widget build(BuildContext context) => InkWell(
    onTap: onTap,
    borderRadius: BorderRadius.circular(12),
    child: InputDecorator(
      decoration: InputDecoration(
        labelText: label,
        errorText: error,
        errorMaxLines: 2,
      ),
      child: Text(value, style: const TextStyle(fontSize: 16)),
    ),
  );
}
