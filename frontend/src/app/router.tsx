import { createBrowserRouter, Navigate } from 'react-router-dom'

import { AdminLayout } from '@/components/layout/AdminLayout'
import { AuthLayout } from '@/components/layout/AuthLayout'
import { ProfessorLayout } from '@/components/layout/ProfessorLayout'
import { StudentLayout } from '@/components/layout/StudentLayout'
import { AdminDashboardPage } from '@/features/admin/pages/AdminDashboardPage'
import { ProtectedRoute } from '@/features/auth/components/ProtectedRoute'
import { LoginPage } from '@/features/auth/pages/LoginPage'
import { ProfessorAttendancePage } from '@/features/professor/pages/ProfessorAttendancePage'
import { ProfessorCoursesPage } from '@/features/professor/pages/ProfessorCoursesPage'
import { ProfessorDashboardPage } from '@/features/professor/pages/ProfessorDashboardPage'
import { ProfessorGradebookPage } from '@/features/professor/pages/ProfessorGradebookPage'
import { StudentAttendancePage } from '@/features/student/pages/StudentAttendancePage'
import { StudentCommunicationPage } from '@/features/student/pages/StudentCommunicationPage'
import { StudentCourseDetailPage } from '@/features/student/pages/StudentCourseDetailPage'
import { StudentCoursesPage } from '@/features/student/pages/StudentCoursesPage'
import { StudentDashboardPage } from '@/features/student/pages/StudentDashboardPage'
import { StudentGradesPage } from '@/features/student/pages/StudentGradesPage'
import { StudentHelpPage } from '@/features/student/pages/StudentHelpPage'
import { StudentProfilePage } from '@/features/student/pages/StudentProfilePage'

export const router = createBrowserRouter([
  {
    path: '/',
    element: <Navigate to="/login" replace />,
  },
  {
    element: <AuthLayout />,
    children: [
      {
        path: '/login',
        element: <LoginPage />,
      },
    ],
  },
  {
    path: '/student',
    element: (
      <ProtectedRoute role="student">
        <StudentLayout />
      </ProtectedRoute>
    ),
    children: [
      {
        index: true,
        element: <Navigate to="/student/dashboard" replace />,
      },
      {
        path: 'dashboard',
        element: <StudentDashboardPage />,
      },
      {
        path: 'courses',
        element: <StudentCoursesPage />,
      },
      {
        path: 'courses/:courseId',
        element: <StudentCourseDetailPage />,
      },
      {
        path: 'attendance',
        element: <StudentAttendancePage />,
      },
      {
        path: 'grades',
        element: <StudentGradesPage />,
      },
      {
        path: 'profile',
        element: <StudentProfilePage />,
      },
      {
        path: 'communication',
        element: <StudentCommunicationPage />,
      },
      {
        path: 'help',
        element: <StudentHelpPage />,
      },
    ],
  },
  {
    path: '/professor',
    element: (
      <ProtectedRoute role="professor">
        <ProfessorLayout />
      </ProtectedRoute>
    ),
    children: [
      {
        index: true,
        element: <Navigate to="/professor/dashboard" replace />,
      },
      {
        path: 'dashboard',
        element: <ProfessorDashboardPage />,
      },
      {
        path: 'courses',
        element: <ProfessorCoursesPage />,
      },
      {
        path: 'attendance',
        element: <ProfessorAttendancePage />,
      },
      {
        path: 'gradebook',
        element: <ProfessorGradebookPage />,
      },
    ],
  },
  {
    path: '/admin',
    element: (
      <ProtectedRoute role="admin">
        <AdminLayout />
      </ProtectedRoute>
    ),
    children: [
      {
        index: true,
        element: <Navigate to="/admin/dashboard" replace />,
      },
      {
        path: 'dashboard',
        element: <AdminDashboardPage />,
      },
    ],
  },
  {
    path: '*',
    element: <Navigate to="/login" replace />,
  },
])
