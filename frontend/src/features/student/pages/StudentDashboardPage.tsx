import type { LucideIcon } from 'lucide-react'
import {
  AlertTriangle,
  ArrowRight,
  BookOpen,
  CalendarCheck,
  CalendarDays,
  ClipboardCheck,
  FileText,
  Info,
  TrendingUp,
  UserRound,
} from 'lucide-react'
import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'

import { PageHeader } from '@/components/shared/PageHeader'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { getStudentDashboard } from '@/lib/api/student'
import { cn } from '@/lib/utils'
import type {
  StudentDashboardClass,
  StudentDashboardDeadline,
  StudentDashboardGrade,
  StudentDashboardMetric,
  StudentDashboardSummary,
} from '@/types/student'

const metricIcons: Record<StudentDashboardMetric['tone'], LucideIcon> = {
  blue: CalendarDays,
  green: CalendarCheck,
  orange: ClipboardCheck,
  purple: TrendingUp,
}

const quickLinks = [
  { label: 'Courses', to: '/student/courses', icon: BookOpen, tone: 'blue' },
  { label: 'Attendance', to: '/student/attendance', icon: CalendarCheck, tone: 'green' },
  { label: 'Grades', to: '/student/grades', icon: FileText, tone: 'purple' },
  { label: 'Profile', to: '/student/profile', icon: UserRound, tone: 'blue' },
] as const

export function StudentDashboardPage() {
  const [dashboard, setDashboard] = useState<StudentDashboardSummary | null>(null)
  const [errorMessage, setErrorMessage] = useState<string | null>(null)

  useEffect(() => {
    let isMounted = true

    getStudentDashboard()
      .then((response) => {
        if (!isMounted) {
          return
        }

        setDashboard(response.data)
        setErrorMessage(null)
      })
      .catch((error: unknown) => {
        if (!isMounted) {
          return
        }

        setErrorMessage(error instanceof Error ? error.message : 'Unable to load student dashboard')
      })

    return () => {
      isMounted = false
    }
  }, [])

  if (!dashboard) {
    return (
      <>
        <PageHeader title="Student Dashboard" description="Overview of your academic activity" />
        {errorMessage ? (
          <Card>
            <CardContent className="pt-5">
              <p className="text-sm text-slate-500">{errorMessage}</p>
            </CardContent>
          </Card>
        ) : (
          <StudentDashboardSkeleton />
        )}
      </>
    )
  }

  return (
    <>
      <PageHeader title="Student Dashboard" description="Overview of your academic activity" />

      {errorMessage ? (
        <div className="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {errorMessage}
        </div>
      ) : null}

      <div className="mb-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        {dashboard.metrics.map((metric) => (
          <MetricCard key={metric.id} metric={metric} />
        ))}
      </div>

      <div className="grid gap-5 xl:grid-cols-2">
        <Card>
          <CardHeader className="flex-row items-center justify-between">
            <CardTitle>Today's Classes</CardTitle>
            <TextLink to="/student/courses">View Full Schedule</TextLink>
          </CardHeader>
          <CardContent>
            <div className="divide-y divide-slate-100">
              {dashboard.todaysClasses.map((classItem) => (
                <ClassRow key={classItem.id} classItem={classItem} />
              ))}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex-row items-center justify-between">
            <CardTitle>Upcoming Exams & Deadlines</CardTitle>
            <TextLink to="/student/courses">View All</TextLink>
          </CardHeader>
          <CardContent>
            <div className="divide-y divide-slate-100">
              {dashboard.upcomingDeadlines.map((deadline) => (
                <DeadlineRow key={deadline.id} deadline={deadline} />
              ))}
            </div>
          </CardContent>
        </Card>
      </div>

      <div className="mt-5 grid gap-5 xl:grid-cols-[1.35fr_0.95fr_0.7fr]">
        <Card>
          <CardHeader className="flex-row items-center justify-between">
            <CardTitle>Latest Grades</CardTitle>
            <TextLink to="/student/grades">View All</TextLink>
          </CardHeader>
          <CardContent>
            <div className="overflow-x-auto">
              <table className="w-full min-w-[620px] text-left text-sm">
                <thead>
                  <tr className="border-b border-slate-200 text-xs font-medium text-slate-500">
                    <th className="py-3 pr-4">Course</th>
                    <th className="py-3 pr-4">Assessment</th>
                    <th className="py-3 pr-4">Type</th>
                    <th className="py-3 pr-4">Grade</th>
                    <th className="py-3">Date</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {dashboard.latestGrades.map((grade) => (
                    <GradeRow key={grade.id} grade={grade} />
                  ))}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex-row items-center justify-between">
            <CardTitle>Attendance Warning</CardTitle>
            <TextLink to="/student/attendance">View Details</TextLink>
          </CardHeader>
          <CardContent>
            <div className="rounded-lg border border-red-200 bg-red-50 p-4">
              <div className="flex gap-3">
                <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-red-600" />
                <div>
                  <p className="text-sm font-semibold text-slate-950">{dashboard.attendanceWarning.message}</p>
                  <p className="mt-1 text-sm text-slate-500">{dashboard.attendanceWarning.detail}</p>
                </div>
              </div>
              <div className="mt-4 flex items-center justify-between text-sm">
                <span className="font-medium text-slate-700">
                  {dashboard.attendanceWarning.courseName} ({dashboard.attendanceWarning.courseCode})
                </span>
                <span className="font-semibold text-red-600">{dashboard.attendanceWarning.rate}%</span>
              </div>
              <div className="mt-2 h-2 rounded-full bg-red-100">
                <div
                  className="h-2 rounded-full bg-red-500"
                  style={{ width: `${dashboard.attendanceWarning.rate}%` }}
                />
              </div>
            </div>

            <div className="mt-5 space-y-3">
              {dashboard.attendanceSummary.map((item) => (
                <div key={item.courseName} className="flex items-center justify-between text-sm">
                  <span className="text-slate-600">{item.courseName}</span>
                  <span className="font-semibold text-green-700">{item.rate}%</span>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Quick Links</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2">
            {quickLinks.map((link) => {
              const Icon = link.icon

              return (
                <Link
                  key={link.to}
                  to={link.to}
                  className="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-3 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 hover:text-slate-950"
                >
                  <span className={cn('flex h-9 w-9 items-center justify-center rounded-md', toneClasses[link.tone].soft)}>
                    <Icon className="h-5 w-5" />
                  </span>
                  <span className="min-w-0 flex-1">{link.label}</span>
                  <ArrowRight className="h-4 w-4 text-slate-400" />
                </Link>
              )
            })}
          </CardContent>
        </Card>
      </div>
    </>
  )
}

function StudentDashboardSkeleton() {
  return (
    <div aria-label="Loading student dashboard" aria-busy="true">
      <div className="mb-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        {Array.from({ length: 4 }).map((_, index) => (
          <Card key={index}>
            <CardContent className="flex items-center gap-5 p-5">
              <Skeleton className="h-16 w-16 shrink-0 rounded-full" />
              <div className="min-w-0 flex-1 space-y-2">
                <Skeleton className="h-4 w-32" />
                <Skeleton className="h-8 w-16" />
                <Skeleton className="h-4 w-36" />
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      <div className="grid gap-5 xl:grid-cols-2">
        <Card>
          <CardHeader className="flex-row items-center justify-between">
            <Skeleton className="h-5 w-32" />
            <Skeleton className="h-4 w-28" />
          </CardHeader>
          <CardContent>
            <div className="divide-y divide-slate-100">
              {Array.from({ length: 3 }).map((_, index) => (
                <div key={index} className="grid gap-3 py-4 sm:grid-cols-[0.9fr_1.4fr_0.8fr_auto] sm:items-center">
                  <div className="flex items-center gap-4">
                    <Skeleton className="h-2 w-2 rounded-full" />
                    <Skeleton className="h-4 w-24" />
                  </div>
                  <div className="space-y-2">
                    <Skeleton className="h-4 w-40" />
                    <Skeleton className="h-4 w-16" />
                  </div>
                  <Skeleton className="h-4 w-20" />
                  <Skeleton className="h-7 w-16 rounded-md" />
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex-row items-center justify-between">
            <Skeleton className="h-5 w-48" />
            <Skeleton className="h-4 w-16" />
          </CardHeader>
          <CardContent>
            <div className="divide-y divide-slate-100">
              {Array.from({ length: 4 }).map((_, index) => (
                <div key={index} className="grid gap-3 py-3 sm:grid-cols-[auto_1fr_auto] sm:items-center">
                  <Skeleton className="h-10 w-10 rounded-md" />
                  <div className="space-y-2">
                    <Skeleton className="h-4 w-56 max-w-full" />
                    <Skeleton className="h-4 w-20" />
                  </div>
                  <div className="space-y-2">
                    <Skeleton className="h-4 w-24" />
                    <Skeleton className="h-4 w-16" />
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>

      <div className="mt-5 grid gap-5 xl:grid-cols-[1.35fr_0.95fr_0.7fr]">
        <Card>
          <CardHeader className="flex-row items-center justify-between">
            <Skeleton className="h-5 w-28" />
            <Skeleton className="h-4 w-16" />
          </CardHeader>
          <CardContent>
            <div className="overflow-x-auto">
              <div className="min-w-[620px]">
                <div className="grid grid-cols-5 gap-4 border-b border-slate-200 pb-3">
                  {Array.from({ length: 5 }).map((_, index) => (
                    <Skeleton key={index} className="h-3 w-20" />
                  ))}
                </div>
                <div className="divide-y divide-slate-100">
                  {Array.from({ length: 4 }).map((_, rowIndex) => (
                    <div key={rowIndex} className="grid grid-cols-5 gap-4 py-3">
                      <Skeleton className="h-4 w-36" />
                      <Skeleton className="h-4 w-28" />
                      <Skeleton className="h-4 w-16" />
                      <Skeleton className="h-4 w-20" />
                      <Skeleton className="h-4 w-24" />
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex-row items-center justify-between">
            <Skeleton className="h-5 w-40" />
            <Skeleton className="h-4 w-24" />
          </CardHeader>
          <CardContent>
            <div className="rounded-lg border border-red-100 bg-red-50 p-4">
              <div className="flex gap-3">
                <Skeleton className="mt-0.5 h-5 w-5 shrink-0 rounded-full bg-red-100" />
                <div className="flex-1 space-y-2">
                  <Skeleton className="h-4 w-48 max-w-full bg-red-100" />
                  <Skeleton className="h-4 w-full bg-red-100" />
                </div>
              </div>
              <div className="mt-4 flex items-center justify-between">
                <Skeleton className="h-4 w-48 bg-red-100" />
                <Skeleton className="h-4 w-10 bg-red-100" />
              </div>
              <Skeleton className="mt-2 h-2 w-full rounded-full bg-red-100" />
            </div>

            <div className="mt-5 space-y-3">
              {Array.from({ length: 3 }).map((_, index) => (
                <div key={index} className="flex items-center justify-between">
                  <Skeleton className="h-4 w-44" />
                  <Skeleton className="h-4 w-10" />
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <Skeleton className="h-5 w-24" />
          </CardHeader>
          <CardContent className="space-y-2">
            {Array.from({ length: 4 }).map((_, index) => (
              <div key={index} className="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-3">
                <Skeleton className="h-9 w-9 rounded-md" />
                <Skeleton className="h-4 flex-1" />
                <Skeleton className="h-4 w-4" />
              </div>
            ))}
          </CardContent>
        </Card>
      </div>
    </div>
  )
}

function MetricCard({ metric }: { metric: StudentDashboardMetric }) {
  const Icon = metricIcons[metric.tone]

  return (
    <Card>
      <CardContent className="flex items-center gap-5 p-5">
        <div className={cn('flex h-16 w-16 shrink-0 items-center justify-center rounded-full', toneClasses[metric.tone].soft)}>
          <Icon className="h-8 w-8" />
        </div>
        <div className="min-w-0">
          <div className="flex items-center gap-2">
            <p className="text-sm font-medium text-slate-700">{metric.label}</p>
            <Info className="h-4 w-4 text-slate-400" />
          </div>
          <p className="mt-2 text-3xl font-semibold text-slate-950">{metric.value}</p>
          <p className="mt-1 text-sm text-slate-500">{metric.helper}</p>
        </div>
      </CardContent>
    </Card>
  )
}

function ClassRow({ classItem }: { classItem: StudentDashboardClass }) {
  return (
    <div className="grid gap-3 py-4 text-sm sm:grid-cols-[0.9fr_1.4fr_0.8fr_auto] sm:items-center">
      <div className="flex items-center gap-4 text-slate-500">
        <span className="h-2 w-2 rounded-full bg-blue-500" />
        <span>{classItem.time}</span>
      </div>
      <div>
        <p className="font-semibold text-slate-950">{classItem.courseName}</p>
        <p className="text-slate-500">{classItem.courseCode}</p>
      </div>
      <p className="text-slate-500">{classItem.room}</p>
      <Badge variant={badgeVariant(classItem.tone)}>{classItem.type}</Badge>
    </div>
  )
}

function DeadlineRow({ deadline }: { deadline: StudentDashboardDeadline }) {
  return (
    <div className="grid gap-3 py-3 text-sm sm:grid-cols-[auto_1fr_auto] sm:items-center">
      <div className={cn('flex h-10 w-10 items-center justify-center rounded-md', toneClasses[deadline.tone].soft)}>
        <ClipboardCheck className="h-5 w-5" />
      </div>
      <div className="min-w-0">
        <p className="font-medium text-slate-950">{deadline.title}</p>
        <p className="text-slate-500">{deadline.courseCode}</p>
      </div>
      <div className="text-left sm:text-right">
        <p className="text-slate-600">{deadline.date}</p>
        <p className={cn('font-semibold', deadline.statusLabel === 'Today' ? 'text-green-700' : 'text-red-600')}>
          {deadline.statusLabel}
        </p>
      </div>
    </div>
  )
}

function GradeRow({ grade }: { grade: StudentDashboardGrade }) {
  return (
    <tr>
      <td className="py-3 pr-4 text-slate-600">{grade.course}</td>
      <td className="py-3 pr-4 text-slate-600">{grade.assessment}</td>
      <td className="py-3 pr-4 text-slate-600">{grade.type}</td>
      <td className={cn('py-3 pr-4 font-semibold', grade.tone === 'blue' ? 'text-blue-700' : 'text-green-700')}>
        {grade.grade}
      </td>
      <td className="py-3 text-slate-600">{grade.date}</td>
    </tr>
  )
}

function TextLink({ to, children }: { to: string; children: string }) {
  return (
    <Link to={to} className="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-700">
      {children}
      <ArrowRight className="h-4 w-4" />
    </Link>
  )
}

const toneClasses: Record<StudentDashboardMetric['tone'], { soft: string }> = {
  blue: { soft: 'bg-blue-50 text-blue-600' },
  green: { soft: 'bg-green-50 text-green-700' },
  orange: { soft: 'bg-orange-50 text-orange-600' },
  purple: { soft: 'bg-purple-50 text-purple-600' },
}

function badgeVariant(tone: StudentDashboardMetric['tone']) {
  if (tone === 'green') {
    return 'success'
  }

  if (tone === 'orange') {
    return 'warning'
  }

  if (tone === 'purple') {
    return 'secondary'
  }

  return 'default'
}
