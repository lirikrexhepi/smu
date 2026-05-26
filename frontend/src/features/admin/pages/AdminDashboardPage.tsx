import { useEffect, useState } from 'react'
import { CalendarDays, GraduationCap, Users, Trophy } from 'lucide-react'
import { PageHeader } from '@/components/shared/PageHeader'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { Badge } from '@/components/ui/badge'
import { getAdminDashboard } from '@/lib/api/admin'
import { cn } from '@/lib/utils'

type Metric = {
  id: string
  label: string
  value: string
  helper: string
  tone: 'green' | 'blue' | 'orange' | 'purple'
}

type GradeDist = {
  grade: number
  label: string
  count: number
  percentage: number
}

type DepartmentStat = {
  id: number
  name: string
  studentCount: number
  courseCount: number
}

type RecentUser = {
  id: number
  name: string
  role: 'student' | 'professor' | 'admin'
  institutionId: string
  timeLabel: string
}

type DashboardData = {
  metrics: Metric[]
  gradeDistribution: GradeDist[]
  departments: DepartmentStat[]
  recentUsers: RecentUser[]
}

export function AdminDashboardPage() {
  const [data, setData] = useState<DashboardData | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    let active = true
    getAdminDashboard()
      .then((res) => {
        if (active) {
          setData(res.data)
          setLoading(false)
        }
      })
      .catch((err) => {
        if (active) {
          setError(err instanceof Error ? err.message : 'Failed to load dashboard data')
          setLoading(false)
        }
      })
    return () => {
      active = false
    }
  }, [])

  if (loading) {
    return (
      <div className="space-y-6">
        <PageHeader title="Admin Dashboard" description="Overview of the university administration." />
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          {Array.from({ length: 4 }).map((_, i) => (
            <Card key={i}>
              <CardContent className="p-6">
                <Skeleton className="h-4 w-24 mb-2" />
                <Skeleton className="h-8 w-16 mb-2" />
                <Skeleton className="h-4 w-32" />
              </CardContent>
            </Card>
          ))}
        </div>
        <div className="grid gap-6 md:grid-cols-2">
          <Card>
            <CardHeader><Skeleton className="h-5 w-48" /></CardHeader>
            <CardContent className="space-y-3"><Skeleton className="h-20 w-full" /><Skeleton className="h-20 w-full" /></CardContent>
          </Card>
          <Card>
            <CardHeader><Skeleton className="h-5 w-48" /></CardHeader>
            <CardContent className="space-y-3"><Skeleton className="h-20 w-full" /><Skeleton className="h-20 w-full" /></CardContent>
          </Card>
        </div>
      </div>
    )
  }

  if (error || !data) {
    return (
      <div className="space-y-6">
        <PageHeader title="Admin Dashboard" description="Overview of the university administration." />
        <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
          {error ?? 'Something went wrong.'}
        </div>
      </div>
    )
  }

  const toneIcons = {
    green: GraduationCap,
    blue: Users,
    orange: CalendarDays,
    purple: Trophy,
  }

  const toneClasses = {
    green: 'bg-emerald-50 text-emerald-600 border-emerald-100',
    blue: 'bg-blue-50 text-blue-600 border-blue-100',
    orange: 'bg-amber-50 text-amber-600 border-amber-100',
    purple: 'bg-indigo-50 text-indigo-600 border-indigo-100',
  }

  return (
    <div className="space-y-6 pb-12">
      <PageHeader title="Admin Dashboard" description="Manage academic resources, user registrations, and platform configuration." />

      {/* Metrics Row */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        {data.metrics.map((metric) => {
          const Icon = toneIcons[metric.tone] || Users
          return (
            <Card key={metric.id} className="transition-all hover:shadow-md">
              <CardContent className="flex items-center gap-4 p-6">
                <div className={cn("flex h-12 w-12 items-center justify-center rounded-xl border", toneClasses[metric.tone])}>
                  <Icon className="h-6 w-6" />
                </div>
                <div>
                  <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">{metric.label}</p>
                  <p className="mt-1 text-2xl font-bold text-slate-900">{metric.value}</p>
                  <p className="text-xs text-slate-400 mt-0.5">{metric.helper}</p>
                </div>
              </CardContent>
            </Card>
          )
        })}
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        {/* Department Stats */}
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle className="text-base font-semibold">Top Academic Departments</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase">
                    <th className="pb-3 font-semibold">Department</th>
                    <th className="pb-3 text-right font-semibold">Students</th>
                    <th className="pb-3 text-right font-semibold">Courses</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 text-sm">
                  {data.departments.map((dept) => (
                    <tr key={dept.id} className="hover:bg-slate-50 transition-colors">
                      <td className="py-3.5 font-medium text-slate-900">{dept.name}</td>
                      <td className="py-3.5 text-right font-semibold text-slate-700">{dept.studentCount}</td>
                      <td className="py-3.5 text-right text-slate-600">{dept.courseCount}</td>
                    </tr>
                  ))}
                  {data.departments.length === 0 && (
                    <tr>
                      <td colSpan={3} className="py-4 text-center text-slate-400">No departments configured.</td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>

        {/* Recent Registrations */}
        <Card>
          <CardHeader>
            <CardTitle className="text-base font-semibold">Recent Registrations</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {data.recentUsers.map((user) => (
              <div key={user.id} className="flex items-center justify-between gap-3 text-sm">
                <div className="min-w-0">
                  <p className="font-semibold text-slate-900 truncate">{user.name}</p>
                  <p className="text-xs text-slate-400 mt-0.5">{user.institutionId} &bull; {user.timeLabel}</p>
                </div>
                <Badge
                  variant={
                    user.role === 'admin'
                      ? 'danger'
                      : user.role === 'professor'
                      ? 'default'
                      : 'secondary'
                  }
                  className="capitalize font-medium shrink-0"
                >
                  {user.role}
                </Badge>
              </div>
            ))}
            {data.recentUsers.length === 0 && (
              <p className="text-center text-slate-400 py-4">No recent user registrations.</p>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Grade Distribution */}
      <Card>
        <CardHeader>
          <CardTitle className="text-base font-semibold">University Grade Distribution</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {data.gradeDistribution.map((dist) => (
              <div key={dist.grade} className="space-y-1.5 text-sm">
                <div className="flex justify-between font-medium">
                  <span className="text-slate-700">{dist.label}</span>
                  <span className="text-slate-600 font-semibold">{dist.count} records ({dist.percentage}%)</span>
                </div>
                <div className="h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                  <div
                    className={cn(
                      "h-full rounded-full transition-all duration-500",
                      dist.grade < 6 ? "bg-red-500" : dist.grade < 8 ? "bg-amber-500" : "bg-emerald-500"
                    )}
                    style={{ width: `${dist.percentage}%` }}
                  />
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
