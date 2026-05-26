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

        $professorIds = $this->seedProfessors($facultyId, $departmentId);
        $this->seedAdmin($facultyId, $departmentId);
        $courses = $this->seedCourses($departmentId, $semesters, $professorIds);
        $students = $this->seedStudents($facultyId, $departmentId, $programId);

        foreach ($students as $student) {
            $this->seedStudentData($student, $courses, $semesters);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function seedCourses(int $departmentId, array $semesters, array $professorIds): array
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

            $professorId = $professorIds[count($courses) % count($professorIds)];

            DB::table('course_professor')
                ->where('course_id', $courseId)
                ->where('role', 'instructor')
                ->where('professor_id', '!=', $professorId)
                ->delete();

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
                ['key' => 'syllabus', 'title' => $row['name'].' Syllabus', 'type' => 'PDF', 'size' => '420 KB', 'date' => $row['semester_code'] === 'sem-3' ? '2025-10-01 09:00:00' : '2026-02-17 09:00:00'],
                ['key' => 'lecture-notes', 'title' => 'Lecture Notes Pack', 'type' => 'PDF', 'size' => '1.8 MB', 'date' => $row['semester_code'] === 'sem-3' ? '2025-11-10 09:00:00' : '2026-04-08 09:00:00'],
            ] as $material) {
                $this->updateOrCreateId('course_materials', [
                    'course_id' => $courseId,
                    'material_key' => $material['key'],
                ], [
                    'title' => $material['title'],
                    'type' => $material['type'],
                    'size_label' => $material['size'],
                    'download_url' => '/uploads/materials/test-document.txt',
                    'published_at' => $material['date'],
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

            $assessmentDate = $row['semester_code'] === 'sem-3' ? '2025-11-20' : '2026-04-29';
            $this->updateOrCreateId('course_events', [
                'course_id' => $courseId,
                'event_key' => 'midterm',
                'category' => 'assessment',
            ], [
                'title' => $row['name'].' Midterm',
                'type' => 'Midterm',
                'event_date' => $assessmentDate,
                'event_time' => '10:00:00',
                'date_label' => date('M j, Y', strtotime($assessmentDate)),
                'time_label' => '10:00',
                'status_label' => $row['semester_code'] === 'sem-3' ? 'Completed' : 'Graded',
                'tone' => $row['semester_code'] === 'sem-3' ? 'green' : 'purple',
                'mode' => 'On campus',
                'duration' => '75 minutes',
                'room' => $row['room'],
                'description' => 'Covers lectures, lab work, and assigned readings from the first half of the course.',
                'updated_at' => now(),
                'created_at' => now(),
            ]);

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

            foreach ([
                ['key' => 'welcome', 'title' => 'Course workspace opened', 'body' => 'The course workspace now includes the syllabus, weekly topics, and first set of materials.', 'date' => $row['semester_code'] === 'sem-3' ? '2025-10-01' : '2026-02-17'],
                ['key' => 'project', 'title' => 'Project guidance published', 'body' => 'Project requirements and grading expectations are available in the materials section.', 'date' => $row['semester_code'] === 'sem-3' ? '2025-12-01' : '2026-05-20'],
            ] as $announcement) {
                $this->updateOrCreateId('course_announcements', [
                    'course_id' => $courseId,
                    'announcement_key' => $announcement['key'],
                ], [
                    'title' => $announcement['title'],
                    'body' => $announcement['body'],
                    'published_on' => $announcement['date'],
                    'date_label' => date('M j', strtotime($announcement['date'])),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);
            }

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
            ],
        ];

        $students = [];

        foreach ($rows as $row) {
            $userId = $this->updateOrCreateId('users', ['public_id' => $row['public_id']], [
                'role' => 'student',
                'institution_id' => $row['institution_id'],
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

    /**
     * @return array<int, int>
     */
    private function seedProfessors(int $facultyId, int $departmentId): array
    {
        $rows = [
            [
                'public_id' => 'prof-demo-ce',
                'institution_id' => 'PROF-1001',
                'name' => 'Dr. Arben Krasniqi',
                'email' => 'arben.krasniqi@example.com',
                'title' => 'Associate Professor',
                'office' => 'B-301',
                'office_hours' => 'Tue 13:00 - 15:00',
                'consultation' => 'By appointment',
            ],
            [
                'public_id' => 'prof-demo-elira',
                'institution_id' => 'PROF-1002',
                'name' => 'Dr. Elira Dervishi',
                'email' => 'elira.dervishi@example.com',
                'title' => 'Assistant Professor',
                'office' => 'C-214',
                'office_hours' => 'Thu 13:00 - 15:00',
                'consultation' => 'Email for appointment',
            ],
        ];

        $professors = [];

        foreach ($rows as $row) {
            $userId = $this->updateOrCreateId('users', ['public_id' => $row['public_id']], [
                'role' => 'professor',
                'institution_id' => $row['institution_id'],
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

            $professors[] = $this->updateOrCreateId('professors', ['user_id' => $userId], [
                'title' => $row['title'],
                'office' => $row['office'],
                'office_hours' => $row['office_hours'],
                'consultation' => $row['consultation'],
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }

        return $professors;
    }

    private function seedAdmin(int $facultyId, int $departmentId): void
    {
        $this->updateOrCreateId('users', ['public_id' => 'admin-demo-1001'], [
            'role' => 'admin',
            'institution_id' => 'ADM-1001',
            'name' => 'Demo Admin',
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'faculty_id' => $facultyId,
            'department_id' => $departmentId,
            'avatar_url' => null,
            'remember_token' => null,
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
            ? ['ce-4-web' => 9, 'ce-4-arch' => 8, 'ce-4-ai' => 9, 'ce-4-mobile' => 9, 'ce-4-sec' => 7, 'ce-4-hci' => 10]
            : ['ce-4-web' => 8, 'ce-4-arch' => 7, 'ce-4-ai' => 8, 'ce-4-mobile' => 7, 'ce-4-sec' => 6, 'ce-4-hci' => 9];

        foreach ($courses as $key => $course) {
            $isCompleted = $course['semester_code'] === 'sem-3';
            $grade = $isCompleted ? $completedGrades[$key] : $activeScores[$key];
            $attendance = $this->attendanceRateFor($student['student_key'], $key);

            $enrollmentId = $this->updateOrCreateId('student_enrollments', [
                'student_id' => $student['id'],
                'course_id' => $course['id'],
                'semester_id' => $course['semester_id'],
            ], [
                'status' => $isCompleted ? 'completed' : 'active',
                'status_label' => $isCompleted ? 'Completed' : 'Active',
                'enrolled_on' => $isCompleted ? '2025-10-01' : '2026-02-17',
                'updated_at' => now(),
                'created_at' => now(),
            ]);

            $this->seedEnrollmentGrades($enrollmentId, $isCompleted, $grade);
            $this->seedCourseAttendance($enrollmentId, $isCompleted, $attendance);
        }

        $this->seedAttendancePage($student, $courses, $semesters);
    }

    private function seedEnrollmentGrades(int $enrollmentId, bool $isCompleted, int $grade): void
    {
        $records = $isCompleted
            ? [
                ['key' => 'midterm', 'title' => 'Midterm Exam', 'type' => 'Exam', 'grade' => $grade, 'weight' => 30, 'date' => '2025-11-20', 'status' => 'Graded'],
                ['key' => 'assignments', 'title' => 'Assignments Portfolio', 'type' => 'Assignments', 'grade' => $grade, 'weight' => 30, 'date' => '2025-12-16', 'status' => 'Graded'],
                ['key' => 'final', 'title' => 'Final Exam', 'type' => 'Exam', 'grade' => $grade, 'weight' => 40, 'date' => '2026-01-20', 'status' => 'Final'],
            ]
            : [
                ['key' => 'quiz-1', 'title' => 'Quiz 1', 'type' => 'Quiz', 'grade' => $grade, 'weight' => 10, 'date' => '2026-03-18', 'status' => 'Graded'],
                ['key' => 'assignment-1', 'title' => 'Assignment 1', 'type' => 'Assignment', 'grade' => $grade, 'weight' => 15, 'date' => '2026-04-10', 'status' => 'Graded'],
                ['key' => 'midterm', 'title' => 'Midterm Exam', 'type' => 'Exam', 'grade' => $grade, 'weight' => 30, 'date' => '2026-04-29', 'status' => 'Graded'],
            ];

        foreach ($records as $record) {
            $this->updateOrCreateId('course_grade_records', [
                'student_enrollment_id' => $enrollmentId,
                'grade_key' => $record['key'],
            ], [
                'title' => $record['title'],
                'type' => $record['type'],
                'score' => null,
                'grade' => $record['grade'],
                'weight' => $record['weight'],
                'weight_label' => null,
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
        $sessionsHeld = 100;
        $sessionsAttended = (int) round($sessionsHeld * $attendance / 100);
        $lateRecords = min(3, max(0, $sessionsAttended - 1), $attendance < 90 ? 2 : 0);
        $presentRecords = $sessionsAttended - $lateRecords;
        $baseDate = $isCompleted ? '2025-10-13' : '2026-02-15';

        foreach (range(0, $sessionsHeld - 1) as $index) {
            $date = date('Y-m-d', strtotime($baseDate.' +'.$index.' days'));
            $status = match (true) {
                $index < $presentRecords => 'present',
                $index < $sessionsAttended => 'late',
                default => 'absent',
            };

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
        $activeCourses = array_values(array_filter($courses, fn (array $course): bool => $course['semester_code'] === 'sem-4'));
        $historyStatuses = ['present', 'late', 'present', 'absent', 'present', 'present', 'late', 'present', 'absent', 'present', 'present', 'present'];
        $historyDates = ['2026-03-02', '2026-03-04', '2026-03-09', '2026-03-12', '2026-03-16', '2026-03-19', '2026-03-23', '2026-03-26', '2026-04-02', '2026-04-09', '2026-04-16', '2026-04-23'];

        foreach ($historyStatuses as $index => $status) {
            $course = $activeCourses[$index % count($activeCourses)];
            $date = $historyDates[$index];

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
