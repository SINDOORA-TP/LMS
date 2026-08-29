import 'video.dart';

class Module {
  final int id;
  final int courseId;
  final String title;
  final String? description;
  final int order;
  final List<Video>? videos;

  Module({
    required this.id,
    required this.courseId,
    required this.title,
    this.description,
    required this.order,
    this.videos,
  });

  factory Module.fromJson(Map<String, dynamic> json) {
    return Module(
      id: json['id'],
      courseId: json['course_id'],
      title: json['title'],
      description: json['description'],
      order: json['order'] ?? 0,
      videos: json['videos'] != null
          ? (json['videos'] as List).map((v) => Video.fromJson(v)).toList()
          : null,
    );
  }
}
