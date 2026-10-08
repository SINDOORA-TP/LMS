import 'package:intl/intl.dart';

/// Model representing a Zoom live class session.
class ZoomMeeting {
  final int id;
  final int courseId;
  final String title;
  final String? description;
  final String? joinUrl;
  final String? password;
  final DateTime scheduledAt;
  final int duration;
  final String timezone;
  final String status;
  final bool isLive;
  final bool isUpcoming;

  // Optional course info (when fetched from /live-classes/upcoming)
  final String? courseTitle;
  final String? courseThumbnail;

  ZoomMeeting({
    required this.id,
    required this.courseId,
    required this.title,
    this.description,
    this.joinUrl,
    this.password,
    required this.scheduledAt,
    required this.duration,
    required this.timezone,
    required this.status,
    required this.isLive,
    required this.isUpcoming,
    this.courseTitle,
    this.courseThumbnail,
  });

  factory ZoomMeeting.fromJson(Map<String, dynamic> json) {
    final course = json['course'] as Map<String, dynamic>?;

    return ZoomMeeting(
      id: json['id'],
      courseId: json['course_id'],
      title: json['title'],
      description: json['description'],
      joinUrl: json['join_url'],
      password: json['password'],
      scheduledAt: DateTime.parse(json['scheduled_at']),
      duration: json['duration'] ?? 60,
      timezone: json['timezone'] ?? 'Asia/Kolkata',
      status: json['status'] ?? 'scheduled',
      isLive: json['is_live'] ?? false,
      isUpcoming: json['is_upcoming'] ?? false,
      courseTitle: course?['title'],
      courseThumbnail: course?['thumbnail'],
    );
  }

  /// Formatted date string: "Sep 3, 2026"
  String get formattedDate {
    return DateFormat('MMM d, yyyy').format(scheduledAt.toLocal());
  }

  /// Formatted time string: "8:00 PM"
  String get formattedTime {
    return DateFormat('h:mm a').format(scheduledAt.toLocal());
  }

  /// Formatted date + time: "Sep 3, 2026 at 8:00 PM"
  String get formattedDateTime {
    return '$formattedDate at $formattedTime';
  }

  /// Duration formatted: "1h 30m" or "45m"
  String get formattedDuration {
    if (duration >= 60) {
      final hours = duration ~/ 60;
      final mins = duration % 60;
      return mins > 0 ? '${hours}h ${mins}m' : '${hours}h';
    }
    return '${duration}m';
  }

  /// Time remaining until the meeting starts
  String get timeUntilStart {
    final diff = scheduledAt.difference(DateTime.now());
    if (diff.isNegative) return 'Started';
    if (diff.inDays > 0) return 'In ${diff.inDays}d';
    if (diff.inHours > 0) return 'In ${diff.inHours}h';
    if (diff.inMinutes > 0) return 'In ${diff.inMinutes}m';
    return 'Starting soon';
  }

  /// Whether the user can join this meeting
  bool get canJoin => joinUrl != null && (isLive || isUpcoming);
}
