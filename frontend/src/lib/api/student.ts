import { apiGet, apiPatch, apiUpload } from '@/lib/api/client'
import type {
  StudentCourseDetail,
  StudentCoursesOverview,
  StudentAttendance,
  StudentDashboardSummary,
  StudentGradesTranscript,
  StudentProfile,
  StudentProfileUpdate,
} from '@/types/student'

export function getStudentDashboard() {
  return apiGet<StudentDashboardSummary>('/api/student/dashboard')
}

export function getStudentCourses() {
  return apiGet<StudentCoursesOverview>('/api/student/courses')
}

export function getStudentCourseDetail(courseId: string) {
  return apiGet<StudentCourseDetail>(`/api/student/courses/${encodeURIComponent(courseId)}`)
}

export function getStudentAttendance(
  filters: { courseId?: string; semester?: string; week?: string } = {},
) {
  const params = new URLSearchParams()

  if (filters.courseId && filters.courseId !== 'all') {
    params.set('courseId', filters.courseId)
  }

  if (filters.semester && filters.semester !== 'all') {
    params.set('semester', filters.semester)
  }

  if (filters.week) {
    params.set('week', filters.week)
  }

  const query = params.toString()

  return apiGet<StudentAttendance>(`/api/student/attendance${query ? `?${query}` : ''}`)
}

export function getStudentGradesTranscript(
  filters: { semester?: string; courseId?: string } = {},
) {
  const params = new URLSearchParams()

  if (filters.semester) {
    params.set('semester', filters.semester)
  }

  if (filters.courseId && filters.courseId !== 'all') {
    params.set('courseId', filters.courseId)
  }

  const query = params.toString()

  return apiGet<StudentGradesTranscript>(`/api/student/grades-transcript${query ? `?${query}` : ''}`)
}

export function getStudentProfile() {
  return apiGet<StudentProfile>('/api/student/profile')
}

export function updateStudentProfile(profile: StudentProfileUpdate) {
  return apiPatch<StudentProfile>('/api/student/profile', profile)
}

export function uploadStudentProfileAvatar(file: File) {
  const formData = new FormData()
  formData.append('avatar', file)

  return apiUpload<StudentProfile>('/api/student/profile/avatar', formData)
}
