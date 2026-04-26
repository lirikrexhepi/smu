import { apiGet, apiPatch, apiUpload } from '@/lib/api/client'
import type { StudentDashboardSummary, StudentProfile, StudentProfileUpdate } from '@/types/student'

export function getMockStudentDashboard() {
  return apiGet<StudentDashboardSummary>('/api/mock/student/dashboard')
}

export function getStudentProfile(studentKey = 'luri') {
  return apiGet<StudentProfile>(`/api/student/profile?studentKey=${encodeURIComponent(studentKey)}`)
}

export function updateStudentProfile(profile: StudentProfileUpdate, studentKey = 'luri') {
  return apiPatch<StudentProfile>(`/api/student/profile?studentKey=${encodeURIComponent(studentKey)}`, profile)
}

export function uploadStudentProfileAvatar(file: File, studentKey = 'luri') {
  const formData = new FormData()
  formData.append('avatar', file)

  return apiUpload<StudentProfile>(`/api/student/profile/avatar?studentKey=${encodeURIComponent(studentKey)}`, formData)
}
