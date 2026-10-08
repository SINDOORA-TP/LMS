import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../config/theme.dart';
import '../models/zoom_meeting.dart';

/// Reusable card widget for displaying a live class (Zoom meeting).
/// Used in both the Live Classes screen and Course Detail screen.
class LiveClassCard extends StatelessWidget {
  final ZoomMeeting meeting;
  final bool showCourseName;

  const LiveClassCard({
    super.key,
    required this.meeting,
    this.showCourseName = false,
  });

  Future<void> _joinMeeting(BuildContext context) async {
    if (meeting.joinUrl == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Enroll in this course to join the live class'),
          backgroundColor: AppTheme.warningColor,
        ),
      );
      return;
    }

    final uri = Uri.parse(meeting.joinUrl!);
    try {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Could not open Zoom: $e'),
            backgroundColor: AppTheme.errorColor,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 20, vertical: 6),
      decoration: BoxDecoration(
        gradient: meeting.isLive
            ? LinearGradient(
                colors: [
                  AppTheme.primaryColor.withOpacity(0.15),
                  AppTheme.accentColor.withOpacity(0.08),
                ],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              )
            : null,
        color: meeting.isLive ? null : AppTheme.darkCard,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: meeting.isLive
              ? AppTheme.accentColor.withOpacity(0.4)
              : AppTheme.darkBorder,
          width: meeting.isLive ? 1.5 : 1,
        ),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Top row: status badge + duration
            Row(
              children: [
                _StatusBadge(meeting: meeting),
                const Spacer(),
                Icon(
                  Icons.access_time_rounded,
                  size: 14,
                  color: Colors.white.withOpacity(0.4),
                ),
                const SizedBox(width: 4),
                Text(
                  meeting.formattedDuration,
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.white.withOpacity(0.4),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),

            // Title
            Text(
              meeting.title,
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
                color: Colors.white,
              ),
            ),

            // Course name (if showing across courses)
            if (showCourseName && meeting.courseTitle != null) ...[
              const SizedBox(height: 4),
              Row(
                children: [
                  Icon(
                    Icons.school_rounded,
                    size: 14,
                    color: AppTheme.secondaryColor.withOpacity(0.7),
                  ),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      meeting.courseTitle!,
                      style: TextStyle(
                        fontSize: 13,
                        color: AppTheme.secondaryColor.withOpacity(0.7),
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
            ],

            // Description
            if (meeting.description != null &&
                meeting.description!.isNotEmpty) ...[
              const SizedBox(height: 6),
              Text(
                meeting.description!,
                style: TextStyle(
                  fontSize: 13,
                  color: Colors.white.withOpacity(0.4),
                  height: 1.4,
                ),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            ],

            const SizedBox(height: 12),

            // Bottom row: date/time + join button
            Row(
              children: [
                // Date & Time
                Expanded(
                  child: Row(
                    children: [
                      Icon(
                        Icons.calendar_today_rounded,
                        size: 14,
                        color: Colors.white.withOpacity(0.5),
                      ),
                      const SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          meeting.isLive
                              ? '🔴 Live Now'
                              : meeting.formattedDateTime,
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight:
                                meeting.isLive ? FontWeight.w600 : FontWeight.w400,
                            color: meeting.isLive
                                ? AppTheme.accentColor
                                : Colors.white.withOpacity(0.5),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),

                // Join button
                if (meeting.canJoin)
                  SizedBox(
                    height: 36,
                    child: ElevatedButton.icon(
                      onPressed: () => _joinMeeting(context),
                      icon: Icon(
                        meeting.isLive
                            ? Icons.videocam_rounded
                            : Icons.event_rounded,
                        size: 16,
                      ),
                      label: Text(
                        meeting.isLive ? 'Join Now' : 'Join',
                        style: const TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: meeting.isLive
                            ? AppTheme.accentColor
                            : AppTheme.primaryColor,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
                        elevation: 0,
                      ),
                    ),
                  )
                else if (meeting.joinUrl == null &&
                    (meeting.isLive || meeting.isUpcoming))
                  Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.05),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      'Enroll to join',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.white.withOpacity(0.4),
                      ),
                    ),
                  ),
              ],
            ),

            // Countdown for upcoming meetings
            if (meeting.isUpcoming && !meeting.isLive) ...[
              const SizedBox(height: 8),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: AppTheme.primaryColor.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  meeting.timeUntilStart,
                  style: const TextStyle(
                    fontSize: 11,
                    color: AppTheme.primaryColor,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

/// Status badge showing meeting state.
class _StatusBadge extends StatelessWidget {
  final ZoomMeeting meeting;

  const _StatusBadge({required this.meeting});

  @override
  Widget build(BuildContext context) {
    final String label;
    final Color color;
    final IconData icon;

    if (meeting.isLive) {
      label = 'LIVE';
      color = AppTheme.accentColor;
      icon = Icons.circle;
    } else if (meeting.status == 'scheduled') {
      label = 'UPCOMING';
      color = AppTheme.primaryColor;
      icon = Icons.schedule_rounded;
    } else if (meeting.status == 'ended') {
      label = 'ENDED';
      color = Colors.white38;
      icon = Icons.check_circle_outline;
    } else if (meeting.status == 'cancelled') {
      label = 'CANCELLED';
      color = AppTheme.errorColor;
      icon = Icons.cancel_outlined;
    } else {
      label = meeting.status.toUpperCase();
      color = Colors.white38;
      icon = Icons.info_outline;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withOpacity(0.15),
        borderRadius: BorderRadius.circular(6),
        border: meeting.isLive
            ? Border.all(color: color.withOpacity(0.4))
            : null,
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (meeting.isLive)
            _PulsingDot(color: color)
          else
            Icon(icon, size: 12, color: color),
          const SizedBox(width: 4),
          Text(
            label,
            style: TextStyle(
              fontSize: 10,
              fontWeight: FontWeight.w700,
              color: color,
              letterSpacing: 0.5,
            ),
          ),
        ],
      ),
    );
  }
}

/// Animated pulsing dot for live indicator.
class _PulsingDot extends StatefulWidget {
  final Color color;
  const _PulsingDot({required this.color});

  @override
  State<_PulsingDot> createState() => _PulsingDotState();
}

class _PulsingDotState extends State<_PulsingDot>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1200),
    )..repeat(reverse: true);
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _controller,
      builder: (context, child) {
        return Container(
          width: 8,
          height: 8,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: widget.color.withOpacity(0.5 + _controller.value * 0.5),
            boxShadow: [
              BoxShadow(
                color: widget.color.withOpacity(0.3 * _controller.value),
                blurRadius: 6,
                spreadRadius: 1,
              ),
            ],
          ),
        );
      },
    );
  }
}
