import { ClipboardCheck, FileCheck2, Search, TrendingUp, Users } from 'lucide-react'
import { useMemo, useState } from 'react'

import { PageHeader } from '@/components/shared/PageHeader'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { assessments, gradebookRows, type GradebookRow } from '@/features/professor/data/mockProfessor'
import { cn } from '@/lib/utils'

const classAverage = gradebookRows.reduce((total, row) => total + row.average, 0) / gradebookRows.length
const atRiskCount = gradebookRows.filter((row) => row.status === 'At Risk').length
const gradedAssessments = assessments.reduce((total, assessment) => total + assessment.graded, 0)

export function ProfessorGradebookPage() {
  const [query, setQuery] = useState('')

  const visibleRows = useMemo(() => {
    const normalizedQuery = query.trim().toLowerCase()

    if (normalizedQuery === '') {
      return gradebookRows
    }

    return gradebookRows.filter((row) =>
      [row.student, row.studentId, row.courseCode].some((value) => value.toLowerCase().includes(normalizedQuery)),
    )
  }, [query])

  return (
    <>
      <PageHeader title="Gradebook" description="Assessment and student grade overview" />

      <div className="mb-5">
        <h1 className="text-2xl font-semibold text-slate-950">Gradebook</h1>
        <p className="mt-1 text-sm text-slate-500">Assessment progress and student performance by course.</p>
      </div>

      <div className="mb-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <SummaryCard label="Class Average" value={classAverage.toFixed(1)} helper="Across visible courses" icon={TrendingUp} tone="green" />
        <SummaryCard label="Students" value={String(gradebookRows.length)} helper="In gradebook" icon={Users} tone="blue" />
        <SummaryCard label="At Risk" value={String(atRiskCount)} helper="Needs attention" icon={ClipboardCheck} tone="orange" />
        <SummaryCard label="Items Graded" value={String(gradedAssessments)} helper="This grading cycle" icon={FileCheck2} tone="purple" />
      </div>

      <div className="mb-5 grid gap-5 xl:grid-cols-[1fr_360px]">
        <Card>
          <CardHeader className="flex-row items-center justify-between border-b border-slate-100 p-4">
            <CardTitle>Student Grades</CardTitle>
            <div className="relative w-full max-w-xs">
              <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
              <Input
                aria-label="Search gradebook"
                className="pl-9"
                placeholder="Search..."
                value={query}
                onChange={(event) => setQuery(event.target.value)}
              />
            </div>
          </CardHeader>
          <CardContent className="p-0">
            <div className="overflow-x-auto">
              <table className="w-full min-w-[880px] text-left text-sm">
                <thead className="bg-slate-50 text-xs font-semibold text-slate-600">
                  <tr>
                    <th className="px-4 py-3">Student</th>
                    <th className="px-4 py-3">Course</th>
                    <th className="px-4 py-3">Midterm</th>
                    <th className="px-4 py-3">Project</th>
                    <th className="px-4 py-3">Final</th>
                    <th className="px-4 py-3">Average</th>
                    <th className="px-4 py-3">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {visibleRows.map((row) => (
                    <GradeRow key={row.id} row={row} />
                  ))}
                </tbody>
              </table>
            </div>
            {visibleRows.length === 0 ? (
              <div className="border-t border-slate-100 px-4 py-6 text-sm text-slate-500">
                No gradebook rows match the current search.
              </div>
            ) : null}
          </CardContent>
        </Card>

        <Card className="h-fit">
          <CardHeader>
            <CardTitle>Assessment Progress</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {assessments.map((assessment) => {
              const gradedPercent = Math.round((assessment.graded / assessment.total) * 100)
              const submittedPercent = Math.round((assessment.submitted / assessment.total) * 100)

              return (
                <div key={assessment.id} className="rounded-lg border border-slate-200 p-4">
                  <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                      <p className="truncate font-semibold text-slate-950">{assessment.title}</p>
                      <p className="mt-1 text-sm text-slate-500">
                        {assessment.courseCode} - {assessment.type}
                      </p>
                    </div>
                    <Badge variant={gradedPercent >= 80 ? 'success' : 'warning'}>{gradedPercent}%</Badge>
                  </div>
                  <Progress label="Submitted" value={submittedPercent} className="mt-4 bg-blue-500" />
                  <Progress label="Graded" value={gradedPercent} className="mt-3 bg-green-500" />
                </div>
              )
            })}
          </CardContent>
        </Card>
      </div>
    </>
  )
}

function GradeRow({ row }: { row: GradebookRow }) {
  return (
    <tr>
      <td className="px-4 py-3">
        <p className="font-medium text-slate-800">{row.student}</p>
        <p className="text-xs text-slate-500">{row.studentId}</p>
      </td>
      <td className="px-4 py-3 font-medium text-slate-700">{row.courseCode}</td>
      <td className="px-4 py-3 text-slate-600">{formatGrade(row.midterm)}</td>
      <td className="px-4 py-3 text-slate-600">{formatGrade(row.project)}</td>
      <td className="px-4 py-3 text-slate-600">{row.final === null ? '-' : formatGrade(row.final)}</td>
      <td className="px-4 py-3 font-semibold text-slate-950">{formatGrade(row.average)}</td>
      <td className="px-4 py-3">
        <Badge variant={statusVariant(row.status)}>{row.status}</Badge>
      </td>
    </tr>
  )
}

function SummaryCard({
  label,
  value,
  helper,
  icon: Icon,
  tone,
}: {
  label: string
  value: string
  helper: string
  icon: typeof TrendingUp
  tone: 'blue' | 'green' | 'orange' | 'purple'
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

function Progress({ label, value, className }: { label: string; value: number; className: string }) {
  return (
    <div>
      <div className="flex items-center justify-between text-sm">
        <span className="font-medium text-slate-700">{label}</span>
        <span className="font-semibold text-slate-950">{value}%</span>
      </div>
      <div className="mt-2 h-2 rounded-full bg-slate-100">
        <div className={cn('h-2 rounded-full', className)} style={{ width: `${value}%` }} />
      </div>
    </div>
  )
}

function statusVariant(status: string) {
  if (status === 'Passed') {
    return 'success'
  }

  if (status === 'At Risk') {
    return 'danger'
  }

  return 'warning'
}

function formatGrade(value: number): string {
  return value.toFixed(1)
}

const toneClasses = {
  blue: 'bg-blue-50 text-blue-600',
  green: 'bg-green-50 text-green-700',
  orange: 'bg-orange-50 text-orange-600',
  purple: 'bg-purple-50 text-purple-600',
}
