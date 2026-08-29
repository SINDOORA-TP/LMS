import 'package:flutter/material.dart';
import '../models/course.dart';
import '../services/course_service.dart';

/// Manages course listing and enrollment state.
class CourseProvider extends ChangeNotifier {
  final CourseService _courseService = CourseService();

  List<Course> _courses = [];
  List<Map<String, dynamic>> _myCourses = [];
  Course? _selectedCourse;
  bool _isLoading = false;
  String? _error;

  List<Course> get courses => _courses;
  List<Map<String, dynamic>> get myCourses => _myCourses;
  Course? get selectedCourse => _selectedCourse;
  bool get isLoading => _isLoading;
  String? get error => _error;

  /// Load all published courses.
  Future<void> loadCourses({String? search, String? level, bool? isFree}) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      _courses = await _courseService.getCourses(
        search: search,
        level: level,
        isFree: isFree,
      );
      _isLoading = false;
      notifyListeners();
    } catch (e, stack) {
      debugPrint('DEBUG ERROR in loadCourses: $e');
      debugPrint(stack.toString());
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Load course detail with modules and videos.
  Future<void> loadCourseDetail(int courseId) async {
    _isLoading = true;
    _selectedCourse = null;
    _error = null;
    notifyListeners();

    try {
      _selectedCourse = await _courseService.getCourseDetail(courseId);
      _isLoading = false;
      notifyListeners();
    } catch (e, stack) {
      debugPrint('DEBUG ERROR in loadCourseDetail: $e');
      debugPrint(stack.toString());
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Enroll in a course.
  Future<Map<String, dynamic>> enrollInCourse(int courseId) async {
    try {
      final result = await _courseService.enrollInCourse(courseId);

      // If enrollment was free/successful, reload course detail
      if (!result['requiresPayment']) {
        await loadCourseDetail(courseId);
        await loadMyCourses();
      }

      return result;
    } catch (e) {
      throw Exception(e.toString());
    }
  }

  /// Load enrolled courses.
  Future<void> loadMyCourses() async {
    _isLoading = true;
    notifyListeners();

    try {
      _myCourses = await _courseService.getMyCourses();
      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Clear selected course.
  void clearSelectedCourse() {
    _selectedCourse = null;
    notifyListeners();
  }
}
