import type { LucideIcon } from 'lucide-react'
import {
  ArrowLeft,
  BarChart3,
  BookOpen,
  Building2,
  CalendarDays,
  Clock3,
  Download,
  FileText,
  GraduationCap,
  Info,
  Megaphone,
  UserRound,
} from 'lucide-react'
import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'

import { PageHeader } from '@/components/shared/PageHeader'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { apiAssetUrl } from '@/lib/api/client'
import { getStudentCourseDetail } from '@/lib/api/student'
import { getStoredAuthUser } from '@/lib/auth/session'
import { cn } from '@/lib/utils'
import type {
  StudentCourseAnnouncement,
  StudentCourseAssessment,
  StudentCourseDetail,
  StudentCourseEvent,
  StudentCourseExam,
  StudentCourseTone,
} from '@/types/student'

const tabs = [
  { id: 'overview', label: 'Overview' },
  { id: 'materials', label: 'Materials' },
  { id: 'attendance', label: 'Attendance' },
  { id: 'grades', label: 'Grades' },
  { id: 'exams', label: 'Exams' },
  { id: 'announcements', label: 'Announcements' },
] as const

type CourseTab = (typeof tabs)[number]['id']

export function StudentCourseDetailPage() {
  const { courseId } = useParams()
  const storedUser = getStoredAuthUser()
  const studentKey = storedUser?.role === 'student' ? storedUser.institutionId : null
  const [course, setCourse] = useState<StudentCourseDetail | null>(null)
  const [activeTab, setActiveTab] = useState<CourseTab>('overview')
  const [errorMessage, setErrorMessage] = useState<string | null>(null)

  useEffect(() => {
    if (!studentKey || !courseId) {
      setCourse(null)
      setErrorMessage('Login as a student and select a course')
      return
    }

    let isMounted = true

    getStudentCourseDetail(studentKey, courseId)
      .then((response) => {
        if (!isMounted) {
          return
        }

        setCourse(response.data)
        setErrorMessage(null)
      })
      .catch((error: unknown) => {
        if (!isMounted) {
          return
        }

        setErrorMessage(error instanceof Error ? error.message : 'Unable to load course')
      })

    return () => {
      isMounted = false
    }
  }, [courseId, studentKey])

  if (!course) {
    return (
      <>
        <PageHeader title="Course Detail" description={courseId ?? 'Course'} />
        <Card>
          <CardContent className="pt-5">
            <p className="text-sm text-slate-500">{errorMessage ?? 'Loading course'}</p>
          </CardContent>
        </Card>
      </>
    )
  }

  return (
    <>
      <PageHeader title="Course Detail" description={`${course.name} (${course.code})`} />

      {errorMessage ? (
        <div className="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {errorMessage}
        </div>
      ) : null}

      <Link
        to="/student/courses"
        className="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-blue-600 hover:text-blue-700"
      >
        <span className="flex h-7 w-7 items-center justify-center rounded-full bg-blue-50">
          <ArrowLeft className="h-4 w-4" />
        </span>
        Back to Courses
      </Link>

      <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div className="min-w-0">
          <h1 className="text-2xl font-semibold text-slate-950">
            {course.name} ({course.code})
          </h1>
          <p className="mt-1 text-sm text-slate-500">Course overview and learning resources</p>
        </div>
        <Badge variant={statusVariant(course.enrollment.status)} className="w-fit gap-2 px-3 py-2 text-sm">
          <span className="h-2 w-2 rounded-full bg-current" />
          {course.enrollment.statusLabel}
        </Badge>
      </div>

      <Card className="mb-6">
        <CardContent className="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-5">
          <InfoStripItem icon={UserRound} label="Professor" value={course.professor.name} />
          <InfoStripItem icon={GraduationCap} label="ECTS" value={`${course.ects} ECTS`} tone="green" />
          <InfoStripItem icon={CalendarDays} label="Schedule" value={course.schedule.label} />
          <InfoStripItem icon={Building2} label="Room" value={course.room} tone="purple" />
          <InfoStripItem icon={Clock3} label="Semester" value={course.semester} tone="orange" />
        </CardContent>
      </Card>

      <div className="mb-5 overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <div className="flex min-w-max">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              type="button"
              className={cn(
                'h-14 border-b-2 px-6 text-sm font-medium transition-colors',
                activeTab === tab.id
                  ? 'border-blue-600 text-blue-600'
                  : 'border-transparent text-slate-600 hover:text-slate-950',
              )}
              onClick={() => setActiveTab(tab.id)}
            >
              {tab.label}
            </button>
          ))}
        </div>
      </div>

      {activeTab === 'overview' ? <OverviewTab course={course} onOpenMaterials={() => setActiveTab('materials')} /> : null}
      {activeTab === 'materials' ? <MaterialsTab course={course} /> : null}
      {activeTab === 'attendance' ? <AttendanceTab course={course} /> : null}
      {activeTab === 'grades' ? <GradesTab course={course} /> : null}
      {activeTab === 'exams' ? <ExamsTab course={course} /> : null}
      {activeTab === 'announcements' ? <AnnouncementsTab announcements={course.announcements} /> : null}
    </>
  )
}

function InfoStripItem({
  icon: Icon,
  label,
  value,
  tone = 'blue',
}: {
  icon: LucideIcon
  label: string
  value: string
  tone?: StudentCourseTone
}) {
  return (
    <div className="flex min-w-0 items-center gap-4 border-slate-100 md:border-r md:pr-4 last:border-r-0">
      <div className={cn('flex h-12 w-12 shrink-0 items-center justify-center rounded-full', toneClasses[tone].soft)}>
        <Icon className="h-6 w-6" />
      </div>
      <div className="min-w-0">
        <p className="text-sm text-slate-500">{label}</p>
        <p className="mt-1 truncate text-sm font-semibold text-slate-950">{value}</p>
      </div>
    </div>
  )
}

function OverviewTab({ course, onOpenMaterials }: { course: StudentCourseDetail; onOpenMaterials: () => void }) {
  return (
    <div className="grid gap-5 xl:grid-cols-[1.05fr_0.95fr_1.05fr]">
      <div className="space-y-5">
        <Card>
          <CardHeader>
            <CardTitle>About this course</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-sm leading-7 text-slate-600">{course.description}</p>
            <div className="mt-4 flex flex-wrap gap-2">
              {course.overview.topics.map((topic) => (
                <Badge key={topic} variant="secondary">
                  {topic}
                </Badge>
              ))}
            </div>
            <Button type="button" className="mt-5" onClick={onOpenMaterials}>
              <BookOpen className="h-4 w-4" />
              Open Materials
            </Button>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex-row items-center gap-3">
            <span className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-50 text-blue-600">
              <Info className="h-4 w-4" />
            </span>
            <CardTitle>Course Info</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="divide-y divide-slate-100">
              {course.courseInfo.map((item) => (
                <div key={item.id} className="grid gap-2 py-3 text-sm sm:grid-cols-[150px_1fr]">
                  <p className="font-medium text-slate-700">{item.label}</p>
                  <p className="text-slate-500">{item.value}</p>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>

      <Card className="h-fit">
        <CardHeader className="flex-row items-center justify-between">
          <div className="flex items-center gap-3">
            <span className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-50 text-blue-600">
              <CalendarDays className="h-4 w-4" />
            </span>
            <CardTitle>Upcoming Assessments</CardTitle>
          </div>
        </CardHeader>
        <CardContent>
          <div className="divide-y divide-slate-100">
            {course.assessments.slice(0, 3).map((assessment) => (
              <AssessmentRow key={assessment.id} assessment={assessment} />
            ))}
          </div>
        </CardContent>
      </Card>

      <Card className="h-fit">
        <CardHeader className="flex-row items-center justify-between">
          <div className="flex items-center gap-3">
            <span className="flex h-8 w-8 items-center justify-center rounded-full bg-purple-50 text-purple-600">
              <Megaphone className="h-4 w-4" />
            </span>
            <CardTitle>Recent Announcements</CardTitle>
          </div>
        </CardHeader>
        <CardContent>
          <div className="space-y-5">
            {course.announcements.slice(0, 3).map((announcement) => (
              <AnnouncementItem key={announcement.id} announcement={announcement} compact />
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  )
}

function MaterialsTab({ course }: { course: StudentCourseDetail }) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Course Materials</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="divide-y divide-slate-100">
          {course.materials.map((material) => {
            const downloadUrl = material.downloadUrl ?? '/uploads/materials/test-document.txt'

            return (
              <div key={material.id} className="grid gap-3 py-4 text-sm md:grid-cols-[auto_1fr_auto] md:items-center">
                <div className="flex h-10 w-10 items-center justify-center rounded-md bg-blue-50 text-blue-600">
                  <FileText className="h-5 w-5" />
                </div>
                <div className="min-w-0">
                  <p className="font-semibold text-slate-950">{material.title}</p>
                  <p className="text-slate-500">
                    {material.type} - Updated {material.updatedAt} - {material.size}
                  </p>
                </div>
                <Button
                  asChild
                  variant="secondary"
                  size="sm"
                  className="border-blue-200 bg-white text-blue-600 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700"
                >
                  <a href={apiAssetUrl(downloadUrl) ?? downloadUrl} download>
                    <Download className="h-4 w-4" />
                    Download
                  </a>
                </Button>
              </div>
            )
          })}
        </div>
      </CardContent>
    </Card>
  )
}

function AttendanceTab({ course }: { course: StudentCourseDetail }) {
  return (
    <div className="grid gap-5 xl:grid-cols-[0.8fr_1.2fr]">
      <Card>
        <CardHeader>
          <CardTitle>Attendance Summary</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="rounded-lg border border-slate-200 p-4">
            <div className="flex items-end justify-between">
              <div>
                <p className="text-sm text-slate-500">Current attendance</p>
                <p className="mt-1 text-4xl font-semibold text-slate-950">{course.attendance.percentage}%</p>
              </div>
              <Badge variant={course.attendance.percentage >= course.attendance.requiredPercentage ? 'success' : 'danger'}>
                {course.attendance.status}
              </Badge>
            </div>
            <div className="mt-4 h-2 rounded-full bg-slate-100">
              <div
                className={cn(
                  'h-2 rounded-full',
                  course.attendance.percentage >= course.attendance.requiredPercentage ? 'bg-green-600' : 'bg-red-500',
                )}
                style={{ width: `${course.attendance.percentage}%` }}
              />
            </div>
            <p className="mt-2 text-xs text-slate-500">Required minimum: {course.attendance.requiredPercentage}%</p>
          </div>

          <div className="mt-4 space-y-3">
            {course.attendance.summary.map((item) => (
              <div key={item.label} className="flex justify-between text-sm">
                <span className="text-slate-500">{item.label}</span>
                <span className="font-semibold text-slate-950">{item.value}</span>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Recent Attendance</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="divide-y divide-slate-100">
            {course.attendance.records.length > 0 ? (
              course.attendance.records.map((record) => (
                <div key={record.id} className="grid gap-3 py-3 text-sm sm:grid-cols-[1fr_1fr_auto] sm:items-center">
                  <p className="font-medium text-slate-950">{record.date}</p>
                  <p className="text-slate-500">{record.type}</p>
                  <Badge variant={record.status === 'Present' ? 'success' : 'danger'}>{record.status}</Badge>
                </div>
              ))
            ) : (
              <p className="py-4 text-sm text-slate-500">No attendance records yet.</p>
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  )
}

function GradesTab({ course }: { course: StudentCourseDetail }) {
  return (
    <div className="grid gap-5 xl:grid-cols-[0.8fr_1.2fr]">
      <Card>
        <CardHeader>
          <CardTitle>Grade Summary</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="rounded-lg border border-blue-100 bg-blue-50 p-4">
            <p className="text-sm font-medium text-blue-700">Current Grade</p>
            <p className="mt-2 text-4xl font-semibold text-blue-700">{course.grades.currentGrade || 'Pending'}</p>
            <p className="mt-1 text-sm text-slate-600">{course.grades.scale}</p>
          </div>
          <div className="mt-5 space-y-4">
            {course.grades.breakdown.map((item) => (
              <div key={item.component}>
                <div className="mb-2 flex justify-between text-sm">
                  <span className="font-medium text-slate-700">{item.component}</span>
                  <span className="text-slate-500">{item.weight}%</span>
                </div>
                <div className="h-2 rounded-full bg-slate-100">
                  <div className="h-2 rounded-full bg-blue-600" style={{ width: `${item.weight}%` }} />
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Grade Records</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <table className="w-full min-w-[620px] text-left text-sm">
              <thead>
                <tr className="border-b border-slate-200 text-xs font-medium text-slate-500">
                  <th className="py-3 pr-4">Assessment</th>
                  <th className="py-3 pr-4">Type</th>
                  <th className="py-3 pr-4">Score</th>
                  <th className="py-3 pr-4">Weight</th>
                  <th className="py-3">Date</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {course.grades.records.map((record) => (
                  <tr key={record.id}>
                    <td className="py-3 pr-4 font-medium text-slate-950">{record.title}</td>
                    <td className="py-3 pr-4 text-slate-500">{record.type}</td>
                    <td className="py-3 pr-4 font-semibold text-blue-700">{record.score}</td>
                    <td className="py-3 pr-4 text-slate-500">{record.weight}</td>
                    <td className="py-3 text-slate-500">{record.date}</td>
                  </tr>
                ))}
              </tbody>
            </table>
            {course.grades.records.length === 0 ? <p className="py-4 text-sm text-slate-500">No grades published yet.</p> : null}
          </div>
        </CardContent>
      </Card>
    </div>
  )
}

function ExamsTab({ course }: { course: StudentCourseDetail }) {
  return (
    <div className="grid gap-5 xl:grid-cols-2">
      <Card>
        <CardHeader className="flex-row items-center gap-3">
          <span className="flex h-8 w-8 items-center justify-center rounded-full bg-orange-50 text-orange-600">
            <CalendarDays className="h-4 w-4" />
          </span>
          <CardTitle>Exams</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="divide-y divide-slate-100">
            {course.exams.map((exam) => (
              <ExamRow key={exam.id} exam={exam} />
            ))}
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex-row items-center gap-3">
          <span className="flex h-8 w-8 items-center justify-center rounded-full bg-purple-50 text-purple-600">
            <BarChart3 className="h-4 w-4" />
          </span>
          <CardTitle>Assessments</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="divide-y divide-slate-100">
            {course.assessments.map((assessment) => (
              <AssessmentRow key={assessment.id} assessment={assessment} />
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  )
}

function AnnouncementsTab({ announcements }: { announcements: StudentCourseAnnouncement[] }) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Announcements</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="space-y-5">
          {announcements.map((announcement) => (
            <AnnouncementItem key={announcement.id} announcement={announcement} />
          ))}
        </div>
      </CardContent>
    </Card>
  )
}

function AssessmentRow({ assessment }: { assessment: StudentCourseAssessment }) {
  return (
    <div className="grid gap-3 py-4 text-sm sm:grid-cols-[auto_1fr_auto] sm:items-center">
      <DateTile event={assessment} />
      <div className="min-w-0">
        <p className="font-semibold text-slate-950">{assessment.title}</p>
        <p className="text-slate-500">
          {assessment.type} - {assessment.mode}
        </p>
      </div>
      <p className={cn('font-medium', assessment.statusLabel === 'Today' ? 'text-green-700' : 'text-slate-500')}>
        {assessment.statusLabel}
      </p>
    </div>
  )
}

function ExamRow({ exam }: { exam: StudentCourseExam }) {
  return (
    <div className="grid gap-3 py-4 text-sm sm:grid-cols-[auto_1fr_auto] sm:items-center">
      <DateTile event={exam} />
      <div className="min-w-0">
        <p className="font-semibold text-slate-950">{exam.title}</p>
        <p className="text-slate-500">
          {exam.duration} - {exam.room}
        </p>
      </div>
      <p className="font-medium text-slate-500">{exam.statusLabel}</p>
    </div>
  )
}

function DateTile({ event }: { event: StudentCourseEvent }) {
  const [day, month] = event.date.split(' ')

  return (
    <div className="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-md border border-blue-100 bg-white text-center shadow-sm">
      <span className="text-xs font-semibold uppercase text-blue-600">{month}</span>
      <span className="text-lg font-semibold text-slate-950">{day}</span>
    </div>
  )
}

function AnnouncementItem({ announcement, compact = false }: { announcement: StudentCourseAnnouncement; compact?: boolean }) {
  return (
    <div className={cn('grid gap-3 text-sm', compact ? 'grid-cols-[auto_1fr_auto]' : 'grid-cols-[auto_1fr_auto] border-b border-slate-100 pb-5 last:border-b-0 last:pb-0')}>
      <span className="mt-2 h-2 w-2 rounded-full bg-blue-600" />
      <div>
        <p className="font-semibold text-slate-950">{announcement.title}</p>
        <p className="mt-2 leading-6 text-slate-500">{announcement.body}</p>
      </div>
      <p className="w-20 text-right text-slate-500">{announcement.date}</p>
    </div>
  )
}

const toneClasses: Record<StudentCourseTone, { soft: string }> = {
  blue: { soft: 'bg-blue-50 text-blue-600' },
  green: { soft: 'bg-green-50 text-green-700' },
  orange: { soft: 'bg-orange-50 text-orange-600' },
  purple: { soft: 'bg-purple-50 text-purple-600' },
}

function statusVariant(status: string): 'default' | 'success' | 'warning' | 'danger' | 'secondary' {
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
