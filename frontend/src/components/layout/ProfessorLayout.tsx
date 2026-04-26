import { DashboardLayout } from '@/components/layout/DashboardLayout'
import { professorNavItems } from '@/components/layout/navigation'

export function ProfessorLayout() {
  return (
    <DashboardLayout
      role="professor"
      portalLabel="Professor Portal"
      userLabel="Dr. Evelyn Carter"
      navItems={professorNavItems}
    />
  )
}
