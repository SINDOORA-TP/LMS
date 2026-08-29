import '../models/course.dart';
import 'api_service.dart';

/// Handles course-related API calls.
class CourseService {
  final ApiService _api = ApiService();

  /// Get all published courses.
  Future<List<Course>> getCourses({String? search, String? level, bool? isFree}) async {
    var endpoint = '/courses?';
    if (search != null) endpoint += 'search=$search&';
    if (level != null) endpoint += 'level=$level&';
    if (isFree != null) endpoint += 'is_free=$isFree&';

    final response = await _api.get(endpoint, requireAuth: false);

    if (!response.success) {
      throw Exception(response.message ?? 'Failed to load courses');
    }

    final List data = response.data['data'] ?? response.data;
    return data.map((json) => Course.fromJson(json)).toList();
  }

  /// Get a single course with modules and videos.
  Future<Course> getCourseDetail(int courseId) async {
    final response = await _api.get('/courses/$courseId');

    if (!response.success) {
      throw Exception(response.message ?? 'Failed to load course');
    }

    return Course.fromJson(response.data);
  }

  /// Enroll in a course.
  /// Returns a redirect URL for PhonePe if payment is required.
  Future<Map<String, dynamic>> enrollInCourse(int courseId) async {
    final response = await _api.post('/courses/$courseId/enroll');

    if (!response.success) {
      throw Exception(response.message ?? 'Failed to enroll');
    }

    return {
      'requiresPayment': response.data['requires_payment'] ?? false,
      'redirectUrl': response.data['redirect_url'],
      'merchantTransactionId': response.data['merchant_transaction_id'],
    };
  }

  /// Get enrolled courses with progress.
  Future<List<Map<String, dynamic>>> getMyCourses() async {
    final response = await _api.get('/my-courses');

    if (!response.success) {
      throw Exception(response.message ?? 'Failed to load enrolled courses');
    }

    return List<Map<String, dynamic>>.from(response.data);
  }
}
