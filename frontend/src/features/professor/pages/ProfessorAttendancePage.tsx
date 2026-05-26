import { CalendarCheck, CheckCircle2, Clock3, Plus, UserCheck, XCircle, Loader2 } from 'lucide-react'
import { useEffect, useState } from 'react'

import { PageHeader } from '@/components/shared/PageHeader'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { getProfessorAttendance, createAttendanceSession } from '@/lib/api/professor'
import { cn } from '@/lib/utils'

type Session = {
  id: string
  courseCode: string
  courseName: string
  date: string
  time: string
  room: string
  type: string
  present: number
  absent: number
  late: number
  status: string
}

type CourseSummary = {
  id: string
  code: string
  name: string
  attendanceRate: number
}

export function ProfessorAttendancePage() {
  const [sessions, setSessions] = useState<Session[]>([])
  const [courses, setCourses] = useState<CourseSummary[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  
  // Modal State
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [selectedCourseKey, setSelectedCourseKey] = useState('')
  const [createdSession, setCreatedSession] = useState<{ code: string; courseName: string } | null>(null)
  const [submitting, setSubmitting] = useState(false)

  const fetchData = () => {
    setLoading(true)
    getProfessorAttendance()
      .then((res) => {
        if (res.success && res.data) {
          setSessions(res.data.sessions)
          setCourses(res.data.courses)
          if (res.data.courses.length > 0) {
            setSelectedCourseKey(res.data.courses[0].id)
          }
        } else {
          setError(res.message || 'Failed to load attendance records.')
        }
      })
      .catch(() => {
        setError('Error connecting to the server.')
      })
      .finally(() => {
        setLoading(false)
      })
  }

  useEffect(() => {
    fetchData()
  }, [])

  const handleCreateSession = (e: React.FormEvent) => {
    e.preventDefault()
    if (!selectedCourseKey) return

    setSubmitting(true)
    createAttendanceSession({ courseKey: selectedCourseKey })
      .then((res) => {
        if (res.success && res.data) {
          setCreatedSession({
            code: res.data.code,
            courseName: res.data.courseName,
          })
          // Reload sessions list
          getProfessorAttendance().then((res2) => {
            if (res2.success && res2.data) {
              setSessions(res2.data.sessions)
            }
          })
        } else {
          alert(res.message || 'Failed to create session.')
        }
      })
      .catch(() => {
        alert('Error connecting to the server.')
      })
      .finally(() => {
        setSubmitting(false)
      })
  }

  if (loading && sessions.length === 0) {
    return (
      <div className="flex h-[400px] items-center justify-center">
        <Loader2 className="h-8 w-8 animate-spin text-blue-500" />
      </div>
    )
  }

  if (error) {
    return (
      <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-600">
        {error}
      </div>
    )
  }

  const recordedSessions = sessions.filter((session) => session.status === 'Recorded')
  const totalPresent = recordedSessions.reduce((total, session) => total + session.present, 0)
  const totalAbsent = recordedSessions.reduce((total, session) => total + session.absent, 0)
  const totalLate = recordedSessions.reduce((total, session) => total + session.late, 0)

  return (
    <>
      <PageHeader title="Attendance Management" description="Class attendance records" />

      <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold text-slate-950">Attendance Management</h1>
          <p className="mt-1 text-sm text-slate-500">Record sessions and monitor attendance across assigned courses.</p>
        </div>
        <Button type="button" onClick={() => { setCreatedSession(null); setIsModalOpen(true); }}>
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
            <Badge variant="secondary">{sessions.length} total</Badge>
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
                  {sessions.map((session) => (
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
            {courses.map((course) => (
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

      {/* New Session Modal */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
          <div className="w-full max-w-md rounded-lg border border-slate-200 bg-white p-6 shadow-xl">
            <h2 className="text-xl font-semibold text-slate-900">Create Attendance Session</h2>
            
            {!createdSession ? (
              <form onSubmit={handleCreateSession} className="mt-4 space-y-4">
                <div>
                  <label htmlFor="course-select" className="text-sm font-medium text-slate-700">Select Course</label>
                  <select
                    id="course-select"
                    className="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none"
                    value={selectedCourseKey}
                    onChange={(e) => setSelectedCourseKey(e.target.value)}
                  >
                    {courses.map((c) => (
                      <option key={c.id} value={c.id}>
                        {c.code} - {c.name}
                      </option>
                    ))}
                  </select>
                </div>

                <div className="flex justify-end gap-2 pt-2">
                  <Button type="button" variant="secondary" onClick={() => setIsModalOpen(false)}>
                    Cancel
                  </Button>
                  <Button type="submit" disabled={submitting}>
                    {submitting ? 'Creating...' : 'Start Session'}
                  </Button>
                </div>
              </form>
            ) : (
              <div className="mt-4 space-y-4 text-center">
                <p className="text-sm text-slate-600">
                  Attendance session started for <strong>{createdSession.courseName}</strong>!
                </p>
                <div className="rounded-lg bg-blue-50 p-4 border border-blue-200">
                  <p className="text-xs font-semibold uppercase tracking-wider text-blue-700">Student Access Code</p>
                  <p className="mt-2 text-4xl font-extrabold tracking-widest text-blue-900">{createdSession.code}</p>
                </div>
                <p className="text-xs text-slate-500">
                  Provide this code or share the QR view with your students to let them check-in automatically.
                </p>
                <Button type="button" className="w-full" onClick={() => setIsModalOpen(false)}>
                  Close
                </Button>
              </div>
            )}
          </div>
        </div>
      )}
    </>
  )
}

function SessionRow({ session }: { session: Session }) {
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
