export type StudentDashboardMetric = {
  label: string
  value: string
  tone: 'blue' | 'green' | 'orange' | 'purple'
}

export type StudentDashboardSummary = {
  studentName: string
  semester: string
  metrics: StudentDashboardMetric[]
}

export type StudentEmergencyContact = {
  name: string
  relationship: string
  phone: string
}

export type StudentProfile = {
  studentId: string
  fullName: string
  initials: string
  avatarUrl: string | null
  status: string
  studentStatusLabel: string
  faculty: string
  department: string
  program: string
  yearOfStudy: string
  semester: string
  academicYear: string
  currentGpa: string
  creditsEarned: string
  creditsRequired: string
  academicStanding: string
  email: string
  phone: string
  address: string
  dateOfBirth: string
  gender: string
  nationality: string
  personalNumber: string
  emergencyContact: StudentEmergencyContact
  updatedAt: string | null
}

export type StudentProfileUpdate = {
  fullName: string
  email: string
  phone: string
  address: string
  dateOfBirth: string
  gender: string
  nationality: string
  personalNumber: string
  emergencyContactName: string
  emergencyContactRelationship: string
  emergencyContactPhone: string
}
