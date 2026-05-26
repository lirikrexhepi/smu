import { apiGet, apiPatch, apiPost } from '@/lib/api/client'
import type {
  ProfessorAttendanceOverview,
  ProfessorAttendanceSession,
  ProfessorAvailableAttendanceClass,
} from '@/types/professor'

export function getProfessorAttendance() {
  return apiGet<ProfessorAttendanceOverview>('/api/professor/attendance')
}

export function getProfessorAttendanceAvailableClasses() {
  return apiGet<ProfessorAvailableAttendanceClass[]>('/api/professor/attendance/available-classes')
}

export function startProfessorAttendanceSession(payload: { courseId: string; courseScheduleId: number }) {
  return apiPost<ProfessorAttendanceSession>('/api/professor/attendance/sessions', payload)
}

export function getProfessorAttendanceSession(sessionId: string) {
  return apiGet<ProfessorAttendanceSession>(`/api/professor/attendance/sessions/${encodeURIComponent(sessionId)}`)
}

export function closeProfessorAttendanceSession(sessionId: string) {
  return apiPatch<ProfessorAttendanceSession>(`/api/professor/attendance/sessions/${encodeURIComponent(sessionId)}/close`, {})
}
