export type StudentDashboardMetric = {
  id: string
  label: string
  value: string
  helper: string
  tone: 'blue' | 'green' | 'orange' | 'purple'
}

export type StudentDashboardClass = {
  id: string
  time: string
  courseCode: string
  courseName: string
  room: string
  type: string
  tone: StudentDashboardMetric['tone']
}

export type StudentDashboardDeadline = {
  id: string
  title: string
  courseCode: string
  date: string
  statusLabel: string
  tone: StudentDashboardMetric['tone']
}

export type StudentDashboardGrade = {
  id: string
  course: string
  assessment: string
  type: string
  grade: string
  date: string
  tone: 'blue' | 'green' | 'orange' | 'purple'
}

export type StudentDashboardAttendanceWarning = {
  courseCode: string
  courseName: string
  rate: number
  requiredRate: number
  message: string
  detail: string
}

export type StudentDashboardAttendanceSummary = {
  courseName: string
  rate: number
}

export type StudentDashboardSummary = {
  studentName: string
  semester: string
  academicTerm: string
  metrics: StudentDashboardMetric[]
  todaysClasses: StudentDashboardClass[]
  upcomingDeadlines: StudentDashboardDeadline[]
  latestGrades: StudentDashboardGrade[]
  attendanceWarning: StudentDashboardAttendanceWarning
  attendanceSummary: StudentDashboardAttendanceSummary[]
}

export type StudentEmergencyContact = {
  name: string
  relationship: string
  phone: string
}

export type StudentProfile = {
  studentId: string
  fullName: string
  initials: string
  avatarUrl: string | null
  status: string
  studentStatusLabel: string
  faculty: string
  department: string
  program: string
  yearOfStudy: string
  semester: string
  academicYear: string
  currentGpa: string
  creditsEarned: string
  creditsRequired: string
  academicStanding: string
  email: string
  phone: string
  address: string
  dateOfBirth: string
  gender: string
  nationality: string
  personalNumber: string
  emergencyContact: StudentEmergencyContact
  updatedAt: string | null
}

export type StudentProfileUpdate = {
  fullName: string
  email: string
  phone: string
  address: string
  dateOfBirth: string
  gender: string
  nationality: string
  personalNumber: string
  emergencyContactName: string
  emergencyContactRelationship: string
  emergencyContactPhone: string
}

export type StudentCourseTone = 'blue' | 'green' | 'orange' | 'purple'

export type StudentCourseStatus = 'active' | 'registered' | 'upcoming'

export type StudentCourseEvent = {
  id: string
  title: string
  type: string
  date: string
  time: string
  statusLabel: string
  tone: StudentCourseTone
}

export type StudentCourseSchedule = {
  days: string
  time: string
  room: string
  label: string
}

export type StudentCourseOverviewItem = {
  courseId: string
  code: string
  name: string
  professor: string
  ects: number
  schedule: StudentCourseSchedule
  room: string
  semester: string
  enrollmentStatus: StudentCourseStatus
  enrollmentStatusLabel: string
  currentGrade: string
  attendancePercentage: number
  nextImportantEvent: StudentCourseEvent | null
}

export type StudentCourseStatusOption = {
  value: StudentCourseStatus
  label: string
}

export type StudentCoursesOverview = {
  semester: string
  academicYear: string
  summary: {
    enrolledCourses: number
    totalEcts: number
    ectsTarget: number
    upcomingDeadlines: number
  }
  filters: {
    semesters: string[]
    statuses: StudentCourseStatusOption[]
  }
  courses: StudentCourseOverviewItem[]
  upcomingDeadlines: Array<StudentCourseEvent & {
    courseId: string
    courseCode: string
    courseName: string
  }>
}

export type StudentCourseMaterial = {
  id: string
  title: string
  type: string
  updatedAt: string
  size: string
  downloadUrl?: string
}

export type StudentCourseInfoItem = {
  id: string
  label: string
  value: string
}

export type StudentCourseAttendance = {
  percentage: number
  requiredPercentage: number
  sessionsHeld: number
  sessionsAttended: number
  status: string
  summary: Array<{ label: string; value: string }>
  records: Array<{ id: string; date: string; type: string; status: string }>
}

export type StudentCourseGrades = {
  currentGrade: string
  scale: string
  breakdown: Array<{ component: string; weight: number }>
  records: Array<{
    id: string
    title: string
    type: string
    score: string
    weight: string
    date: string
    status: string
  }>
}

export type StudentCourseAssessment = StudentCourseEvent & {
  mode: string
  description: string
}

export type StudentCourseExam = StudentCourseEvent & {
  duration: string
  room: string
}

export type StudentCourseAnnouncement = {
  id: string
  title: string
  body: string
  date: string
}

export type StudentCourseDetail = {
  courseId: string
  code: string
  name: string
  professor: {
    name: string
    email: string
    officeHours: string
    consultation: string
  }
  ects: number
  schedule: StudentCourseSchedule
  room: string
  semester: string
  status: string
  description: string
  overview: {
    learningOutcomes: string[]
    topics: string[]
    gradingBreakdown: string
  }
  courseInfo: StudentCourseInfoItem[]
  materials: StudentCourseMaterial[]
  attendance: StudentCourseAttendance
  grades: StudentCourseGrades
  assessments: StudentCourseAssessment[]
  exams: StudentCourseExam[]
  announcements: StudentCourseAnnouncement[]
  deadlines: StudentCourseEvent[]
  enrollment: {
    status: StudentCourseStatus
    statusLabel: string
    currentGrade: string
    attendancePercentage: number
    nextImportantEventId: string
    enrolledAt: string
  }
}
