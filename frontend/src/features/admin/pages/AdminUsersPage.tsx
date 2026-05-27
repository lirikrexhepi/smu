import { useEffect, useState } from 'react'
import { Search, UserPlus, Edit2, Trash2, Shield, User, GraduationCap, X, ChevronLeft, ChevronRight } from 'lucide-react'
import { PageHeader } from '@/components/shared/PageHeader'
import { Card, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Skeleton } from '@/components/ui/skeleton'
import { listAdminUsers, getAdminOptions, getAdminUser, createAdminUser, updateAdminUser, deleteAdminUser } from '@/lib/api/admin'
import { useToast } from '@/components/ui/toast'

type UserListItem = {
  id: number
  publicId: string
  name: string
  email: string
  role: 'student' | 'professor' | 'admin'
  institutionId: string
  faculty?: string
  department?: string
  studentId?: string
  status?: string
  yearOfStudy?: string
  title?: string
  office?: string
  createdAt: string
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
  name: '',
  email: '',
  password: '',
  role: 'student',
  faculty_id: '',
  department_id: '',
  // Student specific
  program_id: '',
  year_of_study: '1st Year',
  phone: '',
  address: '',
  date_of_birth: '',
  gender: '',
  nationality: '',
  personal_number: '',
  emergency_contact_name: '',
  emergency_contact_relationship: 'Parent',
  emergency_contact_phone: '',
  // Professor specific
  title: 'Assistant Professor',
  office: '',
  office_hours: '',
  consultation: '',
}

export function AdminUsersPage() {
  const { success: toastSuccess, error: toastError } = useToast()
  const [users, setUsers] = useState<UserListItem[]>([])

  const [meta, setMeta] = useState<any>({ current_page: 1, last_page: 1, total: 0 })
  const [filters, setFilters] = useState({ search: '', role: 'all', faculty_id: 'all', department_id: 'all', page: 1 })
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

  // Fetch users & options
  const fetchUsers = () => {
    setLoading(true)
    listAdminUsers(filters)
      .then((res) => {
        setUsers(res.data.items)
        setMeta(res.data.meta)
        setLoading(false)
      })
      .catch((err) => {
        setError(err instanceof Error ? err.message : 'Failed to fetch users')
        setLoading(false)
      })
  }

  useEffect(() => {
    fetchUsers()
  }, [filters])

  useEffect(() => {
    getAdminOptions()
      .then((res) => setOptions(res.data))
      .catch(console.error)
  }, [])

  // Filter department list by faculty selected
  const filteredDepartments = form.faculty_id
    ? options.departments.filter((d) => d.faculty_id === Number(form.faculty_id))
    : options.departments

  // Filter program list by department selected
  const filteredPrograms = form.department_id
    ? options.programs.filter((p) => p.department_id === Number(form.department_id))
    : options.programs

  const handleOpenCreate = () => {
    setForm(initialForm)
    setFormErrors({})
    setIsEditing(false)
    setEditingId(null)
    setModalOpen(true)
  }

  const handleOpenEdit = (user: UserListItem) => {
    setSubmitting(true)
    setFormErrors({})
    getAdminUser(user.id)
      .then((res) => {
        const u = res.data
        setForm({
          name: u.name || '',
          email: u.email || '',
          password: '', // blank password unless changing
          role: u.role || 'student',
          faculty_id: u.facultyId ? String(u.facultyId) : '',
          department_id: u.departmentId ? String(u.departmentId) : '',
          program_id: u.programId ? String(u.programId) : '',
          year_of_study: u.yearOfStudy || '1st Year',
          phone: u.phone || '',
          address: u.address || '',
          date_of_birth: u.dateOfBirth || '',
          gender: u.gender || '',
          nationality: u.nationality || '',
          personal_number: u.personalNumber || '',
          emergency_contact_name: u.emergencyContactName || '',
          emergency_contact_relationship: u.emergencyContactRelationship || 'Parent',
          emergency_contact_phone: u.emergencyContactPhone || '',
          title: u.title || 'Assistant Professor',
          office: u.office || '',
          office_hours: u.officeHours || '',
          consultation: u.consultation || '',
        })
        setIsEditing(true)
        setEditingId(user.id)
        setModalOpen(true)
        setSubmitting(false)
      })
      .catch((err) => {
        toastError(err instanceof Error ? err.message : 'Failed to retrieve user details')
        setSubmitting(false)
      })

  }

  const handleDelete = (user: UserListItem) => {
    if (confirm(`Are you sure you want to delete user "${user.name}"? This action is permanent.`)) {
      deleteAdminUser(user.id)
        .then(() => {
          toastSuccess('User deleted successfully')
          fetchUsers()
        })
        .catch((err) => toastError(err instanceof Error ? err.message : 'Delete failed'))

    }
  }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setSubmitting(true)
    setFormErrors({})

    const payload: Record<string, any> = { ...form }
    
    // Parse numeric fields properly
    if (payload.faculty_id) payload.faculty_id = Number(payload.faculty_id)
    else delete payload.faculty_id

    if (payload.department_id) payload.department_id = Number(payload.department_id)
    else delete payload.department_id

    if (payload.program_id) payload.program_id = Number(payload.program_id)
    else delete payload.program_id

    // Cleanup irrelevant fields based on role
    if (form.role !== 'student') {
      delete payload.program_id
      delete payload.year_of_study
      delete payload.phone
      delete payload.address
      delete payload.date_of_birth
      delete payload.gender
      delete payload.nationality
      delete payload.personal_number
      delete payload.emergency_contact_name
      delete payload.emergency_contact_relationship
      delete payload.emergency_contact_phone
    }
    if (form.role !== 'professor') {
      delete payload.title
      delete payload.office
      delete payload.office_hours
      delete payload.consultation
    }

    const request = isEditing && editingId
      ? updateAdminUser(editingId, payload)
      : createAdminUser(payload)

    request
      .then(() => {
        toastSuccess(isEditing ? 'User updated successfully' : 'User registered successfully')
        setModalOpen(false)
        fetchUsers()
      })
      .catch((err: any) => {
        if (err && err.errors) {
          setFormErrors(err.errors)
        } else {
          toastError(err instanceof Error ? err.message : 'Submit failed')
        }
      })
      .finally(() => setSubmitting(false))

  }

  const updateField = (key: string, val: string) => {
    setForm((prev) => {
      const next = { ...prev, [key]: val }
      // Reset dependent values if parent option changes
      if (key === 'faculty_id') {
        next.department_id = ''
        next.program_id = ''
      }
      if (key === 'department_id') {
        next.program_id = ''
      }
      return next
    })
  }

  return (
    <div className="space-y-6 pb-12">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <PageHeader title="User Management" description="Create, view, update and delete university members." />
        <Button onClick={handleOpenCreate} className="h-10 self-start sm:self-center gap-2">
          <UserPlus className="h-4 w-4" />
          Register User
        </Button>
      </div>

      {/* Filters Bar */}
      <Card>
        <CardContent className="p-4 grid gap-3 md:grid-cols-4">
          <div className="relative">
            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <Input
              aria-label="Search users"
              className="pl-9"
              placeholder="Search by name, email, ID..."
              value={filters.search}
              onChange={(e) => setFilters((f) => ({ ...f, search: e.target.value, page: 1 }))}
            />
          </div>

          <select
            aria-label="Filter by role"
            className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            value={filters.role}
            onChange={(e) => setFilters((f) => ({ ...f, role: e.target.value, page: 1 }))}
          >
            <option value="all">All Roles</option>
            <option value="student">Student</option>
            <option value="professor">Professor</option>
            <option value="admin">Administrator</option>
          </select>

          <select
            aria-label="Filter by faculty"
            className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            value={filters.faculty_id}
            onChange={(e) => setFilters((f) => ({ ...f, faculty_id: e.target.value, department_id: 'all', page: 1 }))}
          >
            <option value="all">All Faculties</option>
            {options.faculties.map((fac) => (
              <option key={fac.id} value={fac.id}>{fac.name}</option>
            ))}
          </select>

          <select
            aria-label="Filter by department"
            className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            value={filters.department_id}
            onChange={(e) => setFilters((f) => ({ ...f, department_id: e.target.value, page: 1 }))}
            disabled={filters.faculty_id === 'all'}
          >
            <option value="all">All Departments</option>
            {options.departments
              .filter((d) => filters.faculty_id === 'all' || d.faculty_id === Number(filters.faculty_id))
              .map((dept) => (
                <option key={dept.id} value={dept.id}>{dept.name}</option>
              ))}
          </select>
        </CardContent>
      </Card>

      {/* Error State */}
      {error && (
        <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
          {error}
        </div>
      )}

      {/* Users Table */}
      <Card>
        <CardContent className="p-0 overflow-x-auto">
          {loading ? (
            <div className="p-8 space-y-4">
              <Skeleton className="h-6 w-full" />
              <Skeleton className="h-6 w-full" />
              <Skeleton className="h-6 w-full" />
            </div>
          ) : (
            <table className="w-full text-left border-collapse min-w-[700px]">
              <thead>
                <tr className="border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase bg-slate-50">
                  <th className="p-4">Name</th>
                  <th className="p-4">Role</th>
                  <th className="p-4">ID</th>
                  <th className="p-4">Affiliation</th>
                  <th className="p-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 text-sm">
                {users.map((user) => (
                  <tr key={user.id} className="hover:bg-slate-50 transition-colors">
                    <td className="p-4">
                      <div>
                        <div className="font-semibold text-slate-900">{user.name}</div>
                        <div className="text-xs text-slate-400 mt-0.5">{user.email}</div>
                      </div>
                    </td>
                    <td className="p-4">
                      <Badge
                        variant={
                          user.role === 'admin'
                            ? 'danger'
                            : user.role === 'professor'
                            ? 'default'
                            : 'secondary'
                        }
                        className="capitalize font-medium"
                      >
                        {user.role}
                      </Badge>
                    </td>
                    <td className="p-4 font-mono text-slate-700">{user.institutionId}</td>
                    <td className="p-4">
                      {user.faculty ? (
                        <div>
                          <div className="text-slate-800 font-medium">{user.faculty}</div>
                          {user.department && <div className="text-xs text-slate-400 mt-0.5">{user.department}</div>}
                        </div>
                      ) : (
                        <span className="text-slate-400 font-light">&mdash;</span>
                      )}
                    </td>
                    <td className="p-4 text-right">
                      <div className="flex justify-end gap-2">
                        <Button variant="secondary" size="sm" onClick={() => handleOpenEdit(user)} className="h-8 w-8 p-0" title="Edit">
                          <Edit2 className="h-3.5 w-3.5" />
                        </Button>
                        <Button variant="secondary" size="sm" onClick={() => handleDelete(user)} className="h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50 border-red-100" title="Delete">
                          <Trash2 className="h-3.5 w-3.5" />
                        </Button>
                      </div>
                    </td>
                  </tr>
                ))}
                {users.length === 0 && (
                  <tr>
                    <td colSpan={5} className="p-8 text-center text-slate-400">No users found.</td>
                  </tr>
                )}
              </tbody>
            </table>
          )}
        </CardContent>
      </Card>

      {/* Pagination Controls */}
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
                {isEditing ? `Edit User Profile: ${form.name}` : 'Register New User'}
              </h3>
              <button onClick={() => setModalOpen(false)} className="rounded-lg p-1 text-slate-400 hover:bg-slate-50 hover:text-slate-600 transition-colors">
                <X className="h-5 w-5" />
              </button>
            </div>

            <form onSubmit={handleSubmit} className="flex-1 overflow-y-auto p-6 space-y-6">
              {/* Account Role */}
              {!isEditing && (
                <div className="space-y-2">
                  <Label>Registration Role</Label>
                  <div className="grid grid-cols-3 gap-2">
                    {[
                      { role: 'student', label: 'Student', icon: GraduationCap },
                      { role: 'professor', label: 'Professor', icon: User },
                      { role: 'admin', label: 'Administrator', icon: Shield },
                    ].map((item) => {
                      const Icon = item.icon
                      const selected = form.role === item.role
                      return (
                        <button
                          key={item.role}
                          type="button"
                          onClick={() => updateField('role', item.role)}
                          className={`flex items-center justify-center gap-2 rounded-lg border py-3 text-sm font-semibold transition-all ${
                            selected
                              ? 'border-blue-600 bg-blue-50 text-blue-700 shadow-sm'
                              : 'border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700'
                          }`}
                        >
                          <Icon className="h-4 w-4" />
                          {item.label}
                        </button>
                      )
                    })}
                  </div>
                </div>
              )}

              {/* Core Information */}
              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-1.5">
                  <Label htmlFor="name">Full Name</Label>
                  <Input id="name" placeholder="John Doe" value={form.name} onChange={(e) => updateField('name', e.target.value)} required />
                  {formErrors.name?.map((e) => <p key={e} className="text-xs text-red-600 mt-1">{e}</p>)}
                </div>

                <div className="space-y-1.5">
                  <Label htmlFor="email">Email Address</Label>
                  <Input id="email" type="email" placeholder="johndoe@university.edu" value={form.email} onChange={(e) => updateField('email', e.target.value)} required />
                  {formErrors.email?.map((e) => <p key={e} className="text-xs text-red-600 mt-1">{e}</p>)}
                </div>

                <div className="space-y-1.5">
                  <Label htmlFor="password">{isEditing ? 'New Password (Optional)' : 'Password'}</Label>
                  <Input id="password" type="password" placeholder={isEditing ? 'Leave blank to retain current' : 'At least 8 characters'} value={form.password} onChange={(e) => updateField('password', e.target.value)} required={!isEditing} />
                  {formErrors.password?.map((e) => <p key={e} className="text-xs text-red-600 mt-1">{e}</p>)}
                </div>
              </div>

              {/* Faculty and Department (Shared) */}
              <div className="grid gap-4 sm:grid-cols-2 border-t border-slate-100 pt-4">
                <div className="space-y-1.5">
                  <Label htmlFor="faculty_id">Faculty Selection</Label>
                  <select
                    id="faculty_id"
                    className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-blue-500 focus:outline-none"
                    value={form.faculty_id}
                    onChange={(e) => updateField('faculty_id', e.target.value)}
                  >
                    <option value="">No Faculty</option>
                    {options.faculties.map((f) => (
                      <option key={f.id} value={f.id}>{f.name}</option>
                    ))}
                  </select>
                  {formErrors.faculty_id?.map((e) => <p key={e} className="text-xs text-red-600 mt-1">{e}</p>)}
                </div>

                <div className="space-y-1.5">
                  <Label htmlFor="department_id">Department Selection</Label>
                  <select
                    id="department_id"
                    className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-blue-500 focus:outline-none disabled:bg-slate-50 disabled:text-slate-400"
                    value={form.department_id}
                    onChange={(e) => updateField('department_id', e.target.value)}
                    disabled={!form.faculty_id}
                  >
                    <option value="">No Department</option>
                    {filteredDepartments.map((d) => (
                      <option key={d.id} value={d.id}>{d.name}</option>
                    ))}
                  </select>
                  {formErrors.department_id?.map((e) => <p key={e} className="text-xs text-red-600 mt-1">{e}</p>)}
                </div>
              </div>

              {/* STUDENT SPECIFIC FIELDS */}
              {form.role === 'student' && (
                <div className="space-y-4 border-t border-slate-100 pt-4">
                  <h4 className="text-sm font-bold text-slate-800 uppercase tracking-wider">Student Profile Details</h4>
                  
                  <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-1.5">
                      <Label htmlFor="program_id">Study Program</Label>
                      <select
                        id="program_id"
                        className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-blue-500 focus:outline-none disabled:bg-slate-50 disabled:text-slate-400"
                        value={form.program_id}
                        onChange={(e) => updateField('program_id', e.target.value)}
                        disabled={!form.department_id}
                        required
                      >
                        <option value="">Select Program</option>
                        {filteredPrograms.map((p) => (
                          <option key={p.id} value={p.id}>{p.name}</option>
                        ))}
                      </select>
                      {formErrors.program_id?.map((e) => <p key={e} className="text-xs text-red-600 mt-1">{e}</p>)}
                    </div>

                    <div className="space-y-1.5">
                      <Label htmlFor="year_of_study">Year of Study</Label>
                      <select
                        id="year_of_study"
                        className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-blue-500 focus:outline-none"
                        value={form.year_of_study}
                        onChange={(e) => updateField('year_of_study', e.target.value)}
                        required
                      >
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                        <option value="Master Year 1">Master Year 1</option>
                        <option value="Master Year 2">Master Year 2</option>
                        <option value="PhD Candidate">PhD Candidate</option>
                      </select>
                      {formErrors.year_of_study?.map((e) => <p key={e} className="text-xs text-red-600 mt-1">{e}</p>)}
                    </div>

                    <div className="space-y-1.5">
                      <Label htmlFor="personal_number">National Personal ID Number</Label>
                      <Input id="personal_number" placeholder="ID Number / Personal Number" value={form.personal_number} onChange={(e) => updateField('personal_number', e.target.value)} required />
                      {formErrors.personal_number?.map((e) => <p key={e} className="text-xs text-red-600 mt-1">{e}</p>)}
                    </div>

                    <div className="space-y-1.5">
                      <Label htmlFor="phone">Phone Number</Label>
                      <Input id="phone" placeholder="+1234567890" value={form.phone} onChange={(e) => updateField('phone', e.target.value)} />
                      {formErrors.phone?.map((e) => <p key={e} className="text-xs text-red-600 mt-1">{e}</p>)}
                    </div>

                    <div className="space-y-1.5">
                      <Label htmlFor="date_of_birth">Date of Birth</Label>
                      <Input id="date_of_birth" type="date" value={form.date_of_birth} onChange={(e) => updateField('date_of_birth', e.target.value)} />
                      {formErrors.date_of_birth?.map((e) => <p key={e} className="text-xs text-red-600 mt-1">{e}</p>)}
                    </div>

                    <div className="space-y-1.5">
                      <Label htmlFor="gender">Gender</Label>
                      <select
                        id="gender"
                        className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-blue-500 focus:outline-none"
                        value={form.gender}
                        onChange={(e) => updateField('gender', e.target.value)}
                      >
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                      </select>
                      {formErrors.gender?.map((e) => <p key={e} className="text-xs text-red-600 mt-1">{e}</p>)}
                    </div>

                    <div className="space-y-1.5">
                      <Label htmlFor="nationality">Nationality</Label>
                      <Input id="nationality" placeholder="e.g. American, Kosovar" value={form.nationality} onChange={(e) => updateField('nationality', e.target.value)} />
                      {formErrors.nationality?.map((e) => <p key={e} className="text-xs text-red-600 mt-1">{e}</p>)}
                    </div>
                  </div>

                  <div className="space-y-1.5">
                    <Label htmlFor="address">Address</Label>
                    <Textarea id="address" placeholder="Residential Address" value={form.address} onChange={(e) => updateField('address', e.target.value)} rows={2} />
                    {formErrors.address?.map((e) => <p key={e} className="text-xs text-red-600 mt-1">{e}</p>)}
                  </div>

                  {/* Student Emergency Contact */}
                  <div className="space-y-3 bg-slate-50 p-4 rounded-lg border border-slate-100">
                    <h5 className="text-xs font-bold text-slate-700 uppercase tracking-wider">Primary Emergency Contact</h5>
                    <div className="grid gap-3 sm:grid-cols-3">
                      <div className="space-y-1">
                        <Label htmlFor="emergency_contact_name" className="text-xs">Contact Name</Label>
                        <Input id="emergency_contact_name" placeholder="Full Name" value={form.emergency_contact_name} onChange={(e) => updateField('emergency_contact_name', e.target.value)} className="h-9 text-xs" />
                      </div>
                      <div className="space-y-1">
                        <Label htmlFor="emergency_contact_relationship" className="text-xs">Relationship</Label>
                        <select
                          id="emergency_contact_relationship"
                          className="flex h-9 w-full rounded-md border border-slate-200 bg-white px-2 py-1 text-xs text-slate-800 shadow-sm focus:border-blue-500 focus:outline-none"
                          value={form.emergency_contact_relationship}
                          onChange={(e) => updateField('emergency_contact_relationship', e.target.value)}
                        >
                          <option value="Parent">Parent</option>
                          <option value="Guardian">Guardian</option>
                          <option value="Spouse">Spouse</option>
                          <option value="Sibling">Sibling</option>
                          <option value="Other">Other</option>
                        </select>
                      </div>
                      <div className="space-y-1">
                        <Label htmlFor="emergency_contact_phone" className="text-xs">Contact Phone</Label>
                        <Input id="emergency_contact_phone" placeholder="+1234567890" value={form.emergency_contact_phone} onChange={(e) => updateField('emergency_contact_phone', e.target.value)} className="h-9 text-xs" />
                      </div>
                    </div>
                  </div>
                </div>
              )}

              {/* PROFESSOR SPECIFIC FIELDS */}
              {form.role === 'professor' && (
                <div className="space-y-4 border-t border-slate-100 pt-4">
                  <h4 className="text-sm font-bold text-slate-800 uppercase tracking-wider">Faculty Member Details</h4>

                  <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-1.5">
                      <Label htmlFor="title">Professional Title</Label>
                      <select
                        id="title"
                        className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-blue-500 focus:outline-none"
                        value={form.title}
                        onChange={(e) => updateField('title', e.target.value)}
                        required
                      >
                        <option value="Professor">Professor</option>
                        <option value="Associate Professor">Associate Professor</option>
                        <option value="Assistant Professor">Assistant Professor</option>
                        <option value="Lecturer">Lecturer</option>
                        <option value="Teaching Assistant">Teaching Assistant</option>
                      </select>
                      {formErrors.title?.map((e) => <p key={e} className="text-xs text-red-600 mt-1">{e}</p>)}
                    </div>

                    <div className="space-y-1.5">
                      <Label htmlFor="office">Office Location</Label>
                      <Input id="office" placeholder="e.g. Building A, Room 102" value={form.office} onChange={(e) => updateField('office', e.target.value)} />
                      {formErrors.office?.map((e) => <p key={e} className="text-xs text-red-600 mt-1">{e}</p>)}
                    </div>

                    <div className="space-y-1.5">
                      <Label htmlFor="office_hours">Office Hours</Label>
                      <Input id="office_hours" placeholder="e.g. Mon/Wed 14:00 - 16:00" value={form.office_hours} onChange={(e) => updateField('office_hours', e.target.value)} />
                      {formErrors.office_hours?.map((e) => <p key={e} className="text-xs text-red-600 mt-1">{e}</p>)}
                    </div>

                    <div className="space-y-1.5">
                      <Label htmlFor="consultation">Consultation Slots</Label>
                      <Input id="consultation" placeholder="e.g. By email booking" value={form.consultation} onChange={(e) => updateField('consultation', e.target.value)} />
                      {formErrors.consultation?.map((e) => <p key={e} className="text-xs text-red-600 mt-1">{e}</p>)}
                    </div>
                  </div>
                </div>
              )}
            </form>

            <div className="flex justify-end gap-2 border-t border-slate-100 px-6 py-4 bg-slate-50 rounded-b-xl">
              <Button type="button" variant="secondary" onClick={() => setModalOpen(false)}>Cancel</Button>
              <Button onClick={handleSubmit} disabled={submitting} className="min-w-[100px]">
                {submitting ? 'Saving...' : 'Save User'}
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
