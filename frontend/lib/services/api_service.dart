import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:firebase_auth/firebase_auth.dart' as firebase_auth;
import '../config/api_config.dart';

/// Core API service that handles all HTTP requests to the Laravel backend.
/// Automatically attaches Firebase Auth token to every request.
class ApiService {
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  ApiService._internal();

  /// Get the current Firebase user's ID token for API authorization.
  Future<String?> _getAuthToken() async {
    final user = firebase_auth.FirebaseAuth.instance.currentUser;
    if (user == null) return null;
    return await user.getIdToken();
  }

  /// Build headers with optional auth token.
  Future<Map<String, String>> _headers({bool requireAuth = true}) async {
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };

    if (requireAuth) {
      final token = await _getAuthToken();
      if (token != null) {
        headers['Authorization'] = 'Bearer $token';
      }
    }

    return headers;
  }

  /// GET request
  Future<ApiResponse> get(String endpoint, {bool requireAuth = true}) async {
    try {
      final response = await http
          .get(
            Uri.parse('${ApiConfig.baseUrl}$endpoint'),
            headers: await _headers(requireAuth: requireAuth),
          )
          .timeout(const Duration(milliseconds: ApiConfig.connectionTimeout));

      return ApiResponse.fromHttpResponse(response);
    } catch (e) {
      return ApiResponse(
        success: false,
        statusCode: 0,
        message: 'Connection error: ${e.toString()}',
      );
    }
  }

  /// POST request
  Future<ApiResponse> post(String endpoint,
      {Map<String, dynamic>? body, bool requireAuth = true}) async {
    try {
      final response = await http
          .post(
            Uri.parse('${ApiConfig.baseUrl}$endpoint'),
            headers: await _headers(requireAuth: requireAuth),
            body: body != null ? jsonEncode(body) : null,
          )
          .timeout(const Duration(milliseconds: ApiConfig.connectionTimeout));

      return ApiResponse.fromHttpResponse(response);
    } catch (e) {
      return ApiResponse(
        success: false,
        statusCode: 0,
        message: 'Connection error: ${e.toString()}',
      );
    }
  }

  /// PUT request
  Future<ApiResponse> put(String endpoint,
      {Map<String, dynamic>? body, bool requireAuth = true}) async {
    try {
      final response = await http
          .put(
            Uri.parse('${ApiConfig.baseUrl}$endpoint'),
            headers: await _headers(requireAuth: requireAuth),
            body: body != null ? jsonEncode(body) : null,
          )
          .timeout(const Duration(milliseconds: ApiConfig.connectionTimeout));

      return ApiResponse.fromHttpResponse(response);
    } catch (e) {
      return ApiResponse(
        success: false,
        statusCode: 0,
        message: 'Connection error: ${e.toString()}',
      );
    }
  }

  /// DELETE request
  Future<ApiResponse> delete(String endpoint,
      {bool requireAuth = true}) async {
    try {
      final response = await http
          .delete(
            Uri.parse('${ApiConfig.baseUrl}$endpoint'),
            headers: await _headers(requireAuth: requireAuth),
          )
          .timeout(const Duration(milliseconds: ApiConfig.connectionTimeout));

      return ApiResponse.fromHttpResponse(response);
    } catch (e) {
      return ApiResponse(
        success: false,
        statusCode: 0,
        message: 'Connection error: ${e.toString()}',
      );
    }
  }
}

/// Standardized API response wrapper.
class ApiResponse {
  final bool success;
  final int statusCode;
  final String? message;
  final dynamic data;

  ApiResponse({
    required this.success,
    required this.statusCode,
    this.message,
    this.data,
  });

  factory ApiResponse.fromHttpResponse(http.Response response) {
    Map<String, dynamic>? body;
    try {
      body = jsonDecode(response.body);
    } catch (_) {
      body = null;
    }

    return ApiResponse(
      success: body?['success'] ?? (response.statusCode >= 200 && response.statusCode < 300),
      statusCode: response.statusCode,
      message: body?['message'],
      data: body?['data'],
    );
  }
}
