# SEMS Database Schema

The student portal read model is derived from normalized academic rows. Dashboard, courses, attendance, grades, transcript, and profile endpoints should not read dashboard-only or transcript-only mock tables.

## Source Of Truth

- `faculties`, `departments`, `programs` store the academic organization.
- `users`, `students`, `professors` store identity, role, and profile data.
- `academic_years`, `semesters` store term structure.
- `courses`, `course_professor`, `course_schedules` store course catalog, instructors, and meeting times.
- `course_materials`, `course_events`, `course_announcements` store course resources, assessments, exams, deadlines, and messages.
- `student_enrollments` joins students to courses and stores enrollment status only, not grade or attendance caches.
- `course_attendance_records` stores per-enrollment attendance events used for attendance summaries and course detail attendance.
- `attendance_history_records` stores the cross-course attendance history feed.
- `course_grade_components` stores course grading weights.
- `course_grade_records` stores the one real student grade record source. Each row belongs to a student enrollment and stores numeric `grade` values on the 5-10 scale.

## Derived Values

- Dashboard metrics, latest grades, grade averages, grade distribution, transcript summaries, earned credits, and course current grades are calculated from enrollments, courses, semesters, and `course_grade_records`.
- Dashboard classes and attendance weekly schedule are calculated from enrollments plus `course_schedules`.
- Attendance percentages, attendance warnings, and attendance detail summaries are calculated from `course_attendance_records`.
- Profile academic year, semester, average grade, earned credits, required credits, and standing are derived from enrollments, semesters, programs, courses, and grade records.

## Removed Mock/Cache Tables

Migration `2026_05_26_000001_normalize_student_read_model.php` removes these old mockup/cache tables from the migrated schema:

- `course_info_items`
- `course_attendance_summaries`
- `attendance_weeks`
- `attendance_summaries`
- `attendance_schedule_days`
- `attendance_schedule_blocks`
- `attendance_last_recorded`
- `transcript_semester_options`
- `transcript_summaries`
- `grade_distributions`
- `transcript_course_grades`
- `student_dashboard_metrics`
- `student_dashboard_classes`
- `student_dashboard_deadlines`
- `student_dashboard_latest_grades`
- `student_dashboard_attendance_warnings`
- `student_dashboard_attendance_summaries`

The same migration removes cached academic columns from `students` and cached grade/attendance/event columns from `student_enrollments`.
