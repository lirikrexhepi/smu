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
