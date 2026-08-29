import 'package:flutter/material.dart';
import '../services/video_service.dart';

/// Manages video progress state.
class ProgressProvider extends ChangeNotifier {
  final VideoService _videoService = VideoService();

  List<Map<String, dynamic>> _overallProgress = [];
  bool _isLoading = false;

  List<Map<String, dynamic>> get overallProgress => _overallProgress;
  bool get isLoading => _isLoading;

  /// Load overall progress for all enrolled courses.
  Future<void> loadMyProgress() async {
    _isLoading = true;
    notifyListeners();

    try {
      _overallProgress = await _videoService.getMyProgress();
      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _isLoading = false;
      notifyListeners();
    }
  }
}
