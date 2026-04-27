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

export function ProtectedRoute({ children, role }: ProtectedRouteProps) {
  const [isAllowed, setIsAllowed] = useState<boolean | null>(null)

  useEffect(() => {
    let isMounted = true

    getSession()
      .then((response) => {
        if (!isMounted) {
          return
        }

        if (!response.data.authenticated || response.data.user?.role !== role) {
          clearAuthUser()
          setIsAllowed(false)
          return
        }

        storeAuthUser(response.data.user)
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
    return <Navigate to="/login" replace />
  }

  return children
}
