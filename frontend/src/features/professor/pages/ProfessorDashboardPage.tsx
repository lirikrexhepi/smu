import type { LucideIcon } from 'lucide-react'
import { BookOpen, CalendarCheck, ClipboardCheck, Users } from 'lucide-react'

import { PageHeader } from '@/components/shared/PageHeader'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { assessments, professorCourses, professorSessions, type Tone } from '@/features/professor/data/mockProfessor'
import { cn } from '@/lib/utils'

const totalStudents = professorCourses.reduce((total, course) => total + course.students, 0)
const averageAttendance = Math.round(
  professorCourses.reduce((total, course) => total + course.attendanceRate, 0) / professorCourses.length,
)
const pendingGrades = professorCourses.reduce((total, course) => total + course.pendingGrades, 0)

const metrics = [
  { label: 'Active Courses', value: String(professorCourses.length), helper: 'Spring 2026', icon: BookOpen, tone: 'blue' },
  { label: 'Students', value: String(totalStudents), helper: 'Across all sections', icon: Users, tone: 'green' },
  { label: 'Attendance', value: `${averageAttendance}%`, helper: 'Average this week', icon: CalendarCheck, tone: 'orange' },
  { label: 'Pending Grades', value: String(pendingGrades), helper: 'Needs review', icon: ClipboardCheck, tone: 'purple' },
] satisfies Array<{ label: string; value: string; helper: string; icon: LucideIcon; tone: Tone }>

export function ProfessorDashboardPage() {
  return (
    <>
      <PageHeader title="Professor Dashboard" description="Teaching overview" />

      <div className="mb-5">
        <h1 className="text-2xl font-semibold text-slate-950">Professor Dashboard</h1>
        <p className="mt-1 text-sm text-slate-500">Teaching overview for Spring 2026.</p>
      </div>

      <div className="mb-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        {metrics.map((metric) => (
          <MetricCard key={metric.label} metric={metric} />
        ))}
      </div>

      <div className="grid gap-5 xl:grid-cols-[1.15fr_0.85fr]">
        <Card>
          <CardHeader className="flex-row items-center justify-between">
            <CardTitle>Today's Teaching Schedule</CardTitle>
            <Badge variant="secondary">3 sessions</Badge>
          </CardHeader>
          <CardContent>
            <div className="divide-y divide-slate-100">
              {professorSessions.map((session) => (
                <div key={session.id} className="grid gap-3 py-4 text-sm md:grid-cols-[120px_1fr_110px_auto] md:items-center">
                  <div>
                    <p className="font-semibold text-slate-950">{session.time}</p>
                    <p className="text-slate-500">{session.date}</p>
                  </div>
                  <div className="min-w-0">
                    <p className="font-semibold text-slate-950">{session.courseName}</p>
                    <p className="text-slate-500">
                      {session.courseCode} - {session.type}
                    </p>
                  </div>
                  <p className="text-slate-600">{session.room}</p>
                  <Badge variant={sessionStatusVariant(session.status)}>{session.status}</Badge>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Assessment Queue</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {assessments.map((assessment) => {
                const percent = Math.round((assessment.graded / assessment.total) * 100)

                return (
                  <div key={assessment.id} className="rounded-lg border border-slate-200 p-4">
                    <div className="flex items-start justify-between gap-3">
                      <div className="min-w-0">
                        <p className="truncate font-semibold text-slate-950">{assessment.title}</p>
                        <p className="mt-1 text-sm text-slate-500">
                          {assessment.courseCode} - {assessment.type}
                        </p>
                      </div>
                      <Badge variant={percent >= 80 ? 'success' : 'warning'}>{percent}% graded</Badge>
                    </div>
                    <div className="mt-3 h-2 rounded-full bg-slate-100">
                      <div className="h-2 rounded-full bg-blue-500" style={{ width: `${percent}%` }} />
                    </div>
                    <p className="mt-2 text-sm text-slate-500">
                      {assessment.submitted}/{assessment.total} submitted - due {assessment.dueDate}
                    </p>
                  </div>
                )
              })}
            </div>
          </CardContent>
        </Card>
      </div>

      <div className="mt-5 grid gap-5 xl:grid-cols-3">
        {professorCourses.map((course) => (
          <Card key={course.id}>
            <CardContent className="p-5">
              <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                  <p className="text-sm font-semibold text-blue-600">{course.code}</p>
                  <h2 className="mt-1 truncate text-base font-semibold text-slate-950">{course.name}</h2>
                </div>
                <Badge variant={course.status === 'Active' ? 'success' : 'warning'}>{course.status}</Badge>
              </div>
              <div className="mt-4 grid grid-cols-3 gap-3 text-sm">
                <Stat label="Students" value={course.students} />
                <Stat label="Average" value={course.averageGrade.toFixed(1)} />
                <Stat label="Attendance" value={`${course.attendanceRate}%`} />
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </>
  )
}

function MetricCard({
  metric,
}: {
  metric: { label: string; value: string; helper: string; icon: LucideIcon; tone: Tone }
}) {
  const Icon = metric.icon

  return (
    <Card>
      <CardContent className="flex items-center gap-5 p-5">
        <div className={cn('flex h-14 w-14 shrink-0 items-center justify-center rounded-full', toneClasses[metric.tone])}>
          <Icon className="h-7 w-7" />
        </div>
        <div className="min-w-0">
          <p className="text-sm font-medium text-slate-700">{metric.label}</p>
          <p className="mt-1 text-3xl font-semibold text-slate-950">{metric.value}</p>
          <p className="mt-1 text-sm text-slate-500">{metric.helper}</p>
        </div>
      </CardContent>
    </Card>
  )
}

function Stat({ label, value }: { label: string; value: string | number }) {
  return (
    <div className="rounded-md bg-slate-50 px-3 py-2">
      <p className="text-xs font-medium text-slate-500">{label}</p>
      <p className="mt-1 text-lg font-semibold text-slate-950">{value}</p>
    </div>
  )
}

function sessionStatusVariant(status: string) {
  if (status === 'Recorded') {
    return 'success'
  }

  if (status === 'Open') {
    return 'warning'
  }

  return 'secondary'
}

const toneClasses: Record<Tone, string> = {
  blue: 'bg-blue-50 text-blue-600',
  green: 'bg-green-50 text-green-700',
  orange: 'bg-orange-50 text-orange-600',
  purple: 'bg-purple-50 text-purple-600',
  red: 'bg-red-50 text-red-600',
  teal: 'bg-teal-50 text-teal-700',
}
