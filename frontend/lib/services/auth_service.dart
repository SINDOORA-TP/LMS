import 'package:firebase_auth/firebase_auth.dart' as firebase_auth;
import '../models/user.dart';
import 'api_service.dart';

/// Handles Firebase Authentication and syncs with Laravel backend.
class AuthService {
  final firebase_auth.FirebaseAuth _firebaseAuth = firebase_auth.FirebaseAuth.instance;
  final ApiService _api = ApiService();

  /// Get current Firebase user
  firebase_auth.User? get currentFirebaseUser => _firebaseAuth.currentUser;

  /// Check if user is signed in
  bool get isSignedIn => currentFirebaseUser != null;

  /// Auth state changes stream
  Stream<firebase_auth.User?> get authStateChanges => _firebaseAuth.authStateChanges();

  /// Register with email and password.
  /// Creates Firebase account + syncs with Laravel backend.
  Future<User> register({
    required String name,
    required String email,
    required String password,
  }) async {
    // Create Firebase account
    final credential = await _firebaseAuth.createUserWithEmailAndPassword(
      email: email,
      password: password,
    );

    // Update display name in Firebase
    await credential.user?.updateDisplayName(name);

    // Sync with Laravel backend
    final response = await _api.post('/auth/sync');

    if (!response.success) {
      throw Exception(response.message ?? 'Failed to sync user');
    }

    return User.fromJson(response.data['user']);
  }

  /// Login with email and password.
  Future<User> login({
    required String email,
    required String password,
  }) async {
    // Sign in with Firebase
    await _firebaseAuth.signInWithEmailAndPassword(
      email: email,
      password: password,
    );

    // Sync with Laravel backend
    final response = await _api.post('/auth/sync');

    if (!response.success) {
      throw Exception(response.message ?? 'Failed to sync user');
    }

    return User.fromJson(response.data['user']);
  }

  /// Get current user profile from Laravel.
  Future<User> getCurrentUser() async {
    final response = await _api.get('/auth/me');

    if (!response.success) {
      throw Exception(response.message ?? 'Failed to get user');
    }

    return User.fromJson(response.data['user']);
  }

  /// Update user profile.
  Future<User> updateProfile({String? name, String? phone}) async {
    final body = <String, dynamic>{};
    if (name != null) body['name'] = name;
    if (phone != null) body['phone'] = phone;

    final response = await _api.put('/auth/profile', body: body);

    if (!response.success) {
      throw Exception(response.message ?? 'Failed to update profile');
    }

    return User.fromJson(response.data['user']);
  }

  /// Logout from Firebase.
  Future<void> logout() async {
    await _firebaseAuth.signOut();
  }

  /// Send password reset email.
  Future<void> resetPassword(String email) async {
    await _firebaseAuth.sendPasswordResetEmail(email: email);
  }
}
