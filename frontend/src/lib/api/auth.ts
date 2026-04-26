import { apiPost } from '@/lib/api/client'
import type { LoginResponse } from '@/types/auth'

export function login(identifier: string, password: string) {
  return apiPost<LoginResponse>('/api/auth/login', {
    identifier,
    password,
  })
}
