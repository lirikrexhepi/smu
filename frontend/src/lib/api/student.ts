import { apiGet, apiPatch, apiUpload } from '@/lib/api/client'
import type {
  StudentCourseDetail,
  StudentCoursesOverview,
  StudentAttendance,
  StudentDashboardSummary,
  StudentProfile,
  StudentProfileUpdate,
} from '@/types/student'

export function getStudentDashboard(studentKey: string) {
  return apiGet<StudentDashboardSummary>(`/api/student/dashboard?studentKey=${encodeURIComponent(studentKey)}`)
}

export function getStudentCourses(studentKey: string) {
  return apiGet<StudentCoursesOverview>(`/api/student/courses?studentKey=${encodeURIComponent(studentKey)}`)
}

export function getStudentCourseDetail(studentKey: string, courseId: string) {
  return apiGet<StudentCourseDetail>(
    `/api/student/courses/${encodeURIComponent(courseId)}?studentKey=${encodeURIComponent(studentKey)}`,
  )
}

export function getStudentAttendance(
  studentKey: string,
  filters: { courseId?: string; semester?: string; week?: string } = {},
) {
  const params = new URLSearchParams({ studentKey })

  if (filters.courseId && filters.courseId !== 'all') {
    params.set('courseId', filters.courseId)
  }

  if (filters.semester && filters.semester !== 'all') {
    params.set('semester', filters.semester)
  }

  if (filters.week) {
    params.set('week', filters.week)
  }

  return apiGet<StudentAttendance>(`/api/student/attendance?${params.toString()}`)
}

export function getStudentProfile(studentKey: string) {
  return apiGet<StudentProfile>(`/api/student/profile?studentKey=${encodeURIComponent(studentKey)}`)
}

export function updateStudentProfile(profile: StudentProfileUpdate, studentKey: string) {
  return apiPatch<StudentProfile>(`/api/student/profile?studentKey=${encodeURIComponent(studentKey)}`, profile)
}

export function uploadStudentProfileAvatar(file: File, studentKey: string) {
  const formData = new FormData()
  formData.append('avatar', file)

  return apiUpload<StudentProfile>(`/api/student/profile/avatar?studentKey=${encodeURIComponent(studentKey)}`, formData)
}
