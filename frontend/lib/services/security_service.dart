import 'dart:convert';
import 'dart:io';
import 'package:flutter/services.dart';
import 'package:fluttertoast/fluttertoast.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';

class SecurityService {
  final _storage = const FlutterSecureStorage();

  Future<void> reportSecurityViolation(String violationType) async {
    try {
      final token = await _storage.read(key: 'auth_token');
      if (token == null) return; // Can't report without auth

      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/security-violation'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );

      if (response.statusCode == 200) {
        print('Security violation reported: $violationType');
      }
    } catch (e) {
      print('Failed to report security violation: $e');
    }
  }

  void handleViolation(String type) async {
    print('SECURITY VIOLATION DETECTED: $type');
    
    // Show toast message to user
    Fluttertoast.showToast(
      msg: "Security Violation: Screen capture is not allowed.",
      toastLength: Toast.LENGTH_LONG,
      gravity: ToastGravity.BOTTOM,
    );

    // Report to backend
    await reportSecurityViolation(type);

    // Delay slightly to let the toast show and request complete, then close app
    await Future.delayed(const Duration(seconds: 2));
    
    if (Platform.isAndroid) {
      SystemNavigator.pop();
    } else {
      exit(0);
    }
  }
}
