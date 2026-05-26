import type { CSSProperties } from 'react'
import type { LucideIcon } from 'lucide-react'
import {
  Camera,
  CheckCircle2,
  ChevronLeft,
  ChevronRight,
  Clock3,
  Info,
  Keyboard,
  QrCode,
  TrendingDown,
  TrendingUp,
  UserCheck,
  X,
  XCircle,
} from 'lucide-react'
import { useEffect, useMemo, useRef, useState } from 'react'

import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Skeleton } from '@/components/ui/skeleton'
import { checkInStudentAttendance, getStudentAttendance } from '@/lib/api/student'
import { cn } from '@/lib/utils'
import type {
  StudentAttendance,
  StudentAttendanceCheckInResult,
  StudentAttendanceHistoryRecord,
  StudentAttendanceScheduleBlock,
  StudentAttendanceScheduleDay,
  StudentAttendanceTone,
} from '@/types/student'

const calendarStartMinutes = 8 * 60
const calendarEndMinutes = 18 * 60
const scheduleGridHeightClass = 'h-[520px]'
const timeSlots = ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00']

type ViewMode = 'week' | 'day'

export function StudentAttendancePage() {
  const [attendance, setAttendance] = useState<StudentAttendance | null>(null)
  const [selectedCourse, setSelectedCourse] = useState('all')
  const [selectedSemester, setSelectedSemester] = useState('all')
  const [viewMode, setViewMode] = useState<ViewMode>('week')
  const [errorMessage, setErrorMessage] = useState<string | null>(null)
  const [isCheckInModalOpen, setIsCheckInModalOpen] = useState(false)

  useEffect(() => {
    let isMounted = true

    getStudentAttendance({
      courseId: selectedCourse,
      semester: selectedSemester,
    })
      .then((response) => {
        if (!isMounted) {
          return
        }

        setAttendance(response.data)
        setErrorMessage(null)

        if (selectedSemester === 'all' && response.data.filters.semesters[0]) {
          setSelectedSemester(response.data.filters.semesters[0])
        }
      })
      .catch((error: unknown) => {
        if (!isMounted) {
          return
        }

        setErrorMessage(error instanceof Error ? error.message : 'Unable to load attendance')
      })

    return () => {
      isMounted = false
    }
  }, [selectedCourse, selectedSemester])

  const visibleDays = useMemo(() => {
    if (!attendance) {
      return []
    }

    if (viewMode === 'day') {
      const today = attendance.weeklySchedule.find((day) => day.isToday)

      return today ? [today] : attendance.weeklySchedule.slice(0, 1)
    }

    return attendance.weeklySchedule
  }, [attendance, viewMode])

  function refreshAttendance() {
    return getStudentAttendance({
      courseId: selectedCourse,
      semester: selectedSemester,
    }).then((response) => {
      setAttendance(response.data)
      setErrorMessage(null)
    })
  }

  if (!attendance) {
    return (
      <>
      
        {errorMessage ? (
          <Card>
            <CardContent className="pt-5">
              <p className="text-sm text-slate-500">{errorMessage}</p>
            </CardContent>
          </Card>
        ) : (
          <StudentAttendanceSkeleton />
        )}
      </>
    )
  }

  return (
    <>
      {errorMessage ? (
        <div className="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {errorMessage}
        </div>
      ) : null}

      <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold text-slate-950">Attendance</h1>
          <p className="mt-1 text-sm text-slate-500">Review attendance records and check in to active class sessions.</p>
        </div>
        <Button type="button" onClick={() => setIsCheckInModalOpen(true)}>
          <QrCode className="h-4 w-4" />
          Check in
        </Button>
      </div>

      <div className="mb-4 grid gap-3 lg:grid-cols-[minmax(210px,240px)_minmax(260px,320px)_minmax(240px,270px)]">
        <select
          aria-label="Filter attendance by course"
          className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
          value={selectedCourse}
          onChange={(event) => setSelectedCourse(event.target.value)}
        >
          <option value="all">All Courses</option>
          {attendance.filters.courses.map((course) => (
            <option key={course.courseId} value={course.courseId}>
              {course.code} - {course.name}
            </option>
          ))}
        </select>

        <select
          aria-label="Filter attendance by semester"
          className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
          value={selectedSemester}
          onChange={(event) => setSelectedSemester(event.target.value)}
        >
          <option value="all">All Semesters</option>
          {attendance.filters.semesters.map((semester) => (
            <option key={semester} value={semester}>
              {semester}
            </option>
          ))}
        </select>

        <div className="flex h-10 items-center justify-between rounded-md border border-slate-200 bg-white px-2 text-sm font-medium text-slate-700 shadow-sm">
          <Button type="button" variant="ghost" size="icon" className="h-8 w-8" aria-label="Previous week">
            <ChevronLeft className="h-4 w-4" />
          </Button>
          <span className="truncate px-2">{attendance.week.label}</span>
          <Button type="button" variant="ghost" size="icon" className="h-8 w-8" aria-label="Next week">
            <ChevronRight className="h-4 w-4" />
          </Button>
        </div>
      </div>

      <div className="mb-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <AttendanceMetricCard
          label="Overall Attendance"
          value={`${attendance.summary.overallAttendance}%`}
          helper={comparisonHelper(attendance.summary.comparisonVsLast4Weeks)}
          helperTone={attendance.summary.comparisonVsLast4Weeks.direction === 'down' ? 'red' : 'green'}
          icon={UserCheck}
          tone="green"
          trendDirection={attendance.summary.comparisonVsLast4Weeks.direction}
        />
        <AttendanceMetricCard
          label="Present Sessions"
          value={String(attendance.summary.presentSessions)}
          helper={`of ${attendance.summary.totalSessions} total sessions`}
          icon={CheckCircle2}
          tone="blue"
        />
        <AttendanceMetricCard
          label="Absences"
          value={String(attendance.summary.absences)}
          helper={`${attendance.summary.absenceRate}% of total sessions`}
          icon={XCircle}
          tone="red"
        />
        <AttendanceMetricCard
          label="Late Records"
          value={String(attendance.summary.lateRecords)}
          helper={`${attendance.summary.lateRate}% of total sessions`}
          icon={Clock3}
          tone="orange"
        />
      </div>

      <LastRecordedStrip lastRecorded={attendance.lastRecorded} />

      <WeeklyScheduleCard
        days={visibleDays}
        viewMode={viewMode}
        weekLabel={attendance.week.label}
        onViewModeChange={setViewMode}
      />

      <AttendanceHistoryTable records={attendance.history} />

      <CheckInModal
        isOpen={isCheckInModalOpen}
        onClose={() => setIsCheckInModalOpen(false)}
        onCheckedIn={refreshAttendance}
      />
    </>
  )
}

function CheckInModal({
  isOpen,
  onClose,
  onCheckedIn,
}: {
  isOpen: boolean
  onClose: () => void
  onCheckedIn: () => Promise<void>
}) {
  const [code, setCode] = useState('')
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [isScanning, setIsScanning] = useState(false)
  const [message, setMessage] = useState<string | null>(null)
  const [result, setResult] = useState<StudentAttendanceCheckInResult | null>(null)
  const videoRef = useRef<HTMLVideoElement | null>(null)
  const streamRef = useRef<MediaStream | null>(null)
  const frameRef = useRef<number | null>(null)

  useEffect(() => {
    if (!isOpen) {
      stopScanner()
      setCode('')
      setMessage(null)
      setResult(null)
    }

    return () => stopScanner()
  }, [isOpen])

  if (!isOpen) {
    return null
  }

  function stopScanner() {
    if (frameRef.current !== null) {
      window.cancelAnimationFrame(frameRef.current)
      frameRef.current = null
    }

    streamRef.current?.getTracks().forEach((track) => track.stop())
    streamRef.current = null

    if (videoRef.current) {
      videoRef.current.srcObject = null
    }

    setIsScanning(false)
  }

  function submitCheckIn(payload: { code?: string; qrToken?: string }) {
    setIsSubmitting(true)
    setMessage(null)
    setResult(null)

    checkInStudentAttendance(payload)
      .then((response) => {
        setResult(response.data)
        setCode('')
        return onCheckedIn()
      })
      .catch((error: unknown) => {
        setMessage(error instanceof Error ? error.message : 'Unable to check in')
      })
      .finally(() => {
        setIsSubmitting(false)
      })
  }

  function handleManualSubmit() {
    if (!/^\d{6}$/.test(code)) {
      setMessage('Enter a valid 6-digit code.')
      return
    }

    submitCheckIn({ code })
  }

  function handleCodeChange(value: string) {
    setCode(value.replace(/\D/g, '').slice(0, 6))
  }

  async function startScanner() {
    const BarcodeDetectorConstructor = getBarcodeDetectorConstructor()

    if (!BarcodeDetectorConstructor) {
      setMessage('QR scanning is not supported in this browser. Enter the 6-digit code instead.')
      return
    }

    if (!navigator.mediaDevices?.getUserMedia) {
      setMessage('Camera access is not available in this browser. Enter the 6-digit code instead.')
      return
    }

    try {
      setMessage(null)
      const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
      const video = videoRef.current

      if (!video) {
        stream.getTracks().forEach((track) => track.stop())
        return
      }

      streamRef.current = stream
      video.srcObject = stream
      await video.play()
      setIsScanning(true)

      const detector = new BarcodeDetectorConstructor({ formats: ['qr_code'] })

      const scanFrame = async () => {
        const currentVideo = videoRef.current

        if (!currentVideo || !streamRef.current) {
          return
        }

        try {
          const barcodes = await detector.detect(currentVideo)
          const rawValue = barcodes[0]?.rawValue

          if (rawValue) {
            stopScanner()
            submitCheckIn(parseScannedCheckIn(rawValue))
            return
          }
        } catch {
          // Keep the scanner alive; some browsers throw while the video is warming up.
        }

        frameRef.current = window.requestAnimationFrame(scanFrame)
      }

      frameRef.current = window.requestAnimationFrame(scanFrame)
    } catch {
      stopScanner()
      setMessage('Camera permission was denied or unavailable. Enter the 6-digit code instead.')
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 px-4 py-6">
      <div className="w-full max-w-lg rounded-lg border border-slate-200 bg-white shadow-xl">
        <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
          <h2 className="text-base font-semibold text-slate-950">Attendance Check-in</h2>
          <Button type="button" variant="ghost" size="icon" aria-label="Close check-in modal" onClick={onClose}>
            <X className="h-4 w-4" />
          </Button>
        </div>

        <div className="space-y-4 p-5">
          <div className="rounded-md border border-slate-200 bg-slate-50 p-3">
            <div className="flex items-center justify-between gap-3">
              <div className="flex items-center gap-2 text-sm font-medium text-slate-800">
                <Camera className="h-4 w-4 text-blue-600" />
                QR scan
              </div>
              <Button type="button" variant="secondary" size="sm" disabled={isScanning || isSubmitting} onClick={startScanner}>
                {isScanning ? 'Scanning...' : 'Start camera'}
              </Button>
            </div>
            <video
              ref={videoRef}
              className={cn('mt-3 aspect-video w-full rounded-md bg-slate-900 object-cover', !isScanning && 'hidden')}
              muted
              playsInline
            />
          </div>

          <div className="space-y-2">
            <div className="flex items-center gap-2 text-sm font-medium text-slate-800">
              <Keyboard className="h-4 w-4 text-blue-600" />
              Manual code
            </div>
            <div className="flex gap-2">
              <Input
                inputMode="numeric"
                pattern="[0-9]*"
                maxLength={6}
                value={code}
                placeholder="6-digit code"
                className="font-mono text-lg tracking-[0.24em]"
                onChange={(event) => handleCodeChange(event.target.value)}
              />
              <Button type="button" disabled={code.length !== 6 || isSubmitting} onClick={handleManualSubmit}>
                Check in
              </Button>
            </div>
          </div>

          {message ? <p className="text-sm text-red-600">{message}</p> : null}

          {result ? (
            <div className="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-slate-700">
              <p className="font-semibold text-green-800">Marked {result.statusLabel.toLowerCase()}</p>
              <p className="mt-1">
                {result.course.name} ({result.course.code}) · Professor {result.professor.name}
              </p>
            </div>
          ) : null}
        </div>
      </div>
    </div>
  )
}

function AttendanceMetricCard({
  label,
  value,
  helper,
  icon: Icon,
  tone,
  helperTone = 'slate',
  trendDirection,
}: {
  label: string
  value: string
  helper: string
  icon: LucideIcon
  tone: StudentAttendanceTone
  helperTone?: 'green' | 'red' | 'slate'
  trendDirection?: 'up' | 'down' | 'flat'
}) {
  const TrendIcon = trendDirection === 'down' ? TrendingDown : TrendingUp

  return (
    <Card>
      <CardContent className="flex items-center gap-5 p-5">
        <div className={cn('flex h-14 w-14 shrink-0 items-center justify-center rounded-full', toneClasses[tone].soft)}>
          <Icon className="h-7 w-7" />
        </div>
        <div className="min-w-0">
          <p className="text-sm font-medium text-slate-700">{label}</p>
          <p className="mt-1 text-3xl font-semibold text-slate-950">{value}</p>
          <p
            className={cn(
              'mt-1 flex items-center gap-1 text-sm',
              helperTone === 'green' && 'text-green-600',
              helperTone === 'red' && 'text-red-600',
              helperTone === 'slate' && 'text-slate-500',
            )}
          >
            {trendDirection && trendDirection !== 'flat' ? <TrendIcon className="h-3.5 w-3.5" /> : null}
            {helper}
          </p>
        </div>
      </CardContent>
    </Card>
  )
}

function LastRecordedStrip({ lastRecorded }: { lastRecorded: StudentAttendance['lastRecorded'] }) {
  if (!lastRecorded) {
    return (
      <div className="mb-4 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-500 shadow-sm">
        No attendance has been recorded for the selected filters.
      </div>
    )
  }

  return (
    <div className="mb-4 flex flex-wrap items-center gap-x-7 gap-y-2 rounded-lg border border-green-100 bg-green-50/40 px-5 py-3 text-sm text-slate-700 shadow-sm">
      <div className="flex items-center gap-3 font-semibold text-slate-900">
        <span className="h-2.5 w-2.5 rounded-full bg-green-600" />
        Last Recorded
      </div>
      <span>{lastRecorded.courseName} ({lastRecorded.courseCode})</span>
      <span className="text-slate-400">-</span>
      <span>{lastRecorded.dateLabel}</span>
      <span className="text-slate-400">-</span>
      <span>{lastRecorded.time}</span>
      <Badge variant="success" className="gap-1 border border-green-200 bg-white">
        <CheckCircle2 className="h-3.5 w-3.5" />
        {lastRecorded.statusLabel}
      </Badge>
    </div>
  )
}

function WeeklyScheduleCard({
  days,
  viewMode,
  weekLabel,
  onViewModeChange,
}: {
  days: StudentAttendanceScheduleDay[]
  viewMode: ViewMode
  weekLabel: string
  onViewModeChange: (viewMode: ViewMode) => void
}) {
  const gridTemplateColumns = `58px repeat(${days.length || 1}, minmax(150px, 1fr))`

  return (
    <Card className="mb-4 overflow-hidden">
      <CardHeader className="flex-col gap-4 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between">
        <div className="flex items-center gap-2">
          <CardTitle>Weekly Schedule</CardTitle>
          <Info className="h-4 w-4 text-slate-400" />
        </div>
        <div className="flex flex-wrap items-center gap-3">
          <div className="grid h-11 grid-cols-2 overflow-hidden rounded-md border border-slate-200 bg-white text-sm">
            <button
              type="button"
              className={cn(
                'min-w-20 px-5 leading-none font-medium text-slate-600 transition-colors',
                viewMode === 'week' && 'bg-blue-50 text-blue-700',
              )}
              onClick={() => onViewModeChange('week')}
            >
              Week
            </button>
            <button
              type="button"
              className={cn(
                'min-w-20 border-l border-slate-200 px-5 leading-none font-medium text-slate-600 transition-colors',
                viewMode === 'day' && 'bg-blue-50 text-blue-700',
              )}
              onClick={() => onViewModeChange('day')}
            >
              Day
            </button>
          </div>
          <Button type="button" variant="secondary" className="h-9 px-5">
            Today
          </Button>
          <div className="flex overflow-hidden rounded-md border border-slate-200 bg-white">
            <Button type="button" variant="ghost" size="icon" className="h-9 w-11 rounded-none" aria-label="Previous schedule">
              <ChevronLeft className="h-4 w-4" />
            </Button>
            <Button type="button" variant="ghost" size="icon" className="h-9 w-11 rounded-none border-l border-slate-200" aria-label="Next schedule">
              <ChevronRight className="h-4 w-4" />
            </Button>
          </div>
        </div>
      </CardHeader>
      <CardContent className="p-0">
        <div className="overflow-x-auto">
          <div
            className={cn('min-w-[1080px]', viewMode === 'day' && 'min-w-[380px]')}
            aria-label={`${weekLabel} attendance schedule`}
          >
            <div className="grid" style={{ gridTemplateColumns }}>
              <div className="h-14 border-r border-slate-100 bg-white" />
              {days.map((day) => (
                <div
                  key={day.date}
                  className={cn(
                    'flex h-14 flex-col items-center justify-center border-r border-slate-100 bg-white text-center last:border-r-0',
                    day.isToday && 'bg-blue-50/60',
                  )}
                >
                  <p className={cn('text-sm font-semibold text-slate-900', day.isToday && 'text-blue-700')}>{day.dayShort}</p>
                  <p className="mt-0.5 text-xs text-slate-500">{day.dateLabel}</p>
                </div>
              ))}
            </div>

            <div className="grid border-t border-slate-100" style={{ gridTemplateColumns }}>
              <div className={cn('relative border-r border-slate-100 bg-white', scheduleGridHeightClass)}>
                {timeSlots.map((slot) => (
                  <div key={slot} className="absolute left-4 text-sm font-medium text-slate-700" style={timeLabelStyle(slot)}>
                    {slot}
                  </div>
                ))}
              </div>
              {days.map((day) => (
                <ScheduleDayColumn key={day.date} day={day} />
              ))}
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  )
}

function ScheduleDayColumn({ day }: { day: StudentAttendanceScheduleDay }) {
  return (
    <div className={cn('relative border-r border-slate-100 bg-white last:border-r-0', scheduleGridHeightClass, day.isToday && 'bg-blue-50/30')}>
      {timeSlots.map((slot) => (
        <div key={slot} className="absolute left-0 right-0 border-t border-dashed border-slate-200" style={timeLineStyle(slot)} />
      ))}

      {day.blocks.map((block) => (
        <ScheduleBlock key={block.id} block={block} />
      ))}
    </div>
  )
}

function ScheduleBlock({ block }: { block: StudentAttendanceScheduleBlock }) {
  const StatusIcon = statusIcon(block.status)

  return (
    <div
      className={cn(
        'absolute left-2 right-2 overflow-hidden rounded-md border p-2 text-xs shadow-sm',
        scheduleBlockClass(block),
      )}
      style={blockStyle(block)}
      title={`${block.courseName} - ${block.time}`}
    >
      <p className="truncate font-semibold leading-4 text-slate-950">{block.courseName}</p>
      <p className="mt-1 truncate leading-4 text-slate-700">{block.time}</p>
      <p className="truncate leading-4 text-slate-600">Room {block.room}</p>

      {block.status === 'recorded' ? (
        <span className="absolute bottom-2 right-2 inline-flex items-center gap-1 rounded-md border border-green-200 bg-white px-1.5 py-0.5 text-[11px] font-semibold text-green-700">
          <CheckCircle2 className="h-3 w-3" />
          Recorded
        </span>
      ) : StatusIcon ? (
        <span className={cn('absolute bottom-2 right-2 rounded-full bg-white', statusIconClasses(block.status))}>
          <StatusIcon className="h-4 w-4" />
        </span>
      ) : null}
    </div>
  )
}

function AttendanceHistoryTable({ records }: { records: StudentAttendanceHistoryRecord[] }) {
  return (
    <Card>
      <CardHeader className="border-b border-slate-100 p-4">
        <CardTitle>Attendance History</CardTitle>
      </CardHeader>
      <CardContent className="p-0">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[900px] text-left text-sm">
            <thead className="bg-slate-50 text-xs font-semibold text-slate-600">
              <tr>
                <th className="px-4 py-3">Course</th>
                <th className="px-4 py-3">Date</th>
                <th className="px-4 py-3">Time</th>
                <th className="px-4 py-3">Type</th>
                <th className="px-4 py-3">Professor</th>
                <th className="px-4 py-3">Result</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {records.map((record) => (
                <tr key={record.id}>
                  <td className="px-4 py-3 font-medium text-slate-700">
                    {record.courseName} ({record.courseCode})
                  </td>
                  <td className="px-4 py-3 text-slate-600">{record.date}</td>
                  <td className="px-4 py-3 text-slate-600">{record.time}</td>
                  <td className="px-4 py-3 text-slate-600">{record.type}</td>
                  <td className="px-4 py-3 text-slate-600">{record.professor}</td>
                  <td className="px-4 py-3">
                    <ResultBadge record={record} />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {records.length === 0 ? (
          <div className="border-t border-slate-100 px-4 py-6 text-sm text-slate-500">
            No attendance history matches the selected filters.
          </div>
        ) : (
          <button type="button" className="flex items-center gap-1 px-4 py-3 text-sm font-semibold text-blue-600 hover:text-blue-700">
            View full attendance history
            <ChevronRight className="h-4 w-4" />
          </button>
        )}
      </CardContent>
    </Card>
  )
}

function ResultBadge({ record }: { record: StudentAttendanceHistoryRecord }) {
  const Icon = statusIcon(record.result) ?? CheckCircle2

  return (
    <span className={cn('inline-flex items-center gap-1 text-xs font-semibold', resultClasses(record.result))}>
      <Icon className="h-3.5 w-3.5" />
      {record.resultLabel}
    </span>
  )
}

function StudentAttendanceSkeleton() {
  return (
    <div aria-label="Loading attendance" aria-busy="true">
      <div className="mb-4 space-y-2">
        <Skeleton className="h-8 w-36" />
        <Skeleton className="h-4 w-72" />
      </div>
      <div className="mb-4 grid gap-3 lg:grid-cols-[minmax(210px,240px)_minmax(260px,320px)_minmax(240px,270px)]">
        <Skeleton className="h-10 w-full" />
        <Skeleton className="h-10 w-full" />
        <Skeleton className="h-10 w-full" />
      </div>
      <div className="mb-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        {Array.from({ length: 4 }).map((_, index) => (
          <Card key={index}>
            <CardContent className="flex items-center gap-5 p-5">
              <Skeleton className="h-14 w-14 rounded-full" />
              <div className="flex-1 space-y-2">
                <Skeleton className="h-4 w-32" />
                <Skeleton className="h-8 w-16" />
                <Skeleton className="h-4 w-36" />
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
      <Skeleton className="mb-4 h-11 w-full" />
      <Card className="mb-4 overflow-hidden">
        <CardHeader className="border-b border-slate-100 p-4">
          <Skeleton className="h-5 w-40" />
        </CardHeader>
        <CardContent className="p-0">
          <Skeleton className="h-[420px] w-full rounded-none" />
        </CardContent>
      </Card>
      <Card>
        <CardHeader className="border-b border-slate-100 p-4">
          <Skeleton className="h-5 w-44" />
        </CardHeader>
        <CardContent className="p-4">
          <div className="space-y-3">
            {Array.from({ length: 4 }).map((_, index) => (
              <Skeleton key={index} className="h-8 w-full" />
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  )
}

function timeToMinutes(time: string): number {
  const [hours = '0', minutes = '0'] = time.split(':')

  return Number(hours) * 60 + Number(minutes)
}

function timeLineStyle(time: string): CSSProperties {
  const minutes = timeToMinutes(time)
  const top = ((minutes - calendarStartMinutes) / (calendarEndMinutes - calendarStartMinutes)) * 100

  return {
    top: `${Math.max(0, Math.min(100, top))}%`,
  }
}

function timeLabelStyle(time: string): CSSProperties {
  return {
    ...timeLineStyle(time),
    transform: 'translateY(-50%)',
  }
}

function blockStyle(block: StudentAttendanceScheduleBlock): CSSProperties {
  const start = timeToMinutes(block.startTime)
  const end = timeToMinutes(block.endTime)
  const top = ((start - calendarStartMinutes) / (calendarEndMinutes - calendarStartMinutes)) * 100
  const height = ((Math.max(end, start + 30) - start) / (calendarEndMinutes - calendarStartMinutes)) * 100

  return {
    top: `calc(${Math.max(0, Math.min(100, top))}% + 4px)`,
    height: `calc(${Math.max(8, height)}% - 8px)`,
    minHeight: '58px',
  }
}

function statusIcon(status: string): LucideIcon | null {
  if (status === 'absent') {
    return XCircle
  }

  if (status === 'late') {
    return Clock3
  }

  if (status === 'present' || status === 'recorded') {
    return CheckCircle2
  }

  return null
}

function statusIconClasses(status: string): string {
  if (status === 'absent') {
    return 'text-red-500'
  }

  if (status === 'late') {
    return 'text-orange-500'
  }

  return 'text-blue-500'
}

function resultClasses(result: string): string {
  if (result === 'absent') {
    return 'text-red-600'
  }

  if (result === 'late') {
    return 'text-orange-600'
  }

  return 'text-green-700'
}

function scheduleBlockClass(block: StudentAttendanceScheduleBlock): string {
  if (block.status === 'absent') {
    return blockToneClasses.red
  }

  if (block.status === 'late') {
    return blockToneClasses.orange
  }

  return blockToneClasses[block.tone] ?? blockToneClasses.blue
}

function comparisonHelper(comparison: StudentAttendance['summary']['comparisonVsLast4Weeks']): string {
  const sign = comparison.direction === 'down' ? '-' : comparison.direction === 'flat' ? '' : '+'

  return `${sign}${comparison.value}% ${comparison.label}`
}

type BrowserBarcodeDetector = {
  detect: (source: HTMLVideoElement) => Promise<Array<{ rawValue: string }>>
}

type BrowserBarcodeDetectorConstructor = new (options?: { formats?: string[] }) => BrowserBarcodeDetector

function getBarcodeDetectorConstructor(): BrowserBarcodeDetectorConstructor | null {
  const detector = (window as typeof window & { BarcodeDetector?: BrowserBarcodeDetectorConstructor }).BarcodeDetector

  return typeof detector === 'function' ? detector : null
}

function parseScannedCheckIn(value: string): { code?: string; qrToken?: string } {
  const trimmed = value.trim()

  if (/^\d{6}$/.test(trimmed)) {
    return { code: trimmed }
  }

  if (trimmed.startsWith('sems-attendance:')) {
    return { qrToken: trimmed.replace('sems-attendance:', '').trim() }
  }

  try {
    const parsed = JSON.parse(trimmed) as { qrToken?: unknown; token?: unknown; code?: unknown }

    if (typeof parsed.code === 'string' && /^\d{6}$/.test(parsed.code)) {
      return { code: parsed.code }
    }

    if (typeof parsed.qrToken === 'string') {
      return { qrToken: parsed.qrToken }
    }

    if (typeof parsed.token === 'string') {
      return { qrToken: parsed.token }
    }
  } catch {
    // Plain opaque tokens are valid QR payloads.
  }

  try {
    const url = new URL(trimmed)
    const code = url.searchParams.get('code')
    const token = url.searchParams.get('qrToken') ?? url.searchParams.get('token')

    if (code && /^\d{6}$/.test(code)) {
      return { code }
    }

    if (token) {
      return { qrToken: token }
    }
  } catch {
    // Not a URL; treat the full value as the opaque token.
  }

  return { qrToken: trimmed }
}

const toneClasses: Record<StudentAttendanceTone, { soft: string }> = {
  blue: { soft: 'bg-blue-50 text-blue-600' },
  green: { soft: 'bg-green-50 text-green-700' },
  orange: { soft: 'bg-orange-50 text-orange-600' },
  purple: { soft: 'bg-purple-50 text-purple-600' },
  red: { soft: 'bg-red-50 text-red-600' },
  teal: { soft: 'bg-teal-50 text-teal-700' },
}

const blockToneClasses: Record<StudentAttendanceTone, string> = {
  blue: 'border-blue-200 bg-blue-50/80 text-blue-700',
  green: 'border-green-300 bg-green-50/80 text-green-700',
  orange: 'border-orange-200 bg-orange-50/85 text-orange-700',
  purple: 'border-purple-200 bg-purple-50/85 text-purple-700',
  red: 'border-red-200 bg-red-50/85 text-red-700',
  teal: 'border-teal-200 bg-teal-50/85 text-teal-700',
}
