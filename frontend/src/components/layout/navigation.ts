import {
  BookOpen,
  CalendarCheck,
  ClipboardCheck,
  GraduationCap,
  LayoutDashboard,
  Shield,
  UserRound,
  Users,
} from 'lucide-react'
import type { LucideIcon } from 'lucide-react'

export type NavItem = {
  label: string
  to: string
  icon: LucideIcon
}

export const studentNavItems: NavItem[] = [
  { label: 'Dashboard', to: '/student/dashboard', icon: LayoutDashboard },
  { label: 'Courses', to: '/student/courses', icon: BookOpen },
  { label: 'Attendance', to: '/student/attendance', icon: CalendarCheck },
  { label: 'Grades & Transcript', to: '/student/grades', icon: ClipboardCheck },
  { label: 'Profile', to: '/student/profile', icon: UserRound },
]

export const professorNavItems: NavItem[] = [
  { label: 'Dashboard', to: '/professor/dashboard', icon: LayoutDashboard },
  { label: 'My Courses', to: '/professor/courses', icon: BookOpen },
  { label: 'Attendance', to: '/professor/attendance', icon: CalendarCheck },
  { label: 'Gradebook', to: '/professor/gradebook', icon: ClipboardCheck },
]

export const adminNavItems: NavItem[] = [
  { label: 'Dashboard', to: '/admin/dashboard', icon: LayoutDashboard },
  { label: 'Users', to: '/admin/dashboard', icon: Users },
  { label: 'System', to: '/admin/dashboard', icon: Shield },
]

export const roleIcons = {
  student: GraduationCap,
  professor: BookOpen,
  admin: Shield,
}
