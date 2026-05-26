import { apiGet, apiPost, apiPut, apiDelete } from '@/lib/api/client'

export function getAdminDashboard() {
  return apiGet<any>('/api/admin/dashboard')
}

export function getAdminOptions() {
  return apiGet<any>('/api/admin/options')
}

export function listAdminUsers(filters: { search?: string; role?: string; faculty_id?: string; department_id?: string; page?: number } = {}) {
  const params = new URLSearchParams()
  if (filters.search?.trim()) {
    params.set('search', filters.search.trim())
  }
  if (filters.role && filters.role !== 'all') {
    params.set('role', filters.role)
  }
  if (filters.faculty_id && filters.faculty_id !== 'all') {
    params.set('faculty_id', filters.faculty_id)
  }
  if (filters.department_id && filters.department_id !== 'all') {
    params.set('department_id', filters.department_id)
  }
  if (filters.page) {
    params.set('page', String(filters.page))
  }
  const query = params.toString()
  return apiGet<any>(`/api/admin/users${query ? `?${query}` : ''}`)
}

export function getAdminUser(id: number | string) {
  return apiGet<any>(`/api/admin/users/${id}`)
}

export function createAdminUser(body: Record<string, unknown>) {
  return apiPost<any>('/api/admin/users', body)
}

export function updateAdminUser(id: number | string, body: Record<string, unknown>) {
  return apiPut<any>(`/api/admin/users/${id}`, body)
}

export function deleteAdminUser(id: number | string) {
  return apiDelete<any>(`/api/admin/users/${id}`)
}

export function listAdminCourses(filters: { search?: string; department_id?: string; semester_id?: string; page?: number } = {}) {
  const params = new URLSearchParams()
  if (filters.search?.trim()) {
    params.set('search', filters.search.trim())
  }
  if (filters.department_id && filters.department_id !== 'all') {
    params.set('department_id', filters.department_id)
  }
  if (filters.semester_id && filters.semester_id !== 'all') {
    params.set('semester_id', filters.semester_id)
  }
  if (filters.page) {
    params.set('page', String(filters.page))
  }
  const query = params.toString()
  return apiGet<any>(`/api/admin/courses${query ? `?${query}` : ''}`)
}

export function getAdminCourse(id: number | string) {
  return apiGet<any>(`/api/admin/courses/${id}`)
}

export function createAdminCourse(body: Record<string, unknown>) {
  return apiPost<any>('/api/admin/courses', body)
}

export function updateAdminCourse(id: number | string, body: Record<string, unknown>) {
  return apiPut<any>(`/api/admin/courses/${id}`, body)
}

export function deleteAdminCourse(id: number | string) {
  return apiDelete<any>(`/api/admin/courses/${id}`)
}
