import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../config/theme.dart';
import '../../models/course.dart';
import '../../models/module.dart';
import '../../models/video.dart';
import '../../providers/course_provider.dart';
import '../../services/course_service.dart';

/// Course detail screen with modules, videos, and enrollment.
class CourseDetailScreen extends StatefulWidget {
  final int courseId;

  const CourseDetailScreen({super.key, required this.courseId});

  @override
  State<CourseDetailScreen> createState() => _CourseDetailScreenState();
}

class _CourseDetailScreenState extends State<CourseDetailScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<CourseProvider>().loadCourseDetail(widget.courseId);
    });
  }

  Future<void> _handleEnroll() async {
    try {
      final result =
          await context.read<CourseProvider>().enrollInCourse(widget.courseId);

      if (result['requiresPayment'] == true) {
        // Open PhonePe payment URL
        // TODO: Implement WebView or URL launcher for PhonePe redirect
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Redirecting to payment...')),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: const Text('Successfully enrolled! 🎉'),
            backgroundColor: AppTheme.successColor,
          ),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(e.toString()),
          backgroundColor: AppTheme.errorColor,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Consumer<CourseProvider>(
        builder: (context, provider, _) {
          if (provider.isLoading) {
            return const Center(
              child: CircularProgressIndicator(color: AppTheme.primaryColor),
            );
          }

          if (provider.error != null) {
            return Scaffold(
              appBar: AppBar(
                backgroundColor: Colors.transparent,
                leading: IconButton(
                  onPressed: () => context.pop(),
                  icon: const Icon(Icons.arrow_back),
                ),
              ),
              body: Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.error_outline,
                        color: AppTheme.errorColor, size: 48),
                    const SizedBox(height: 16),
                    Text(
                      provider.error!,
                      textAlign: TextAlign.center,
                      style: const TextStyle(color: Colors.white54),
                    ),
                    const SizedBox(height: 16),
                    ElevatedButton(
                      onPressed: () =>
                          provider.loadCourseDetail(widget.courseId),
                      child: const Text('Retry'),
                    ),
                  ],
                ),
              ),
            );
          }

          final course = provider.selectedCourse;
          if (course == null) {
            return const Center(child: Text('Course not found'));
          }

          return CustomScrollView(
            slivers: [
              // App bar with course header
              SliverAppBar(
                expandedHeight: 220,
                pinned: true,
                leading: IconButton(
                  onPressed: () => context.pop(),
                  icon: Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: Colors.black.withOpacity(0.3),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(Icons.arrow_back, color: Colors.white),
                  ),
                ),
                flexibleSpace: FlexibleSpaceBar(
                  background: Container(
                    decoration: const BoxDecoration(
                      gradient: AppTheme.primaryGradient,
                    ),
                    child: Padding(
                      padding: const EdgeInsets.fromLTRB(20, 100, 20, 20),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisAlignment: MainAxisAlignment.end,
                        children: [
                          // Level badge
                          Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 12, vertical: 4),
                            decoration: BoxDecoration(
                              color: Colors.white.withOpacity(0.2),
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: Text(
                              course.level.toUpperCase(),
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 11,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                          const SizedBox(height: 12),
                          Text(
                            course.title,
                            style: const TextStyle(
                              fontSize: 24,
                              fontWeight: FontWeight.w700,
                              color: Colors.white,
                            ),
                          ),
                          const SizedBox(height: 8),
                          Row(
                            children: [
                              Icon(Icons.play_circle_outline,
                                  color: Colors.white70, size: 16),
                              const SizedBox(width: 4),
                              Text('${course.totalVideos} videos',
                                  style: TextStyle(color: Colors.white70)),
                              const SizedBox(width: 16),
                              Icon(Icons.access_time,
                                  color: Colors.white70, size: 16),
                              const SizedBox(width: 4),
                              Text(course.formattedDuration,
                                  style: TextStyle(color: Colors.white70)),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),

              // Course progress (if enrolled)
              if (course.isEnrolled == true && course.courseProgress != null)
                SliverToBoxAdapter(
                  child: Container(
                    margin: const EdgeInsets.all(20),
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: AppTheme.darkCard,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: AppTheme.darkBorder),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text(
                              'Your Progress',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                                color: Colors.white,
                              ),
                            ),
                            Text(
                              '${course.courseProgress!.toStringAsFixed(0)}%',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w700,
                                color: AppTheme.primaryColor,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        ClipRRect(
                          borderRadius: BorderRadius.circular(6),
                          child: LinearProgressIndicator(
                            value: course.courseProgress! / 100,
                            backgroundColor: AppTheme.darkBorder,
                            color: AppTheme.primaryColor,
                            minHeight: 8,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),

              // Description
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'About this course',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w600,
                          color: Colors.white,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        course.description,
                        style: TextStyle(
                          fontSize: 14,
                          color: Colors.white.withOpacity(0.6),
                          height: 1.6,
                        ),
                      ),
                      const SizedBox(height: 24),
                      const Text(
                        'Course Content',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w600,
                          color: Colors.white,
                        ),
                      ),
                      const SizedBox(height: 12),
                    ],
                  ),
                ),
              ),

              // Modules and Videos
              if (course.modules != null)
                SliverList(
                  delegate: SliverChildBuilderDelegate(
                    (context, index) {
                      final module = course.modules![index];
                      return _ModuleTile(
                        module: module,
                        isEnrolled: course.isEnrolled == true,
                      );
                    },
                    childCount: course.modules!.length,
                  ),
                ),

              // Bottom spacing for enroll button
              const SliverToBoxAdapter(
                child: SizedBox(height: 100),
              ),
            ],
          );
        },
      ),

      // Enroll / Price button at bottom
      bottomNavigationBar: Consumer<CourseProvider>(
        builder: (context, provider, _) {
          final course = provider.selectedCourse;
          if (course == null) return const SizedBox.shrink();

          if (course.isEnrolled == true) {
            return const SizedBox.shrink(); // Already enrolled
          }

          return Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: AppTheme.darkSurface,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.3),
                  blurRadius: 10,
                  offset: const Offset(0, -5),
                ),
              ],
            ),
            child: SafeArea(
              child: Row(
                children: [
                  // Price
                  Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Price',
                        style: TextStyle(color: Colors.white54, fontSize: 12),
                      ),
                      Text(
                        course.formattedPrice,
                        style: const TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.w700,
                          color: Colors.white,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(width: 20),
                  // Enroll button
                  Expanded(
                    child: SizedBox(
                      height: 56,
                      child: ElevatedButton(
                        onPressed: _handleEnroll,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppTheme.primaryColor,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        child: Text(
                          course.isFree ? 'Enroll for Free' : 'Buy Now',
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}

/// Module tile with expandable video list.
class _ModuleTile extends StatefulWidget {
  final Module module;
  final bool isEnrolled;

  const _ModuleTile({required this.module, required this.isEnrolled});

  @override
  State<_ModuleTile> createState() => _ModuleTileState();
}

class _ModuleTileState extends State<_ModuleTile> {
  bool _isExpanded = true;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 20, vertical: 6),
      decoration: BoxDecoration(
        color: AppTheme.darkCard,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppTheme.darkBorder),
      ),
      child: Column(
        children: [
          // Module header
          GestureDetector(
            onTap: () => setState(() => _isExpanded = !_isExpanded),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  Container(
                    width: 40,
                    height: 40,
                    decoration: BoxDecoration(
                      color: AppTheme.primaryColor.withOpacity(0.15),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Center(
                      child: Text(
                        '${widget.module.order + 1}',
                        style: const TextStyle(
                          color: AppTheme.primaryColor,
                          fontWeight: FontWeight.w700,
                          fontSize: 16,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          widget.module.title,
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w600,
                            color: Colors.white,
                          ),
                        ),
                        if (widget.module.videos != null)
                          Text(
                            '${widget.module.videos!.length} lessons',
                            style: TextStyle(
                              fontSize: 13,
                              color: Colors.white.withOpacity(0.4),
                            ),
                          ),
                      ],
                    ),
                  ),
                  AnimatedRotation(
                    turns: _isExpanded ? 0.5 : 0,
                    duration: const Duration(milliseconds: 200),
                    child: const Icon(
                      Icons.keyboard_arrow_down,
                      color: Colors.white38,
                    ),
                  ),
                ],
              ),
            ),
          ),

          // Video list
          if (_isExpanded && widget.module.videos != null)
            Column(
              children: widget.module.videos!.map((video) {
                return _VideoTile(
                  video: video,
                  isEnrolled: widget.isEnrolled,
                );
              }).toList(),
            ),
        ],
      ),
    );
  }
}

/// Individual video tile with lock/unlock/completed status.
class _VideoTile extends StatelessWidget {
  final Video video;
  final bool isEnrolled;

  const _VideoTile({required this.video, required this.isEnrolled});

  @override
  Widget build(BuildContext context) {
    final isLocked = video.isLocked == true && !video.isPreview;

    return InkWell(
      onTap: isLocked
          ? () {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('Complete the previous video first (90% required)'),
                  backgroundColor: AppTheme.warningColor,
                ),
              );
            }
          : () {
              if (!isEnrolled && !video.isPreview) {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('Enroll in this course to watch'),
                    backgroundColor: AppTheme.warningColor,
                  ),
                );
                return;
              }
              context.push('/video/${video.id}');
            },
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        decoration: BoxDecoration(
          border: Border(
            top: BorderSide(color: AppTheme.darkBorder.withOpacity(0.5)),
          ),
        ),
        child: Row(
          children: [
            // Status icon
            Container(
              width: 36,
              height: 36,
              decoration: BoxDecoration(
                color: _getStatusColor().withOpacity(0.15),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(
                _getStatusIcon(),
                size: 18,
                color: _getStatusColor(),
              ),
            ),
            const SizedBox(width: 12),

            // Title and duration
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    video.title,
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w500,
                      color: isLocked ? Colors.white38 : Colors.white,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Row(
                    children: [
                      Text(
                        video.formattedDuration,
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.white.withOpacity(0.3),
                        ),
                      ),
                      if (video.isPreview) ...[
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: AppTheme.secondaryColor.withOpacity(0.2),
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: const Text(
                            'PREVIEW',
                            style: TextStyle(
                              fontSize: 9,
                              color: AppTheme.secondaryColor,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),
                  // Progress bar for partially watched videos
                  if (video.completionPercentage != null &&
                      video.completionPercentage! > 0 &&
                      video.isCompleted != true)
                    Padding(
                      padding: const EdgeInsets.only(top: 6),
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(2),
                        child: LinearProgressIndicator(
                          value: video.completionPercentage! / 100,
                          backgroundColor: AppTheme.darkBorder,
                          color: AppTheme.primaryColor,
                          minHeight: 3,
                        ),
                      ),
                    ),
                ],
              ),
            ),

            // Play icon
            if (!isLocked)
              const Icon(Icons.play_arrow_rounded,
                  color: Colors.white38, size: 24),
          ],
        ),
      ),
    );
  }

  IconData _getStatusIcon() {
    if (video.isCompleted == true) return Icons.check_circle;
    if (video.isLocked == true && !video.isPreview) return Icons.lock;
    if (video.isPreview) return Icons.play_circle_outline;
    return Icons.lock_open;
  }

  Color _getStatusColor() {
    if (video.isCompleted == true) return AppTheme.successColor;
    if (video.isLocked == true && !video.isPreview) return Colors.white38;
    if (video.isPreview) return AppTheme.secondaryColor;
    return AppTheme.primaryColor;
  }
}
