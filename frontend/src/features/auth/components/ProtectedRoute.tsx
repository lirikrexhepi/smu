import { useEffect, useState } from 'react'
import type { ReactNode } from 'react'
import { Navigate } from 'react-router-dom'

import { getSession } from '@/lib/api/auth'
import { clearAuthUser, storeAuthUser } from '@/lib/auth/session'
import type { AuthRole } from '@/types/auth'

type ProtectedRouteProps = {
  children: ReactNode
  role: AuthRole
}

const dashboardByRole: Record<AuthRole, string> = {
  admin: '/admin/dashboard',
  professor: '/professor/dashboard',
  student: '/student/dashboard',
}

export function ProtectedRoute({ children, role }: ProtectedRouteProps) {
  const [isAllowed, setIsAllowed] = useState<boolean | null>(null)
  const [redirectPath, setRedirectPath] = useState('/login')

  useEffect(() => {
    let isMounted = true

    setIsAllowed(null)
    setRedirectPath('/login')

    getSession()
      .then((response) => {
        if (!isMounted) {
          return
        }

        if (!response.data.authenticated || response.data.user === null) {
          clearAuthUser()
          setRedirectPath('/login')
          setIsAllowed(false)
          return
        }

        storeAuthUser(response.data.user)

        if (response.data.user.role !== role) {
          setRedirectPath(dashboardByRole[response.data.user.role])
          setIsAllowed(false)
          return
        }

        setIsAllowed(true)
      })
      .catch(() => {
        if (!isMounted) {
          return
        }

        clearAuthUser()
        setIsAllowed(false)
      })

    return () => {
      isMounted = false
    }
  }, [role])

  if (isAllowed === null) {
    return null
  }

  if (!isAllowed) {
    return <Navigate to={redirectPath} replace />
  }

  return children
}
