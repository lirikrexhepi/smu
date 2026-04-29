import type { LucideIcon } from 'lucide-react'
import { ArrowRight, BookOpen, CalendarDays, Clock3, GraduationCap, Search, UserRound } from 'lucide-react'
import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'

import { PageHeader } from '@/components/shared/PageHeader'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Skeleton } from '@/components/ui/skeleton'
import { getStudentCourses } from '@/lib/api/student'
import { cn } from '@/lib/utils'
import type {
  StudentCourseEvent,
  StudentCourseOverviewItem,
  StudentCourseStatus,
  StudentCourseTone,
  StudentCoursesOverview,
} from '@/types/student'

type StatusFilter = StudentCourseStatus | 'all'
type SortFilter = 'default' | 'name-asc' | 'grade-desc' | 'attendance-desc' | 'ects-desc'

export function StudentCoursesPage() {
  const [overview, setOverview] = useState<StudentCoursesOverview | null>(null)
  const [searchQuery, setSearchQuery] = useState('')
  const [semesterFilter, setSemesterFilter] = useState('all')
  const [statusFilter, setStatusFilter] = useState<StatusFilter>('all')
  const [sortFilter, setSortFilter] = useState<SortFilter>('default')
  const [errorMessage, setErrorMessage] = useState<string | null>(null)

  useEffect(() => {
    let isMounted = true

    getStudentCourses({
      search: searchQuery,
      semester: semesterFilter,
      status: statusFilter,
      sort: sortFilter,
    })
      .then((response) => {
        if (!isMounted) {
          return
        }

        setOverview(response.data)
        setErrorMessage(null)
      })
      .catch((error: unknown) => {
        if (!isMounted) {
          return
        }

        setErrorMessage(error instanceof Error ? error.message : 'Unable to load courses')
      })

    return () => {
      isMounted = false
    }
  }, [searchQuery, semesterFilter, statusFilter, sortFilter])

  if (!overview) {
    return (
      <>
        <PageHeader title="Courses" description="My enrolled courses" />
        {errorMessage ? (
          <Card>
            <CardContent className="pt-5">
              <p className="text-sm text-slate-500">{errorMessage}</p>
            </CardContent>
          </Card>
        ) : (
          <CoursesOverviewSkeleton />
        )}
      </>
    )
  }

  const summaryCards: Array<{
    id: string
    label: string
    value: string
    helper: string
    tone: StudentCourseTone
    icon: LucideIcon
  }> = [
    {
      id: 'enrolled-courses',
      label: 'Enrolled Courses',
      value: String(overview.summary.enrolledCourses),
      helper: 'All active courses',
      tone: 'blue',
      icon: BookOpen,
    },
    {
      id: 'total-ects',
      label: 'Total ECTS',
      value: String(overview.summary.totalEcts),
      helper: `Of ${overview.summary.ectsTarget} ECTS enrolled`,
      tone: 'green',
      icon: GraduationCap,
    },
    {
      id: 'upcoming-deadlines',
      label: 'Upcoming Deadlines',
      value: String(overview.summary.upcomingDeadlines),
      helper: 'In the next 14 days',
      tone: 'orange',
      icon: Clock3,
    },
  ]

  return (
    <>
      <PageHeader title="Courses" description="My enrolled courses" />

      {errorMessage ? (
        <div className="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {errorMessage}
        </div>
      ) : null}

      <div className="mb-6 grid gap-3 lg:grid-cols-[minmax(260px,420px)_220px_170px_210px]">
        <div className="relative">
          <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <Input
            aria-label="Search courses"
            className="pl-9"
            placeholder="Search courses..."
            value={searchQuery}
            onChange={(event) => setSearchQuery(event.target.value)}
          />
        </div>
        <select
          aria-label="Filter by semester"
          className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
          value={semesterFilter}
          onChange={(event) => setSemesterFilter(event.target.value)}
        >
          <option value="all">All Semesters</option>
          {overview.filters.semesters.map((semester) => (
            <option key={semester} value={semester}>
              {semester}
            </option>
          ))}
        </select>
        <select
          aria-label="Filter by status"
          className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            value={statusFilter}
            onChange={(event) => setStatusFilter(event.target.value as StatusFilter)}
        >
          <option value="all">All Status</option>
          {overview.filters.statuses.map((status) => (
            <option key={status.value} value={status.value}>
              {status.label}
            </option>
          ))}
        </select>
        <select
          aria-label="Sort courses"
          className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
          value={sortFilter}
          onChange={(event) => setSortFilter(event.target.value as SortFilter)}
        >
          <option value="default">Default Order</option>
          <option value="name-asc">Course Name A-Z</option>
          <option value="grade-desc">Highest Grade</option>
          <option value="attendance-desc">Highest Attendance</option>
          <option value="ects-desc">Most ECTS</option>
        </select>
      </div>

      <div className="grid gap-6 xl:grid-cols-[1fr_360px]">
        <div>
          <div className="mb-5 grid gap-4 md:grid-cols-3">
            {summaryCards.map((card) => (
              <SummaryCard key={card.id} card={card} />
            ))}
          </div>

          {overview.courses.length > 0 ? (
            <div className="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
              {overview.courses.map((course) => (
                <CourseCard key={course.courseId} course={course} />
              ))}
            </div>
          ) : (
            <Card>
              <CardContent className="pt-5">
                <p className="text-sm text-slate-500">No courses match the selected filters.</p>
              </CardContent>
            </Card>
          )}
        </div>

        <Card className="h-fit">
          <CardHeader className="flex-row items-center justify-between">
            <CardTitle>Upcoming Course Deadlines</CardTitle>
            <span className="inline-flex items-center gap-1 text-sm font-medium text-blue-600">
              View All
              <ArrowRight className="h-4 w-4" />
            </span>
          </CardHeader>
          <CardContent>
            <div className="divide-y divide-slate-100">
              {overview.upcomingDeadlines.map((deadline) => (
                <DeadlineRow key={`${deadline.courseId}-${deadline.id}`} deadline={deadline} />
              ))}
            </div>
          </CardContent>
        </Card>
      </div>
    </>
  )
}

function CoursesOverviewSkeleton() {
  return (
    <div aria-label="Loading courses" aria-busy="true">
      <div className="mb-6 grid gap-3 lg:grid-cols-[minmax(260px,420px)_260px_180px]">
        <Skeleton className="h-10 w-full" />
        <Skeleton className="h-10 w-full" />
        <Skeleton className="h-10 w-full" />
      </div>

      <div className="grid gap-6 xl:grid-cols-[1fr_360px]">
        <div>
          <div className="mb-5 grid gap-4 md:grid-cols-3">
            {Array.from({ length: 3 }).map((_, index) => (
              <Card key={index}>
                <CardContent className="flex items-center gap-5 p-5">
                  <Skeleton className="h-14 w-14 shrink-0 rounded-full" />
                  <div className="min-w-0 flex-1 space-y-2">
                    <Skeleton className="h-4 w-32" />
                    <Skeleton className="h-8 w-14" />
                    <Skeleton className="h-4 w-40" />
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>

          <div className="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
            {Array.from({ length: 6 }).map((_, index) => (
              <Card key={index}>
                <CardContent className="p-4">
                  <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0 flex-1 space-y-2">
                      <Skeleton className="h-5 w-44 max-w-full" />
                      <Skeleton className="h-4 w-16" />
                    </div>
                    <Skeleton className="h-7 w-16 rounded-md" />
                  </div>

                  <div className="mt-3 border-b border-slate-100 pb-3">
                    <Skeleton className="h-4 w-40 max-w-full" />
                  </div>

                  <div className="grid grid-cols-2 gap-3 border-b border-slate-100 py-3">
                    <Skeleton className="h-5 w-20" />
                    <div className="space-y-2 border-l border-slate-100 pl-3">
                      <Skeleton className="h-4 w-20" />
                      <Skeleton className="h-4 w-24" />
                    </div>
                  </div>

                  <div className="mt-3 flex gap-3">
                    <Skeleton className="h-9 w-9 rounded-md" />
                    <div className="flex-1 space-y-2">
                      <Skeleton className="h-3 w-20" />
                      <Skeleton className="h-4 w-32" />
                      <Skeleton className="h-4 w-28" />
                    </div>
                  </div>

                  <Skeleton className="mt-4 h-10 w-full" />
                </CardContent>
              </Card>
            ))}
          </div>
        </div>

        <Card className="h-fit">
          <CardHeader className="flex-row items-center justify-between">
            <Skeleton className="h-5 w-48" />
            <Skeleton className="h-4 w-16" />
          </CardHeader>
          <CardContent className="space-y-4">
            {Array.from({ length: 4 }).map((_, index) => (
              <div key={index} className="grid gap-3 text-sm sm:grid-cols-[auto_1fr_auto] sm:items-center">
                <Skeleton className="h-10 w-10 rounded-md" />
                <div className="space-y-2">
                  <Skeleton className="h-4 w-32" />
                  <Skeleton className="h-4 w-44" />
                </div>
                <div className="space-y-2">
                  <Skeleton className="h-4 w-20" />
                  <Skeleton className="h-4 w-16" />
                </div>
              </div>
            ))}
          </CardContent>
        </Card>
      </div>
    </div>
  )
}

function SummaryCard({
  card,
}: {
  card: {
    label: string
    value: string
    helper: string
    tone: StudentCourseTone
    icon: LucideIcon
  }
}) {
  const Icon = card.icon

  return (
    <Card>
      <CardContent className="flex items-center gap-5 p-5">
        <div className={cn('flex h-14 w-14 shrink-0 items-center justify-center rounded-full', toneClasses[card.tone].soft)}>
          <Icon className="h-7 w-7" />
        </div>
        <div className="min-w-0">
          <p className="text-sm font-medium text-slate-700">{card.label}</p>
          <p className="mt-1 text-3xl font-semibold text-slate-950">{card.value}</p>
          <p className="mt-1 text-sm text-slate-500">{card.helper}</p>
        </div>
      </CardContent>
    </Card>
  )
}

function CourseCard({ course }: { course: StudentCourseOverviewItem }) {
  return (
    <Card>
      <CardContent className="p-4">
        <div className="flex items-start justify-between gap-3">
          <div className="min-w-0">
            <h2 className="truncate text-base font-semibold text-slate-950">{course.name}</h2>
            <p className="mt-1 text-sm font-semibold text-blue-600">{course.code}</p>
          </div>
          <Badge variant={statusVariant(course.enrollmentStatus)}>{course.enrollmentStatusLabel}</Badge>
        </div>

        <div className="mt-3 flex items-center gap-2 border-b border-slate-100 pb-3 text-sm text-slate-500">
          <UserRound className="h-4 w-4" />
          <span className="truncate">{course.professor}</span>
        </div>

        <div className="grid grid-cols-2 gap-3 border-b border-slate-100 py-3 text-sm">
          <div className="flex items-center gap-2 text-slate-700">
            <Clock3 className="h-4 w-4 text-slate-500" />
            <span className="font-medium">{course.ects} ECTS</span>
          </div>
          <div className="min-w-0 border-l border-slate-100 pl-3 text-slate-600">
            <p className="truncate font-medium text-slate-700">{course.schedule.days}</p>
            <p className="truncate">{course.schedule.time}</p>
          </div>
        </div>

        {course.nextImportantEvent ? (
          <div className="mt-3 flex gap-3">
            <div className={cn('flex h-9 w-9 shrink-0 items-center justify-center rounded-md', toneClasses[course.nextImportantEvent.tone].soft)}>
              <CalendarDays className="h-5 w-5" />
            </div>
            <div className="min-w-0 text-sm">
              <p className="text-xs text-slate-500">{course.nextImportantEvent.type}</p>
              <p className="truncate font-semibold text-slate-950">{course.nextImportantEvent.title}</p>
              <p className="truncate text-slate-500">
                {course.nextImportantEvent.date}
                {course.nextImportantEvent.time ? `, ${course.nextImportantEvent.time}` : ''}
              </p>
            </div>
          </div>
        ) : null}

        <Button asChild variant="secondary" className="mt-4 w-full border-blue-200 text-blue-600 hover:bg-blue-50">
          <Link to={`/student/courses/${course.courseId}`}>Open Course</Link>
        </Button>
      </CardContent>
    </Card>
  )
}

function DeadlineRow({
  deadline,
}: {
  deadline: StudentCourseEvent & {
    courseCode: string
    courseName: string
  }
}) {
  return (
    <div className="grid gap-3 py-4 text-sm sm:grid-cols-[auto_1fr_auto] sm:items-center">
      <div className={cn('flex h-10 w-10 items-center justify-center rounded-md', toneClasses[deadline.tone].soft)}>
        <CalendarDays className="h-5 w-5" />
      </div>
      <div className="min-w-0">
        <p className="truncate font-semibold text-slate-950">{deadline.title}</p>
        <p className="truncate text-slate-500">
          {deadline.courseName} ({deadline.courseCode})
        </p>
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

const toneClasses: Record<StudentCourseTone, { soft: string }> = {
  blue: { soft: 'bg-blue-50 text-blue-600' },
  green: { soft: 'bg-green-50 text-green-700' },
  orange: { soft: 'bg-orange-50 text-orange-600' },
  purple: { soft: 'bg-purple-50 text-purple-600' },
}

function statusVariant(status: StudentCourseStatus): 'default' | 'success' | 'warning' | 'secondary' {
  if (status === 'active') {
    return 'success'
  }

  if (status === 'upcoming') {
    return 'warning'
  }

  if (status === 'registered') {
    return 'default'
  }

  return 'secondary'
}
