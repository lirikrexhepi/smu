export type AuthRole = 'student' | 'professor' | 'admin'

export type AuthUser = {
  id: string
  name: string
  role: AuthRole
  email: string
  institutionId: string
  faculty: string
  department: string
  avatarUrl?: string | null
}

export type LoginResponse = {
  user: AuthUser
  redirectPath: string
}

export type SessionResponse = {
  authenticated: boolean
  user: AuthUser | null
}
