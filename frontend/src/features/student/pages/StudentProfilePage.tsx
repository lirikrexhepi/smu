import type { LucideIcon } from 'lucide-react'
import {
  Award,
  BookOpenCheck,
  Building2,
  CalendarDays,
  Camera,
  Edit3,
  GraduationCap,
  Mail,
  MapPin,
  Phone,
  Save,
  ShieldCheck,
  TrendingUp,
  UserRound,
  X,
} from 'lucide-react'
import { useEffect, useMemo, useRef, useState } from 'react'
import type { ReactNode } from 'react'

import { PageHeader } from '@/components/shared/PageHeader'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { apiAssetUrl } from '@/lib/api/client'
import { getStudentProfile, updateStudentProfile, uploadStudentProfileAvatar } from '@/lib/api/student'
import { getStoredAuthUser, storeAuthUser } from '@/lib/auth/session'
import { cn } from '@/lib/utils'
import type { StudentProfile, StudentProfileUpdate } from '@/types/student'

type ProfileTab = 'personal' | 'academic' | 'contact' | 'emergency'

const tabs: Array<{ id: ProfileTab; label: string }> = [
  { id: 'personal', label: 'Personal Info' },
  { id: 'academic', label: 'Academic Info' },
  { id: 'contact', label: 'Contact Info' },
  { id: 'emergency', label: 'Emergency Contact' },
]

export function StudentProfilePage() {
  const storedUser = getStoredAuthUser()
  const studentKey = storedUser?.role === 'student' ? storedUser.institutionId : null
  const [profile, setProfile] = useState<StudentProfile | null>(null)
  const [form, setForm] = useState<StudentProfileUpdate | null>(null)
  const [activeTab, setActiveTab] = useState<ProfileTab>('personal')
  const [isEditing, setIsEditing] = useState(false)
  const [isSaving, setIsSaving] = useState(false)
  const [isUploadingAvatar, setIsUploadingAvatar] = useState(false)
  const [statusMessage, setStatusMessage] = useState('Loading profile')
  const [errorMessage, setErrorMessage] = useState<string | null>(null)
  const avatarInputRef = useRef<HTMLInputElement | null>(null)

  useEffect(() => {
    if (!studentKey) {
      setProfile(null)
      setForm(null)
      setErrorMessage('Login as a student to load profile data')
      setStatusMessage('Profile unavailable')
      return
    }

    getStudentProfile(studentKey)
      .then((response) => {
        setProfile(response.data)
        setForm(formFromProfile(response.data))
        syncStoredStudent(response.data)
        setStatusMessage(response.message ?? 'Profile loaded')
      })
      .catch((error: unknown) => {
        setErrorMessage(error instanceof Error ? error.message : 'Unable to load student profile')
        setStatusMessage('Profile unavailable')
      })
  }, [studentKey])

  const emergencySummary = useMemo(() => {
    if (!profile) {
      return ''
    }

    return `${profile.emergencyContact.name} (${profile.emergencyContact.relationship})`
  }, [profile])

  function updateField(field: keyof StudentProfileUpdate, value: string) {
    setForm((current) => (current ? { ...current, [field]: value } : current))
  }

  function startEditing() {
    if (!profile) {
      return
    }

    setForm(formFromProfile(profile))
    setIsEditing(true)
    setErrorMessage(null)
  }

  function cancelEditing() {
    if (profile) {
      setForm(formFromProfile(profile))
    }

    setIsEditing(false)
    setErrorMessage(null)
  }

  async function saveProfile() {
    if (!form || !studentKey) {
      return
    }

    setIsSaving(true)
    setErrorMessage(null)

    try {
      const response = await updateStudentProfile(form, studentKey)
      setProfile(response.data)
      setForm(formFromProfile(response.data))
      setIsEditing(false)
      setStatusMessage(response.message ?? 'Profile updated')
      syncStoredStudent(response.data)
    } catch (error: unknown) {
      setErrorMessage(error instanceof Error ? error.message : 'Unable to update student profile')
    } finally {
      setIsSaving(false)
    }
  }

  async function uploadAvatar(file: File | undefined) {
    if (!file || !studentKey) {
      return
    }

    setIsUploadingAvatar(true)
    setErrorMessage(null)

    try {
      const response = await uploadStudentProfileAvatar(file, studentKey)
      setProfile(response.data)
      setForm(formFromProfile(response.data))
      setStatusMessage(response.message ?? 'Profile image updated')
      syncStoredStudent(response.data)
    } catch (error: unknown) {
      setErrorMessage(error instanceof Error ? error.message : 'Unable to upload profile image')
    } finally {
      setIsUploadingAvatar(false)

      if (avatarInputRef.current) {
        avatarInputRef.current.value = ''
      }
    }
  }

  function syncStoredStudent(nextProfile: StudentProfile) {
    if (storedUser?.role !== 'student') {
      return
    }

    storeAuthUser({
      ...storedUser,
      name: nextProfile.fullName,
      email: nextProfile.email,
      faculty: nextProfile.faculty,
      department: nextProfile.department,
      avatarUrl: nextProfile.avatarUrl,
    })
  }

  if (!profile || !form) {
    return (
      <>
        <PageHeader title="My Profile" description="Manage your personal and academic information" />
        <Card>
          <CardContent className="pt-5">
            <p className="text-sm text-slate-500">{errorMessage ?? statusMessage}</p>
          </CardContent>
        </Card>
      </>
    )
  }

  const avatarUrl = apiAssetUrl(profile.avatarUrl)

  return (
    <>
      <PageHeader title="My Profile" description="Manage your personal and academic information" />

      <div className="mb-5 overflow-x-auto border-b border-slate-200">
        <div className="flex min-w-max gap-4">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              type="button"
              className={cn(
                'border-b-2 px-5 py-3 text-sm font-medium transition-colors',
                activeTab === tab.id
                  ? 'border-blue-600 text-blue-600'
                  : 'border-transparent text-slate-500 hover:text-slate-950',
              )}
              onClick={() => setActiveTab(tab.id)}
            >
              {tab.label}
            </button>
          ))}
        </div>
      </div>

      <Card className="mb-5">
        <CardContent className="p-5 lg:p-6">
          <div className="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
            <div className="flex flex-col gap-5 sm:flex-row sm:items-center">
              <div className="relative h-32 w-32 shrink-0 rounded-full bg-green-500 text-white shadow-sm">
                {avatarUrl ? (
                  <img
                    src={avatarUrl}
                    alt=""
                    className="h-full w-full rounded-full object-cover"
                  />
                ) : (
                  <div className="flex h-full w-full items-center justify-center rounded-full bg-gradient-to-br from-green-400 to-green-600 text-4xl font-medium">
                    {profile.initials}
                  </div>
                )}
                <input
                  ref={avatarInputRef}
                  type="file"
                  accept="image/png,image/jpeg,image/webp"
                  className="hidden"
                  onChange={(event) => void uploadAvatar(event.target.files?.[0])}
                />
                <button
                  type="button"
                  aria-label="Upload profile photo"
                  disabled={isUploadingAvatar}
                  className="absolute bottom-1 right-1 flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                  onClick={() => avatarInputRef.current?.click()}
                >
                  <Camera className="h-5 w-5" />
                </button>
              </div>

              <div className="min-w-0">
                {isEditing ? (
                  <div className="max-w-sm">
                    <Label htmlFor="fullName">Full Name</Label>
                    <Input
                      id="fullName"
                      className="mt-1"
                      value={form.fullName}
                      onChange={(event) => updateField('fullName', event.target.value)}
                    />
                  </div>
                ) : (
                  <h2 className="text-2xl font-semibold text-slate-950">{profile.fullName}</h2>
                )}
                <Badge variant="success" className="mt-3 text-sm">
                  {profile.studentStatusLabel}
                </Badge>
                <p className="mt-4 text-sm font-medium text-slate-500">Student ID</p>
                <p className="mt-1 text-lg font-medium text-slate-950">{profile.studentId}</p>
              </div>
            </div>

            <div className="grid flex-1 gap-5 md:grid-cols-2 xl:max-w-3xl xl:grid-cols-4">
              <ProfileStat icon={Building2} label="Department" value={profile.department} />
              <ProfileStat icon={GraduationCap} label="Program" value={profile.program} />
              <ProfileStat icon={CalendarDays} label="Year of Study" value={profile.yearOfStudy} />
              <ProfileStat icon={ShieldCheck} label="Status" value={profile.status} valueClassName="text-green-700" />
            </div>

            <div className="flex shrink-0 gap-2">
              {isEditing ? (
                <>
                  <Button type="button" variant="secondary" onClick={cancelEditing} disabled={isSaving}>
                    <X className="h-4 w-4" />
                    Cancel
                  </Button>
                  <Button type="button" onClick={saveProfile} disabled={isSaving}>
                    <Save className="h-4 w-4" />
                    {isSaving ? 'Saving' : 'Save Profile'}
                  </Button>
                </>
              ) : (
                <Button type="button" onClick={startEditing}>
                  <Edit3 className="h-4 w-4" />
                  Edit Profile
                </Button>
              )}
            </div>
          </div>
        </CardContent>
      </Card>

      {errorMessage ? (
        <div className="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {errorMessage}
        </div>
      ) : null}

      <div className="grid gap-5 xl:grid-cols-[0.95fr_1fr]">
        <Card>
          <CardHeader>
            <CardTitle>{activeTab === 'academic' ? 'Academic Details' : 'Academic Summary'}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {activeTab === 'academic' ? (
              <>
                <SummaryRow icon={Building2} iconClassName="bg-blue-50 text-blue-600" label="Faculty" value={profile.faculty} />
                <SummaryRow icon={GraduationCap} iconClassName="bg-purple-50 text-purple-600" label="Department" value={profile.department} />
                <SummaryRow icon={CalendarDays} iconClassName="bg-orange-50 text-orange-600" label="Current Semester" value={`${profile.semester} · ${profile.academicYear}`} />
              </>
            ) : (
              <>
                <SummaryRow icon={TrendingUp} iconClassName="bg-purple-50 text-purple-600" label="Current GPA" helper="Out of 4.00" value={profile.currentGpa} strong />
                <SummaryRow icon={BookOpenCheck} iconClassName="bg-orange-50 text-orange-600" label="Total Credits Earned" helper={`Out of ${profile.creditsRequired}`} value={profile.creditsEarned} strong />
                <SummaryRow icon={Award} iconClassName="bg-green-50 text-green-700" label="Academic Standing" value={profile.academicStanding} valueClassName="text-green-700" />
              </>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>{cardTitleForTab(activeTab)}</CardTitle>
          </CardHeader>
          <CardContent>
            {activeTab === 'personal' ? (
              <div className="grid gap-4 sm:grid-cols-2">
                <EditField label="Date of Birth" field="dateOfBirth" value={form.dateOfBirth} isEditing={isEditing} onChange={updateField} />
                <EditField label="Gender" field="gender" value={form.gender} isEditing={isEditing} onChange={updateField} />
                <EditField label="Nationality" field="nationality" value={form.nationality} isEditing={isEditing} onChange={updateField} />
                <EditField label="Personal Number" field="personalNumber" value={form.personalNumber} isEditing={isEditing} onChange={updateField} />
              </div>
            ) : null}

            {activeTab === 'academic' ? (
              <div className="grid gap-4 sm:grid-cols-2">
                <ReadOnlyField label="Student ID" value={profile.studentId} />
                <ReadOnlyField label="Program" value={profile.program} />
                <ReadOnlyField label="Year of Study" value={profile.yearOfStudy} />
                <ReadOnlyField label="Status" value={profile.status} valueClassName="text-green-700" />
              </div>
            ) : null}

            {activeTab === 'contact' ? (
              <div className="space-y-4">
                <ContactRow icon={Mail} label="Email">
                  <EditField field="email" value={form.email} isEditing={isEditing} onChange={updateField} />
                </ContactRow>
                <ContactRow icon={Phone} iconClassName="bg-green-50 text-green-700" label="Phone">
                  <EditField field="phone" value={form.phone} isEditing={isEditing} onChange={updateField} />
                </ContactRow>
                <ContactRow icon={MapPin} iconClassName="bg-purple-50 text-purple-600" label="Address">
                  {isEditing ? (
                    <Textarea value={form.address} onChange={(event) => updateField('address', event.target.value)} />
                  ) : (
                    <p className="text-sm text-slate-600">{profile.address}</p>
                  )}
                </ContactRow>
              </div>
            ) : null}

            {activeTab === 'emergency' ? (
              <div className="space-y-4">
                <ContactRow icon={UserRound} iconClassName="bg-orange-50 text-orange-600" label="Emergency Contact">
                  <EditField field="emergencyContactName" value={form.emergencyContactName} isEditing={isEditing} onChange={updateField} />
                </ContactRow>
                <EditField label="Relationship" field="emergencyContactRelationship" value={form.emergencyContactRelationship} isEditing={isEditing} onChange={updateField} />
                <EditField label="Phone" field="emergencyContactPhone" value={form.emergencyContactPhone} isEditing={isEditing} onChange={updateField} />
              </div>
            ) : null}
          </CardContent>
        </Card>
      </div>

      <p className="mt-4 text-xs text-slate-500">
        {isUploadingAvatar ? 'Uploading profile image' : statusMessage}
        {activeTab !== 'emergency' && emergencySummary ? ` · Emergency contact: ${emergencySummary}` : ''}
      </p>
    </>
  )
}

function formFromProfile(profile: StudentProfile): StudentProfileUpdate {
  return {
    fullName: profile.fullName,
    email: profile.email,
    phone: profile.phone,
    address: profile.address,
    dateOfBirth: profile.dateOfBirth,
    gender: profile.gender,
    nationality: profile.nationality,
    personalNumber: profile.personalNumber,
    emergencyContactName: profile.emergencyContact.name,
    emergencyContactRelationship: profile.emergencyContact.relationship,
    emergencyContactPhone: profile.emergencyContact.phone,
  }
}

function cardTitleForTab(tab: ProfileTab) {
  if (tab === 'personal') {
    return 'Personal Information'
  }

  if (tab === 'contact') {
    return 'Contact & Emergency'
  }

  if (tab === 'emergency') {
    return 'Emergency Contact'
  }

  return 'Enrollment Information'
}

function ProfileStat({
  icon: Icon,
  label,
  value,
  valueClassName,
}: {
  icon: LucideIcon
  label: string
  value: string
  valueClassName?: string
}) {
  return (
    <div className="flex items-start gap-3 border-slate-200 md:border-l md:pl-5">
      <Icon className="mt-1 h-5 w-5 shrink-0 text-slate-500" />
      <div>
        <p className="text-sm text-slate-500">{label}</p>
        <p className={cn('mt-1 text-sm font-medium text-slate-950', valueClassName)}>{value}</p>
      </div>
    </div>
  )
}

function SummaryRow({
  icon: Icon,
  iconClassName,
  label,
  helper,
  value,
  valueClassName,
  strong = false,
}: {
  icon: LucideIcon
  iconClassName: string
  label: string
  helper?: string
  value: string
  valueClassName?: string
  strong?: boolean
}) {
  return (
    <div className="flex items-center gap-4 border-b border-slate-100 pb-4 last:border-b-0 last:pb-0">
      <div className={cn('flex h-11 w-11 shrink-0 items-center justify-center rounded-lg', iconClassName)}>
        <Icon className="h-5 w-5" />
      </div>
      <div className="min-w-0 flex-1">
        <p className="text-sm font-medium text-slate-700">{label}</p>
        {helper ? <p className="text-sm text-slate-500">{helper}</p> : null}
      </div>
      <p className={cn(strong ? 'text-xl font-semibold text-slate-950' : 'text-sm font-semibold text-slate-950', valueClassName)}>
        {value}
      </p>
    </div>
  )
}

function ContactRow({
  icon: Icon,
  iconClassName = 'bg-blue-50 text-blue-600',
  label,
  children,
}: {
  icon: LucideIcon
  iconClassName?: string
  label: string
  children: ReactNode
}) {
  return (
    <div className="flex gap-4 border-b border-slate-100 pb-4 last:border-b-0 last:pb-0">
      <div className={cn('flex h-11 w-11 shrink-0 items-center justify-center rounded-lg', iconClassName)}>
        <Icon className="h-5 w-5" />
      </div>
      <div className="min-w-0 flex-1">
        <p className="text-sm font-medium text-slate-700">{label}</p>
        <div className="mt-1">{children}</div>
      </div>
    </div>
  )
}

function EditField({
  label,
  field,
  value,
  isEditing,
  onChange,
}: {
  label?: string
  field: keyof StudentProfileUpdate
  value: string
  isEditing: boolean
  onChange: (field: keyof StudentProfileUpdate, value: string) => void
}) {
  const inputId = `profile-${field}`

  if (!isEditing) {
    return <ReadOnlyField label={label} value={value} />
  }

  return (
    <div>
      {label ? <Label htmlFor={inputId}>{label}</Label> : null}
      <Input
        id={inputId}
        className={label ? 'mt-1' : undefined}
        value={value}
        onChange={(event) => onChange(field, event.target.value)}
      />
    </div>
  )
}

function ReadOnlyField({ label, value, valueClassName }: { label?: string; value: string; valueClassName?: string }) {
  return (
    <div>
      {label ? <p className="text-xs font-medium text-slate-500">{label}</p> : null}
      <p className={cn('text-sm text-slate-700', label && 'mt-1', valueClassName)}>{value}</p>
    </div>
  )
}
