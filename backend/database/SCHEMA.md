# SEMS Database Schema

The Laravel migration in `migrations/2026_05_25_000001_create_sems_schema.php` creates the relational schema needed by the current React frontend and the legacy mock PHP data model.

## Core Identity

- `faculties` -> parent for departments.
- `departments` -> belongs to one faculty.
- `programs` -> belongs to one department.
- `users` -> shared login table for `student`, `professor`, and `admin` roles.
- `students` -> one-to-one profile extension of `users`.
- `student_emergency_contacts` -> belongs to a student.
- `professors` -> one-to-one profile extension of `users`.

## Academic Structure

- `academic_years` -> academic year labels and date ranges.
- `semesters` -> belongs to an academic year.
- `courses` -> belongs to an optional department and semester.
- `course_professor` -> many-to-many relation between courses and professors.
- `course_schedules` -> meeting schedule rows for courses.
- `course_info_items` -> key/value course detail rows shown on the course detail page.
- `course_materials` -> course downloadable/uploaded resources.
- `course_events` -> assessments, exams, and deadline records for courses.
- `course_announcements` -> course announcement feed.

## Student Course Data

- `student_enrollments` -> joins students to courses and stores status, current grade, attendance percentage, and next important event.
- `course_attendance_summaries` -> course-detail attendance summary for one enrollment.
- `course_attendance_records` -> course-detail attendance rows for one enrollment.
- `course_grade_components` -> grading breakdown per course.
- `course_grade_records` -> grade records for one student enrollment.

## Attendance Page

- `attendance_weeks` -> selected weekly attendance window for a student.
- `attendance_summaries` -> overall or per-course attendance summary for a student.
- `attendance_schedule_days` -> days inside an attendance week.
- `attendance_schedule_blocks` -> classes inside each attendance day.
- `attendance_history_records` -> historical attendance rows.
- `attendance_last_recorded` -> latest recorded attendance marker per student/course.

## Grades / Transcript Page

- `transcript_semester_options` -> selectable transcript semesters for a student.
- `transcript_summaries` -> transcript summary metrics for a student.
- `grade_distributions` -> distribution chart rows for a student.
- `transcript_course_grades` -> transcript table rows by student, course, and semester.

## Student Dashboard

- `student_dashboard_metrics` -> top metric cards.
- `student_dashboard_classes` -> today's classes list.
- `student_dashboard_deadlines` -> upcoming dashboard deadlines.
- `student_dashboard_latest_grades` -> latest grades list.
- `student_dashboard_attendance_warnings` -> dashboard attendance warning block.
- `student_dashboard_attendance_summaries` -> dashboard attendance mini chart rows.

## Notes

- Legacy string IDs such as `luri`, `cs308`, and `stu-1001` are preserved through fields like `student_key`, `course_key`, and `public_id`.
- Flexible UI-only arrays from the legacy JSON, such as course topics and learning outcomes, are stored as JSON columns where full normalization would add complexity without improving current frontend behavior.
- Uploaded assets can continue using `/uploads/...`; database rows store the public URL/path only.
