import { DashboardLayout } from '@/components/layout/DashboardLayout'
import { adminNavItems } from '@/components/layout/navigation'

export function AdminLayout() {
  return (
    <DashboardLayout
      role="admin"
      portalLabel="Admin Portal"
      userLabel="Admin User"
      navItems={adminNavItems}
    />
  )
}
