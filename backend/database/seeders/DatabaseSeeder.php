<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Module;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with sample LMS data.
     */
    public function run(): void
    {
        // Create admin user (you'll need to register this email in Firebase first)
        User::create([
            'firebase_uid' => 'admin_placeholder_uid',
            'name' => 'Admin',
            'email' => 'admin@lms.com',
            'role' => 'admin',
            'password' => bcrypt('admin123'),
        ]);

        // Create sample courses
        $pythonCourse = Course::create([
            'title' => 'Python Programming Masterclass',
            'description' => 'Learn Python from scratch. This comprehensive course covers everything from basic syntax to advanced concepts like OOP, file handling, and working with APIs.',
            'price' => 499.00,
            'is_free' => false,
            'status' => 'published',
            'level' => 'beginner',
            'language' => 'English',
        ]);

        $webCourse = Course::create([
            'title' => 'Web Development Bootcamp',
            'description' => 'Master HTML, CSS, and JavaScript. Build responsive websites from scratch with modern tools and frameworks.',
            'price' => 0,
            'is_free' => true,
            'status' => 'published',
            'level' => 'beginner',
            'language' => 'English',
        ]);

        $flutterCourse = Course::create([
            'title' => 'Flutter App Development',
            'description' => 'Build beautiful Android apps with Flutter and Dart. Learn widgets, state management, API integration, and more.',
            'price' => 799.00,
            'is_free' => false,
            'status' => 'published',
            'level' => 'intermediate',
            'language' => 'English',
        ]);

        // ---- Python Course Modules & Videos ----

        $pyModule1 = Module::create([
            'course_id' => $pythonCourse->id,
            'title' => 'Getting Started',
            'description' => 'Introduction to Python programming',
            'order' => 0,
        ]);

        // Use placeholder YouTube IDs — replace with real unlisted video IDs
        Video::create([
            'module_id' => $pyModule1->id,
            'title' => 'What is Python?',
            'description' => 'Introduction to Python and why you should learn it',
            'youtube_video_id' => 'w7ehZ1cDYDw', // Replace with actual video ID
            'duration' => 2700, // 45 minutes
            'order' => 0,
            'is_preview' => true, // Free preview
        ]);

        Video::create([
            'module_id' => $pyModule1->id,
            'title' => 'Installing Python',
            'description' => 'How to install Python on Windows, Mac, and Linux',
            'youtube_video_id' => 'w7ehZ1cDYDw',
            'duration' => 2820, // 47 minutes
            'order' => 1,
        ]);

        Video::create([
            'module_id' => $pyModule1->id,
            'title' => 'Your First Python Program',
            'description' => 'Write and run your first Hello World program',
            'youtube_video_id' => 'w7ehZ1cDYDw',
            'duration' => 2940, // 49 minutes
            'order' => 2,
        ]);

        $pyModule2 = Module::create([
            'course_id' => $pythonCourse->id,
            'title' => 'Variables and Data Types',
            'description' => 'Learn about variables, strings, numbers, and booleans',
            'order' => 1,
        ]);

        Video::create([
            'module_id' => $pyModule2->id,
            'title' => 'Variables in Python',
            'description' => 'Understanding variables and naming conventions',
            'youtube_video_id' => 'w7ehZ1cDYDw',
            'duration' => 3000, // 50 minutes
            'order' => 0,
        ]);

        Video::create([
            'module_id' => $pyModule2->id,
            'title' => 'Strings and Numbers',
            'description' => 'Working with text and numeric data types',
            'youtube_video_id' => 'w7ehZ1cDYDw',
            'duration' => 2760, // 46 minutes
            'order' => 1,
        ]);

        Video::create([
            'module_id' => $pyModule2->id,
            'title' => 'Type Conversion',
            'description' => 'Converting between different data types',
            'youtube_video_id' => 'w7ehZ1cDYDw',
            'duration' => 2880, // 48 minutes
            'order' => 2,
        ]);

        $pyModule3 = Module::create([
            'course_id' => $pythonCourse->id,
            'title' => 'Control Flow',
            'description' => 'Conditions, loops, and flow control',
            'order' => 2,
        ]);

        Video::create([
            'module_id' => $pyModule3->id,
            'title' => 'If-Else Statements',
            'description' => 'Making decisions in your code',
            'youtube_video_id' => 'w7ehZ1cDYDw',
            'duration' => 2700, // 45 minutes
            'order' => 0,
        ]);

        Video::create([
            'module_id' => $pyModule3->id,
            'title' => 'For Loops',
            'description' => 'Iterating with for loops',
            'youtube_video_id' => 'w7ehZ1cDYDw',
            'duration' => 2940, // 49 minutes
            'order' => 1,
        ]);

        Video::create([
            'module_id' => $pyModule3->id,
            'title' => 'While Loops',
            'description' => 'Using while loops for repeated execution',
            'youtube_video_id' => 'w7ehZ1cDYDw',
            'duration' => 3000, // 50 minutes
            'order' => 2,
        ]);

        // Recalculate totals for courses
        $pythonCourse->recalculateTotals();

        // ---- Web Dev Course Modules ----

        $webModule1 = Module::create([
            'course_id' => $webCourse->id,
            'title' => 'HTML Basics',
            'description' => 'Learn HTML structure and elements',
            'order' => 0,
        ]);

        Video::create([
            'module_id' => $webModule1->id,
            'title' => 'What is HTML?',
            'youtube_video_id' => 'x99GcbxCOII',
            'duration' => 2700, // 45 minutes
            'order' => 0,
            'is_preview' => true,
        ]);

        Video::create([
            'module_id' => $webModule1->id,
            'title' => 'HTML Tags and Elements',
            'youtube_video_id' => 'w7ehZ1cDYDw',
            'duration' => 2880, // 48 minutes
            'order' => 1,
        ]);

        $webCourse->recalculateTotals();

        // ---- Flutter Course Modules ----

        $flutterModule1 = Module::create([
            'course_id' => $flutterCourse->id,
            'title' => 'Dart Fundamentals',
            'description' => 'Learn Dart programming language basics',
            'order' => 0,
        ]);

        Video::create([
            'module_id' => $flutterModule1->id,
            'title' => 'Introduction to Dart',
            'youtube_video_id' => 'w7ehZ1cDYDw',
            'duration' => 2700, // 45 minutes
            'order' => 0,
            'is_preview' => true,
        ]);

        Video::create([
            'module_id' => $flutterModule1->id,
            'title' => 'Dart Variables and Functions',
            'youtube_video_id' => 'w7ehZ1cDYDw',
            'duration' => 3000, // 50 minutes
            'order' => 1,
        ]);

        $flutterCourse->recalculateTotals();
    }
}
