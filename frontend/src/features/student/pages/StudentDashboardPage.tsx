import { useEffect, useState } from 'react'

import { PageHeader } from '@/components/shared/PageHeader'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { getMockStudentDashboard } from '@/lib/api/student'
import type { StudentDashboardSummary } from '@/types/student'

export function StudentDashboardPage() {
  const [summary, setSummary] = useState<StudentDashboardSummary | null>(null)
  const [status, setStatus] = useState('Waiting for API response')

  useEffect(() => {
    getMockStudentDashboard()
      .then((response) => {
        setSummary(response.data)
        setStatus(response.message ?? 'Connected to backend mock API')
      })
      .catch((error: unknown) => {
        setStatus(error instanceof Error ? error.message : 'Unable to reach backend mock API')
      })
  }, [])

  return (
    <>
      <PageHeader
        title="Student Dashboard"
        description="Route and API foundation for the student portal."
      />

      <Card>
        <CardHeader>
          <div className="flex items-center justify-between gap-4">
            <CardTitle>Mock API Connection</CardTitle>
            <Badge variant={summary ? 'success' : 'warning'}>{summary ? 'Connected' : 'Pending'}</Badge>
          </div>
        </CardHeader>
        <CardContent>
          <p className="text-sm text-slate-500">{status}</p>
          {summary ? (
            <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
              {summary.metrics.map((metric) => (
                <div key={metric.label} className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                  <p className="text-sm text-slate-500">{metric.label}</p>
                  <p className="mt-2 text-xl font-semibold text-slate-950">{metric.value}</p>
                </div>
              ))}
            </div>
          ) : null}
        </CardContent>
      </Card>
    </>
  )
}
