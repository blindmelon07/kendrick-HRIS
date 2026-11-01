import 'package:flutter/material.dart';

import 'src/screens/shell.dart';
import 'src/screens/sign_in.dart';
import 'src/session.dart';
import 'src/theme.dart';
import 'src/updater.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  final session = Session()..restore();

  runApp(TriStellarApp(session: session));
}

class TriStellarApp extends StatelessWidget {
  const TriStellarApp({super.key, required this.session});

  final Session session;

  @override
  Widget build(BuildContext context) {
    return SessionScope(
      session: session,
      child: MaterialApp(
        title: 'TriStellar HRIS',
        debugShowCheckedModeBanner: false,
        theme: buildTheme(Brightness.light),
        darkTheme: buildTheme(Brightness.dark),
        home: ListenableBuilder(
          listenable: session,
          builder: (context, _) {
            if (session.isLoading) {
              return const Scaffold(
                body: Center(child: CircularProgressIndicator()),
              );
            }

            return UpdateChecker(
              child: session.isSignedIn ? const Shell() : const SignInScreen(),
            );
          },
        ),
      ),
    );
  }
}
