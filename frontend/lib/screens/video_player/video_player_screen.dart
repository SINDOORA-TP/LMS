import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import 'package:video_player/video_player.dart';
import 'package:youtube_explode_dart/youtube_explode_dart.dart' as yt_exp;
import 'package:wakelock_plus/wakelock_plus.dart';
import '../../config/api_config.dart';
import '../../config/theme.dart';
import '../../services/video_service.dart';

/// Full-screen native video player with progress tracking.
class VideoPlayerScreen extends StatefulWidget {
  final int videoId;

  const VideoPlayerScreen({super.key, required this.videoId});

  @override
  State<VideoPlayerScreen> createState() => _VideoPlayerScreenState();
}

class _VideoPlayerScreenState extends State<VideoPlayerScreen> {
  final VideoService _videoService = VideoService();

  VideoPlayerController? _videoPlayerController;
  Timer? _heartbeatTimer;
  Timer? _controlsTimer;

  bool _isLoading = true;
  String? _error;
  String? _videoTitle;
  int _videoDuration = 0;
  bool _showControls = true;
  bool _isFullscreen = false;

  // Progress tracking
  final List<List<int>> _watchedRanges = [];
  int _lastRecordedPosition = 0;
  int _rangeStart = 0;
  bool _isTracking = false;

  @override
  void initState() {
    super.initState();
    // Force landscape for video
    SystemChrome.setPreferredOrientations([
      DeviceOrientation.landscapeLeft,
      DeviceOrientation.landscapeRight,
      DeviceOrientation.portraitUp,
    ]);
    _loadVideo();
  }

  Future<void> _loadVideo() async {
    try {
      final videoData = await _videoService.getVideoAccess(widget.videoId);
      _videoTitle = videoData['title'];
      _videoDuration = videoData['duration'] ?? 0;
      final youtubeId = videoData['youtube_video_id'];

      // Extract direct video stream URL using youtube_explode_dart
      final yt = yt_exp.YoutubeExplode();
      final manifest = await yt.videos.streamsClient.getManifest(youtubeId);
      yt_exp.MuxedStreamInfo? streamInfo;
      if (manifest.muxed.isNotEmpty) {
        var bestInfo = manifest.muxed.first;
        for (var info in manifest.muxed) {
          if (info.size.totalBytes > bestInfo.size.totalBytes) {
            bestInfo = info;
          }
        }
        streamInfo = bestInfo;
      }
      yt.close();

      if (streamInfo == null) {
        throw Exception('No playable video stream found');
      }

      final streamUrl = streamInfo.url.toString();

      _videoPlayerController = VideoPlayerController.networkUrl(Uri.parse(streamUrl));
      await _videoPlayerController!.initialize();
      _videoPlayerController!.addListener(_onVideoPlayerChanged);
      
      // Auto-start video
      _videoPlayerController!.play();

      // Start heartbeat timer
      _heartbeatTimer = Timer.periodic(
        const Duration(seconds: ApiConfig.progressHeartbeatSeconds),
        (_) => _sendHeartbeat(),
      );

      _startControlsTimer();

      setState(() {
        _isLoading = false;
      });
    } on VideoAccessException catch (e) {
      setState(() {
        _isLoading = false;
        _error = e.message;
      });
    } catch (e) {
      setState(() {
        _isLoading = false;
        _error = 'Failed to load video: ${e.toString()}';
      });
    }
  }

  void _onVideoPlayerChanged() {
    if (_videoPlayerController == null || !mounted) return;

    final position = _videoPlayerController!.value.position.inSeconds;
    final isPlaying = _videoPlayerController!.value.isPlaying;

    if (isPlaying) {
      WakelockPlus.enable();
      if (!_isTracking) {
        _rangeStart = position;
        _isTracking = true;
      }
      _lastRecordedPosition = position;
    } else {
      WakelockPlus.disable();
      // Video paused or stopped — record the range
      if (_isTracking && _lastRecordedPosition > _rangeStart) {
        _watchedRanges.add([_rangeStart, _lastRecordedPosition]);
        _isTracking = false;
      }
    }
  }

  Future<void> _sendHeartbeat() async {
    if (_videoPlayerController == null) return;

    final position = _videoPlayerController!.value.position.inSeconds;

    // Close current range temporarily for heartbeat
    final ranges = List<List<int>>.from(_watchedRanges);
    if (_isTracking && position > _rangeStart) {
      ranges.add([_rangeStart, position]);
    }

    try {
      final result = await _videoService.sendProgressHeartbeat(
        videoId: widget.videoId,
        currentPosition: position,
        ranges: ranges,
      );

      // Check if video was just completed
      if (result['completed'] == true) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: const Row(
                children: [
                  Icon(Icons.check_circle, color: Colors.white),
                  SizedBox(width: 8),
                  Text('Video completed! Next video unlocked! 🎉'),
                ],
              ),
              backgroundColor: AppTheme.successColor,
              duration: const Duration(seconds: 3),
            ),
          );
        }
      }
    } catch (e) {
      // Silently fail heartbeat — don't interrupt playback
      debugPrint('Heartbeat failed: $e');
    }
  }

  void _toggleFullscreen() {
    setState(() {
      _isFullscreen = !_isFullscreen;
    });

    if (_isFullscreen) {
      SystemChrome.setPreferredOrientations([
        DeviceOrientation.landscapeLeft,
        DeviceOrientation.landscapeRight,
      ]);
      SystemChrome.setEnabledSystemUIMode(SystemUiMode.immersiveSticky);
    } else {
      SystemChrome.setPreferredOrientations([
        DeviceOrientation.landscapeLeft,
        DeviceOrientation.landscapeRight,
        DeviceOrientation.portraitUp,
      ]);
      SystemChrome.setEnabledSystemUIMode(SystemUiMode.edgeToEdge);
    }
  }

  void _toggleControls() {
    setState(() {
      _showControls = !_showControls;
    });
    if (_showControls) {
      _startControlsTimer();
    }
  }

  void _startControlsTimer() {
    _controlsTimer?.cancel();
    _controlsTimer = Timer(const Duration(seconds: 3), () {
      if (mounted) {
        setState(() {
          _showControls = false;
        });
      }
    });
  }

  @override
  void dispose() {
    // Send final heartbeat before leaving
    _sendHeartbeat();

    _heartbeatTimer?.cancel();
    _controlsTimer?.cancel();
    _videoPlayerController?.removeListener(_onVideoPlayerChanged);
    _videoPlayerController?.dispose();

    // Reset orientation and system UI
    SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
    SystemChrome.setEnabledSystemUIMode(SystemUiMode.edgeToEdge);

    WakelockPlus.disable();

    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        backgroundColor: Colors.black,
        body: const Center(
          child: CircularProgressIndicator(color: AppTheme.primaryColor),
        ),
      );
    }

    if (_error != null) {
      return Scaffold(
        backgroundColor: AppTheme.darkBg,
        appBar: AppBar(
          backgroundColor: Colors.transparent,
          leading: IconButton(
            onPressed: () => context.pop(),
            icon: const Icon(Icons.arrow_back),
          ),
        ),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(32),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 80,
                  height: 80,
                  decoration: BoxDecoration(
                    color: AppTheme.errorColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: const Icon(
                    Icons.lock_rounded,
                    size: 40,
                    color: AppTheme.errorColor,
                  ),
                ),
                const SizedBox(height: 24),
                Text(
                  _error!,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w600,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: () => context.pop(),
                  child: const Text('Go Back'),
                ),
              ],
            ),
          ),
        ),
      );
    }

    final isPlaying = _videoPlayerController?.value.isPlaying ?? false;
    final position = _videoPlayerController?.value.position ?? Duration.zero;
    final duration = _videoPlayerController?.value.duration ?? Duration.zero;

    final playerWidget = AspectRatio(
      aspectRatio: _videoPlayerController?.value.aspectRatio ?? 16 / 9,
      child: Stack(
        alignment: Alignment.bottomCenter,
        children: [
          GestureDetector(
            onTap: _toggleControls,
            child: VideoPlayer(_videoPlayerController!),
          ),
          // Custom Controls Overlay
          if (_showControls)
            Positioned.fill(
              child: Container(
                color: Colors.black.withOpacity(0.4),
                child: Stack(
                  children: [
                    // Play/Pause button in center
                    Center(
                      child: IconButton(
                        iconSize: 64,
                        icon: Icon(
                          isPlaying ? Icons.pause_circle_filled : Icons.play_circle_filled,
                          color: Colors.white,
                        ),
                        onPressed: () {
                          setState(() {
                            if (isPlaying) {
                              _videoPlayerController!.pause();
                            } else {
                              _videoPlayerController!.play();
                            }
                          });
                          _startControlsTimer();
                        },
                      ),
                    ),
                    // Bottom Controls Bar
                    Positioned(
                      bottom: 0,
                      left: 0,
                      right: 0,
                      child: Container(
                        color: Colors.black.withOpacity(0.6),
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            // Scrub Slider
                            VideoProgressIndicator(
                              _videoPlayerController!,
                              allowScrubbing: true,
                              colors: const VideoProgressColors(
                                playedColor: AppTheme.primaryColor,
                                bufferedColor: Colors.white24,
                                backgroundColor: Colors.white12,
                              ),
                            ),
                            const SizedBox(height: 8),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  '${_formatDuration(position.inSeconds)} / ${_formatDuration(duration.inSeconds)}',
                                  style: const TextStyle(color: Colors.white, fontSize: 12),
                                ),
                                IconButton(
                                  icon: Icon(
                                    _isFullscreen ? Icons.fullscreen_exit : Icons.fullscreen,
                                    color: Colors.white,
                                  ),
                                  onPressed: _toggleFullscreen,
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
        ],
      ),
    );

    if (_isFullscreen) {
      return Scaffold(
        backgroundColor: Colors.black,
        body: Center(
          child: playerWidget,
        ),
      );
    }

    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.black,
        leading: IconButton(
          onPressed: () => context.pop(),
          icon: const Icon(Icons.arrow_back, color: Colors.white),
        ),
        title: Text(
          _videoTitle ?? 'Video',
          style: const TextStyle(fontSize: 16, color: Colors.white),
        ),
      ),
      body: Column(
        children: [
          // Native Video Player
          playerWidget,

          // Video info below player
          Expanded(
            child: Container(
              color: AppTheme.darkBg,
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    _videoTitle ?? '',
                    style: const TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.w600,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      const Icon(Icons.access_time, size: 16, color: Colors.white38),
                      const SizedBox(width: 4),
                      Text(
                        _formatDuration(_videoDuration),
                        style: const TextStyle(color: Colors.white38, fontSize: 14),
                      ),
                    ],
                  ),
                  const SizedBox(height: 24),
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: AppTheme.primaryColor.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(
                        color: AppTheme.primaryColor.withOpacity(0.3),
                      ),
                    ),
                    child: const Row(
                      children: [
                        Icon(Icons.info_outline, color: AppTheme.primaryColor, size: 20),
                        SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            'Watch at least 90% to unlock the next video',
                            style: TextStyle(
                              color: AppTheme.primaryColor,
                              fontSize: 13,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  String _formatDuration(int seconds) {
    final m = seconds ~/ 60;
    final s = seconds % 60;
    return '${m}:${s.toString().padLeft(2, '0')}';
  }
}
