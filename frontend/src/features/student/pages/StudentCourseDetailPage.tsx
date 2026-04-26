import { useParams } from 'react-router-dom'

import { FoundationPlaceholder } from '@/components/shared/FoundationPlaceholder'
import { PageHeader } from '@/components/shared/PageHeader'

export function StudentCourseDetailPage() {
  const { courseId } = useParams()

  return (
    <>
      <PageHeader title="Course Detail" description={`Course route parameter: ${courseId ?? 'missing'}.`} />
      <FoundationPlaceholder title="Course Detail Foundation" description="Course tabs will be added later." />
    </>
  )
}
