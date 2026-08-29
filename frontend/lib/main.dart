import 'package:flutter/material.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';

import 'config/router.dart';
import 'config/theme.dart';
import 'providers/auth_provider.dart';
import 'providers/course_provider.dart';
import 'providers/progress_provider.dart';

import 'package:freerasp/freerasp.dart';
import 'services/security_service.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  if (const bool.fromEnvironment('dart.library.html')) {
    await Firebase.initializeApp(
      options: const FirebaseOptions(
        apiKey: "AIzaSyD-5507TYkXvfV1Vb19eriJSBZkcptjxV8",
        appId: "1:678729887692:web:8c8ff54f05378282627769",
        messagingSenderId: "678729887692",
        projectId: "lmss-a7b5c",
        authDomain: "lmss-a7b5c.firebaseapp.com",
        storageBucket: "lmss-a7b5c.firebasestorage.app",
        measurementId: "G-LFST8GJ7NP",
      ),
    );
  } else {
    await Firebase.initializeApp();
  }

  // Initialize FreeRASP Security
  final securityService = SecurityService();
  
  try {
    final talsecConfig = TalsecConfig(
      androidConfig: AndroidConfig(
        packageName: 'com.nylms.app',
        signingCertHashes: ['n4bQgYhMfWWaL+qgxVrQFaO/TxsrC4Is0V1sFbDwCgg='], // Valid Base64 placeholder
      ),
      iosConfig: IOSConfig(
        bundleIds: ['com.nylms.app'],
        teamId: 'YOUR_TEAM_ID',
      ),
      watcherMail: 'admin@lms.com',
      isProd: false, // Set to true in production
    );

    final callback = ThreatCallback(
      onScreenshot: () => securityService.handleViolation('Screenshot'),
      onScreenRecording: () => securityService.handleViolation('Screen Recording'),
    );

    Talsec.instance.attachListener(callback);
    await Talsec.instance.start(talsecConfig);
  } catch (e) {
    debugPrint('FreeRASP error: $e');
  }

  runApp(const LmsApp());
}

class LmsApp extends StatelessWidget {
  const LmsApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider(create: (_) => CourseProvider()),
        ChangeNotifierProvider(create: (_) => ProgressProvider()),
      ],
      child: MaterialApp.router(
        title: 'LMS - Learning Platform',
        debugShowCheckedModeBanner: false,
        theme: AppTheme.lightTheme,
        darkTheme: AppTheme.darkTheme,
        themeMode: ThemeMode.dark, // Default to dark mode for premium feel
        routerConfig: appRouter,
      ),
    );
  }
}
