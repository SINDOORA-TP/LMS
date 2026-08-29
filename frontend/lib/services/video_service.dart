import 'dart:async';
import 'api_service.dart';

/// Handles video access and progress tracking.
class VideoService {
  final ApiService _api = ApiService();

  /// Request video access — returns YouTube video ID if authorized.
  /// Checks auth + enrollment + unlock status on the server.
  Future<Map<String, dynamic>> getVideoAccess(int videoId) async {
    final response = await _api.get('/videos/$videoId/access');

    if (!response.success) {
      throw VideoAccessException(
        response.message ?? 'Access denied',
        response.data is Map ? response.data['error_code'] : null,
      );
    }

    return Map<String, dynamic>.from(response.data);
  }

  /// Send playback heartbeat with current position and watched ranges.
  /// Called every 10 seconds during video playback.
  Future<Map<String, dynamic>> sendProgressHeartbeat({
    required int videoId,
    required int currentPosition,
    required List<List<int>> ranges,
  }) async {
    final response = await _api.post(
      '/videos/$videoId/progress',
      body: {
        'current_position': currentPosition,
        'ranges': ranges,
      },
    );

    if (!response.success) {
      throw Exception(response.message ?? 'Failed to update progress');
    }

    return Map<String, dynamic>.from(response.data);
  }

  /// Get progress for a specific course.
  Future<double> getCourseProgress(int courseId) async {
    final response = await _api.get('/courses/$courseId/progress');

    if (!response.success) {
      throw Exception(response.message ?? 'Failed to load progress');
    }

    return double.tryParse((response.data['completion_percentage'] ?? 0).toString()) ?? 0.0;
  }

  /// Get overall progress summary.
  Future<List<Map<String, dynamic>>> getMyProgress() async {
    final response = await _api.get('/my-progress');

    if (!response.success) {
      throw Exception(response.message ?? 'Failed to load progress');
    }

    return List<Map<String, dynamic>>.from(response.data);
  }

  /// Check payment status.
  Future<Map<String, dynamic>> checkPaymentStatus(String merchantTransactionId) async {
    final response = await _api.get('/payments/$merchantTransactionId/status');

    return {
      'success': response.success,
      'message': response.message,
    };
  }
}

/// Custom exception for video access errors.
class VideoAccessException implements Exception {
  final String message;
  final String? errorCode;

  VideoAccessException(this.message, this.errorCode);

  bool get isNotEnrolled => errorCode == 'NOT_ENROLLED';
  bool get isVideoLocked => errorCode == 'VIDEO_LOCKED';

  @override
  String toString() => message;
}
