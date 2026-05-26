export type ProfessorAttendanceMetricSummary = {
  recordedSessions: number
  present: number
  absent: number
  late: number
}

export type ProfessorAttendanceSessionRecord = {
  id: string
  courseId: string
  courseCode: string
  courseName: string
  date: string
  time: string
  room: string
  present: number
  absent: number
  late: number
  status: string
}

export type ProfessorCourseAttendance = {
  courseId: string
  code: string
  name: string
  attendanceRate: number
}

export type ProfessorAvailableAttendanceClass = {
  courseId: string
  courseCode: string
  courseName: string
  courseScheduleId: number
  scheduleLabel: string
  days: string
  time: string
  room: string
  enrolledCount: number
}

export type ProfessorAttendanceStudentRecord = {
  id: string
  studentId: string
  studentNumber: string
  institutionId: string
  name: string
  email: string
  status: 'pending' | 'present' | 'late' | 'absent' | string
  statusLabel: string
  checkedInAt: string | null
  method: 'qr' | 'code' | 'manual' | null
}

export type ProfessorAttendanceSession = {
  id: string
  courseId: string
  courseCode: string
  courseName: string
  room: string
  schedule: {
    label: string
    days: string
    time: string
  }
  professor: {
    id: string
    name: string
    email: string
  }
  startsAt: string
  endsAt: string
  lateAfterAt: string
  closedAt: string | null
  isActive: boolean
  remainingSeconds: number
  checkInCode: string
  qrToken: string
  qrPayload: string
  totalEnrolled: number
  checkedInCount: number
  presentCount: number
  lateCount: number
  pendingCount: number
  records: ProfessorAttendanceStudentRecord[]
}

export type ProfessorAttendanceOverview = {
  metrics: ProfessorAttendanceMetricSummary
  sessionRecords: ProfessorAttendanceSessionRecord[]
  courseAttendance: ProfessorCourseAttendance[]
  activeSession: ProfessorAttendanceSession | null
}
