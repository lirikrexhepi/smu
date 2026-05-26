import { useCallback, useEffect, useMemo, useState } from 'react'
import { QRCodeSVG } from 'qrcode.react'
import type { LucideIcon } from 'lucide-react'
import {
  CalendarCheck,
  CheckCircle2,
  Clock3,
  Maximize2,
  Plus,
  RefreshCw,
  X,
  XCircle,
} from 'lucide-react'

import { PageHeader } from '@/components/shared/PageHeader'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
  getProfessorAttendance,
  getProfessorAttendanceAvailableClasses,
  getProfessorAttendanceSession,
  startProfessorAttendanceSession,
} from '@/lib/api/professor'
import { cn } from '@/lib/utils'
import type {
  ProfessorAttendanceOverview,
  ProfessorAttendanceSession,
  ProfessorAttendanceSessionRecord,
  ProfessorAvailableAttendanceClass,
} from '@/types/professor'

export function ProfessorAttendancePage() {
  const [overview, setOverview] = useState<ProfessorAttendanceOverview | null>(null)
  const [activeSession, setActiveSession] = useState<ProfessorAttendanceSession | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [errorMessage, setErrorMessage] = useState<string | null>(null)
  const [isSessionModalOpen, setIsSessionModalOpen] = useState(false)
  const [isQrModalOpen, setIsQrModalOpen] = useState(false)
  const [nowTick, setNowTick] = useState(() => Date.now())

  const loadOverview = useCallback(() => {
    setIsLoading(true)

    getProfessorAttendance()
      .then((response) => {
        setOverview(response.data)
        setActiveSession(response.data.activeSession)
        setErrorMessage(null)
      })
      .catch((error: unknown) => {
        setErrorMessage(error instanceof Error ? error.message : 'Unable to load attendance')
      })
      .finally(() => {
        setIsLoading(false)
      })
  }, [])

  const refreshActiveSession = useCallback(() => {
    if (!activeSession) {
      loadOverview()
      return
    }

    getProfessorAttendanceSession(activeSession.id)
      .then((response) => {
        if (response.data.isActive) {
          setActiveSession(response.data)
          setErrorMessage(null)
          return
        }

        loadOverview()
      })
      .catch((error: unknown) => {
        setErrorMessage(error instanceof Error ? error.message : 'Unable to refresh session')
      })
  }, [activeSession, loadOverview])

  useEffect(() => {
    loadOverview()
  }, [loadOverview])

  useEffect(() => {
    if (!activeSession) {
      return
    }

    const refreshId = window.setInterval(() => {
      getProfessorAttendanceSession(activeSession.id)
        .then((response) => {
          if (response.data.isActive) {
            setActiveSession(response.data)
            setErrorMessage(null)
            return
          }

          loadOverview()
        })
        .catch(() => undefined)
    }, 12_000)

    return () => window.clearInterval(refreshId)
  }, [activeSession?.id, loadOverview])

  useEffect(() => {
    const timerId = window.setInterval(() => setNowTick(Date.now()), 1_000)

    return () => window.clearInterval(timerId)
  }, [])

  const metrics = overview?.metrics ?? {
    recordedSessions: 0,
    present: 0,
    absent: 0,
    late: 0,
  }

  return (
    <>
      <PageHeader title="Attendance Management" description="Class attendance records" />

      {errorMessage ? (
        <div className="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {errorMessage}
        </div>
      ) : null}

      {activeSession ? (
        <ActiveAttendanceSessionView
          session={activeSession}
          nowTick={nowTick}
          onRefresh={refreshActiveSession}
          onMaximizeQr={() => setIsQrModalOpen(true)}
        />
      ) : (
        <>
          <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
              <h1 className="text-2xl font-semibold text-slate-950">Attendance Management</h1>
              <p className="mt-1 text-sm text-slate-500">Record sessions and monitor attendance across assigned courses.</p>
            </div>
            <Button type="button" onClick={() => setIsSessionModalOpen(true)}>
              <Plus className="h-4 w-4" />
              New Session
            </Button>
          </div>

          {isLoading && !overview ? (
            <Card>
              <CardContent className="p-5 text-sm text-slate-500">Loading attendance...</CardContent>
            </Card>
          ) : (
            <>
              <div className="mb-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <MetricCard label="Recorded Sessions" value={String(metrics.recordedSessions)} helper="Database records" icon={CalendarCheck} tone="blue" />
                <MetricCard label="Present" value={String(metrics.present)} helper="Recorded students" icon={CheckCircle2} tone="green" />
                <MetricCard label="Absent" value={String(metrics.absent)} helper="Needs follow-up" icon={XCircle} tone="red" />
                <MetricCard label="Late" value={String(metrics.late)} helper="Late arrivals" icon={Clock3} tone="orange" />
              </div>

              <div className="grid gap-5 xl:grid-cols-[1fr_360px]">
                <SessionRecordsTable records={overview?.sessionRecords ?? []} />
                <CourseAttendancePanel courses={overview?.courseAttendance ?? []} />
              </div>
            </>
          )}
        </>
      )}

      <NewSessionModal
        isOpen={isSessionModalOpen}
        onClose={() => setIsSessionModalOpen(false)}
        onStarted={(session) => {
          setActiveSession(session)
          setOverview((current) => (current ? { ...current, activeSession: session } : current))
          setIsSessionModalOpen(false)
        }}
      />

      {activeSession && isQrModalOpen ? (
        <QrMaximizeModal session={activeSession} onClose={() => setIsQrModalOpen(false)} />
      ) : null}
    </>
  )
}

function ActiveAttendanceSessionView({
  session,
  nowTick,
  onRefresh,
  onMaximizeQr,
}: {
  session: ProfessorAttendanceSession
  nowTick: number
  onRefresh: () => void
  onMaximizeQr: () => void
}) {
  const remainingSeconds = sessionRemainingSeconds(session, nowTick)

  return (
    <>
      <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold text-slate-950">{session.courseName}</h1>
          <p className="mt-1 text-sm text-slate-500">
            {session.courseCode} · {session.schedule.label} · {session.schedule.days} {session.schedule.time} · Room {session.room}
          </p>
        </div>
        <Button type="button" variant="secondary" onClick={onRefresh}>
          <RefreshCw className="h-4 w-4" />
          Refresh
        </Button>
      </div>

      <div className="mb-5 grid gap-4 xl:grid-cols-[340px_1fr]">
        <Card>
          <CardHeader className="flex-row items-center justify-between border-b border-slate-100 p-4">
            <CardTitle>Check-in QR</CardTitle>
            <Button type="button" variant="ghost" size="icon" aria-label="Maximize QR code" onClick={onMaximizeQr}>
              <Maximize2 className="h-4 w-4" />
            </Button>
          </CardHeader>
          <CardContent className="p-5">
            <div className="mx-auto flex aspect-square max-w-[220px] items-center justify-center rounded-md border border-slate-200 bg-white p-4">
              <QRCodeSVG value={session.qrPayload} size={180} level="M" includeMargin />
            </div>
            <div className="mt-4 rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-center">
              <p className="text-xs font-medium uppercase tracking-wide text-slate-500">Check-in code</p>
              <p className="mt-1 font-mono text-3xl font-semibold tracking-[0.24em] text-slate-950">{session.checkInCode}</p>
            </div>
          </CardContent>
        </Card>

        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <ActiveMetric label="Remaining" value={formatRemaining(remainingSeconds)} helper="Session closes automatically" tone="blue" />
          <ActiveMetric label="Enrolled" value={String(session.totalEnrolled)} helper="Students in course" tone="slate" />
          <ActiveMetric label="Checked in" value={String(session.checkedInCount)} helper={`${session.pendingCount} pending`} tone="green" />
          <ActiveMetric label="Late" value={String(session.lateCount)} helper={`${session.presentCount} present`} tone="orange" />
        </div>
      </div>

      <Card>
        <CardHeader className="flex-col gap-2 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between">
          <CardTitle>Enrolled Students</CardTitle>
          <Badge variant="secondary">{session.records.length} students</Badge>
        </CardHeader>
        <CardContent className="p-0">
          <div className="overflow-x-auto">
            <table className="w-full min-w-[860px] text-left text-sm">
              <thead className="bg-slate-50 text-xs font-semibold text-slate-600">
                <tr>
                  <th className="px-4 py-3">Student</th>
                  <th className="px-4 py-3">Student ID</th>
                  <th className="px-4 py-3">Email</th>
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3">Checked in</th>
                  <th className="px-4 py-3">Method</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {session.records.map((record) => (
                  <tr key={record.id}>
                    <td className="px-4 py-3 font-medium text-slate-800">{record.name}</td>
                    <td className="px-4 py-3 text-slate-600">{record.institutionId}</td>
                    <td className="px-4 py-3 text-slate-600">{record.email}</td>
                    <td className="px-4 py-3">
                      <Badge variant={attendanceStatusVariant(record.status)}>{record.statusLabel}</Badge>
                    </td>
                    <td className="px-4 py-3 text-slate-600">{record.checkedInAt ? formatDateTime(record.checkedInAt) : '-'}</td>
                    <td className="px-4 py-3 text-slate-600">{record.method ?? '-'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </>
  )
}

function NewSessionModal({
  isOpen,
  onClose,
  onStarted,
}: {
  isOpen: boolean
  onClose: () => void
  onStarted: (session: ProfessorAttendanceSession) => void
}) {
  const [classes, setClasses] = useState<ProfessorAvailableAttendanceClass[]>([])
  const [selectedKey, setSelectedKey] = useState('')
  const [isLoading, setIsLoading] = useState(false)
  const [isStarting, setIsStarting] = useState(false)
  const [message, setMessage] = useState<string | null>(null)

  useEffect(() => {
    if (!isOpen) {
      return
    }

    setIsLoading(true)
    setMessage(null)

    getProfessorAttendanceAvailableClasses()
      .then((response) => {
        setClasses(response.data)
        setSelectedKey(response.data[0] ? classKey(response.data[0]) : '')
      })
      .catch((error: unknown) => {
        setMessage(error instanceof Error ? error.message : 'Unable to load available classes')
      })
      .finally(() => {
        setIsLoading(false)
      })
  }, [isOpen])

  const selectedClass = useMemo(
    () => classes.find((attendanceClass) => classKey(attendanceClass) === selectedKey) ?? null,
    [classes, selectedKey],
  )

  if (!isOpen) {
    return null
  }

  function handleStart() {
    if (!selectedClass) {
      return
    }

    setIsStarting(true)
    setMessage(null)

    startProfessorAttendanceSession({
      courseId: selectedClass.courseId,
      courseScheduleId: selectedClass.courseScheduleId,
    })
      .then((response) => {
        onStarted(response.data)
      })
      .catch((error: unknown) => {
        setMessage(error instanceof Error ? error.message : 'Unable to start attendance session')
      })
      .finally(() => {
        setIsStarting(false)
      })
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 px-4 py-6">
      <div className="w-full max-w-lg rounded-lg border border-slate-200 bg-white shadow-xl">
        <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
          <h2 className="text-base font-semibold text-slate-950">New Attendance Session</h2>
          <Button type="button" variant="ghost" size="icon" aria-label="Close modal" onClick={onClose}>
            <X className="h-4 w-4" />
          </Button>
        </div>

        <div className="space-y-4 p-5">
          {isLoading ? (
            <p className="text-sm text-slate-500">Checking your current schedule...</p>
          ) : classes.length === 0 ? (
            <p className="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
              You have no active classes right now.
            </p>
          ) : (
            <>
              <label className="block text-sm font-medium text-slate-700" htmlFor="attendance-class">
                Active class
              </label>
              <select
                id="attendance-class"
                className="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                value={selectedKey}
                onChange={(event) => setSelectedKey(event.target.value)}
              >
                {classes.map((attendanceClass) => (
                  <option key={classKey(attendanceClass)} value={classKey(attendanceClass)}>
                    {attendanceClass.courseCode} - {attendanceClass.courseName}
                  </option>
                ))}
              </select>

              {selectedClass ? (
                <div className="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                  <p className="font-medium text-slate-900">{selectedClass.scheduleLabel}</p>
                  <p className="mt-1">
                    {selectedClass.days} {selectedClass.time} · Room {selectedClass.room}
                  </p>
                  <p className="mt-1">{selectedClass.enrolledCount} enrolled students</p>
                </div>
              ) : null}
            </>
          )}

          {message ? <p className="text-sm text-red-600">{message}</p> : null}
        </div>

        <div className="flex justify-end gap-3 border-t border-slate-100 px-5 py-4">
          <Button type="button" variant="secondary" onClick={onClose}>
            Cancel
          </Button>
          <Button type="button" disabled={!selectedClass || isStarting} onClick={handleStart}>
            {isStarting ? 'Starting...' : 'Start Session'}
          </Button>
        </div>
      </div>
    </div>
  )
}

function QrMaximizeModal({ session, onClose }: { session: ProfessorAttendanceSession; onClose: () => void }) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 px-4 py-6">
      <div className="w-full max-w-xl rounded-lg border border-slate-200 bg-white shadow-xl">
        <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
          <div>
            <h2 className="text-base font-semibold text-slate-950">{session.courseCode} Check-in</h2>
            <p className="mt-1 text-sm text-slate-500">{session.courseName}</p>
          </div>
          <Button type="button" variant="ghost" size="icon" aria-label="Close QR modal" onClick={onClose}>
            <X className="h-4 w-4" />
          </Button>
        </div>
        <div className="flex flex-col items-center gap-5 p-6">
          <div className="flex aspect-square w-full max-w-[380px] items-center justify-center rounded-md border border-slate-200 bg-white p-5">
            <QRCodeSVG value={session.qrPayload} size={320} level="M" includeMargin />
          </div>
          <p className="font-mono text-4xl font-semibold tracking-[0.28em] text-slate-950">{session.checkInCode}</p>
        </div>
      </div>
    </div>
  )
}

function SessionRecordsTable({ records }: { records: ProfessorAttendanceSessionRecord[] }) {
  return (
    <Card>
      <CardHeader className="flex-row items-center justify-between border-b border-slate-100 p-4">
        <CardTitle>Session Records</CardTitle>
        <Badge variant="secondary">{records.length} total</Badge>
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
              {records.map((session) => (
                <SessionRow key={session.id} session={session} />
              ))}
            </tbody>
          </table>
        </div>
        {records.length === 0 ? (
          <div className="border-t border-slate-100 px-4 py-6 text-sm text-slate-500">
            No attendance sessions have been recorded yet.
          </div>
        ) : null}
      </CardContent>
    </Card>
  )
}

function SessionRow({ session }: { session: ProfessorAttendanceSessionRecord }) {
  return (
    <tr>
      <td className="px-4 py-3">
        <p className="font-medium text-slate-800">{session.courseName}</p>
        <p className="text-xs text-slate-500">{session.courseCode}</p>
      </td>
      <td className="px-4 py-3 text-slate-600">{session.date}</td>
      <td className="px-4 py-3 text-slate-600">{session.time || '-'}</td>
      <td className="px-4 py-3 text-slate-600">{session.room || '-'}</td>
      <td className="px-4 py-3 font-semibold text-green-700">{session.present || '-'}</td>
      <td className="px-4 py-3 font-semibold text-red-600">{session.absent || '-'}</td>
      <td className="px-4 py-3 font-semibold text-orange-600">{session.late || '-'}</td>
      <td className="px-4 py-3">
        <Badge variant={session.status === 'Open' ? 'warning' : 'success'}>{session.status}</Badge>
      </td>
    </tr>
  )
}

function CourseAttendancePanel({ courses }: { courses: ProfessorAttendanceOverview['courseAttendance'] }) {
  return (
    <Card className="h-fit">
      <CardHeader>
        <CardTitle>Course Attendance</CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        {courses.map((course) => (
          <div key={course.courseId}>
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
        {courses.length === 0 ? <p className="text-sm text-slate-500">No assigned courses found.</p> : null}
      </CardContent>
    </Card>
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
  icon: LucideIcon
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

function ActiveMetric({
  label,
  value,
  helper,
  tone,
}: {
  label: string
  value: string
  helper: string
  tone: 'blue' | 'green' | 'orange' | 'slate'
}) {
  return (
    <Card>
      <CardContent className="p-5">
        <p className="text-sm font-medium text-slate-700">{label}</p>
        <p className={cn('mt-2 text-3xl font-semibold', activeMetricToneClasses[tone])}>{value}</p>
        <p className="mt-2 text-sm text-slate-500">{helper}</p>
      </CardContent>
    </Card>
  )
}

function classKey(attendanceClass: ProfessorAvailableAttendanceClass): string {
  return `${attendanceClass.courseId}:${attendanceClass.courseScheduleId}`
}

function sessionRemainingSeconds(session: ProfessorAttendanceSession, nowTick: number): number {
  return Math.max(0, Math.floor((new Date(session.endsAt).getTime() - nowTick) / 1000))
}

function formatRemaining(seconds: number): string {
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const remainingSeconds = seconds % 60

  if (hours > 0) {
    return `${hours}h ${minutes}m`
  }

  return `${minutes}m ${remainingSeconds}s`
}

function formatDateTime(value: string): string {
  return new Intl.DateTimeFormat(undefined, {
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(value))
}

function attendanceStatusVariant(status: string) {
  if (status === 'present') {
    return 'success'
  }

  if (status === 'late') {
    return 'warning'
  }

  if (status === 'absent') {
    return 'danger'
  }

  return 'secondary'
}

const toneClasses = {
  blue: 'bg-blue-50 text-blue-600',
  green: 'bg-green-50 text-green-700',
  orange: 'bg-orange-50 text-orange-600',
  red: 'bg-red-50 text-red-600',
}

const activeMetricToneClasses = {
  blue: 'text-blue-700',
  green: 'text-green-700',
  orange: 'text-orange-700',
  slate: 'text-slate-950',
}
