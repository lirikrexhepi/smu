import { CalendarCheck, CheckCircle2, Clock3, Plus, UserCheck, XCircle } from 'lucide-react'

import { PageHeader } from '@/components/shared/PageHeader'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { professorCourses, professorSessions, type ProfessorSession } from '@/features/professor/data/mockProfessor'
import { cn } from '@/lib/utils'

const recordedSessions = professorSessions.filter((session) => session.status === 'Recorded')
const totalPresent = recordedSessions.reduce((total, session) => total + session.present, 0)
const totalAbsent = recordedSessions.reduce((total, session) => total + session.absent, 0)
const totalLate = recordedSessions.reduce((total, session) => total + session.late, 0)

export function ProfessorAttendancePage() {
  return (
    <>
      <PageHeader title="Attendance Management" description="Class attendance records" />

      <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold text-slate-950">Attendance Management</h1>
          <p className="mt-1 text-sm text-slate-500">Record sessions and monitor attendance across assigned courses.</p>
        </div>
        <Button type="button">
          <Plus className="h-4 w-4" />
          New Session
        </Button>
      </div>

      <div className="mb-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <MetricCard label="Recorded Sessions" value={String(recordedSessions.length)} helper="This week" icon={CalendarCheck} tone="blue" />
        <MetricCard label="Present" value={String(totalPresent)} helper="Recorded students" icon={CheckCircle2} tone="green" />
        <MetricCard label="Absent" value={String(totalAbsent)} helper="Needs follow-up" icon={XCircle} tone="red" />
        <MetricCard label="Late" value={String(totalLate)} helper="Late arrivals" icon={Clock3} tone="orange" />
      </div>

      <div className="grid gap-5 xl:grid-cols-[1fr_360px]">
        <Card>
          <CardHeader className="flex-row items-center justify-between border-b border-slate-100 p-4">
            <CardTitle>Session Records</CardTitle>
            <Badge variant="secondary">{professorSessions.length} total</Badge>
          </CardHeader>
          <CardContent className="p-0">
            <div className="overflow-x-auto">
              <table className="w-full min-w-[860px] text-left text-sm">
                <thead className="bg-slate-50 text-xs font-semibold text-slate-600">
                  <tr>
                    <th className="px-4 py-3">Course</th>
                    <th className="px-4 py-3">Date</th>
                    <th className="px-4 py-3">Time</th>
                    <th className="px-4 py-3">Room</th>
                    <th className="px-4 py-3">Present</th>
                    <th className="px-4 py-3">Absent</th>
                    <th className="px-4 py-3">Late</th>
                    <th className="px-4 py-3">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {professorSessions.map((session) => (
                    <SessionRow key={session.id} session={session} />
                  ))}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>

        <Card className="h-fit">
          <CardHeader>
            <CardTitle>Course Attendance</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {professorCourses.map((course) => (
              <div key={course.id}>
                <div className="flex items-center justify-between gap-3 text-sm">
                  <div className="min-w-0">
                    <p className="truncate font-semibold text-slate-950">{course.name}</p>
                    <p className="text-slate-500">{course.code}</p>
                  </div>
                  <span className="font-semibold text-slate-950">{course.attendanceRate}%</span>
                </div>
                <div className="mt-2 h-2 rounded-full bg-slate-100">
                  <div
                    className={cn('h-2 rounded-full', course.attendanceRate >= 90 ? 'bg-green-500' : 'bg-orange-500')}
                    style={{ width: `${course.attendanceRate}%` }}
                  />
                </div>
              </div>
            ))}
          </CardContent>
        </Card>
      </div>
    </>
  )
}

function SessionRow({ session }: { session: ProfessorSession }) {
  return (
    <tr>
      <td className="px-4 py-3">
        <p className="font-medium text-slate-800">{session.courseName}</p>
        <p className="text-xs text-slate-500">{session.courseCode}</p>
      </td>
      <td className="px-4 py-3 text-slate-600">{session.date}</td>
      <td className="px-4 py-3 text-slate-600">{session.time}</td>
      <td className="px-4 py-3 text-slate-600">{session.room}</td>
      <td className="px-4 py-3 font-semibold text-green-700">{session.present || '-'}</td>
      <td className="px-4 py-3 font-semibold text-red-600">{session.absent || '-'}</td>
      <td className="px-4 py-3 font-semibold text-orange-600">{session.late || '-'}</td>
      <td className="px-4 py-3">
        <Badge variant={statusVariant(session.status)}>{session.status}</Badge>
      </td>
    </tr>
  )
}

function MetricCard({
  label,
  value,
  helper,
  icon: Icon,
  tone,
}: {
  label: string
  value: string
  helper: string
  icon: typeof UserCheck
  tone: 'blue' | 'green' | 'orange' | 'red'
}) {
  return (
    <Card>
      <CardContent className="flex items-center gap-5 p-5">
        <div className={cn('flex h-14 w-14 shrink-0 items-center justify-center rounded-full', toneClasses[tone])}>
          <Icon className="h-7 w-7" />
        </div>
        <div className="min-w-0">
          <p className="text-sm font-medium text-slate-700">{label}</p>
          <p className="mt-1 text-3xl font-semibold text-slate-950">{value}</p>
          <p className="mt-1 text-sm text-slate-500">{helper}</p>
        </div>
      </CardContent>
    </Card>
  )
}

function statusVariant(status: string) {
  if (status === 'Recorded') {
    return 'success'
  }

  if (status === 'Open') {
    return 'warning'
  }

  return 'secondary'
}

const toneClasses = {
  blue: 'bg-blue-50 text-blue-600',
  green: 'bg-green-50 text-green-700',
  orange: 'bg-orange-50 text-orange-600',
  red: 'bg-red-50 text-red-600',
}
