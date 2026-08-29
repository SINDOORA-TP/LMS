import 'package:flutter/material.dart';
import '../../config/theme.dart';
import '../../models/course.dart';

/// Reusable course card widget used in home and my courses screens.
class CourseCard extends StatelessWidget {
  final Course course;
  final VoidCallback onTap;
  final double? progress; // Optional progress percentage

  const CourseCard({
    super.key,
    required this.course,
    required this.onTap,
    this.progress,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        decoration: BoxDecoration(
          color: AppTheme.darkCard,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: AppTheme.darkBorder),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.2),
              blurRadius: 10,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Thumbnail
            Container(
              height: 160,
              width: double.infinity,
              decoration: BoxDecoration(
                borderRadius:
                    const BorderRadius.vertical(top: Radius.circular(20)),
                gradient: LinearGradient(
                  colors: _getGradientColors(),
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
              ),
              child: Stack(
                children: [
                  // Course icon
                  Center(
                    child: Icon(
                      _getCourseIcon(),
                      size: 60,
                      color: Colors.white.withOpacity(0.3),
                    ),
                  ),
                  // Level badge
                  Positioned(
                    top: 12,
                    left: 12,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 12, vertical: 6),
                      decoration: BoxDecoration(
                        color: Colors.black.withOpacity(0.3),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Text(
                        course.level.toUpperCase(),
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                          letterSpacing: 1,
                        ),
                      ),
                    ),
                  ),
                  // Price badge
                  Positioned(
                    top: 12,
                    right: 12,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 12, vertical: 6),
                      decoration: BoxDecoration(
                        color: course.isFree
                            ? AppTheme.successColor.withOpacity(0.9)
                            : AppTheme.primaryColor.withOpacity(0.9),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Text(
                        course.formattedPrice,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // Content
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    course.title,
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w600,
                      color: Colors.white,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    course.description,
                    style: TextStyle(
                      fontSize: 13,
                      color: Colors.white.withOpacity(0.5),
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 12),

                  // Stats row
                  Row(
                    children: [
                      _StatItem(
                        icon: Icons.play_circle_outline,
                        text: '${course.totalVideos} videos',
                      ),
                      const SizedBox(width: 16),
                      _StatItem(
                        icon: Icons.access_time,
                        text: course.formattedDuration,
                      ),
                      if (course.studentCount != null) ...[
                        const SizedBox(width: 16),
                        _StatItem(
                          icon: Icons.people_outline,
                          text: '${course.studentCount}',
                        ),
                      ],
                    ],
                  ),

                  // Progress bar (if enrolled)
                  if (progress != null) ...[
                    const SizedBox(height: 12),
                    ClipRRect(
                      borderRadius: BorderRadius.circular(4),
                      child: LinearProgressIndicator(
                        value: progress! / 100,
                        backgroundColor: AppTheme.darkBorder,
                        color: progress! >= 100
                            ? AppTheme.successColor
                            : AppTheme.primaryColor,
                        minHeight: 6,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      '${progress!.toStringAsFixed(0)}% complete',
                      style: TextStyle(
                        fontSize: 12,
                        color: AppTheme.primaryColor,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  List<Color> _getGradientColors() {
    final hash = course.title.hashCode;
    final palettes = [
      [const Color(0xFF6C63FF), const Color(0xFF9D4EDD)],
      [const Color(0xFF00BFA6), const Color(0xFF00D9FF)],
      [const Color(0xFFFF6584), const Color(0xFFFF8A65)],
      [const Color(0xFF5C6BC0), const Color(0xFF42A5F5)],
      [const Color(0xFFAB47BC), const Color(0xFFEC407A)],
    ];
    return palettes[hash.abs() % palettes.length];
  }

  IconData _getCourseIcon() {
    final title = course.title.toLowerCase();
    if (title.contains('python')) return Icons.code;
    if (title.contains('web')) return Icons.language;
    if (title.contains('flutter') || title.contains('mobile')) return Icons.phone_android;
    if (title.contains('data')) return Icons.bar_chart;
    if (title.contains('design')) return Icons.palette;
    return Icons.school;
  }
}

class _StatItem extends StatelessWidget {
  final IconData icon;
  final String text;

  const _StatItem({required this.icon, required this.text});

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 16, color: Colors.white38),
        const SizedBox(width: 4),
        Text(
          text,
          style: const TextStyle(fontSize: 12, color: Colors.white38),
        ),
      ],
    );
  }
}
