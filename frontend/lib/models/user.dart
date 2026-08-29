class User {
  final int id;
  final String name;
  final String email;
  final String? phone;
  final String? avatar;
  final String role;

  User({
    required this.id,
    required this.name,
    required this.email,
    this.phone,
    this.avatar,
    required this.role,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      name: json['name'],
      email: json['email'],
      phone: json['phone'],
      avatar: json['avatar'],
      role: json['role'],
    );
  }

  bool get isAdmin => role == 'admin';
  bool get isStudent => role == 'student';
}
