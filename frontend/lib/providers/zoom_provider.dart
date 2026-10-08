import 'package:flutter/material.dart';
import '../models/zoom_meeting.dart';
import '../services/zoom_service.dart';

/// Manages live class (Zoom meeting) state.
class ZoomProvider extends ChangeNotifier {
  final ZoomService _zoomService = ZoomService();

  List<ZoomMeeting> _upcomingClasses = [];
  List<ZoomMeeting> _courseClasses = [];
  bool _isLoading = false;
  String? _error;

  List<ZoomMeeting> get upcomingClasses => _upcomingClasses;
  List<ZoomMeeting> get courseClasses => _courseClasses;
  bool get isLoading => _isLoading;
  String? get error => _error;

  /// Load all upcoming live classes across enrolled courses.
  Future<void> loadUpcomingClasses() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      _upcomingClasses = await _zoomService.getUpcomingClasses();
      _isLoading = false;
      notifyListeners();
    } catch (e, stack) {
      debugPrint('DEBUG ERROR in loadUpcomingClasses: $e');
      debugPrint(stack.toString());
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Load live classes for a specific course.
  Future<void> loadCourseClasses(int courseId) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      _courseClasses = await _zoomService.getLiveClasses(courseId);
      _isLoading = false;
      notifyListeners();
    } catch (e, stack) {
      debugPrint('DEBUG ERROR in loadCourseClasses: $e');
      debugPrint(stack.toString());
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Clear course-specific classes.
  void clearCourseClasses() {
    _courseClasses = [];
    notifyListeners();
  }
}
