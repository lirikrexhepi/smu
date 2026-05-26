import { apiGet, apiPost } from '@/lib/api/client'

export function getProfessorDashboard() {
  return apiGet<{
    metrics: Array<{ label: string; value: string; helper: string; tone: string }>
    sessions: Array<{
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
      status: string
    }>
    assessments: Array<{
      id: string
      courseCode: string
      title: string
      type: string
      dueDate: string
      submitted: number
      total: number
      graded: number
    }>
    courses: Array<{
      id: string
      code: string
      name: string
      students: number
      averageGrade: number
      attendanceRate: number
      status: string
    }>
  }>('/api/professor/dashboard')
}

export function getProfessorCourses() {
  return apiGet<{
    courses: Array<{
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
      tone: 'blue' | 'green' | 'orange' | 'purple' | 'red' | 'teal'
    }>
  }>('/api/professor/courses')
}

export function getProfessorAttendance() {
  return apiGet<{
    sessions: Array<{
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
      status: string
    }>
    courses: Array<{
      id: string
      code: string
      name: string
      attendanceRate: number
    }>
  }>('/api/professor/attendance')
}

export function createAttendanceSession(data: { courseKey: string }) {
  return apiPost<{
    id: string
    code: string
    qrToken: string
    courseName: string
    date: string
  }>('/api/professor/attendance/session', data)
}

export function recordAttendance(sessionId: number | string, records: Array<{ studentId: string; status: string }>) {
  return apiPost<null>(`/api/professor/attendance/session/${sessionId}/record`, { records })
}

export function getProfessorGradebook() {
  return apiGet<{
    gradebook: Array<{
      id: string
      student: string
      studentId: string
      courseCode: string
      midterm: number
      project: number
      final: number | null
      average: number
      status: 'Passed' | 'In Progress' | 'At Risk'
    }>
    assessments: Array<{
      id: string
      courseCode: string
      title: string
      type: string
      dueDate: string
      submitted: number
      total: number
      graded: number
    }>
  }>('/api/professor/gradebook')
}

export function saveStudentGrade(data: {
  studentId: string
  courseCode: string
  component: 'midterm' | 'project' | 'final'
  grade: number
}) {
  return apiPost<null>('/api/professor/gradebook/grade', data)
}
