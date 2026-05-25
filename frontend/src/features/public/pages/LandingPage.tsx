import {
  ArrowRight,
  BarChart3,
  BookOpen,
  CalendarCheck,
  CheckCircle2,
  ClipboardList,
  GraduationCap,
  LayoutDashboard,
  LockKeyhole,
  Menu,
  MessageSquare,
  ShieldCheck,
  UserCog,
  Users,
  X,
} from 'lucide-react'
import { useState } from 'react'
import { Link } from 'react-router-dom'

import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'

const navLinks = [
  { label: 'Features', href: '#features' },
  { label: 'Portals', href: '#portals' },
  { label: 'Workflow', href: '#workflow' },
  { label: 'Security', href: '#security' },
  { label: 'Contact', href: '#contact' },
]

const featureCards = [
  {
    title: 'Student Dashboard',
    description:
      'A focused academic home for enrolled courses, weekly schedule, attendance alerts, and pending tasks.',
    icon: LayoutDashboard,
    iconClassName: 'bg-blue-50 text-blue-700',
  },
  {
    title: 'Course Management',
    description:
      'Organize course rosters, materials, credits, professor assignments, and semester progress in one place.',
    icon: BookOpen,
    iconClassName: 'bg-emerald-50 text-emerald-700',
  },
  {
    title: 'Attendance Tracking',
    description:
      'Record and review class participation with clear status indicators for students and faculty staff.',
    icon: CalendarCheck,
    iconClassName: 'bg-cyan-50 text-cyan-700',
  },
  {
    title: 'Grades & Transcripts',
    description:
      'Publish gradebook updates, semester results, and transcript-ready academic records with confidence.',
    icon: GraduationCap,
    iconClassName: 'bg-violet-50 text-violet-700',
  },
  {
    title: 'Professor Tools',
    description:
      'Give instructors streamlined access to course lists, attendance sessions, grading, and student context.',
    icon: ClipboardList,
    iconClassName: 'bg-rose-50 text-rose-700',
  },
  {
    title: 'Admin Control',
    description:
      'Support program operations with role-aware administration for users, academic records, and access.',
    icon: UserCog,
    iconClassName: 'bg-slate-100 text-slate-700',
  },
]

const portalCards = [
  {
    title: 'Students',
    description: 'Track progress, view grades, confirm attendance, and keep the semester plan visible.',
    icon: Users,
    points: ['Course timetable', 'Transcript view', 'Attendance status'],
  },
  {
    title: 'Professors',
    description: 'Manage course delivery with practical tools for sessions, rosters, and assessments.',
    icon: BookOpen,
    points: ['Class rosters', 'Gradebook', 'Attendance sessions'],
  },
  {
    title: 'Administrators',
    description: 'Maintain academic operations with controlled access to users and institutional records.',
    icon: ShieldCheck,
    points: ['Role management', 'Program oversight', 'Record governance'],
  },
]

const workflowSteps = [
  {
    title: 'Enroll & Configure',
    description: 'Academic staff assign students, professors, courses, and semester rules.',
  },
  {
    title: 'Teach & Track',
    description: 'Professors update attendance, course progress, and assessments as the semester moves.',
  },
  {
    title: 'Review & Report',
    description: 'Students and administrators see reliable records for grades, transcripts, and completion.',
  },
]

const securityPoints = [
  'Role-based portals for student, professor, and admin responsibilities',
  'Authenticated access to academic records and operational tools',
  'Clear separation between personal dashboards and administrative functions',
]

export function LandingPage() {
  const [isMenuOpen, setIsMenuOpen] = useState(false)

  return (
    <div className="min-h-screen scroll-smooth bg-[#f7fafc] text-slate-950">
      <header className="fixed inset-x-0 top-0 z-50 border-b border-slate-200/80 bg-white/92 shadow-sm backdrop-blur-md">
        <nav
          className="mx-auto flex min-h-16 w-full max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8"
          aria-label="Primary navigation"
        >
          <Link to="/" className="flex min-w-0 items-center gap-3" aria-label="SEMS home">
            <img
              src="/logoup.gif.png"
              alt="University of Prishtina logo"
              className="h-10 w-10 shrink-0 object-contain"
            />
            <div className="min-w-0">
              <p className="text-lg font-semibold leading-5 text-[#13213a]">SEMS</p>
              <p className="hidden truncate text-xs font-medium text-slate-500 sm:block">
                Student Education Management System
              </p>
            </div>
          </Link>

          <div className="hidden items-center gap-1 lg:flex">
            {navLinks.map((link) => (
              <a
                key={link.href}
                href={link.href}
                className="rounded-md px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
              >
                {link.label}
              </a>
            ))}
          </div>

          <div className="flex items-center text-white gap-2">
            <Button
              asChild
              className="hidden h-10 rounded-lg px-4 text-white shadow-[0_10px_22px_rgba(37,99,235,0.18)] hover:text-white [&_svg]:text-white sm:inline-flex"
            >
              <Link to="/login" className="!text-white">
                Login
                <ArrowRight className="h-4 w-4" />
              </Link>
            </Button>
            <button
              type="button"
              className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50 hover:text-slate-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 lg:hidden"
              aria-label={isMenuOpen ? 'Close navigation menu' : 'Open navigation menu'}
              aria-expanded={isMenuOpen}
              onClick={() => setIsMenuOpen((current) => !current)}
            >
              {isMenuOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
            </button>
          </div>
        </nav>

        {isMenuOpen ? (
          <div className="border-t border-slate-200 bg-white px-4 py-4 shadow-sm lg:hidden">
            <div className="mx-auto grid max-w-7xl gap-2">
              {navLinks.map((link) => (
                <a
                  key={link.href}
                  href={link.href}
                  onClick={() => setIsMenuOpen(false)}
                  className="rounded-lg px-3 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 hover:text-slate-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                >
                  {link.label}
                </a>
              ))}
              <Button asChild className="mt-2 h-11 rounded-lg text-white hover:text-white [&_svg]:text-white sm:hidden">
                <Link to="/login" className="!text-white" onClick={() => setIsMenuOpen(false)}>
                  Login
                  <ArrowRight className="h-4 w-4" />
                </Link>
              </Button>
            </div>
          </div>
        ) : null}
      </header>

      <main>
        <section className="relative flex min-h-screen overflow-hidden border-b border-slate-200 bg-[#13213a] px-4 pt-24 text-white sm:px-6 lg:px-8">
          <video
            className="absolute inset-0 h-full w-full object-cover"
            src="/sems.mp4"
            autoPlay
            muted
            loop
            playsInline
            preload="metadata"
            aria-hidden="true"
          />
          <div className="absolute inset-0 bg-[linear-gradient(90deg,rgba(19,33,58,0.88)_0%,rgba(19,33,58,0.68)_46%,rgba(19,33,58,0.28)_100%)]" />

          <div className="relative mx-auto flex w-full max-w-7xl flex-1 items-center py-10 lg:py-16">
            <div className="max-w-3xl">
              <div className="mb-6 inline-flex items-center gap-2 rounded-lg border border-white/20 bg-white/12 px-3 py-2 text-sm font-semibold text-white shadow-sm backdrop-blur">
                <ShieldCheck className="h-4 w-4" />
                Role-based academic platform
              </div>

              <h1 className="max-w-3xl text-4xl font-semibold leading-tight text-white sm:text-5xl lg:text-[58px]">
                SEMS Student Education Management
              </h1>
              <p className="mt-6 max-w-2xl text-lg leading-8 text-white/88">
                A unified university system for student dashboards, course operations, attendance,
                grades, transcripts, and secure professor and administrator access.
              </p>

              <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                <Button
                  asChild
                  className="h-12 rounded-lg px-6 text-base text-white shadow-[0_14px_28px_rgba(37,99,235,0.22)] hover:text-white [&_svg]:text-white"
                >
                  <Link to="/login" className="!text-white">
                    Open SEMS
                    <ArrowRight className="h-5 w-5" />
                  </Link>
                </Button>
                <Button
                  asChild
                  variant="secondary"
                  className="h-12 rounded-lg bg-white px-6 text-base !text-black hover:bg-white/90 hover:!text-black"
                >
                  <a href="#features" className="!text-black">
                    Explore Features
                  </a>
                </Button>
              </div>
            </div>
          </div>
        </section>

        <section id="features" className="scroll-mt-20 px-4 py-20 sm:px-6 lg:px-8">
          <div className="mx-auto max-w-7xl">
            <div className="max-w-3xl">
              <p className="text-sm font-semibold uppercase text-blue-700">Platform Features</p>
              <h2 className="mt-3 text-3xl font-semibold leading-tight text-[#13213a] sm:text-4xl">
                Built around the daily work of a university
              </h2>
              <p className="mt-4 text-base leading-7 text-slate-600">
                SEMS connects the academic record with the people who maintain it, giving each role
                a clear workspace for the information they need most.
              </p>
            </div>

            <div className="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
              {featureCards.map((feature) => {
                const Icon = feature.icon

                return (
                  <Card key={feature.title} className="shadow-[0_14px_34px_rgba(15,23,42,0.06)] transition hover:-translate-y-0.5 hover:shadow-[0_20px_44px_rgba(15,23,42,0.10)]">
                    <CardContent className="p-5">
                      <div className={`mb-5 flex h-12 w-12 items-center justify-center rounded-lg ${feature.iconClassName}`}>
                        <Icon className="h-6 w-6" />
                      </div>
                      <h3 className="text-lg font-semibold text-slate-950">{feature.title}</h3>
                      <p className="mt-3 text-sm leading-6 text-slate-600">{feature.description}</p>
                    </CardContent>
                  </Card>
                )
              })}
            </div>
          </div>
        </section>

        <section id="portals" className="scroll-mt-20 border-y border-slate-200 bg-white px-4 py-20 sm:px-6 lg:px-8">
          <div className="mx-auto max-w-7xl">
            <div className="grid gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:items-start">
              <div>
                <p className="text-sm font-semibold uppercase text-emerald-700">Portals</p>
                <h2 className="mt-3 text-3xl font-semibold leading-tight text-[#13213a] sm:text-4xl">
                  Separate workspaces for every academic role
                </h2>
                <p className="mt-4 text-base leading-7 text-slate-600">
                  Students, professors, and administrators see purpose-built views instead of a
                  one-size-fits-all dashboard.
                </p>
              </div>

              <div className="grid gap-5 md:grid-cols-3">
                {portalCards.map((portal) => {
                  const Icon = portal.icon

                  return (
                    <Card key={portal.title} className="shadow-[0_14px_34px_rgba(15,23,42,0.06)]">
                      <CardContent className="p-5">
                        <div className="mb-5 flex h-11 w-11 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                          <Icon className="h-5 w-5" />
                        </div>
                        <h3 className="text-lg font-semibold text-slate-950">{portal.title}</h3>
                        <p className="mt-3 text-sm leading-6 text-slate-600">{portal.description}</p>
                        <ul className="mt-5 space-y-3">
                          {portal.points.map((point) => (
                            <li key={point} className="flex gap-2 text-sm font-medium text-slate-700">
                              <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                              {point}
                            </li>
                          ))}
                        </ul>
                      </CardContent>
                    </Card>
                  )
                })}
              </div>
            </div>
          </div>
        </section>

        <section id="workflow" className="scroll-mt-20 px-4 py-20 sm:px-6 lg:px-8">
          <div className="mx-auto max-w-7xl">
            <div className="max-w-3xl">
              <p className="text-sm font-semibold uppercase text-amber-700">Workflow</p>
              <h2 className="mt-3 text-3xl font-semibold leading-tight text-[#13213a] sm:text-4xl">
                From semester setup to transcript review
              </h2>
            </div>

            <div className="mt-10 grid gap-5 lg:grid-cols-3">
              {workflowSteps.map((step, index) => (
                <div key={step.title} className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                  <div className="mb-5 flex h-11 w-11 items-center justify-center rounded-lg bg-[#13213a] text-sm font-semibold text-white">
                    {index + 1}
                  </div>
                  <h3 className="text-lg font-semibold text-slate-950">{step.title}</h3>
                  <p className="mt-3 text-sm leading-6 text-slate-600">{step.description}</p>
                </div>
              ))}
            </div>
          </div>
        </section>

        <section id="security" className="scroll-mt-20 bg-[#13213a] px-4 py-20 text-white sm:px-6 lg:px-8">
          <div className="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
            <div>
              <p className="text-sm font-semibold uppercase text-white">Security</p>
              <h2 className="mt-3 text-3xl font-semibold leading-tight sm:text-4xl">
                Secure access for sensitive academic data
              </h2>
              <p className="mt-4 text-base leading-7 text-white">
                SEMS presents academic information through authenticated, role-based screens so each
                user works with the right level of visibility.
              </p>
            </div>

            <Card className="border-white/15 bg-white/8 text-white shadow-[0_22px_60px_rgba(0,0,0,0.18)]">
              <CardContent className="p-6">
                <div className="flex items-center gap-3">
                  <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-white/12 text-white">
                    <LockKeyhole className="h-6 w-6" />
                  </div>
                  <div>
                    <h3 className="text-lg font-semibold">Role-Based Access</h3>
                    <p className="mt-1 text-sm text-white">Student, professor, and admin boundaries</p>
                  </div>
                </div>
                <ul className="mt-6 space-y-4">
                  {securityPoints.map((point) => (
                    <li key={point} className="flex gap-3 text-sm leading-6 text-white">
                      <ShieldCheck className="mt-0.5 h-5 w-5 shrink-0 text-white" />
                      {point}
                    </li>
                  ))}
                </ul>
              </CardContent>
            </Card>
          </div>
        </section>

        <section id="contact" className="scroll-mt-20 px-4 py-20 sm:px-6 lg:px-8">
          <div className="mx-auto grid max-w-7xl gap-8 rounded-lg border border-slate-200 bg-white p-6 shadow-sm md:grid-cols-[1fr_auto] md:items-center md:p-8">
            <div>
              <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                <MessageSquare className="h-6 w-6" />
              </div>
              <h2 className="text-2xl font-semibold text-[#13213a]">Ready to access SEMS?</h2>
              <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Sign in with your university account to reach your student, professor, or
                administrator portal. For account support, contact faculty administration.
              </p>
            </div>
            <div className="flex flex-col gap-3 sm:flex-row md:justify-end">
              <Button asChild className="h-11 rounded-lg px-5 text-white hover:text-white [&_svg]:text-white">
                <Link to="/login" className="!text-white">
                  Login
                  <ArrowRight className="h-4 w-4" />
                </Link>
              </Button>
              <Button asChild variant="secondary" className="h-11 rounded-lg px-5">
                <a href="#features">
                  <BarChart3 className="h-4 w-4" />
                  View Platform
                </a>
              </Button>
            </div>
          </div>
        </section>
      </main>

      <footer className="border-t border-slate-200 bg-white px-4 py-8 sm:px-6 lg:px-8">
        <div className="mx-auto flex max-w-7xl flex-col gap-3 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
          <p className="font-medium text-slate-700">SEMS Student Education Management System</p>
          <p>Academic portal for students, professors, and administrators.</p>
        </div>
      </footer>
    </div>
  )
}
