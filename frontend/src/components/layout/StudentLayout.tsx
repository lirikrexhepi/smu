import { DashboardLayout } from '@/components/layout/DashboardLayout'
import { studentNavItems } from '@/components/layout/navigation'

export function StudentLayout() {
  return (
    <DashboardLayout
      role="student"
      portalLabel="Student Portal"
      userLabel="Alex Morgan"
      navItems={studentNavItems}
    />
  )
}
