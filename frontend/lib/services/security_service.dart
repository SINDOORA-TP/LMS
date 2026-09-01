import 'dart:io';
import 'package:flutter/services.dart';
import 'package:fluttertoast/fluttertoast.dart';
import 'api_service.dart';

class SecurityService {
  final ApiService _api = ApiService();
  bool _isHandlingViolation = false;

  Future<void> reportSecurityViolation(String violationType) async {
    try {
      final response = await _api.post(
        '/security-violation',
        body: {'violation_type': violationType},
      );

      if (response.success) {
        print('Security violation reported to admin: $violationType');
      } else {
        print('Failed to report violation: ${response.message}');
      }
    } catch (e) {
      print('Error reporting security violation: $e');
    }
  }

  void handleViolation(String type) async {
    // Prevent multiple simultaneous violation handlers
    if (_isHandlingViolation) return;
    _isHandlingViolation = true;

    print('SECURITY VIOLATION DETECTED: $type');

    // Show warning to user
    Fluttertoast.showToast(
      msg: "Security Violation: Screen capture is not allowed!",
      toastLength: Toast.LENGTH_LONG,
      gravity: ToastGravity.CENTER,
    );

    // Report to backend (admin will see this in the panel)
    await reportSecurityViolation(type);

    // Brief delay so toast shows and network request completes
    await Future.delayed(const Duration(milliseconds: 1500));

    // Exit the app
    if (Platform.isAndroid) {
      SystemNavigator.pop();
    } else {
      exit(0);
    }
  }
}
