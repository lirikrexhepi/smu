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
