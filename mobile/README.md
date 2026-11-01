# TriStellar HRIS — Mobile (Flutter)

Employee kiosk app for Android and iOS. It talks to the Laravel system's `/api/v1`
endpoints with Sanctum tokens (stored in the Android Keystore / iOS Keychain).

**Features:** sign in, profile photo, today's logs, attendance per cut-off, filing
Unaccounted / Leave / Overtime / Early Out / Shift Change, withdrawing pending filings,
payslips, team endorsements for Department Heads, and in-app updates.

## Release an update

```powershell
cd C:\xampp2\htdocs\TriStellarHRIS\mobile
powershell -ExecutionPolicy Bypass -File .\release.ps1 -Version 1.2.0 -Notes "What changed"
```

This bumps the build number, builds the signed APK and writes two files in `dist\`:

- `TriStellar-HRIS.apk`
- `latest.json` (version, download URL, SHA-256 checksum, release notes)

Upload **both** to the site's `public/downloads/` folder in Hostinger's File Manager,
replacing the old ones. Installed apps check `latest.json` when opened (and when returning
to the app, at most hourly), show "Update available", download the APK, verify the checksum
and open Android's installer. Add `-Required` to block older versions until they update, or
`-SkipBuild` to rewrite `latest.json` for the APK already in `dist\`.

Android always asks the employee to confirm an install for apps that are not from the
Play Store; the first time, they may need to allow "Install unknown apps" for this app.

## Release signing key — back it up

Updates only install over the existing app when signed with the same key:

- `android\tristellar-release.jks`
- `android\key.properties` (passwords)

Both are git-ignored. Keep copies somewhere safe and private. If they are lost, every
employee must uninstall and reinstall the app.

## Develop

```powershell
flutter pub get
flutter run            # on a connected phone or emulator
flutter test
flutter analyze
```

The app connects to `https://mistyrose-rook-580675.hostingersite.com` by default. To use a
different server, pass `--dart-define=API_URL=https://your-domain` (or `-SiteUrl` to
`release.ps1`).

## iOS

Building for iPhone needs a Mac with Xcode (`flutter build ipa`) and an Apple Developer
account for TestFlight or the App Store. In-app updates are Android-only.
