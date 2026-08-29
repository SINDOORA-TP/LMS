import 'package:flutter/foundation.dart';

/// API configuration for connecting Flutter to Laravel backend.
class ApiConfig {
  // Change this to your Laravel server IP/URL
  // For Android emulator, use 10.0.2.2 to reach localhost
  // For physical device, use your computer's local IP (e.g., 192.168.1.x)
  static const String baseUrl = 'https://lms.abinaasananthaguruji.com/api';

  // Timeouts
  static const int connectionTimeout = 30000; // 30 seconds
  static const int receiveTimeout = 30000;

  // Video progress heartbeat interval
  static const int progressHeartbeatSeconds = 10;

  // Completion threshold
  static const double completionThreshold = 90.0;
}
