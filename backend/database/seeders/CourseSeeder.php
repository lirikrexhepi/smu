<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $departmentId = DB::table('departments')->where('name', 'Computer Engineering')->value('id');
        $semester3Id = DB::table('semesters')->where('code', 'sem-3')->value('id');
        $semester4Id = DB::table('semesters')->where('code', 'sem-4')->value('id');
        $professorIds = DB::table('professors')->pluck('id')->toArray();

        if (!$departmentId || !$semester3Id || !$semester4Id || empty($professorIds)) {
            throw new \RuntimeException('Institution structure and professors must be seeded first.');
        }

        $semesters = [
            'sem-3' => $semester3Id,
            'sem-4' => $semester4Id,
        ];

        $activeDemoSchedule = $this->activeDemoSchedule();

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
                'days_label' => $activeDemoSchedule['days_label'],
                'days' => $activeDemoSchedule['days'],
                'starts_at' => $activeDemoSchedule['starts_at'],
                'ends_at' => $activeDemoSchedule['ends_at'],
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

        $coursesCount = 0;

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

            $professorId = $professorIds[$coursesCount % count($professorIds)];
            $coursesCount++;

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
        }
    }

    private function activeDemoSchedule(): array
    {
        $now = now();
        $startHour = max(0, (int) $now->format('H') - 1);
        $endHour = min(23, (int) $now->format('H') + 1);

        return [
            'days_label' => $now->format('D'),
            'days' => [$now->format('l')],
            'starts_at' => sprintf('%02d:00:00', $startHour),
            'ends_at' => sprintf('%02d:59:00', $endHour),
        ];
    }

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
