import { useEffect, useState } from 'react'
import { Search, Plus, Edit2, Trash2, Calendar, Clock, X, ChevronLeft, ChevronRight } from 'lucide-react'
import { PageHeader } from '@/components/shared/PageHeader'
import { Card, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Skeleton } from '@/components/ui/skeleton'
import { listAdminCourses, getAdminOptions, getAdminCourse, createAdminCourse, updateAdminCourse, deleteAdminCourse } from '@/lib/api/admin'

type CourseListItem = {
  id: number
  courseKey: string
  code: string
  name: string
  department?: string
  departmentId?: number
  semester?: string
  semesterId?: number
  ects: number
  status: string
  room?: string
  studentsCount: number
  professors: string[]
  scheduleDays?: string
  scheduleTime?: string
}

type FacultyOption = { id: number; name: string }
type DepartmentOption = { id: number; faculty_id: number; name: string }
type ProgramOption = { id: number; department_id: number; name: string }
type SemesterOption = { id: number; name: string; code: string }
type ProfessorOption = { id: number; name: string }

type OptionsData = {
  faculties: FacultyOption[]
  departments: DepartmentOption[]
  programs: ProgramOption[]
  semesters: SemesterOption[]
  professors: ProfessorOption[]
}

const initialForm = {
  code: '',
  name: '',
  department_id: '',
  semester_id: '',
  ects: '6',
  room: '',
  description: '',
  grading_breakdown: '',
  status: 'Active',
  professor_ids: [] as number[],
  schedule: {
    days: [] as string[],
    starts_at: '09:00',
    ends_at: '10:30',
  },
  learning_outcomes: [] as string[],
  topics: [] as string[],
}

const DAYS_OF_WEEK = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']

export function AdminCoursesPage() {
  const [courses, setCourses] = useState<CourseListItem[]>([])
  const [meta, setMeta] = useState<any>({ current_page: 1, last_page: 1, total: 0 })
  const [filters, setFilters] = useState({ search: '', department_id: 'all', semester_id: 'all', page: 1 })
  const [options, setOptions] = useState<OptionsData>({ faculties: [], departments: [], programs: [], semesters: [], professors: [] })
  
  // Loading & error states
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  
  // Modal states
  const [modalOpen, setModalOpen] = useState(false)
  const [isEditing, setIsEditing] = useState(false)
  const [editingId, setEditingId] = useState<number | null>(null)
  
  const [form, setForm] = useState(initialForm)
  const [formErrors, setFormErrors] = useState<Record<string, string[]>>({})
  const [submitting, setSubmitting] = useState(false)

  // Dynamic lists in modal
  const [outcomeInput, setOutcomeInput] = useState('')
  const [topicInput, setTopicInput] = useState('')

  const fetchCourses = () => {
    setLoading(true)
    listAdminCourses(filters)
      .then((res) => {
        setCourses(res.data.items)
        setMeta(res.data.meta)
        setLoading(false)
      })
      .catch((err) => {
        setError(err instanceof Error ? err.message : 'Failed to fetch courses')
        setLoading(false)
      })
  }

  useEffect(() => {
    fetchCourses()
  }, [filters])

  useEffect(() => {
    getAdminOptions()
      .then((res) => setOptions(res.data))
      .catch(console.error)
  }, [])

  const handleOpenCreate = () => {
    setForm(initialForm)
    setFormErrors({})
    setIsEditing(false)
    setEditingId(null)
    setModalOpen(true)
    setOutcomeInput('')
    setTopicInput('')
  }

  const handleOpenEdit = (course: CourseListItem) => {
    setSubmitting(true)
    setFormErrors({})
    getAdminCourse(course.id)
      .then((res) => {
        const c = res.data
        setForm({
          code: c.code || '',
          name: c.name || '',
          department_id: c.departmentId ? String(c.departmentId) : '',
          semester_id: c.semesterId ? String(c.semesterId) : '',
          ects: String(c.ects || 6),
          room: c.room || '',
          description: c.description || '',
          grading_breakdown: c.gradingBreakdown || '',
          status: c.status || 'Active',
          professor_ids: c.professorIds || [],
          schedule: c.schedule ? {
            days: c.schedule.days || [],
            starts_at: c.schedule.starts_at || '09:00',
            ends_at: c.schedule.ends_at || '10:30',
          } : { days: [], starts_at: '09:00', ends_at: '10:30' },
          learning_outcomes: Array.isArray(c.learningOutcomes) ? c.learningOutcomes : [],
          topics: Array.isArray(c.topics) ? c.topics : [],
        })
        setIsEditing(true)
        setEditingId(course.id)
        setModalOpen(true)
        setSubmitting(false)
      })
      .catch((err) => {
        alert(err instanceof Error ? err.message : 'Failed to retrieve course details')
        setSubmitting(false)
      })
  }

  const handleDelete = (course: CourseListItem) => {
    if (confirm(`Are you sure you want to delete course "${course.name}" (${course.code})? Students currently enrolled will be affected. This action is permanent.`)) {
      deleteAdminCourse(course.id)
        .then(() => {
          fetchCourses()
        })
        .catch((err) => alert(err instanceof Error ? err.message : 'Delete failed'))
    }
  }

  const handleProfessorToggle = (profId: number) => {
    setForm((prev) => {
      const idx = prev.professor_ids.indexOf(profId)
      const nextProfs = [...prev.professor_ids]
      if (idx > -1) {
        nextProfs.splice(idx, 1)
      } else {
        nextProfs.push(profId)
      }
      return { ...prev, professor_ids: nextProfs }
    })
  }

  const handleDayToggle = (day: string) => {
    setForm((prev) => {
      const idx = prev.schedule.days.indexOf(day)
      const nextDays = [...prev.schedule.days]
      if (idx > -1) {
        nextDays.splice(idx, 1)
      } else {
        nextDays.push(day)
      }
      return {
        ...prev,
        schedule: { ...prev.schedule, days: nextDays },
      }
    })
  }

  const addOutcome = () => {
    if (outcomeInput.trim()) {
      setForm((prev) => ({
        ...prev,
        learning_outcomes: [...prev.learning_outcomes, outcomeInput.trim()],
      }))
      setOutcomeInput('')
    }
  }

  const removeOutcome = (index: number) => {
    setForm((prev) => ({
      ...prev,
      learning_outcomes: prev.learning_outcomes.filter((_, i) => i !== index),
    }))
  }

  const addTopic = () => {
    if (topicInput.trim()) {
      setForm((prev) => ({
        ...prev,
        topics: [...prev.topics, topicInput.trim()],
      }))
      setTopicInput('')
    }
  }

  const removeTopic = (index: number) => {
    setForm((prev) => ({
      ...prev,
      topics: prev.topics.filter((_, i) => i !== index),
    }))
  }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setSubmitting(true)
    setFormErrors({})

    const payload: Record<string, any> = {
      ...form,
      ects: Number(form.ects),
      department_id: Number(form.department_id),
      semester_id: Number(form.semester_id),
    }

    // If no days selected, set schedule to null or clear it
    if (form.schedule.days.length === 0) {
      delete payload.schedule
    }

    const request = isEditing && editingId
      ? updateAdminCourse(editingId, payload)
      : createAdminCourse(payload)

    request
      .then(() => {
        setModalOpen(false)
        fetchCourses()
      })
      .catch((err: any) => {
        if (err && err.errors) {
          setFormErrors(err.errors)
        } else {
          alert(err instanceof Error ? err.message : 'Submit failed')
        }
      })
      .finally(() => setSubmitting(false))
  }

  return (
    <div className="space-y-6 pb-12">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <PageHeader title="Course Management" description="Create new curriculum courses, assign instructors, and set schedules." />
        <Button onClick={handleOpenCreate} className="h-10 self-start sm:self-center gap-2">
          <Plus className="h-4 w-4" />
          Create Course
        </Button>
      </div>

      {/* Filters Bar */}
      <Card>
        <CardContent className="p-4 grid gap-3 md:grid-cols-3">
          <div className="relative">
            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <Input
              aria-label="Search courses"
              className="pl-9"
              placeholder="Search by code or course name..."
              value={filters.search}
              onChange={(e) => setFilters((f) => ({ ...f, search: e.target.value, page: 1 }))}
            />
          </div>

          <select
            aria-label="Filter by department"
            className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            value={filters.department_id}
            onChange={(e) => setFilters((f) => ({ ...f, department_id: e.target.value, page: 1 }))}
          >
            <option value="all">All Departments</option>
            {options.departments.map((dept) => (
              <option key={dept.id} value={dept.id}>{dept.name}</option>
            ))}
          </select>

          <select
            aria-label="Filter by semester"
            className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            value={filters.semester_id}
            onChange={(e) => setFilters((f) => ({ ...f, semester_id: e.target.value, page: 1 }))}
          >
            <option value="all">All Semesters</option>
            {options.semesters.map((sem) => (
              <option key={sem.id} value={sem.id}>{sem.name}</option>
            ))}
          </select>
        </CardContent>
      </Card>

      {/* Error state */}
      {error && (
        <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
          {error}
        </div>
      )}

      {/* Courses List */}
      <Card>
        <CardContent className="p-0 overflow-x-auto">
          {loading ? (
            <div className="p-8 space-y-4">
              <Skeleton className="h-6 w-full" />
              <Skeleton className="h-6 w-full" />
              <Skeleton className="h-6 w-full" />
            </div>
          ) : (
            <table className="w-full text-left border-collapse min-w-[800px]">
              <thead>
                <tr className="border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase bg-slate-50">
                  <th className="p-4">Course</th>
                  <th className="p-4">Department & Semester</th>
                  <th className="p-4 text-center">ECTS</th>
                  <th className="p-4">Faculty Assigned</th>
                  <th className="p-4">Schedule & Venue</th>
                  <th className="p-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 text-sm">
                {courses.map((course) => (
                  <tr key={course.id} className="hover:bg-slate-50 transition-colors">
                    <td className="p-4">
                      <div>
                        <div className="font-semibold text-slate-900">{course.name}</div>
                        <div className="text-xs font-mono font-semibold text-blue-600 mt-0.5">{course.code}</div>
                      </div>
                    </td>
                    <td className="p-4">
                      <div>
                        <div className="text-slate-800 font-medium">{course.department}</div>
                        <div className="text-xs text-slate-400 mt-0.5">{course.semester}</div>
                      </div>
                    </td>
                    <td className="p-4 text-center font-bold text-slate-700">{course.ects}</td>
                    <td className="p-4">
                      {course.professors.length > 0 ? (
                        <div className="flex flex-wrap gap-1">
                          {course.professors.map((p) => (
                            <Badge key={p} variant="secondary" className="text-xs font-medium">{p}</Badge>
                          ))}
                        </div>
                      ) : (
                        <span className="text-xs text-amber-600 bg-amber-50 px-2 py-0.5 rounded border border-amber-100 font-medium">Unassigned</span>
                      )}
                    </td>
                    <td className="p-4">
                      {course.scheduleDays ? (
                        <div className="text-slate-700">
                          <div className="flex items-center gap-1 font-medium text-xs">
                            <Calendar className="h-3 w-3 text-slate-400" />
                            {course.scheduleDays}
                          </div>
                          <div className="flex items-center gap-1 text-slate-400 text-[11px] mt-0.5">
                            <Clock className="h-3 w-3" />
                            {course.scheduleTime} {course.room ? `[Room: ${course.room}]` : ''}
                          </div>
                        </div>
                      ) : (
                        <span className="text-slate-400 italic text-xs">No schedule</span>
                      )}
                    </td>
                    <td className="p-4 text-right">
                      <div className="flex justify-end gap-2">
                        <Button variant="secondary" size="sm" onClick={() => handleOpenEdit(course)} className="h-8 w-8 p-0" title="Edit">
                          <Edit2 className="h-3.5 w-3.5" />
                        </Button>
                        <Button variant="secondary" size="sm" onClick={() => handleDelete(course)} className="h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50 border-red-100" title="Delete">
                          <Trash2 className="h-3.5 w-3.5" />
                        </Button>
                      </div>
                    </td>
                  </tr>
                ))}
                {courses.length === 0 && (
                  <tr>
                    <td colSpan={6} className="p-8 text-center text-slate-400">No courses configured.</td>
                  </tr>
                )}
              </tbody>
            </table>
          )}
        </CardContent>
      </Card>

      {/* Pagination */}
      {meta.last_page > 1 && (
        <div className="flex items-center justify-between">
          <p className="text-xs text-slate-400">Showing page {meta.current_page} of {meta.last_page}</p>
          <div className="flex gap-2">
            <Button
              variant="secondary"
              size="sm"
              onClick={() => setFilters((f) => ({ ...f, page: f.page - 1 }))}
              disabled={filters.page <= 1}
            >
              <ChevronLeft className="h-4 w-4 mr-1" />
              Previous
            </Button>
            <Button
              variant="secondary"
              size="sm"
              onClick={() => setFilters((f) => ({ ...f, page: f.page + 1 }))}
              disabled={filters.page >= meta.last_page}
            >
              Next
              <ChevronRight className="h-4 w-4 ml-1" />
            </Button>
          </div>
        </div>
      )}

      {/* Create / Edit Modal */}
      {modalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto">
          <div className="relative w-full max-w-2xl rounded-xl border border-slate-100 bg-white shadow-2xl transition-all duration-300 max-h-[90vh] flex flex-col">
            <div className="flex items-center justify-between border-b border-slate-100 px-6 py-4">
              <h3 className="text-lg font-bold text-slate-900">
                {isEditing ? `Edit Course: ${form.name}` : 'Create New Course'}
              </h3>
              <button onClick={() => setModalOpen(false)} className="rounded-lg p-1 text-slate-400 hover:bg-slate-50 hover:text-slate-600 transition-colors">
                <X className="h-5 w-5" />
              </button>
            </div>

            <form onSubmit={handleSubmit} className="flex-1 overflow-y-auto p-6 space-y-6">
              {/* Basic Fields */}
              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-1.5">
                  <Label htmlFor="code">Course Code</Label>
                  <Input id="code" placeholder="e.g. CS-101" value={form.code} onChange={(e) => setForm((f) => ({ ...f, code: e.target.value }))} required />
                  {formErrors.code?.map((e) => <p key={e} className="text-xs text-red-600 mt-1">{e}</p>)}
                </div>

                <div className="space-y-1.5">
                  <Label htmlFor="name">Course Title</Label>
                  <Input id="name" placeholder="e.g. Intro to Computer Science" value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} required />
                  {formErrors.name?.map((e) => <p key={e} className="text-xs text-red-600 mt-1">{e}</p>)}
                </div>

                <div className="space-y-1.5">
                  <Label htmlFor="department_id">Academic Department</Label>
                  <select
                    id="department_id"
                    className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-blue-500 focus:outline-none"
                    value={form.department_id}
                    onChange={(e) => setForm((f) => ({ ...f, department_id: e.target.value }))}
                    required
                  >
                    <option value="">Select Department</option>
                    {options.departments.map((d) => (
                      <option key={d.id} value={d.id}>{d.name}</option>
                    ))}
                  </select>
                  {formErrors.department_id?.map((e) => <p key={e} className="text-xs text-red-600 mt-1">{e}</p>)}
                </div>

                <div className="space-y-1.5">
                  <Label htmlFor="semester_id">Semester Term</Label>
                  <select
                    id="semester_id"
                    className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-blue-500 focus:outline-none"
                    value={form.semester_id}
                    onChange={(e) => setForm((f) => ({ ...f, semester_id: e.target.value }))}
                    required
                  >
                    <option value="">Select Semester</option>
                    {options.semesters.map((s) => (
                      <option key={s.id} value={s.id}>{s.name} ({s.code})</option>
                    ))}
                  </select>
                  {formErrors.semester_id?.map((e) => <p key={e} className="text-xs text-red-600 mt-1">{e}</p>)}
                </div>

                <div className="space-y-1.5">
                  <Label htmlFor="ects">ECTS Credits</Label>
                  <Input id="ects" type="number" min="1" max="30" value={form.ects} onChange={(e) => setForm((f) => ({ ...f, ects: e.target.value }))} required />
                  {formErrors.ects?.map((e) => <p key={e} className="text-xs text-red-600 mt-1">{e}</p>)}
                </div>

                <div className="space-y-1.5">
                  <Label htmlFor="room">Lecture Room / Venue</Label>
                  <Input id="room" placeholder="e.g. Lab 3, Auditorium 1" value={form.room} onChange={(e) => setForm((f) => ({ ...f, room: e.target.value }))} />
                  {formErrors.room?.map((e) => <p key={e} className="text-xs text-red-600 mt-1">{e}</p>)}
                </div>
              </div>

              {/* Status (Edit only) */}
              {isEditing && (
                <div className="space-y-1.5">
                  <Label htmlFor="status">Course Status</Label>
                  <select
                    id="status"
                    className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-blue-500 focus:outline-none"
                    value={form.status}
                    onChange={(e) => setForm((f) => ({ ...f, status: e.target.value }))}
                  >
                    <option value="Active">Active</option>
                    <option value="Exam Week">Exam Week</option>
                    <option value="Closing">Closing</option>
                  </select>
                </div>
              )}

              {/* Description & Grading */}
              <div className="space-y-4 border-t border-slate-100 pt-4">
                <div className="space-y-1.5">
                  <Label htmlFor="description">Course Description</Label>
                  <Textarea id="description" placeholder="Summary of course content..." value={form.description} onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))} rows={3} />
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="grading_breakdown">Grading Policy Breakdown</Label>
                  <Input id="grading_breakdown" placeholder="e.g. Midterm 30%, Project 30%, Final 40%" value={form.grading_breakdown} onChange={(e) => setForm((f) => ({ ...f, grading_breakdown: e.target.value }))} />
                </div>
              </div>

              {/* Instructor Assignment */}
              <div className="space-y-2 border-t border-slate-100 pt-4">
                <Label>Assign Professors / Instructors</Label>
                <div className="grid gap-2 sm:grid-cols-2 max-h-40 overflow-y-auto p-3 rounded-lg border border-slate-150 bg-slate-50">
                  {options.professors.map((p) => {
                    const checked = form.professor_ids.includes(p.id)
                    return (
                      <label key={p.id} className="flex items-center gap-2 text-sm font-medium text-slate-700 cursor-pointer select-none">
                        <input
                          type="checkbox"
                          checked={checked}
                          onChange={() => handleProfessorToggle(p.id)}
                          className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        />
                        {p.name}
                      </label>
                    )
                  })}
                  {options.professors.length === 0 && (
                    <p className="col-span-2 text-center text-slate-400 text-xs py-2">No professors available.</p>
                  )}
                </div>
              </div>

              {/* Schedule */}
              <div className="space-y-4 border-t border-slate-100 pt-4">
                <Label>Weekly Schedule (Optional)</Label>
                <div className="grid gap-4 sm:grid-cols-3">
                  <div className="sm:col-span-3">
                    <Label className="text-xs text-slate-400">Select Days</Label>
                    <div className="flex flex-wrap gap-1.5 mt-1.5">
                      {DAYS_OF_WEEK.map((day) => {
                        const active = form.schedule.days.includes(day)
                        return (
                          <button
                            key={day}
                            type="button"
                            onClick={() => handleDayToggle(day)}
                            className={`px-3 py-1.5 text-xs font-semibold rounded-md border transition-all ${
                              active
                                ? 'bg-blue-600 border-blue-600 text-white shadow-sm'
                                : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'
                            }`}
                          >
                            {day.slice(0, 3)}
                          </button>
                        )
                      })}
                    </div>
                  </div>

                  <div className="space-y-1.5">
                    <Label htmlFor="starts_at" className="text-xs">Starts At</Label>
                    <Input id="starts_at" type="time" value={form.schedule.starts_at} onChange={(e) => setForm((f) => ({ ...f, schedule: { ...f.schedule, starts_at: e.target.value } }))} />
                  </div>

                  <div className="space-y-1.5">
                    <Label htmlFor="ends_at" className="text-xs">Ends At</Label>
                    <Input id="ends_at" type="time" value={form.schedule.ends_at} onChange={(e) => setForm((f) => ({ ...f, schedule: { ...f.schedule, ends_at: e.target.value } }))} />
                  </div>
                </div>
              </div>

              {/* Learning Outcomes */}
              <div className="space-y-3 border-t border-slate-100 pt-4">
                <Label>Learning Outcomes</Label>
                <div className="flex gap-2">
                  <Input placeholder="Define a learning goal..." value={outcomeInput} onChange={(e) => setOutcomeInput(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && (e.preventDefault(), addOutcome())} />
                  <Button type="button" variant="secondary" onClick={addOutcome}>Add</Button>
                </div>
                {form.learning_outcomes.length > 0 && (
                  <ul className="space-y-1.5">
                    {form.learning_outcomes.map((item, idx) => (
                      <li key={idx} className="flex items-center justify-between gap-3 text-xs bg-slate-50 p-2 rounded border border-slate-100">
                        <span className="text-slate-700">{item}</span>
                        <button type="button" onClick={() => removeOutcome(idx)} className="text-slate-400 hover:text-red-500">
                          <X className="h-4 w-4" />
                        </button>
                      </li>
                    ))}
                  </ul>
                )}
              </div>

              {/* Topics List */}
              <div className="space-y-3 border-t border-slate-100 pt-4">
                <Label>Curriculum Topics</Label>
                <div className="flex gap-2">
                  <Input placeholder="Define a weekly topic / chapter..." value={topicInput} onChange={(e) => setTopicInput(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && (e.preventDefault(), addTopic())} />
                  <Button type="button" variant="secondary" onClick={addTopic}>Add</Button>
                </div>
                {form.topics.length > 0 && (
                  <ul className="space-y-1.5">
                    {form.topics.map((item, idx) => (
                      <li key={idx} className="flex items-center justify-between gap-3 text-xs bg-slate-50 p-2 rounded border border-slate-100">
                        <span className="text-slate-700">{item}</span>
                        <button type="button" onClick={() => removeTopic(idx)} className="text-slate-400 hover:text-red-500">
                          <X className="h-4 w-4" />
                        </button>
                      </li>
                    ))}
                  </ul>
                )}
              </div>
            </form>

            <div className="flex justify-end gap-2 border-t border-slate-100 px-6 py-4 bg-slate-50 rounded-b-xl">
              <Button type="button" variant="secondary" onClick={() => setModalOpen(false)}>Cancel</Button>
              <Button onClick={handleSubmit} disabled={submitting} className="min-w-[100px]">
                {submitting ? 'Saving...' : 'Save Course'}
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
