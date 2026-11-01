import 'package:flutter/material.dart';

/// TriStellar brand colors (from the "Bigas ng Bayan" logo) for light and dark mode.
@immutable
class AppColors extends ThemeExtension<AppColors> {
  const AppColors({
    required this.textSecondary,
    required this.border,
    required this.brandSoft,
    required this.success,
    required this.successSoft,
    required this.warning,
    required this.warningSoft,
    required this.danger,
    required this.dangerSoft,
    required this.info,
    required this.infoSoft,
  });

  final Color textSecondary;
  final Color border;
  final Color brandSoft;
  final Color success;
  final Color successSoft;
  final Color warning;
  final Color warningSoft;
  final Color danger;
  final Color dangerSoft;
  final Color info;
  final Color infoSoft;

  static const light = AppColors(
    textSecondary: Color(0xFF5F6877),
    border: Color(0xFFE2E5EA),
    brandSoft: Color(0xFFFDEEDD),
    success: Color(0xFF047857),
    successSoft: Color(0xFFD1FAE5),
    warning: Color(0xFFB45309),
    warningSoft: Color(0xFFFEF3C7),
    danger: Color(0xFFB91C1C),
    dangerSoft: Color(0xFFFEE2E2),
    info: Color(0xFF1D4ED8),
    infoSoft: Color(0xFFDBEAFE),
  );

  static const dark = AppColors(
    textSecondary: Color(0xFF98A2B1),
    border: Color(0xFF2A313C),
    brandSoft: Color(0xFF3A2614),
    success: Color(0xFF34D399),
    successSoft: Color(0xFF063B2C),
    warning: Color(0xFFFBBF24),
    warningSoft: Color(0xFF3D2E0A),
    danger: Color(0xFFF87171),
    dangerSoft: Color(0xFF3F1414),
    info: Color(0xFF93C5FD),
    infoSoft: Color(0xFF172554),
  );

  @override
  AppColors copyWith() => this;

  @override
  AppColors lerp(ThemeExtension<AppColors>? other, double t) => this;
}

extension AppColorsX on BuildContext {
  AppColors get colors => Theme.of(this).extension<AppColors>()!;
}

const _brandLight = Color(0xFFC2570C);
const _brandDark = Color(0xFFF59E42);

ThemeData buildTheme(Brightness brightness) {
  final isDark = brightness == Brightness.dark;
  final brand = isDark ? _brandDark : _brandLight;
  final background = isDark ? const Color(0xFF0D1015) : const Color(0xFFF6F7F9);
  final surface = isDark ? const Color(0xFF151A21) : Colors.white;
  final text = isDark ? const Color(0xFFE9EDF2) : const Color(0xFF161A21);
  final colors = isDark ? AppColors.dark : AppColors.light;

  final scheme =
      ColorScheme.fromSeed(
        seedColor: _brandLight,
        brightness: brightness,
      ).copyWith(
        primary: brand,
        onPrimary: isDark ? const Color(0xFF0D1015) : Colors.white,
        surface: surface,
        onSurface: text,
        error: colors.danger,
      );

  final inputBorder = OutlineInputBorder(
    borderRadius: BorderRadius.circular(12),
    borderSide: BorderSide(color: colors.border),
  );

  return ThemeData(
    useMaterial3: true,
    brightness: brightness,
    colorScheme: scheme,
    scaffoldBackgroundColor: background,
    extensions: [colors],
    appBarTheme: AppBarTheme(
      backgroundColor: surface,
      foregroundColor: text,
      surfaceTintColor: Colors.transparent,
      elevation: 0,
      centerTitle: false,
    ),
    cardTheme: CardThemeData(
      color: surface,
      elevation: 0,
      margin: EdgeInsets.zero,
      surfaceTintColor: Colors.transparent,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: colors.border),
      ),
    ),
    navigationBarTheme: NavigationBarThemeData(
      backgroundColor: surface,
      indicatorColor: colors.brandSoft,
      surfaceTintColor: Colors.transparent,
      iconTheme: WidgetStateProperty.resolveWith(
        (states) => IconThemeData(
          color: states.contains(WidgetState.selected)
              ? brand
              : colors.textSecondary,
        ),
      ),
      labelTextStyle: WidgetStateProperty.resolveWith(
        (states) => TextStyle(
          fontSize: 12,
          fontWeight: states.contains(WidgetState.selected)
              ? FontWeight.w600
              : FontWeight.w500,
          color: states.contains(WidgetState.selected)
              ? brand
              : colors.textSecondary,
        ),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: surface,
      border: inputBorder,
      enabledBorder: inputBorder,
      focusedBorder: inputBorder.copyWith(
        borderSide: BorderSide(color: brand, width: 2),
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        minimumSize: const Size.fromHeight(48),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        minimumSize: const Size.fromHeight(48),
        foregroundColor: text,
        side: BorderSide(color: colors.border),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
      ),
    ),
    chipTheme: ChipThemeData(
      selectedColor: colors.brandSoft,
      side: BorderSide(color: colors.border),
      labelStyle: TextStyle(color: text, fontWeight: FontWeight.w600),
      shape: const StadiumBorder(),
    ),
    dividerTheme: DividerThemeData(color: colors.border, space: 1),
  );
}
