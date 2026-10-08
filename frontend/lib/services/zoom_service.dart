import '../models/zoom_meeting.dart';
import 'api_service.dart';

/// Handles live class (Zoom) related API calls.
class ZoomService {
  final ApiService _api = ApiService();

  /// Get live classes for a specific course.
  Future<List<ZoomMeeting>> getLiveClasses(int courseId) async {
    final response = await _api.get('/courses/$courseId/live-classes');

    if (!response.success) {
      throw Exception(response.message ?? 'Failed to load live classes');
    }

    final List data = response.data is List ? response.data : [];
    return data.map((json) => ZoomMeeting.fromJson(json)).toList();
  }

  /// Get all upcoming live classes across enrolled courses.
  Future<List<ZoomMeeting>> getUpcomingClasses() async {
    final response = await _api.get('/live-classes/upcoming');

    if (!response.success) {
      throw Exception(response.message ?? 'Failed to load upcoming classes');
    }

    final List data = response.data is List ? response.data : [];
    return data.map((json) => ZoomMeeting.fromJson(json)).toList();
  }

  /// Get a single live class detail.
  Future<ZoomMeeting> getLiveClassDetail(int id) async {
    final response = await _api.get('/live-classes/$id');

    if (!response.success) {
      throw Exception(response.message ?? 'Failed to load live class');
    }

    return ZoomMeeting.fromJson(response.data);
  }
}
