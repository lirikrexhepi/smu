import { BookOpen, CalendarDays, Clock3, MapPin, Search } from 'lucide-react'
import { useMemo, useState } from 'react'

import { PageHeader } from '@/components/shared/PageHeader'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { professorCourses, type ProfessorCourse, type Tone } from '@/features/professor/data/mockProfessor'
import { cn } from '@/lib/utils'

export function ProfessorCoursesPage() {
  const [query, setQuery] = useState('')

  const visibleCourses = useMemo(() => {
    const normalizedQuery = query.trim().toLowerCase()

    if (normalizedQuery === '') {
      return professorCourses
    }

    return professorCourses.filter((course) =>
      [course.code, course.name, course.room, course.semester].some((value) =>
        value.toLowerCase().includes(normalizedQuery),
      ),
    )
  }, [query])

  return (
    <>
      <PageHeader title="My Courses" description="Assigned courses" />

      <div className="mb-5">
        <h1 className="text-2xl font-semibold text-slate-950">My Courses</h1>
        <p className="mt-1 text-sm text-slate-500">Assigned courses, sections and teaching progress.</p>
      </div>

      <div className="mb-5 grid gap-3 lg:grid-cols-[minmax(260px,420px)_1fr]">
        <div className="relative">
          <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <Input
            aria-label="Search professor courses"
            className="pl-9"
            placeholder="Search courses..."
            value={query}
            onChange={(event) => setQuery(event.target.value)}
          />
        </div>
        <div className="flex flex-wrap items-center gap-2 text-sm text-slate-500">
          <Badge variant="secondary">{professorCourses.length} assigned</Badge>
          <Badge variant="success">{professorCourses.filter((course) => course.status === 'Active').length} active</Badge>
        </div>
      </div>

      <div className="grid gap-4 xl:grid-cols-3">
        {visibleCourses.map((course) => (
          <CourseCard key={course.id} course={course} />
        ))}
      </div>

      {visibleCourses.length === 0 ? (
        <Card>
          <CardContent className="pt-5">
            <p className="text-sm text-slate-500">No professor courses match the current search.</p>
          </CardContent>
        </Card>
      ) : null}
    </>
  )
}

function CourseCard({ course }: { course: ProfessorCourse }) {
  return (
    <Card>
      <CardContent className="p-5">
        <div className="flex items-start justify-between gap-3">
          <div className="min-w-0">
            <p className="text-sm font-semibold text-blue-600">{course.code}</p>
            <h2 className="mt-1 text-lg font-semibold text-slate-950">{course.name}</h2>
          </div>
          <Badge variant={course.status === 'Active' ? 'success' : 'warning'}>{course.status}</Badge>
        </div>

        <div className="mt-4 space-y-3 border-b border-slate-100 pb-4 text-sm text-slate-600">
          <InfoRow icon={CalendarDays} label={course.semester} />
          <InfoRow icon={Clock3} label={course.schedule} />
          <InfoRow icon={MapPin} label={course.room} />
        </div>

        <div className="grid grid-cols-3 gap-3 border-b border-slate-100 py-4 text-sm">
          <CourseStat label="Students" value={course.students} tone="blue" />
          <CourseStat label="Grade" value={course.averageGrade.toFixed(1)} tone="green" />
          <CourseStat label="Attend." value={`${course.attendanceRate}%`} tone="orange" />
        </div>

        <div className="mt-4 rounded-lg border border-slate-200 p-3">
          <div className="flex items-center justify-between gap-3 text-sm">
            <span className="font-medium text-slate-700">Pending grading</span>
            <span className="font-semibold text-slate-950">{course.pendingGrades}</span>
          </div>
          <div className="mt-2 h-2 rounded-full bg-slate-100">
            <div
              className={cn('h-2 rounded-full', progressColor(course.tone))}
              style={{ width: `${Math.min(100, Math.max(8, 100 - course.pendingGrades * 5))}%` }}
            />
          </div>
        </div>

        <Button type="button" variant="secondary" className="mt-4 w-full border-blue-200 text-blue-600 hover:bg-blue-50">
          <BookOpen className="h-4 w-4" />
          Course Details
        </Button>
      </CardContent>
    </Card>
  )
}

function InfoRow({ icon: Icon, label }: { icon: typeof CalendarDays; label: string }) {
  return (
    <div className="flex items-center gap-2">
      <Icon className="h-4 w-4 text-slate-400" />
      <span className="min-w-0 truncate">{label}</span>
    </div>
  )
}

function CourseStat({ label, value, tone }: { label: string; value: string | number; tone: Tone }) {
  return (
    <div className={cn('rounded-md px-3 py-2', toneClasses[tone])}>
      <p className="text-xs font-medium opacity-80">{label}</p>
      <p className="mt-1 text-lg font-semibold">{value}</p>
    </div>
  )
}

function progressColor(tone: Tone) {
  if (tone === 'green') {
    return 'bg-green-500'
  }

  if (tone === 'purple') {
    return 'bg-purple-500'
  }

  return 'bg-blue-500'
}

const toneClasses: Record<Tone, string> = {
  blue: 'bg-blue-50 text-blue-700',
  green: 'bg-green-50 text-green-700',
  orange: 'bg-orange-50 text-orange-700',
  purple: 'bg-purple-50 text-purple-700',
  red: 'bg-red-50 text-red-700',
  teal: 'bg-teal-50 text-teal-700',
}
