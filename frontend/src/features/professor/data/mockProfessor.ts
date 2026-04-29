export type Tone = 'blue' | 'green' | 'orange' | 'purple' | 'red' | 'teal'

export type ProfessorCourse = {
  id: string
  code: string
  name: string
  semester: string
  room: string
  schedule: string
  students: number
  attendanceRate: number
  averageGrade: number
  pendingGrades: number
  status: 'Active' | 'Exam Week' | 'Closing'
  tone: Tone
}

export type ProfessorSession = {
  id: string
  courseCode: string
  courseName: string
  date: string
  time: string
  room: string
  type: string
  present: number
  absent: number
  late: number
  status: 'Recorded' | 'Open' | 'Scheduled'
}

export type Assessment = {
  id: string
  courseCode: string
  title: string
  type: string
  dueDate: string
  submitted: number
  total: number
  graded: number
}

export type GradebookRow = {
  id: string
  student: string
  studentId: string
  courseCode: string
  midterm: number
  project: number
  final: number | null
  average: number
  status: 'Passed' | 'In Progress' | 'At Risk'
}

export const professorCourses: ProfessorCourse[] = [
  {
    id: 'cs302',
    code: 'CS302',
    name: 'Software Engineering',
    semester: 'Spring 2026',
    room: 'B-204',
    schedule: 'Mon, Wed 10:00-11:30',
    students: 42,
    attendanceRate: 91,
    averageGrade: 8.2,
    pendingGrades: 6,
    status: 'Active',
    tone: 'blue',
  },
  {
    id: 'cs306',
    code: 'CS306',
    name: 'Database Systems',
    semester: 'Spring 2026',
    room: 'Lab 3',
    schedule: 'Tue, Thu 13:00-14:30',
    students: 38,
    attendanceRate: 87,
    averageGrade: 7.9,
    pendingGrades: 11,
    status: 'Active',
    tone: 'green',
  },
  {
    id: 'cs310',
    code: 'CS310',
    name: 'Human Computer Interaction',
    semester: 'Spring 2026',
    room: 'A-118',
    schedule: 'Fri 09:00-12:00',
    students: 31,
    attendanceRate: 83,
    averageGrade: 8.6,
    pendingGrades: 3,
    status: 'Exam Week',
    tone: 'purple',
  },
]

export const professorSessions: ProfessorSession[] = [
  {
    id: 's-1',
    courseCode: 'CS302',
    courseName: 'Software Engineering',
    date: '29 Apr 2026',
    time: '10:00-11:30',
    room: 'B-204',
    type: 'Lecture',
    present: 39,
    absent: 2,
    late: 1,
    status: 'Recorded',
  },
  {
    id: 's-2',
    courseCode: 'CS306',
    courseName: 'Database Systems',
    date: '30 Apr 2026',
    time: '13:00-14:30',
    room: 'Lab 3',
    type: 'Lab',
    present: 0,
    absent: 0,
    late: 0,
    status: 'Open',
  },
  {
    id: 's-3',
    courseCode: 'CS310',
    courseName: 'Human Computer Interaction',
    date: '01 May 2026',
    time: '09:00-12:00',
    room: 'A-118',
    type: 'Workshop',
    present: 0,
    absent: 0,
    late: 0,
    status: 'Scheduled',
  },
]

export const assessments: Assessment[] = [
  {
    id: 'a-1',
    courseCode: 'CS302',
    title: 'Sprint Review Report',
    type: 'Project',
    dueDate: '02 May 2026',
    submitted: 34,
    total: 42,
    graded: 28,
  },
  {
    id: 'a-2',
    courseCode: 'CS306',
    title: 'Normalization Quiz',
    type: 'Quiz',
    dueDate: '04 May 2026',
    submitted: 38,
    total: 38,
    graded: 27,
  },
  {
    id: 'a-3',
    courseCode: 'CS310',
    title: 'Usability Test Findings',
    type: 'Assignment',
    dueDate: '06 May 2026',
    submitted: 25,
    total: 31,
    graded: 22,
  },
]

export const gradebookRows: GradebookRow[] = [
  {
    id: 'g-1',
    student: 'Luan Berisha',
    studentId: 'luri',
    courseCode: 'CS302',
    midterm: 8.5,
    project: 9.0,
    final: null,
    average: 8.8,
    status: 'In Progress',
  },
  {
    id: 'g-2',
    student: 'Arta Krasniqi',
    studentId: 'S1002',
    courseCode: 'CS302',
    midterm: 9.2,
    project: 9.5,
    final: null,
    average: 9.4,
    status: 'Passed',
  },
  {
    id: 'g-3',
    student: 'Dion Gashi',
    studentId: 'S1003',
    courseCode: 'CS306',
    midterm: 6.1,
    project: 6.4,
    final: null,
    average: 6.3,
    status: 'At Risk',
  },
  {
    id: 'g-4',
    student: 'Elira Hoxha',
    studentId: 'S1004',
    courseCode: 'CS310',
    midterm: 8.9,
    project: 9.1,
    final: null,
    average: 9.0,
    status: 'Passed',
  },
]
