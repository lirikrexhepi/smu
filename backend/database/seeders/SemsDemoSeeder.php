<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SemsDemoSeeder extends Seeder
{
    private const FACULTY_NAME = 'Faculty of Electrical and Computer Engineering';

    private const DEPARTMENT_NAME = 'Computer Engineering';

    private const PROGRAM_NAME = 'Bachelor of Computer Engineering';

    private const ACADEMIC_YEAR = '2025/2026';

    private const REQUIRED_CREDITS = 180;

    /**
     * Seed deterministic student portal demo data.
     */
    public function run(): void
    {
        $now = now();

        $facultyId = $this->updateOrCreateId('faculties', ['name' => self::FACULTY_NAME], [
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $departmentId = $this->updateOrCreateId('departments', [
            'faculty_id' => $facultyId,
            'name' => self::DEPARTMENT_NAME,
        ], [
            'updated_at' => $now,
            'created_at' => $now,
        ]);

        $programId = $this->updateOrCreateId('programs', [
            'department_id' => $departmentId,
            'name' => self::PROGRAM_NAME,
        ], [
            'degree_level' => 'Bachelor',
            'required_credits' => self::REQUIRED_CREDITS,
            'updated_at' => $now,
            'created_at' => $now,
        ]);

        $academicYearId = $this->updateOrCreateId('academic_years', ['name' => self::ACADEMIC_YEAR], [
            'starts_on' => '2025-10-01',
            'ends_on' => '2026-07-15',
            'is_current' => true,
            'updated_at' => $now,
            'created_at' => $now,
        ]);

        $semesters = [
            'sem-3' => $this->updateOrCreateId('semesters', ['code' => 'sem-3'], [
                'academic_year_id' => $academicYearId,
                'name' => '3rd Semester',
                'number' => 3,
                'starts_on' => '2025-10-01',
                'ends_on' => '2026-02-10',
                'is_current' => false,
                'updated_at' => $now,
                'created_at' => $now,
            ]),
            'sem-4' => $this->updateOrCreateId('semesters', ['code' => 'sem-4'], [
                'academic_year_id' => $academicYearId,
                'name' => '4th Semester',
                'number' => 4,
                'starts_on' => '2026-02-17',
                'ends_on' => '2026-06-26',
                'is_current' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]),
        ];

        $professorId = $this->seedProfessor($facultyId, $departmentId);
        $courses = $this->seedCourses($departmentId, $semesters, $professorId);
        $students = $this->seedStudents($facultyId, $departmentId, $programId);

        foreach ($students as $student) {
            $this->seedStudentData($student, $courses, $semesters);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function seedCourses(int $departmentId, array $semesters, int $professorId): array
    {
        $courseRows = [
            [
                'key' => 'ce-3-db',
                'code' => 'CE201',
                'name' => 'Database Systems',
                'ects' => 5,
                'semester_code' => 'sem-3',
                'room' => 'B-204',
                'days_label' => 'Mon, Wed',
                'days' => ['Monday', 'Wednesday'],
                'starts_at' => '09:00:00',
                'ends_at' => '10:30:00',
                'topics' => ['Relational modeling', 'SQL queries', 'Normalization', 'Transactions'],
                'outcomes' => ['Design normalized schemas', 'Write reliable SQL queries', 'Explain transaction safety'],
            ],
            [
                'key' => 'ce-3-os',
                'code' => 'CE203',
                'name' => 'Operating Systems',
                'ects' => 5,
                'semester_code' => 'sem-3',
                'room' => 'A-112',
                'days_label' => 'Tue, Thu',
                'days' => ['Tuesday', 'Thursday'],
                'starts_at' => '10:45:00',
                'ends_at' => '12:15:00',
                'topics' => ['Processes', 'Threads', 'Memory management', 'File systems'],
                'outcomes' => ['Describe OS scheduling', 'Analyze memory allocation', 'Use synchronization primitives'],
            ],
            [
                'key' => 'ce-3-net',
                'code' => 'CE205',
                'name' => 'Computer Networks',
                'ects' => 5,
                'semester_code' => 'sem-3',
                'room' => 'C-101',
                'days_label' => 'Mon, Thu',
                'days' => ['Monday', 'Thursday'],
                'starts_at' => '13:00:00',
                'ends_at' => '14:30:00',
                'topics' => ['OSI model', 'IP addressing', 'Routing', 'Transport protocols'],
                'outcomes' => ['Configure basic networks', 'Explain TCP/IP behavior', 'Interpret packet captures'],
            ],
            [
                'key' => 'ce-3-dsa',
                'code' => 'CE207',
                'name' => 'Data Structures and Algorithms',
                'ects' => 6,
                'semester_code' => 'sem-3',
                'room' => 'B-110',
                'days_label' => 'Wed, Fri',
                'days' => ['Wednesday', 'Friday'],
                'starts_at' => '11:00:00',
                'ends_at' => '12:30:00',
                'topics' => ['Trees', 'Graphs', 'Sorting', 'Complexity analysis'],
                'outcomes' => ['Select appropriate data structures', 'Analyze algorithm complexity', 'Implement graph traversal'],
            ],
            [
                'key' => 'ce-3-se',
                'code' => 'CE209',
                'name' => 'Software Engineering',
                'ects' => 4,
                'semester_code' => 'sem-3',
                'room' => 'D-016',
                'days_label' => 'Tue',
                'days' => ['Tuesday'],
                'starts_at' => '14:00:00',
                'ends_at' => '16:00:00',
                'topics' => ['Requirements', 'UML', 'Testing', 'Version control'],
                'outcomes' => ['Document requirements', 'Plan implementation tasks', 'Apply basic test design'],
            ],
            [
                'key' => 'ce-3-stat',
                'code' => 'MATH221',
                'name' => 'Probability and Statistics',
                'ects' => 5,
                'semester_code' => 'sem-3',
                'room' => 'A-205',
                'days_label' => 'Fri',
                'days' => ['Friday'],
                'starts_at' => '09:00:00',
                'ends_at' => '11:00:00',
                'topics' => ['Random variables', 'Distributions', 'Estimation', 'Hypothesis testing'],
                'outcomes' => ['Compute probabilities', 'Interpret distributions', 'Run simple statistical tests'],
            ],
            [
                'key' => 'ce-4-web',
                'code' => 'CE202',
                'name' => 'Web Application Development',
                'ects' => 5,
                'semester_code' => 'sem-4',
                'room' => 'B-208',
                'days_label' => 'Mon, Wed',
                'days' => ['Monday', 'Wednesday'],
                'starts_at' => '09:00:00',
                'ends_at' => '10:30:00',
                'topics' => ['HTTP', 'Laravel basics', 'REST APIs', 'Frontend integration'],
                'outcomes' => ['Build API endpoints', 'Validate requests', 'Connect frontend views to backend data'],
            ],
            [
                'key' => 'ce-4-arch',
                'code' => 'CE204',
                'name' => 'Computer Architecture',
                'ects' => 5,
                'semester_code' => 'sem-4',
                'room' => 'A-114',
                'days_label' => 'Tue, Thu',
                'days' => ['Tuesday', 'Thursday'],
                'starts_at' => '10:45:00',
                'ends_at' => '12:15:00',
                'topics' => ['Instruction sets', 'Pipelining', 'Memory hierarchy', 'I/O systems'],
                'outcomes' => ['Explain CPU execution', 'Compare memory designs', 'Analyze basic pipeline hazards'],
            ],
            [
                'key' => 'ce-4-ai',
                'code' => 'CE206',
                'name' => 'Artificial Intelligence',
                'ects' => 5,
                'semester_code' => 'sem-4',
                'room' => 'C-104',
                'days_label' => 'Mon, Thu',
                'days' => ['Monday', 'Thursday'],
                'starts_at' => '13:00:00',
                'ends_at' => '14:30:00',
                'topics' => ['Search', 'Knowledge representation', 'Classification', 'Model evaluation'],
                'outcomes' => ['Implement search algorithms', 'Prepare datasets', 'Evaluate model performance'],
            ],
            [
                'key' => 'ce-4-mobile',
                'code' => 'CE208',
                'name' => 'Mobile Computing',
                'ects' => 4,
                'semester_code' => 'sem-4',
                'room' => 'D-018',
                'days_label' => 'Wed',
                'days' => ['Wednesday'],
                'starts_at' => '14:00:00',
                'ends_at' => '16:00:00',
                'topics' => ['Mobile UI', 'Local storage', 'Networking', 'Device capabilities'],
                'outcomes' => ['Design mobile screens', 'Persist local data', 'Consume remote APIs'],
            ],
            [
                'key' => 'ce-4-sec',
                'code' => 'CE210',
                'name' => 'Information Security',
                'ects' => 5,
                'semester_code' => 'sem-4',
                'room' => 'B-116',
                'days_label' => 'Tue',
                'days' => ['Tuesday'],
                'starts_at' => '14:00:00',
                'ends_at' => '16:00:00',
                'topics' => ['Cryptography basics', 'Authentication', 'Web vulnerabilities', 'Risk management'],
                'outcomes' => ['Identify common vulnerabilities', 'Apply authentication controls', 'Assess basic security risks'],
            ],
            [
                'key' => 'ce-4-hci',
                'code' => 'CE212',
                'name' => 'Human-Computer Interaction',
                'ects' => 4,
                'semester_code' => 'sem-4',
                'room' => 'A-210',
                'days_label' => 'Fri',
                'days' => ['Friday'],
                'starts_at' => '09:00:00',
                'ends_at' => '11:00:00',
                'topics' => ['User research', 'Prototyping', 'Usability testing', 'Accessibility'],
                'outcomes' => ['Create task flows', 'Evaluate prototypes', 'Apply accessibility principles'],
            ],
        ];

        $courses = [];

        foreach ($courseRows as $row) {
            $semesterId = $semesters[$row['semester_code']];
            $courseId = $this->updateOrCreateId('courses', ['course_key' => $row['key']], [
                'code' => $row['code'],
                'name' => $row['name'],
                'department_id' => $departmentId,
                'semester_id' => $semesterId,
                'ects' => $row['ects'],
                'status' => 'Active',
                'room' => $row['room'],
                'description' => $row['name'].' introduces core computer engineering concepts through lectures, labs, and guided assignments.',
                'learning_outcomes' => json_encode($row['outcomes']),
                'topics' => json_encode($row['topics']),
                'grading_breakdown' => 'Midterm 30%, assignments 30%, final exam 40%',
                'updated_at' => now(),
                'created_at' => now(),
            ]);

            $this->updateOrCreateId('course_professor', [
                'course_id' => $courseId,
                'professor_id' => $professorId,
                'role' => 'instructor',
            ], [
                'updated_at' => now(),
                'created_at' => now(),
            ]);

            $this->updateOrCreateId('course_schedules', [
                'course_id' => $courseId,
                'label' => 'Lecture',
            ], [
                'days_label' => $row['days_label'],
                'days' => json_encode($row['days']),
                'time_label' => substr($row['starts_at'], 0, 5).' - '.substr($row['ends_at'], 0, 5),
                'starts_at' => $row['starts_at'],
                'ends_at' => $row['ends_at'],
                'room' => $row['room'],
                'updated_at' => now(),
                'created_at' => now(),
            ]);

            foreach ([
                'credits' => ['label' => 'ECTS', 'value' => (string) $row['ects'], 'sort' => 1],
                'semester' => ['label' => 'Semester', 'value' => $row['semester_code'] === 'sem-3' ? '3rd Semester' : '4th Semester', 'sort' => 2],
                'room' => ['label' => 'Room', 'value' => $row['room'], 'sort' => 3],
            ] as $key => $item) {
                $this->updateOrCreateId('course_info_items', [
                    'course_id' => $courseId,
                    'item_key' => $key,
                ], [
                    'label' => $item['label'],
                    'value' => $item['value'],
                    'sort_order' => $item['sort'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);
            }

            foreach ([
                ['component' => 'Midterm', 'weight' => 30],
                ['component' => 'Assignments', 'weight' => 30],
                ['component' => 'Final Exam', 'weight' => 40],
            ] as $component) {
                $this->updateOrCreateId('course_grade_components', [
                    'course_id' => $courseId,
                    'component' => $component['component'],
                ], [
                    'weight' => $component['weight'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);
            }

            $eventDate = $row['semester_code'] === 'sem-3' ? '2026-01-20' : '2026-06-12';
            $this->updateOrCreateId('course_events', [
                'course_id' => $courseId,
                'event_key' => 'final-exam',
                'category' => 'exam',
            ], [
                'title' => $row['name'].' Final Exam',
                'type' => 'Final exam',
                'event_date' => $eventDate,
                'event_time' => '09:00:00',
                'date_label' => date('M j, Y', strtotime($eventDate)),
                'time_label' => '09:00',
                'status_label' => $row['semester_code'] === 'sem-3' ? 'Completed' : 'Scheduled',
                'tone' => 'blue',
                'mode' => 'On campus',
                'duration' => '120 minutes',
                'room' => $row['room'],
                'description' => 'Cumulative written examination.',
                'updated_at' => now(),
                'created_at' => now(),
            ]);

            $deadlineDate = $row['semester_code'] === 'sem-3' ? '2025-12-12' : '2026-05-29';
            $this->updateOrCreateId('course_events', [
                'course_id' => $courseId,
                'event_key' => 'project-submission',
                'category' => 'deadline',
            ], [
                'title' => $row['name'].' Project Submission',
                'type' => 'Project',
                'event_date' => $deadlineDate,
                'event_time' => '23:59:00',
                'date_label' => date('M j, Y', strtotime($deadlineDate)),
                'time_label' => '23:59',
                'status_label' => $row['semester_code'] === 'sem-3' ? 'Submitted' : 'Due soon',
                'tone' => $row['semester_code'] === 'sem-3' ? 'green' : 'orange',
                'mode' => 'Online',
                'duration' => null,
                'room' => null,
                'description' => 'Submit the course project package through the student portal.',
                'updated_at' => now(),
                'created_at' => now(),
            ]);

            $courses[$row['key']] = $row + [
                'id' => $courseId,
                'semester_id' => $semesterId,
            ];
        }

        return $courses;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function seedStudents(int $facultyId, int $departmentId, int $programId): array
    {
        $rows = [
            [
                'public_id' => 'stu-demo-1001',
                'institution_id' => 'STU-1001',
                'student_number' => 'STU-1001',
                'student_key' => 'demo-student-one',
                'name' => 'Demo Student One',
                'email' => 'student1@example.com',
                'phone' => '+383 44 100 101',
                'address' => 'Rr. B, Prishtina',
                'date_of_birth' => '2004-04-12',
                'gender' => 'Female',
                'nationality' => 'Kosovar',
                'personal_number' => 'DEMO1001',
                'gpa' => 8.67,
                'credits' => 30,
            ],
            [
                'public_id' => 'stu-demo-1002',
                'institution_id' => 'STU-1002',
                'student_number' => 'STU-1002',
                'student_key' => 'demo-student-two',
                'name' => 'Demo Student Two',
                'email' => 'student2@example.com',
                'phone' => '+383 44 100 102',
                'address' => 'Rr. C, Prishtina',
                'date_of_birth' => '2003-09-05',
                'gender' => 'Male',
                'nationality' => 'Kosovar',
                'personal_number' => 'DEMO1002',
                'gpa' => 7.83,
                'credits' => 30,
            ],
        ];

        $students = [];

        foreach ($rows as $row) {
            $userId = $this->updateOrCreateId('users', ['institution_id' => $row['institution_id']], [
                'public_id' => $row['public_id'],
                'role' => 'student',
                'name' => $row['name'],
                'email' => $row['email'],
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'faculty_id' => $facultyId,
                'department_id' => $departmentId,
                'avatar_url' => null,
                'remember_token' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]);

            $studentId = $this->updateOrCreateId('students', ['student_key' => $row['student_key']], [
                'user_id' => $userId,
                'program_id' => $programId,
                'student_number' => $row['student_number'],
                'status' => 'Active',
                'status_label' => 'Active',
                'year_of_study' => '2nd Year',
                'current_semester_label' => '4th Semester',
                'academic_year_label' => self::ACADEMIC_YEAR,
                'current_gpa' => $row['gpa'],
                'credits_earned' => $row['credits'],
                'credits_required' => self::REQUIRED_CREDITS,
                'academic_standing' => 'Good standing',
                'phone' => $row['phone'],
                'address' => $row['address'],
                'date_of_birth' => $row['date_of_birth'],
                'gender' => $row['gender'],
                'nationality' => $row['nationality'],
                'personal_number' => $row['personal_number'],
                'profile_updated_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]);

            $this->updateOrCreateId('student_emergency_contacts', [
                'student_id' => $studentId,
                'is_primary' => true,
            ], [
                'name' => 'Demo Parent',
                'relationship' => 'Parent',
                'phone' => '+383 44 200 200',
                'updated_at' => now(),
                'created_at' => now(),
            ]);

            $students[] = $row + ['id' => $studentId, 'user_id' => $userId];
        }

        return $students;
    }

    private function seedProfessor(int $facultyId, int $departmentId): int
    {
        $userId = $this->updateOrCreateId('users', ['institution_id' => 'PROF-CE-100'], [
            'public_id' => 'prof-demo-ce',
            'role' => 'professor',
            'name' => 'Dr. Arben Krasniqi',
            'email' => 'arben.krasniqi@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'faculty_id' => $facultyId,
            'department_id' => $departmentId,
            'avatar_url' => null,
            'remember_token' => null,
            'updated_at' => now(),
            'created_at' => now(),
        ]);

        return $this->updateOrCreateId('professors', ['user_id' => $userId], [
            'title' => 'Associate Professor',
            'office' => 'B-301',
            'office_hours' => 'Tue 13:00 - 15:00',
            'consultation' => 'By appointment',
            'updated_at' => now(),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $student
     * @param  array<string, array<string, mixed>>  $courses
     * @param  array<string, int>  $semesters
     */
    private function seedStudentData(array $student, array $courses, array $semesters): void
    {
        $completedGrades = $student['student_key'] === 'demo-student-one'
            ? ['ce-3-db' => 9, 'ce-3-os' => 8, 'ce-3-net' => 9, 'ce-3-dsa' => 10, 'ce-3-se' => 8, 'ce-3-stat' => 8]
            : ['ce-3-db' => 8, 'ce-3-os' => 7, 'ce-3-net' => 8, 'ce-3-dsa' => 8, 'ce-3-se' => 7, 'ce-3-stat' => 9];

        $activeScores = $student['student_key'] === 'demo-student-one'
            ? ['ce-4-web' => 9, 'ce-4-arch' => 8, 'ce-4-ai' => 9, 'ce-4-mobile' => 8, 'ce-4-sec' => 7, 'ce-4-hci' => 10]
            : ['ce-4-web' => 8, 'ce-4-arch' => 7, 'ce-4-ai' => 8, 'ce-4-mobile' => 7, 'ce-4-sec' => 6, 'ce-4-hci' => 9];

        foreach ($courses as $key => $course) {
            $isCompleted = $course['semester_code'] === 'sem-3';
            $grade = $isCompleted ? $completedGrades[$key] : $activeScores[$key];
            $attendance = $this->attendanceRateFor($student['student_key'], $key);

            $deadline = DB::table('course_events')
                ->where('course_id', $course['id'])
                ->where('event_key', 'project-submission')
                ->where('category', 'deadline')
                ->first();

            $enrollmentId = $this->updateOrCreateId('student_enrollments', [
                'student_id' => $student['id'],
                'course_id' => $course['id'],
                'semester_id' => $course['semester_id'],
            ], [
                'status' => $isCompleted ? 'completed' : 'active',
                'status_label' => $isCompleted ? 'Completed' : 'Active',
                'current_grade' => $isCompleted ? $this->gradeLabel($grade) : $this->gradeLabel($grade).' projected',
                'current_grade_points' => $grade,
                'attendance_percentage' => $attendance,
                'next_important_event_id' => $isCompleted ? null : $deadline?->id,
                'enrolled_on' => $isCompleted ? '2025-10-01' : '2026-02-17',
                'updated_at' => now(),
                'created_at' => now(),
            ]);

            $this->seedEnrollmentGrades($enrollmentId, $isCompleted, $grade);
            $this->seedCourseAttendance($enrollmentId, $isCompleted, $attendance);

            if ($isCompleted) {
                $this->updateOrCreateId('transcript_course_grades', [
                    'student_id' => $student['id'],
                    'course_id' => $course['id'],
                    'semester_code' => 'sem-3',
                ], [
                    'numeric_grade' => $grade,
                    'grade_points' => $grade,
                    'status' => $grade >= 6 ? 'passed' : 'failed',
                    'status_label' => $grade >= 6 ? 'Passed' : 'Failed',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);
            } else {
                $this->updateOrCreateId('transcript_course_grades', [
                    'student_id' => $student['id'],
                    'course_id' => $course['id'],
                    'semester_code' => 'sem-4',
                ], [
                    'numeric_grade' => $grade,
                    'grade_points' => $grade,
                    'status' => 'in-progress',
                    'status_label' => 'In progress',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);
            }
        }

        $this->seedTranscriptSummary($student, $completedGrades);
        $this->seedAttendancePage($student, $courses, $semesters);
        $this->seedDashboard($student, $courses, $activeScores);
    }

    private function seedEnrollmentGrades(int $enrollmentId, bool $isCompleted, int $grade): void
    {
        $records = $isCompleted
            ? [
                ['key' => 'midterm', 'title' => 'Midterm Exam', 'type' => 'Exam', 'score' => max(6, $grade - 1).'/10', 'weight' => '30%', 'date' => '2025-11-20', 'status' => 'Graded'],
                ['key' => 'assignments', 'title' => 'Assignments Portfolio', 'type' => 'Assignments', 'score' => $grade.'/10', 'weight' => '30%', 'date' => '2025-12-16', 'status' => 'Graded'],
                ['key' => 'final', 'title' => 'Final Exam', 'type' => 'Exam', 'score' => $grade.'/10', 'weight' => '40%', 'date' => '2026-01-20', 'status' => 'Final'],
            ]
            : [
                ['key' => 'quiz-1', 'title' => 'Quiz 1', 'type' => 'Quiz', 'score' => max(6, $grade - 1).'/10', 'weight' => '10%', 'date' => '2026-03-18', 'status' => 'Graded'],
                ['key' => 'assignment-1', 'title' => 'Assignment 1', 'type' => 'Assignment', 'score' => $grade.'/10', 'weight' => '15%', 'date' => '2026-04-10', 'status' => 'Graded'],
                ['key' => 'midterm', 'title' => 'Midterm Exam', 'type' => 'Exam', 'score' => $grade.'/10', 'weight' => '30%', 'date' => '2026-04-29', 'status' => 'Graded'],
            ];

        foreach ($records as $record) {
            $this->updateOrCreateId('course_grade_records', [
                'student_enrollment_id' => $enrollmentId,
                'grade_key' => $record['key'],
            ], [
                'title' => $record['title'],
                'type' => $record['type'],
                'score' => $record['score'],
                'weight_label' => $record['weight'],
                'graded_on' => $record['date'],
                'date_label' => date('M j, Y', strtotime($record['date'])),
                'status' => $record['status'],
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }
    }

    private function seedCourseAttendance(int $enrollmentId, bool $isCompleted, int $attendance): void
    {
        $sessionsHeld = $isCompleted ? 18 : 12;
        $sessionsAttended = (int) round($sessionsHeld * $attendance / 100);

        $this->updateOrCreateId('course_attendance_summaries', [
            'student_enrollment_id' => $enrollmentId,
        ], [
            'required_percentage' => 75,
            'sessions_held' => $sessionsHeld,
            'sessions_attended' => $sessionsAttended,
            'status' => $attendance >= 75 ? 'On track' : 'Needs attention',
            'summary_items' => json_encode([
                ['label' => 'Present', 'value' => $sessionsAttended],
                ['label' => 'Missed', 'value' => $sessionsHeld - $sessionsAttended],
            ]),
            'updated_at' => now(),
            'created_at' => now(),
        ]);

        $baseDate = $isCompleted ? '2025-11-03' : '2026-03-02';
        foreach (range(0, 2) as $index) {
            $date = date('Y-m-d', strtotime($baseDate.' +'.($index * 7).' days'));
            $status = $index === 2 && $attendance < 85 ? 'absent' : ($index === 1 && $attendance < 90 ? 'late' : 'present');
            $this->updateOrCreateId('course_attendance_records', [
                'student_enrollment_id' => $enrollmentId,
                'record_key' => 'demo-'.$index,
            ], [
                'held_on' => $date,
                'date_label' => date('M j, Y', strtotime($date)),
                'type' => 'Lecture',
                'status' => $status,
                'status_label' => ucfirst($status),
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $student
     * @param  array<string, array<string, mixed>>  $courses
     * @param  array<string, int>  $semesters
     */
    private function seedAttendancePage(array $student, array $courses, array $semesters): void
    {
        $weekId = $this->updateOrCreateId('attendance_weeks', [
            'student_id' => $student['id'],
            'starts_on' => '2026-05-25',
            'ends_on' => '2026-05-29',
        ], [
            'semester_id' => $semesters['sem-4'],
            'label' => 'May 25 - May 29, 2026',
            'updated_at' => now(),
            'created_at' => now(),
        ]);

        $activeCourses = array_values(array_filter($courses, fn (array $course): bool => $course['semester_code'] === 'sem-4'));
        $historyStatuses = ['present', 'late', 'present', 'absent', 'present', 'present', 'late', 'present', 'absent', 'present', 'present', 'present'];
        $historyDates = ['2026-03-02', '2026-03-04', '2026-03-09', '2026-03-12', '2026-03-16', '2026-03-19', '2026-03-23', '2026-03-26', '2026-04-02', '2026-04-09', '2026-04-16', '2026-04-23'];

        $present = 0;
        $late = 0;
        $absent = 0;

        foreach ($historyStatuses as $index => $status) {
            $course = $activeCourses[$index % count($activeCourses)];
            $date = $historyDates[$index];
            $present += $status === 'present' ? 1 : 0;
            $late += $status === 'late' ? 1 : 0;
            $absent += $status === 'absent' ? 1 : 0;

            $this->updateOrCreateId('attendance_history_records', [
                'student_id' => $student['id'],
                'course_id' => $course['id'],
                'record_key' => 'demo-'.$student['student_key'].'-'.$index,
            ], [
                'recorded_on' => $date,
                'date_label' => date('M j, Y', strtotime($date)),
                'time_label' => substr($course['starts_at'], 0, 5),
                'type' => 'Lecture',
                'professor_name' => 'Dr. Arben Krasniqi',
                'result' => $status,
                'result_label' => ucfirst($status),
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }

        $total = count($historyStatuses);
        $overall = (int) round((($present + $late) / $total) * 100);

        $this->updateOrCreateId('attendance_summaries', [
            'student_id' => $student['id'],
            'course_id' => null,
            'semester_id' => $semesters['sem-4'],
        ], [
            'overall_attendance' => $overall,
            'present_sessions' => $present + $late,
            'total_sessions' => $total,
            'absences' => $absent,
            'late_records' => $late,
            'comparison_value' => 3,
            'comparison_direction' => 'up',
            'comparison_label' => '3% higher than previous 4 weeks',
            'updated_at' => now(),
            'created_at' => now(),
        ]);

        foreach ($activeCourses as $course) {
            $rate = $this->attendanceRateFor($student['student_key'], $course['key']);
            $this->updateOrCreateId('attendance_summaries', [
                'student_id' => $student['id'],
                'course_id' => $course['id'],
                'semester_id' => $semesters['sem-4'],
            ], [
                'overall_attendance' => $rate,
                'present_sessions' => (int) round($rate / 10),
                'total_sessions' => 10,
                'absences' => max(0, 10 - (int) round($rate / 10)),
                'late_records' => $rate < 90 ? 1 : 0,
                'comparison_value' => 0,
                'comparison_direction' => 'flat',
                'comparison_label' => 'Stable',
                'updated_at' => now(),
                'created_at' => now(),
            ]);

            $this->updateOrCreateId('attendance_last_recorded', [
                'student_id' => $student['id'],
                'course_id' => $course['id'],
            ], [
                'recorded_on' => '2026-05-21',
                'date_label' => 'May 21, 2026',
                'time_label' => substr($course['starts_at'], 0, 5),
                'status' => 'recorded',
                'status_label' => 'Recorded',
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }

        foreach ([
            ['date' => '2026-05-25', 'name' => 'Monday', 'short' => 'Mon'],
            ['date' => '2026-05-26', 'name' => 'Tuesday', 'short' => 'Tue'],
            ['date' => '2026-05-27', 'name' => 'Wednesday', 'short' => 'Wed'],
            ['date' => '2026-05-28', 'name' => 'Thursday', 'short' => 'Thu'],
            ['date' => '2026-05-29', 'name' => 'Friday', 'short' => 'Fri'],
        ] as $day) {
            $dayId = $this->updateOrCreateId('attendance_schedule_days', [
                'attendance_week_id' => $weekId,
                'day_on' => $day['date'],
            ], [
                'day_name' => $day['name'],
                'day_short' => $day['short'],
                'date_label' => date('M j', strtotime($day['date'])),
                'is_today' => $day['date'] === '2026-05-26',
                'updated_at' => now(),
                'created_at' => now(),
            ]);

            foreach ($activeCourses as $course) {
                if (! in_array($day['name'], $course['days'], true)) {
                    continue;
                }

                $status = $day['date'] < '2026-05-26' ? 'present' : 'scheduled';
                $this->updateOrCreateId('attendance_schedule_blocks', [
                    'attendance_schedule_day_id' => $dayId,
                    'course_id' => $course['id'],
                    'block_key' => 'demo-'.$course['key'],
                ], [
                    'time_label' => substr($course['starts_at'], 0, 5).' - '.substr($course['ends_at'], 0, 5),
                    'starts_at' => $course['starts_at'],
                    'ends_at' => $course['ends_at'],
                    'room' => $course['room'],
                    'type' => 'Lecture',
                    'status' => $status,
                    'status_label' => ucfirst($status),
                    'tone' => $status === 'scheduled' ? 'blue' : 'green',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $student
     * @param  array<string, int>  $completedGrades
     */
    private function seedTranscriptSummary(array $student, array $completedGrades): void
    {
        foreach ([
            ['code' => 'sem-3', 'label' => '3rd Semester', 'default' => false],
            ['code' => 'sem-4', 'label' => '4th Semester', 'default' => true],
        ] as $option) {
            $this->updateOrCreateId('transcript_semester_options', [
                'student_id' => $student['id'],
                'semester_code' => $option['code'],
            ], [
                'label' => $option['label'],
                'is_default' => $option['default'],
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }

        $average = round(array_sum($completedGrades) / count($completedGrades), 2);
        $this->updateOrCreateId('transcript_summaries', ['student_id' => $student['id']], [
            'academic_year' => self::ACADEMIC_YEAR,
            'average_grade' => $average,
            'grade_status' => 'Good academic progress',
            'total_credits_earned' => 30,
            'required_credits' => self::REQUIRED_CREDITS,
            'courses_completed' => 6,
            'completion_percentage' => 17,
            'academic_standing' => 'Good standing',
            'eligibility_status' => 'Eligible to continue',
            'transcript_action_label' => 'Download unofficial transcript',
            'transcript_action_status' => 'available',
            'updated_at' => now(),
            'created_at' => now(),
        ]);

        $counts = array_count_values($completedGrades);
        foreach ([10, 9, 8, 7, 6, 5] as $grade) {
            $count = (int) ($counts[$grade] ?? 0);
            $this->updateOrCreateId('grade_distributions', [
                'student_id' => $student['id'],
                'grade' => $grade,
            ], [
                'label' => $this->gradeLabel($grade),
                'count' => $count,
                'percentage' => (int) round(($count / count($completedGrades)) * 100),
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $student
     * @param  array<string, array<string, mixed>>  $courses
     * @param  array<string, int>  $activeScores
     */
    private function seedDashboard(array $student, array $courses, array $activeScores): void
    {
        $activeCourses = array_values(array_filter($courses, fn (array $course): bool => $course['semester_code'] === 'sem-4'));

        foreach ([
            ['key' => 'gpa', 'label' => 'Current GPA', 'value' => number_format((float) $student['gpa'], 2), 'helper' => 'After 3rd Semester', 'tone' => 'blue', 'sort' => 1],
            ['key' => 'credits', 'label' => 'Credits Earned', 'value' => '30 / 180', 'helper' => '6 completed courses', 'tone' => 'green', 'sort' => 2],
            ['key' => 'active-courses', 'label' => 'Active Courses', 'value' => '6', 'helper' => '4th Semester', 'tone' => 'purple', 'sort' => 3],
            ['key' => 'attendance', 'label' => 'Attendance', 'value' => '86%', 'helper' => 'Current semester average', 'tone' => 'orange', 'sort' => 4],
        ] as $metric) {
            $this->updateOrCreateId('student_dashboard_metrics', [
                'student_id' => $student['id'],
                'metric_key' => $metric['key'],
            ], [
                'label' => $metric['label'],
                'value' => $metric['value'],
                'helper' => $metric['helper'],
                'tone' => $metric['tone'],
                'sort_order' => $metric['sort'],
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }

        foreach (array_slice($activeCourses, 0, 3) as $index => $course) {
            $this->updateOrCreateId('student_dashboard_classes', [
                'student_id' => $student['id'],
                'class_key' => 'today-'.$course['key'],
            ], [
                'course_id' => $course['id'],
                'time_label' => substr($course['starts_at'], 0, 5).' - '.substr($course['ends_at'], 0, 5),
                'course_code' => $course['code'],
                'course_name' => $course['name'],
                'room' => $course['room'],
                'type' => 'Lecture',
                'tone' => ['blue', 'green', 'purple'][$index],
                'sort_order' => $index + 1,
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }

        foreach (array_slice($activeCourses, 0, 4) as $index => $course) {
            $this->updateOrCreateId('student_dashboard_deadlines', [
                'student_id' => $student['id'],
                'deadline_key' => 'project-'.$course['key'],
            ], [
                'course_id' => $course['id'],
                'title' => 'Project submission',
                'course_code' => $course['code'],
                'date_label' => 'May 29, 2026',
                'status_label' => $index === 0 ? 'Due soon' : 'Upcoming',
                'tone' => $index === 0 ? 'orange' : 'blue',
                'sort_order' => $index + 1,
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }

        foreach (array_slice($activeCourses, 0, 4) as $index => $course) {
            $score = $activeScores[$course['key']];
            $this->updateOrCreateId('student_dashboard_latest_grades', [
                'student_id' => $student['id'],
                'grade_key' => 'midterm-'.$course['key'],
            ], [
                'course_id' => $course['id'],
                'course' => $course['code'],
                'assessment' => 'Midterm Exam',
                'type' => 'Exam',
                'grade' => $this->gradeLabel($score),
                'date_label' => 'Apr 29, 2026',
                'tone' => $score >= 8 ? 'green' : 'orange',
                'sort_order' => $index + 1,
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }

        $warningCourse = $courses['ce-4-sec'];
        $rate = $this->attendanceRateFor($student['student_key'], 'ce-4-sec');
        $this->updateOrCreateId('student_dashboard_attendance_warnings', ['student_id' => $student['id']], [
            'course_id' => $warningCourse['id'],
            'course_code' => $warningCourse['code'],
            'course_name' => $warningCourse['name'],
            'rate' => $rate,
            'required_rate' => 75,
            'message' => $rate < 75 ? 'Attendance below requirement' : 'Attendance close to requirement',
            'detail' => $rate < 75
                ? 'Attend the next sessions to recover the required minimum.'
                : 'Keep attending regularly to stay above the required minimum.',
            'updated_at' => now(),
            'created_at' => now(),
        ]);

        foreach ($activeCourses as $index => $course) {
            $this->updateOrCreateId('student_dashboard_attendance_summaries', [
                'student_id' => $student['id'],
                'course_id' => $course['id'],
            ], [
                'course_name' => $course['code'],
                'rate' => $this->attendanceRateFor($student['student_key'], $course['key']),
                'sort_order' => $index + 1,
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }
    }

    private function attendanceRateFor(string $studentKey, string $courseKey): int
    {
        $rates = [
            'demo-student-one' => ['ce-4-web' => 92, 'ce-4-arch' => 88, 'ce-4-ai' => 90, 'ce-4-mobile' => 85, 'ce-4-sec' => 78, 'ce-4-hci' => 95],
            'demo-student-two' => ['ce-4-web' => 85, 'ce-4-arch' => 80, 'ce-4-ai' => 82, 'ce-4-mobile' => 76, 'ce-4-sec' => 70, 'ce-4-hci' => 88],
        ];

        if (isset($rates[$studentKey][$courseKey])) {
            return $rates[$studentKey][$courseKey];
        }

        return $studentKey === 'demo-student-one' ? 94 : 88;
    }

    private function gradeLabel(int $grade): string
    {
        return match ($grade) {
            10 => '10 Excellent',
            9 => '9 Very Good',
            8 => '8 Good',
            7 => '7 Satisfactory',
            6 => '6 Sufficient',
            default => '5 Failed',
        };
    }

    /**
     * @param  array<string, mixed>  $where
     * @param  array<string, mixed>  $values
     */
    private function updateOrCreateId(string $table, array $where, array $values): int
    {
        $existing = DB::table($table)->where($where)->first();

        if ($existing !== null) {
            DB::table($table)->where('id', $existing->id)->update(array_diff_key($values, array_flip(['created_at'])));

            return (int) $existing->id;
        }

        return (int) DB::table($table)->insertGetId(array_merge($where, $values));
    }
}
