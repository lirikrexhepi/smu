import { apiGet } from '@/lib/api/client'
import type { StudentDashboardSummary } from '@/types/student'

export function getMockStudentDashboard() {
  return apiGet<StudentDashboardSummary>('/api/mock/student/dashboard')
}
