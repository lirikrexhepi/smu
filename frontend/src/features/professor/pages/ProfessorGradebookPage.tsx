import { ClipboardCheck, FileCheck2, Search, TrendingUp, Users, Loader2, Edit2 } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'

import { PageHeader } from '@/components/shared/PageHeader'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { getProfessorGradebook, saveStudentGrade } from '@/lib/api/professor'
import { cn } from '@/lib/utils'

type GradebookRow = {
  id: string
  student: string
  studentId: string
  courseCode: string
  midterm: number
  project: number
  final: number | null
  average: number
  status: 'Passed' | 'In Progress' | 'At Risk'
}

type Assessment = {
  id: string
  courseCode: string
  title: string
  type: string
  dueDate: string
  submitted: number
  total: number
  graded: number
}

export function ProfessorGradebookPage() {
  const [query, setQuery] = useState('')
  const [gradebook, setGradebook] = useState<GradebookRow[]>([])
  const [assessments, setAssessments] = useState<Assessment[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  // Edit Grade State
  const [editingGrade, setEditingGrade] = useState<{
    studentId: string
    studentName: string
    courseCode: string
    component: 'midterm' | 'project' | 'final'
    currentVal: number
  } | null>(null)
  const [newGradeVal, setNewGradeVal] = useState('')
  const [savingGrade, setSavingGrade] = useState(false)

  const fetchData = () => {
    setLoading(true)
    getProfessorGradebook()
      .then((res) => {
        if (res.success && res.data) {
          setGradebook(res.data.gradebook)
          setAssessments(res.data.assessments)
        } else {
          setError(res.message || 'Failed to load gradebook details.')
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

  const handleOpenEdit = (row: GradebookRow, component: 'midterm' | 'project' | 'final') => {
    const currentVal = component === 'final' ? (row.final ?? 0) : (component === 'project' ? row.project : row.midterm)
    setEditingGrade({
      studentId: row.studentId,
      studentName: row.student,
      courseCode: row.courseCode,
      component,
      currentVal,
    })
    setNewGradeVal(String(currentVal))
  }

  const handleSaveGrade = (e: React.FormEvent) => {
    e.preventDefault()
    if (!editingGrade) return

    const parsedGrade = parseFloat(newGradeVal)
    if (isNaN(parsedGrade) || parsedGrade < 0 || parsedGrade > 10) {
      alert('Please enter a valid numeric grade between 0 and 10.')
      return
    }

    setSavingGrade(true)
    saveStudentGrade({
      studentId: editingGrade.studentId,
      courseCode: editingGrade.courseCode,
      component: editingGrade.component,
      grade: parsedGrade,
    })
      .then((res) => {
        if (res.success) {
          setEditingGrade(null)
          // Fetch updated data to recalculate dynamic values
          getProfessorGradebook().then((res2) => {
            if (res2.success && res2.data) {
              setGradebook(res2.data.gradebook)
            }
          })
        } else {
          alert(res.message || 'Failed to save grade.')
        }
      })
      .catch(() => {
        alert('Error connecting to the server.')
      })
      .finally(() => {
        setSavingGrade(false)
      })
  }

  const visibleRows = useMemo(() => {
    const normalizedQuery = query.trim().toLowerCase()

    if (normalizedQuery === '') {
      return gradebook
    }

    return gradebook.filter((row) =>
      [row.student, row.studentId, row.courseCode].some((value) => value.toLowerCase().includes(normalizedQuery)),
    )
  }, [query, gradebook])

  const classAverage = useMemo(() => {
    if (gradebook.length === 0) return 0
    return gradebook.reduce((total, row) => total + row.average, 0) / gradebook.length
  }, [gradebook])

  const atRiskCount = useMemo(() => {
    return gradebook.filter((row) => row.status === 'At Risk').length
  }, [gradebook])

  const gradedAssessments = useMemo(() => {
    return assessments.reduce((total, assessment) => total + assessment.graded, 0)
  }, [assessments])

  if (loading && gradebook.length === 0) {
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

  return (
    <>
      <PageHeader title="Gradebook" description="Assessment and student grade overview" />

      <div className="mb-5">
        <h1 className="text-2xl font-semibold text-slate-950">Gradebook</h1>
        <p className="mt-1 text-sm text-slate-500">Assessment progress and student performance by course. Click any grade cell to edit it dynamically.</p>
      </div>

      <div className="mb-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <SummaryCard label="Class Average" value={classAverage.toFixed(1)} helper="Across visible courses" icon={TrendingUp} tone="green" />
        <SummaryCard label="Students" value={String(gradebook.length)} helper="In gradebook" icon={Users} tone="blue" />
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
                    <GradeRow
                      key={row.id}
                      row={row}
                      onEditMidterm={() => handleOpenEdit(row, 'midterm')}
                      onEditProject={() => handleOpenEdit(row, 'project')}
                      onEditFinal={() => handleOpenEdit(row, 'final')}
                    />
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

      {/* Edit Grade Modal */}
      {editingGrade && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
          <div className="w-full max-w-sm rounded-lg border border-slate-200 bg-white p-6 shadow-xl">
            <h2 className="text-lg font-semibold text-slate-900">Update Student Grade</h2>
            <div className="mt-2 space-y-1 text-sm text-slate-600">
              <p>Student: <strong>{editingGrade.studentName}</strong></p>
              <p>Course: <strong>{editingGrade.courseCode}</strong></p>
              <p>Component: <strong className="capitalize">{editingGrade.component}</strong></p>
            </div>

            <form onSubmit={handleSaveGrade} className="mt-4 space-y-4">
              <div>
                <label htmlFor="grade-input" className="text-sm font-medium text-slate-700">Numeric Grade (0.0 - 10.0)</label>
                <Input
                  id="grade-input"
                  type="number"
                  step="0.1"
                  min="0"
                  max="10"
                  className="mt-1"
                  value={newGradeVal}
                  onChange={(e) => setNewGradeVal(e.target.value)}
                  required
                />
              </div>

              <div className="flex justify-end gap-2 pt-2">
                <Button type="button" variant="secondary" onClick={() => setEditingGrade(null)}>
                  Cancel
                </Button>
                <Button type="submit" disabled={savingGrade}>
                  {savingGrade ? 'Saving...' : 'Save Grade'}
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}
    </>
  )
}

function GradeRow({
  row,
  onEditMidterm,
  onEditProject,
  onEditFinal,
}: {
  row: GradebookRow
  onEditMidterm: () => void
  onEditProject: () => void
  onEditFinal: () => void
}) {
  return (
    <tr>
      <td className="px-4 py-3">
        <p className="font-medium text-slate-800">{row.student}</p>
        <p className="text-xs text-slate-500">{row.studentId}</p>
      </td>
      <td className="px-4 py-3 font-medium text-slate-700">{row.courseCode}</td>
      <td className="px-4 py-3">
        <button
          onClick={onEditMidterm}
          className="group flex items-center gap-1.5 rounded px-2 py-1 text-left text-slate-600 hover:bg-slate-50 hover:text-blue-600 focus:outline-none"
        >
          <span>{formatGrade(row.midterm)}</span>
          <Edit2 className="h-3 w-3 opacity-0 group-hover:opacity-100 transition-opacity" />
        </button>
      </td>
      <td className="px-4 py-3">
        <button
          onClick={onEditProject}
          className="group flex items-center gap-1.5 rounded px-2 py-1 text-left text-slate-600 hover:bg-slate-50 hover:text-blue-600 focus:outline-none"
        >
          <span>{formatGrade(row.project)}</span>
          <Edit2 className="h-3 w-3 opacity-0 group-hover:opacity-100 transition-opacity" />
        </button>
      </td>
      <td className="px-4 py-3">
        <button
          onClick={onEditFinal}
          className="group flex items-center gap-1.5 rounded px-2 py-1 text-left text-slate-600 hover:bg-slate-50 hover:text-blue-600 focus:outline-none"
        >
          <span>{row.final === null ? '-' : formatGrade(row.final)}</span>
          <Edit2 className="h-3 w-3 opacity-0 group-hover:opacity-100 transition-opacity" />
        </button>
      </td>
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
