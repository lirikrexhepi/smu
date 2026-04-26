import type { CSSProperties } from 'react'
import { ArrowRight, Info } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'

import { PageHeader } from '@/components/shared/PageHeader'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { getStudentGradesTranscript } from '@/lib/api/student'
import { getStoredAuthUser } from '@/lib/auth/session'
import { cn } from '@/lib/utils'
import type {
  StudentCourseGradeRow,
  StudentGradeDistributionItem,
  StudentGradeOverviewItem,
  StudentGradesTranscript,
  StudentGradesTranscriptSummary,
} from '@/types/student'

const chartAxisValues = [10, 9, 8, 7, 6, 5]
const minGradeValue = 5
const maxGradeValue = 10
const chartAnimationMs = 900

export function StudentGradesPage() {
  const storedUser = getStoredAuthUser()
  const studentKey = storedUser?.role === 'student' ? storedUser.institutionId : null
  const [gradesTranscript, setGradesTranscript] = useState<StudentGradesTranscript | null>(null)
  const [selectedSemester, setSelectedSemester] = useState('')
  const [selectedCourse, setSelectedCourse] = useState('all')
  const [errorMessage, setErrorMessage] = useState<string | null>(null)
  const [animationProgress, setAnimationProgress] = useState(0)

  useEffect(() => {
    if (!studentKey) {
      setGradesTranscript(null)
      setErrorMessage('Login as a student to load grades and transcript')
      return
    }

    let isMounted = true

    getStudentGradesTranscript(studentKey, {
      semester: selectedSemester,
      courseId: selectedCourse,
    })
      .then((response) => {
        if (!isMounted) {
          return
        }

        setGradesTranscript(response.data)
        setErrorMessage(null)

        if (selectedSemester === '' && response.data.selectedSemester) {
          setSelectedSemester(response.data.selectedSemester)
        }
      })
      .catch((error: unknown) => {
        if (!isMounted) {
          return
        }

        setErrorMessage(error instanceof Error ? error.message : 'Unable to load grades and transcript')
      })

    return () => {
      isMounted = false
    }
  }, [studentKey, selectedSemester, selectedCourse])

  useEffect(() => {
    if (!gradesTranscript) {
      return
    }

    let animationFrame = 0
    let animationStart = 0

    setAnimationProgress(0)

    function tick(timestamp: number) {
      if (animationStart === 0) {
        animationStart = timestamp
      }

      const elapsed = timestamp - animationStart
      const progress = Math.min(1, elapsed / chartAnimationMs)

      setAnimationProgress(easeOutCubic(progress))

      if (progress < 1) {
        animationFrame = window.requestAnimationFrame(tick)
      }
    }

    animationFrame = window.requestAnimationFrame(tick)

    return () => {
      window.cancelAnimationFrame(animationFrame)
    }
  }, [gradesTranscript])

  const summaryCards = useMemo(() => {
    if (!gradesTranscript) {
      return []
    }

    return buildSummaryCards(gradesTranscript.summary)
  }, [gradesTranscript])

  if (!gradesTranscript) {
    return (
      <>
        <PageHeader title="Grades & Transcript" description="Overview of your academic performance" />
        {errorMessage ? (
          <Card>
            <CardContent className="pt-5">
              <p className="text-sm text-slate-500">{errorMessage}</p>
            </CardContent>
          </Card>
        ) : (
          <StudentGradesSkeleton />
        )}
      </>
    )
  }

  return (
    <>
      <PageHeader title="Grades & Transcript" description="Overview of your academic performance" />

      <div className="mb-4">
        <h1 className="text-2xl font-semibold text-slate-950">Grades & Transcript</h1>
        <p className="mt-1 text-sm text-slate-500">Overview of your academic performance</p>
      </div>

      {errorMessage ? (
        <div className="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {errorMessage}
        </div>
      ) : null}

      <div className="mb-4 grid gap-3 md:grid-cols-[minmax(240px,320px)_minmax(220px,280px)]">
        <select
          aria-label="Filter grades by semester"
          className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
          value={selectedSemester || gradesTranscript.selectedSemester}
          onChange={(event) => setSelectedSemester(event.target.value)}
        >
          {gradesTranscript.filters.semesters.map((semester) => (
            <option key={semester.id} value={semester.id}>
              {semester.label}
            </option>
          ))}
        </select>

        <select
          aria-label="Filter grades by course"
          className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
          value={selectedCourse}
          onChange={(event) => setSelectedCourse(event.target.value)}
        >
          <option value="all">All Courses</option>
          {gradesTranscript.filters.courses.map((course) => (
            <option key={course.courseId} value={course.courseId}>
              {course.label}
            </option>
          ))}
        </select>
      </div>

      <div className="mb-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        {summaryCards.map((card) => (
          <SummaryCard key={card.label} card={card} />
        ))}
      </div>

      <div className="mb-4 grid gap-5 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,0.9fr)]">
        <GradesOverviewChart
          items={gradesTranscript.gradeOverview}
          transcriptActionLabel={gradesTranscript.transcriptAction.label}
          animationProgress={animationProgress}
        />
        <GradeDistributionChart items={gradesTranscript.gradeDistribution} animationProgress={animationProgress} />
      </div>

      <CourseGradesTable
        rows={gradesTranscript.courseGrades}
        transcriptActionLabel={gradesTranscript.transcriptAction.label}
      />
    </>
  )
}

function SummaryCard({
  card,
}: {
  card: {
    label: string
    value: string
    helper: string
    helperTone: 'green' | 'slate'
  }
}) {
  return (
    <Card>
      <CardContent className="p-5">
        <div className="flex items-center gap-2">
          <p className="text-sm font-medium text-slate-700">{card.label}</p>
          <Info className="h-3.5 w-3.5 text-slate-400" />
        </div>
        <p className="mt-2 text-3xl font-semibold text-slate-950">{card.value}</p>
        <p className={cn('mt-1 text-sm', card.helperTone === 'green' ? 'text-green-600' : 'text-slate-500')}>
          {card.helper}
        </p>
      </CardContent>
    </Card>
  )
}

function GradesOverviewChart({
  items,
  transcriptActionLabel,
  animationProgress,
}: {
  items: StudentGradeOverviewItem[]
  transcriptActionLabel: string
  animationProgress: number
}) {
  return (
    <Card>
      <CardHeader className="flex-row items-center justify-between p-4 pb-2">
        <CardTitle>Grades Overview</CardTitle>
        <button
          type="button"
          className="inline-flex items-center gap-1 text-sm font-semibold text-blue-600 hover:text-blue-700"
        >
          {transcriptActionLabel}
          <ArrowRight className="h-4 w-4" />
        </button>
      </CardHeader>
      <CardContent className="p-4 pt-0">
        {items.length > 0 ? (
          <div className="overflow-x-auto">
            <div className="grid min-w-[620px] grid-cols-[38px_1fr] gap-2">
              <div className="flex h-56 flex-col justify-between pb-7 pt-6 text-xs font-medium text-slate-600">
                {chartAxisValues.map((value) => (
                  <span key={value}>{value}</span>
                ))}
              </div>

              <div className="relative h-56">
                <div className="absolute inset-x-0 bottom-7 top-6">
                  {chartAxisValues.map((value) => (
                    <div
                      key={value}
                      className={cn(
                        'absolute left-0 right-0 border-t border-dashed border-slate-200',
                        value === 0 && 'border-solid border-slate-300',
                      )}
                      style={axisLineStyle(value)}
                    />
                  ))}
                </div>

                <div className="absolute inset-x-0 bottom-7 top-6 flex items-end justify-around gap-6">
                  {items.map((item) => (
                    <div key={item.courseId} className="flex h-full min-w-14 flex-col items-center justify-end">
                      <span className="mb-1 text-sm font-semibold text-slate-800">
                        {formatGrade(animatedGradeValue(item.numericGrade, animationProgress))}
                      </span>
                      <div className="flex h-[calc(100%-24px)] items-end">
                        <div
                          className="w-11 rounded-t-md bg-blue-500 shadow-sm shadow-blue-200 transition-[height] duration-300 ease-out"
                          style={barStyle(item.numericGrade, animationProgress)}
                        />
                      </div>
                    </div>
                  ))}
                </div>

                <div className="absolute inset-x-0 bottom-0 flex justify-around gap-6 text-xs font-medium text-slate-600">
                  {items.map((item) => (
                    <span key={item.courseId} className="min-w-14 text-center">
                      {item.courseCode}
                    </span>
                  ))}
                </div>
              </div>
            </div>
          </div>
        ) : (
          <div className="flex h-56 items-center justify-center rounded-md border border-dashed border-slate-200 text-sm text-slate-500">
            No grades match the selected filters.
          </div>
        )}
      </CardContent>
    </Card>
  )
}

function GradeDistributionChart({
  items,
  animationProgress,
}: {
  items: StudentGradeDistributionItem[]
  animationProgress: number
}) {
  const totalCourses = items.reduce((total, item) => total + item.count, 0)
  const visibleCourses = Math.round(totalCourses * animationProgress)
  const [activeItem, setActiveItem] = useState<StudentGradeDistributionItem | null>(null)

  return (
    <Card>
      <CardHeader className="p-4 pb-2">
        <CardTitle>Grade Distribution</CardTitle>
      </CardHeader>
      <CardContent className="grid gap-6 p-4 pt-0 sm:grid-cols-[220px_1fr] sm:items-center xl:grid-cols-[210px_1fr]">
        <div className="relative mx-auto h-52 w-52 shrink-0">
          <GradeDistributionDonut
            items={items}
            animationProgress={animationProgress}
            onActiveItemChange={setActiveItem}
          />
          <div className="pointer-events-none absolute inset-9 flex flex-col items-center justify-center rounded-full bg-white text-center shadow-inner">
            <p className="text-3xl font-semibold text-slate-950">{visibleCourses}</p>
            <p className="mt-1 text-sm text-slate-500">Total Courses</p>
          </div>
          {activeItem ? (
            <div className="pointer-events-none absolute left-1/2 top-2 z-10 w-max -translate-x-1/2 rounded-md border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-lg">
              Grade {activeItem.grade}: {activeItem.count} ({activeItem.percentage}%)
            </div>
          ) : null}
        </div>

        <div className="space-y-3">
          {items.map((item) => (
            <div
              key={item.grade}
              className="grid grid-cols-[auto_1fr] gap-x-3 text-sm"
            >
              <span
                className="mt-1.5 h-3 w-3 rounded-full"
                style={{ backgroundColor: distributionColor(item.grade) }}
              />
              <div>
                <p className="font-medium text-slate-700">
                  {item.grade} ({item.label})
                </p>
              </div>
            </div>
          ))}
        </div>
      </CardContent>
    </Card>
  )
}

function GradeDistributionDonut({
  items,
  animationProgress,
  onActiveItemChange,
}: {
  items: StudentGradeDistributionItem[]
  animationProgress: number
  onActiveItemChange: (item: StudentGradeDistributionItem | null) => void
}) {
  const totalCourses = items.reduce((total, item) => total + item.count, 0)
  const radius = 78
  const circumference = 2 * Math.PI * radius
  let accumulated = 0

  if (totalCourses === 0) {
    return <div className="h-full w-full rounded-full bg-slate-200" />
  }

  return (
    <svg
      aria-label="Grade distribution"
      className="h-full w-full transition-transform duration-300 ease-out"
      style={{ transform: `rotate(${Math.round((1 - animationProgress) * -90)}deg) scale(${0.94 + animationProgress * 0.06})` }}
      viewBox="0 0 200 200"
    >
      <circle cx="100" cy="100" r={radius} fill="none" stroke="#e2e8f0" strokeWidth="34" />
      {items.map((item) => {
        const segmentLength = (item.count / totalCourses) * circumference
        const offset = -accumulated * animationProgress
        accumulated += segmentLength

        return (
          <circle
            key={item.grade}
            tabIndex={0}
            cx="100"
            cy="100"
            r={radius}
            fill="none"
            stroke={distributionColor(item.grade)}
            strokeDasharray={`${segmentLength * animationProgress} ${circumference}`}
            strokeDashoffset={offset}
            strokeLinecap="butt"
            strokeWidth="34"
            transform="rotate(-90 100 100)"
            className="cursor-pointer outline-none transition-opacity hover:opacity-85 focus:opacity-85"
            onBlur={() => onActiveItemChange(null)}
            onFocus={() => onActiveItemChange(item)}
            onMouseEnter={() => onActiveItemChange(item)}
            onMouseLeave={() => onActiveItemChange(null)}
          />
        )
      })}
    </svg>
  )
}

function CourseGradesTable({
  rows,
  transcriptActionLabel,
}: {
  rows: StudentCourseGradeRow[]
  transcriptActionLabel: string
}) {
  return (
    <Card>
      <CardHeader className="border-b border-slate-100 p-4">
        <CardTitle>Course Grades</CardTitle>
      </CardHeader>
      <CardContent className="p-0">
        <div className="overflow-x-auto px-3 pt-3">
          <table className="w-full min-w-[900px] overflow-hidden rounded-md border border-slate-200 text-left text-sm">
            <thead className="bg-slate-50 text-xs font-semibold text-slate-600">
              <tr>
                <th className="px-4 py-3">Course</th>
                <th className="px-4 py-3">Course Name</th>
                <th className="px-4 py-3">Credits</th>
                <th className="px-4 py-3">Grade</th>
                <th className="px-4 py-3">Grade Points</th>
                <th className="px-4 py-3">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {rows.map((row) => (
                <tr key={row.courseId}>
                  <td className="px-4 py-3 font-medium text-slate-700">{row.courseCode}</td>
                  <td className="px-4 py-3 text-slate-600">{row.courseName}</td>
                  <td className="px-4 py-3 text-slate-600">{row.credits}</td>
                  <td className="px-4 py-3 text-slate-700">{row.displayGrade}</td>
                  <td className="px-4 py-3 text-slate-700">{formatPoints(row.gradePoints)}</td>
                  <td className="px-4 py-3">
                    <Badge variant={statusVariant(row.status)}>{row.statusLabel}</Badge>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {rows.length === 0 ? (
          <div className="px-4 py-6 text-sm text-slate-500">No course grades match the selected filters.</div>
        ) : (
          <div className="px-3 py-3">
            <Button type="button" variant="ghost" className="px-2 font-semibold text-blue-600 hover:bg-blue-50 hover:text-blue-700">
              {transcriptActionLabel}
              <ArrowRight className="h-4 w-4" />
            </Button>
          </div>
        )}
      </CardContent>
    </Card>
  )
}

function StudentGradesSkeleton() {
  return (
    <div aria-label="Loading grades and transcript" aria-busy="true">
      <div className="mb-4 space-y-2">
        <Skeleton className="h-8 w-64" />
        <Skeleton className="h-4 w-72" />
      </div>
      <div className="mb-4 grid gap-3 md:grid-cols-[minmax(240px,320px)_minmax(220px,280px)]">
        <Skeleton className="h-10 w-full" />
        <Skeleton className="h-10 w-full" />
      </div>
      <div className="mb-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        {Array.from({ length: 4 }).map((_, index) => (
          <Card key={index}>
            <CardContent className="p-5">
              <Skeleton className="h-4 w-36" />
              <Skeleton className="mt-3 h-8 w-16" />
              <Skeleton className="mt-2 h-4 w-28" />
            </CardContent>
          </Card>
        ))}
      </div>
      <div className="mb-4 grid gap-5 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,0.9fr)]">
        <Card>
          <CardHeader className="p-4">
            <Skeleton className="h-5 w-36" />
          </CardHeader>
          <CardContent className="p-4 pt-0">
            <Skeleton className="h-56 w-full" />
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="p-4">
            <Skeleton className="h-5 w-40" />
          </CardHeader>
          <CardContent className="p-4 pt-0">
            <Skeleton className="h-52 w-full" />
          </CardContent>
        </Card>
      </div>
      <Card>
        <CardHeader className="border-b border-slate-100 p-4">
          <Skeleton className="h-5 w-32" />
        </CardHeader>
        <CardContent className="p-4">
          <div className="space-y-3">
            {Array.from({ length: 6 }).map((_, index) => (
              <Skeleton key={index} className="h-8 w-full" />
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  )
}

function buildSummaryCards(summary: StudentGradesTranscriptSummary) {
  return [
    {
      label: 'Average Grade',
      value: formatAverageGrade(summary.averageGrade),
      helper: summary.gradeStatus,
      helperTone: 'green' as const,
    },
    {
      label: 'Total Credits Earned',
      value: String(summary.totalCreditsEarned),
      helper: `of ${summary.requiredCredits} required`,
      helperTone: 'slate' as const,
    },
    {
      label: 'Courses Completed',
      value: String(summary.coursesCompleted),
      helper: `${summary.completionPercentage}% of total`,
      helperTone: 'slate' as const,
    },
    {
      label: 'Academic Standing',
      value: summary.academicStanding,
      helper: summary.eligibilityStatus,
      helperTone: 'green' as const,
    },
  ]
}

function axisLineStyle(value: number): CSSProperties {
  const top = ((maxGradeValue - value) / (maxGradeValue - minGradeValue)) * 100

  return {
    top: `${top}%`,
  }
}

function barStyle(value: number, animationProgress: number): CSSProperties {
  const animatedValue = animatedGradeValue(value, animationProgress)
  const height = Math.max(4, Math.min(100, ((animatedValue - minGradeValue) / (maxGradeValue - minGradeValue)) * 100))

  return {
    height: `${height}%`,
  }
}

function distributionColor(grade: number): string {
  if (grade === 10) {
    return '#22c55e'
  }

  if (grade === 9) {
    return '#3b82f6'
  }

  if (grade === 8) {
    return '#14b8a6'
  }

  if (grade === 7) {
    return '#f59e0b'
  }

  if (grade === 6) {
    return '#f97316'
  }

  if (grade === 5) {
    return '#ef4444'
  }

  return '#94a3b8'
}

function statusVariant(status: string): 'default' | 'success' | 'warning' | 'danger' | 'secondary' {
  if (status === 'passed') {
    return 'success'
  }

  if (status === 'failed') {
    return 'danger'
  }

  if (status === 'in-progress') {
    return 'warning'
  }

  return 'secondary'
}

function animatedGradeValue(value: number, animationProgress: number): number {
  return minGradeValue + (value - minGradeValue) * animationProgress
}

function formatAverageGrade(value: number): string {
  return value.toFixed(1)
}

function formatGrade(value: number): string {
  return Number.isInteger(value) ? String(value) : value.toFixed(1)
}

function formatPoints(value: number): string {
  return formatGrade(value)
}

function easeOutCubic(value: number): number {
  return 1 - Math.pow(1 - value, 3)
}
