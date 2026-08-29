import 'module.dart';

class Course {
  final int id;
  final String title;
  final String description;
  final String? thumbnail;
  final double price;
  final bool isFree;
  final String status;
  final String level;
  final String language;
  final int totalDuration;
  final int totalVideos;
  final int? studentCount;
  final bool? isEnrolled;
  final double? courseProgress;
  final List<Module>? modules;

  Course({
    required this.id,
    required this.title,
    required this.description,
    this.thumbnail,
    required this.price,
    required this.isFree,
    required this.status,
    required this.level,
    required this.language,
    required this.totalDuration,
    required this.totalVideos,
    this.studentCount,
    this.isEnrolled,
    this.courseProgress,
    this.modules,
  });

  factory Course.fromJson(Map<String, dynamic> json) {
    return Course(
      id: json['id'],
      title: json['title'],
      description: json['description'],
      thumbnail: json['thumbnail'],
      price: double.tryParse(json['price'].toString()) ?? 0,
      isFree: json['is_free'] == true || json['is_free'] == 1,
      status: json['status'] ?? 'draft',
      level: json['level'] ?? 'beginner',
      language: json['language'] ?? 'English',
      totalDuration: json['total_duration'] ?? 0,
      totalVideos: json['total_videos'] ?? 0,
      studentCount: json['student_count'],
      isEnrolled: json['is_enrolled'] != null
          ? (json['is_enrolled'] == true || json['is_enrolled'] == 1)
          : null,
      courseProgress: json['course_progress'] != null
          ? double.tryParse(json['course_progress'].toString())
          : null,
      modules: json['modules'] != null
          ? (json['modules'] as List).map((m) => Module.fromJson(m)).toList()
          : null,
    );
  }

  /// Format duration as "Xh Ym"
  String get formattedDuration {
    final hours = totalDuration ~/ 3600;
    final minutes = (totalDuration % 3600) ~/ 60;
    if (hours > 0) {
      return '${hours}h ${minutes}m';
    }
    return '${minutes}m';
  }

  /// Format price as "₹499" or "Free"
  String get formattedPrice {
    if (isFree || price <= 0) return 'Free';
    return '₹${price.toStringAsFixed(0)}';
  }
}
