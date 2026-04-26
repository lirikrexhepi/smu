import { Bell, Settings } from 'lucide-react'
import { NavLink, Outlet } from 'react-router-dom'

import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'
import type { NavItem } from '@/components/layout/navigation'
import { getStoredAuthUser } from '@/lib/auth/session'

type DashboardLayoutProps = {
  role: 'student' | 'professor' | 'admin'
  portalLabel: string
  userLabel: string
  navItems: NavItem[]
}

export function DashboardLayout({ role, portalLabel, userLabel, navItems }: DashboardLayoutProps) {
  const storedUser = getStoredAuthUser()
  const displayUser = storedUser?.role === role ? storedUser : null
  const displayName = displayUser?.name ?? userLabel
  const displayFaculty = displayUser?.faculty ?? 'Faculty of Information Sciences'
  const displayDepartment = displayUser?.department ?? 'Student Education Management System'
  const profileSubtext = displayUser
    ? `${displayUser.institutionId} · ${displayUser.role === 'professor' ? 'Professor' : 'Student'}`
    : portalLabel
  const initials = displayName
    .split(' ')
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()

  return (
    <div className="min-h-screen bg-slate-50 text-slate-950">
      <aside className="fixed inset-y-0 left-0 hidden w-64 border-r border-slate-200 bg-white lg:flex lg:flex-col">
        <div className="flex h-16 items-center gap-3 border-b border-slate-200 px-5">
          <img
            src="/logoup.gif.png"
            alt="University of Prishtina logo"
            className="h-11 w-11 shrink-0 object-contain"
          />
          <div>
            <p className="text-sm font-semibold text-slate-950">SEMS</p>
            <p className="text-xs text-slate-500">{portalLabel}</p>
          </div>
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
                    'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-slate-600 transition-colors',
                    isActive && 'bg-blue-50 text-blue-700',
                    !isActive && 'hover:bg-slate-100 hover:text-slate-950',
                  )
                }
              >
                <Icon className="h-4 w-4" />
                {item.label}
              </NavLink>
            )
          })}
        </nav>

        <div className="border-t border-slate-200 p-4">
          <p className="text-xs font-medium uppercase text-slate-400">Current Semester</p>
          <p className="mt-1 text-sm font-medium text-slate-700">Spring 2026</p>
        </div>
      </aside>

      <div className="lg:pl-64">
        <header className="sticky top-0 z-10 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 lg:px-6">
          <div>
            <p className="text-sm font-medium text-slate-900">{displayFaculty}</p>
            <p className="text-xs text-slate-500">{displayDepartment}</p>
          </div>

          <div className="flex items-center gap-2">
            <Button variant="ghost" size="icon" aria-label="Notifications">
              <Bell className="h-4 w-4" />
            </Button>
            <Button variant="ghost" size="icon" aria-label="Settings">
              <Settings className="h-4 w-4" />
            </Button>
            <div className="ml-2 hidden items-center gap-3 rounded-md border border-slate-200 px-3 py-2 sm:flex">
              <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700">
                {initials}
              </div>
              <div>
                <p className="text-xs font-medium text-slate-900">{displayName}</p>
                <p className="text-xs text-slate-500">{profileSubtext}</p>
              </div>
            </div>
          </div>
        </header>

        <main className="p-4 lg:p-6">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
