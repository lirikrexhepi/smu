import { apiGet, apiPatch, apiUpload } from '@/lib/api/client'
import type { StudentDashboardSummary, StudentProfile, StudentProfileUpdate } from '@/types/student'

export function getStudentDashboard(studentKey: string) {
  return apiGet<StudentDashboardSummary>(`/api/student/dashboard?studentKey=${encodeURIComponent(studentKey)}`)
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
