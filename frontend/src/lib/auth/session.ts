import type { AuthUser } from '@/types/auth'

const AUTH_USER_KEY = 'sems.auth.user'
export const AUTH_USER_CHANGED_EVENT = 'sems.auth.user.changed'

export function storeAuthUser(user: AuthUser) {
  window.localStorage.setItem(AUTH_USER_KEY, JSON.stringify(user))
  window.dispatchEvent(new CustomEvent<AuthUser>(AUTH_USER_CHANGED_EVENT, { detail: user }))
}

export function clearAuthUser() {
  window.localStorage.removeItem(AUTH_USER_KEY)
  window.dispatchEvent(new CustomEvent(AUTH_USER_CHANGED_EVENT))
}

export function getStoredAuthUser(): AuthUser | null {
  const stored = window.localStorage.getItem(AUTH_USER_KEY)

  if (!stored) {
    return null
  }

  try {
    return JSON.parse(stored) as AuthUser
  } catch {
    window.localStorage.removeItem(AUTH_USER_KEY)
    return null
  }
}
