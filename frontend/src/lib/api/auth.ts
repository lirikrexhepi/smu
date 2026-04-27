import { apiGet, apiPost } from '@/lib/api/client'
import type { LoginResponse, SessionResponse } from '@/types/auth'

export function login(identifier: string, password: string) {
  return apiPost<LoginResponse>('/api/auth/login', {
    identifier,
    password,
  })
}

export function logout() {
  return apiPost<null>('/api/auth/logout', {})
}

export function getSession() {
  return apiGet<SessionResponse>('/api/auth/session')
}
