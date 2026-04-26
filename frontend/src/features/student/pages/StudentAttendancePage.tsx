import { FoundationPlaceholder } from '@/components/shared/FoundationPlaceholder'
import { PageHeader } from '@/components/shared/PageHeader'

export function StudentAttendancePage() {
  return (
    <>
      <PageHeader title="Attendance" description="Student attendance route placeholder." />
      <FoundationPlaceholder
        title="Attendance Foundation"
        description="Attendance calendar and history will be added later."
      />
    </>
  )
}
