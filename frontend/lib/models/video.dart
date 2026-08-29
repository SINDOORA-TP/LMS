class Video {
  final int id;
  final int moduleId;
  final String title;
  final String? description;
  final String? youtubeVideoId; // Only available after access check
  final int duration;
  final int order;
  final bool isPreview;
  final bool? isLocked;
  final bool? isCompleted;
  final double? completionPercentage;
  final int? watchedSeconds;

  Video({
    required this.id,
    required this.moduleId,
    required this.title,
    this.description,
    this.youtubeVideoId,
    required this.duration,
    required this.order,
    required this.isPreview,
    this.isLocked,
    this.isCompleted,
    this.completionPercentage,
    this.watchedSeconds,
  });

  factory Video.fromJson(Map<String, dynamic> json) {
    return Video(
      id: json['id'],
      moduleId: json['module_id'],
      title: json['title'],
      description: json['description'],
      youtubeVideoId: json['youtube_video_id'],
      duration: json['duration'] ?? 0,
      order: json['order'] ?? 0,
      isPreview: json['is_preview'] == true || json['is_preview'] == 1,
      isLocked: json['is_locked'] != null
          ? (json['is_locked'] == true || json['is_locked'] == 1)
          : null,
      isCompleted: json['is_completed'] != null
          ? (json['is_completed'] == true || json['is_completed'] == 1)
          : null,
      completionPercentage: json['completion_percentage'] != null
          ? double.tryParse(json['completion_percentage'].toString())
          : null,
      watchedSeconds: json['watched_seconds'],
    );
  }

  /// Format duration as "12:30"
  String get formattedDuration {
    final minutes = duration ~/ 60;
    final seconds = duration % 60;
    return '${minutes}:${seconds.toString().padLeft(2, '0')}';
  }

  /// Video status icon
  String get statusEmoji {
    if (isCompleted == true) return '✅';
    if (isLocked == true) return '🔒';
    if (isPreview) return '▶️';
    return '🔓';
  }
}
