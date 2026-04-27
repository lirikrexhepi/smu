import { Bell, ChevronDown, LogOut, Menu, PanelLeftClose, PanelLeftOpen, Settings, User, X } from 'lucide-react'
import { useEffect, useState } from 'react'
import { NavLink, Outlet, useNavigate } from 'react-router-dom'

import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { cn } from '@/lib/utils'
import type { NavItem } from '@/components/layout/navigation'
import { apiAssetUrl } from '@/lib/api/client'
import { logout } from '@/lib/api/auth'
import { getStudentProfile } from '@/lib/api/student'
import { AUTH_USER_CHANGED_EVENT, clearAuthUser, getStoredAuthUser, storeAuthUser } from '@/lib/auth/session'
import type { AuthUser } from '@/types/auth'

type DashboardLayoutProps = {
  role: 'student' | 'professor' | 'admin'
  portalLabel: string
  userLabel: string
  navItems: NavItem[]
}

export function DashboardLayout({ role, portalLabel, userLabel, navItems }: DashboardLayoutProps) {
  const navigate = useNavigate()
  const [isSidebarCollapsed, setIsSidebarCollapsed] = useState(false)
  const [isMobileSidebarOpen, setIsMobileSidebarOpen] = useState(false)
  const [storedUser, setStoredUser] = useState<AuthUser | null>(() => getStoredAuthUser())
  const [currentTerm, setCurrentTerm] = useState<string | null>(null)
  const displayUser = storedUser?.role === role ? storedUser : null
  const displayName = displayUser?.name ?? userLabel
  const displayFaculty = displayUser?.faculty ?? 'Faculty of Information Sciences'
  const displayDepartment = displayUser?.department ?? 'Student Education Management System'
  const displayCurrentTerm = role === 'student' ? currentTerm ?? 'Current student term' : 'Spring 2026'
  const profileSubtext = displayUser
    ? `${displayUser.institutionId} · ${displayUser.role === 'professor' ? 'Professor' : 'Student'}`
    : portalLabel
  const initials = displayName
    .split(' ')
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
  const profilePath = role === 'student' ? '/student/profile' : `/${role}/dashboard`
  const avatarUrl = apiAssetUrl(displayUser?.avatarUrl)

  useEffect(() => {
    function handleUserChanged() {
      setStoredUser(getStoredAuthUser())
    }

    window.addEventListener(AUTH_USER_CHANGED_EVENT, handleUserChanged)
    window.addEventListener('storage', handleUserChanged)

    return () => {
      window.removeEventListener(AUTH_USER_CHANGED_EVENT, handleUserChanged)
      window.removeEventListener('storage', handleUserChanged)
    }
  }, [])

  useEffect(() => {
    if (role !== 'student' || !displayUser) {
      return
    }

    let isMounted = true

    getStudentProfile(displayUser.institutionId)
      .then((response) => {
        if (!isMounted) {
          return
        }

        const syncedUser = {
          ...displayUser,
          name: response.data.fullName,
          email: response.data.email,
          faculty: response.data.faculty,
          department: response.data.department,
          avatarUrl: response.data.avatarUrl,
        }

        setCurrentTerm(`${response.data.semester} · ${response.data.academicYear}`)
        setStoredUser(syncedUser)
        storeAuthUser(syncedUser)
      })
      .catch(() => {
        // The shell can still render from the stored login user if the profile mock API is unavailable.
      })

    return () => {
      isMounted = false
    }
  }, [role, displayUser?.institutionId])

  useEffect(() => {
    if (!isMobileSidebarOpen) {
      return
    }

    const originalOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'

    return () => {
      document.body.style.overflow = originalOverflow
    }
  }, [isMobileSidebarOpen])

  async function handleLogout() {
    try {
      await logout()
    } catch {
    }

    clearAuthUser()
    setIsMobileSidebarOpen(false)
    navigate('/login', { replace: true })
  }

  return (
    <div className="min-h-screen bg-slate-50 text-slate-950">
      {isMobileSidebarOpen ? (
        <div className="fixed inset-0 z-50 lg:hidden">
          <button
            type="button"
            aria-label="Close sidebar"
            className="absolute inset-0 h-full w-full cursor-default bg-slate-950/35"
            onClick={() => setIsMobileSidebarOpen(false)}
          />
          <aside className="relative flex h-full w-[280px] max-w-[86vw] flex-col border-r border-slate-200 bg-white shadow-xl">
            <div className="flex h-16 items-center gap-3 border-b border-slate-200 px-4">
              <img src="/logoup.gif.png" alt="University of Prishtina logo" className="h-10 w-10 object-contain" />
              <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-semibold text-slate-950">SEMS</p>
                <p className="truncate text-xs text-slate-500">{portalLabel}</p>
              </div>
              <Button
                type="button"
                variant="ghost"
                size="icon"
                aria-label="Close sidebar"
                onClick={() => setIsMobileSidebarOpen(false)}
              >
                <X className="h-5 w-5" />
              </Button>
            </div>

            <nav className="flex-1 space-y-1 px-3 py-4">
              {navItems.map((item) => {
                const Icon = item.icon

                return (
                  <NavLink
                    key={item.to}
                    to={item.to}
                    className={({ isActive }) =>
                      cn(
                        'flex h-11 items-center gap-3 rounded-md px-3 text-sm font-medium text-slate-600 transition-colors',
                        isActive && 'bg-blue-50 text-blue-700',
                        !isActive && 'hover:bg-slate-100 hover:text-slate-950',
                      )
                    }
                    onClick={() => setIsMobileSidebarOpen(false)}
                  >
                    <Icon className="h-4 w-4 shrink-0" />
                    <span className="min-w-0 truncate">{item.label}</span>
                  </NavLink>
                )
              })}
            </nav>

            <div className="border-t border-slate-200 p-4">
              <p className="text-xs font-medium uppercase text-slate-400">Current Semester</p>
              <p className="mt-1 text-sm font-medium leading-5 text-slate-700">{displayCurrentTerm}</p>
            </div>
          </aside>
        </div>
      ) : null}

      <aside
        className={cn(
          'fixed inset-y-0 left-0 hidden overflow-hidden border-r border-slate-200 bg-white transition-[width] duration-300 ease-in-out lg:flex lg:flex-col',
          isSidebarCollapsed ? 'w-[72px]' : 'w-[232px]',
        )}
      >
        <div
          className={cn(
            'flex h-16 items-center gap-3 border-b border-slate-200 px-4 transition-all duration-300 ease-in-out',
            isSidebarCollapsed && 'px-4',
          )}
        >
          <button
            type="button"
            aria-label={isSidebarCollapsed ? 'Expand sidebar' : 'SEMS home'}
            className={cn(
              'group flex h-10 w-10 shrink-0 items-center justify-center rounded-md',
              isSidebarCollapsed && 'hover:bg-blue-50',
            )}
            onClick={() => {
              if (isSidebarCollapsed) {
                setIsSidebarCollapsed(false)
              }
            }}
          >
            <img
              src="/logoup.gif.png"
              alt="University of Prishtina logo"
              className={cn('h-10 w-10 object-contain', isSidebarCollapsed && 'group-hover:hidden')}
            />
            {isSidebarCollapsed ? (
              <PanelLeftOpen className="hidden h-5 w-5 text-slate-950 group-hover:block" />
            ) : null}
          </button>
          <div
            className={cn(
              'min-w-0 flex-1 overflow-hidden transition-all duration-200 ease-in-out',
              isSidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[116px] opacity-100 delay-75',
            )}
          >
            <p className="truncate text-sm font-semibold text-slate-950">SEMS</p>
            <p className="truncate text-xs text-slate-500">{portalLabel}</p>
          </div>
          <Button
            type="button"
            variant="ghost"
            size="icon"
            aria-label="Collapse sidebar"
            className={cn(
              'h-8 w-8 shrink-0 transition-all duration-200',
              isSidebarCollapsed && 'pointer-events-none w-0 opacity-0',
            )}
            onClick={() => setIsSidebarCollapsed(true)}
          >
            <PanelLeftClose className="h-4 w-4" />
          </Button>
        </div>

        <nav className="flex-1 space-y-1 px-3 py-4">
          {navItems.map((item) => {
            const Icon = item.icon

            return (
              <NavLink
                key={item.to}
                to={item.to}
                className={({ isActive }) =>
                  cn(
                    'flex h-10 items-center gap-3 overflow-hidden rounded-md px-3 text-sm font-medium text-slate-600 transition-colors',
                    isActive && 'bg-blue-50 text-blue-700',
                    !isActive && 'hover:bg-slate-100 hover:text-slate-950',
                  )
                }
                title={isSidebarCollapsed ? item.label : undefined}
              >
                <Icon className="h-4 w-4 shrink-0" />
                <span
                  className={cn(
                    'min-w-0 truncate whitespace-nowrap transition-all duration-200 ease-in-out',
                    isSidebarCollapsed ? 'w-0 opacity-0' : 'w-36 opacity-100 delay-75',
                  )}
                >
                  {item.label}
                </span>
              </NavLink>
            )
          })}
        </nav>

        <div className="overflow-hidden border-t border-slate-200 p-4">
          <p
            className={cn(
              'w-44 whitespace-nowrap text-xs font-medium uppercase text-slate-400 transition-opacity duration-200',
              isSidebarCollapsed ? 'opacity-0' : 'opacity-100 delay-75',
            )}
          >
            Current Semester
          </p>
          <p
            className={cn(
              'mt-1 w-44 whitespace-nowrap text-sm font-medium leading-5 text-slate-700 transition-opacity duration-200',
              isSidebarCollapsed ? 'opacity-0' : 'opacity-100 delay-75',
            )}
          >
            {displayCurrentTerm}
          </p>
        </div>
      </aside>

      <div className={cn('transition-[padding] duration-300 ease-in-out', isSidebarCollapsed ? 'lg:pl-[72px]' : 'lg:pl-[232px]')}>
        <header className="sticky top-0 z-10 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 lg:px-6">
          <div className="flex items-center gap-3">
            <Button
              type="button"
              variant="ghost"
              size="icon"
              aria-label="Open sidebar"
              className="lg:hidden"
              onClick={() => setIsMobileSidebarOpen(true)}
            >
              <Menu className="h-5 w-5" />
            </Button>

            <div className="hidden lg:block">
              <p className="text-sm font-medium text-slate-900">{displayFaculty}</p>
              <p className="text-xs text-slate-500">{displayDepartment}</p>
            </div>
          </div>

          <div className="flex items-center gap-2">
            <Button variant="ghost" size="icon" aria-label="Notifications">
              <Bell className="h-4 w-4" />
            </Button>
            <Button variant="ghost" size="icon" aria-label="Settings">
              <Settings className="h-4 w-4" />
            </Button>
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <button
                  type="button"
                  className="flex h-10 w-10 cursor-pointer items-center justify-center rounded-md transition-colors hover:bg-slate-100 lg:ml-2 lg:h-auto lg:w-auto lg:gap-3 lg:px-3 lg:py-2"
                  aria-label="Open profile menu"
                >
                  {avatarUrl ? (
                    <img
                      src={avatarUrl}
                      alt=""
                      className="h-8 w-8 rounded-full object-cover"
                    />
                  ) : (
                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700">
                      {initials}
                    </div>
                  )}
                  <div className="hidden text-left lg:block">
                    <p className="text-xs font-medium text-slate-900">{displayName}</p>
                    <p className="text-xs text-slate-500">{profileSubtext}</p>
                  </div>
                  <ChevronDown className="hidden h-4 w-4 text-slate-500 lg:block" />
                </button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" className="w-56">
                <DropdownMenuItem onSelect={() => navigate(profilePath)}>
                  <User className="h-4 w-4" />
                  Profile
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem onSelect={handleLogout} className="text-red-600 focus:text-red-700">
                  <LogOut className="h-4 w-4" />
                  Logout
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </header>

        <main className="p-4 lg:p-6">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
