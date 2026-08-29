import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../providers/auth_provider.dart';
import '../screens/splash/splash_screen.dart';
import '../screens/login/login_screen.dart';
import '../screens/register/register_screen.dart';
import '../screens/home/home_screen.dart';
import '../screens/courses/course_detail_screen.dart';
import '../screens/my_courses/my_courses_screen.dart';
import '../screens/video_player/video_player_screen.dart';
import '../screens/profile/profile_screen.dart';
import '../screens/main_shell.dart';

final appRouter = GoRouter(
  initialLocation: '/splash',
  routes: [
    // Splash (auth check)
    GoRoute(
      path: '/splash',
      builder: (context, state) => const SplashScreen(),
    ),

    // Login
    GoRoute(
      path: '/login',
      builder: (context, state) => const LoginScreen(),
    ),

    // Register
    GoRoute(
      path: '/register',
      builder: (context, state) => const RegisterScreen(),
    ),

    // Main app with bottom navigation
    ShellRoute(
      builder: (context, state, child) => MainShell(child: child),
      routes: [
        GoRoute(
          path: '/home',
          builder: (context, state) => const HomeScreen(),
        ),
        GoRoute(
          path: '/my-courses',
          builder: (context, state) => const MyCoursesScreen(),
        ),
        GoRoute(
          path: '/profile',
          builder: (context, state) => const ProfileScreen(),
        ),
      ],
    ),

    // Course detail (outside shell for full screen)
    GoRoute(
      path: '/course/:id',
      builder: (context, state) {
        final courseId = int.parse(state.pathParameters['id']!);
        return CourseDetailScreen(courseId: courseId);
      },
    ),

    // Video player (full screen)
    GoRoute(
      path: '/video/:id',
      builder: (context, state) {
        final videoId = int.parse(state.pathParameters['id']!);
        return VideoPlayerScreen(videoId: videoId);
      },
    ),
  ],
);
