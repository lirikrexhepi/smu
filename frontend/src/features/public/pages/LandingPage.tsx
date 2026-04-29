import {
  ArrowRight,
  BookOpen,
  CalendarCheck,
  GraduationCap,
  MessageSquare,
  ShieldCheck,
  Users,
} from 'lucide-react'
import { Link } from 'react-router-dom'

import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'

const features = [
  {
    title: 'Courses',
    description: 'View enrolled courses, schedules, materials, and academic progress.',
    icon: BookOpen,
    iconClassName: 'bg-blue-50 text-blue-600',
  },
  {
    title: 'Attendance',
    description: 'Track attendance records and weekly class participation at a glance.',
    icon: CalendarCheck,
    iconClassName: 'bg-emerald-50 text-emerald-600',
  },
  {
    title: 'Grades & Transcript',
    description: 'Review current grades, semester performance, and transcript details.',
    icon: GraduationCap,
    iconClassName: 'bg-violet-50 text-violet-600',
  },
  {
    title: 'Communication',
    description: 'Keep student, professor, and faculty conversations in one place.',
    icon: MessageSquare,
    iconClassName: 'bg-amber-50 text-amber-600',
  },
]

const previewStats = [
  { label: 'Active Courses', value: '6' },
  { label: 'Attendance', value: '94%' },
  { label: 'Average Grade', value: '8.7' },
]

export function LandingPage() {
  return (
    <main className="min-h-screen bg-[#f8fbff] text-slate-950">
      <section className="mx-auto flex min-h-screen w-full max-w-7xl flex-col px-5 py-5 sm:px-8 lg:px-10">
        <header className="flex items-center justify-between gap-4 rounded-lg border border-slate-200 bg-white/90 px-4 py-3 shadow-sm">
          <Link to="/" className="flex min-w-0 items-center gap-3" aria-label="SEMS home">
            <img
              src="/logoup.gif.png"
              alt="University of Prishtina logo"
              className="h-11 w-11 shrink-0 object-contain"
            />
            <div className="min-w-0">
              <p className="text-lg font-semibold leading-6 text-slate-950">SEMS</p>
              <p className="truncate text-sm text-slate-500">University Student Portal</p>
            </div>
          </Link>

          <Button asChild className="h-10 rounded-lg px-4 shadow-[0_10px_20px_rgba(37,99,235,0.18)]">
            <Link to="/login">
              Login
              <ArrowRight className="h-4 w-4" />
            </Link>
          </Button>
        </header>

        <div className="grid flex-1 items-center gap-8 py-10 lg:grid-cols-[minmax(0,1fr)_440px] lg:py-12">
          <div className="max-w-3xl">
            <div className="mb-5 inline-flex items-center gap-2 rounded-lg border border-blue-100 bg-white px-3 py-2 text-sm font-medium text-blue-700 shadow-sm">
              <ShieldCheck className="h-4 w-4" />
              Academic services for students and professors
            </div>

            <h1 className="text-4xl font-semibold leading-tight text-[#15213b] sm:text-5xl lg:text-[56px]">
              Student Education Management System
            </h1>
            <p className="mt-5 max-w-2xl text-lg leading-8 text-[#61708b]">
              A clean university portal for managing courses, attendance, grades, transcripts, and
              academic communication across student and professor workflows.
            </p>

            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
              <Button
                asChild
                className="h-12 rounded-lg px-6 text-base shadow-[0_12px_24px_rgba(37,99,235,0.22)]"
              >
                <Link to="/login">
                  Go to Login
                  <ArrowRight className="h-5 w-5" />
                </Link>
              </Button>
              <div className="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600 shadow-sm">
                <Users className="h-5 w-5 text-blue-600" />
                Student, professor, and admin access
              </div>
            </div>

            <div className="mt-10 grid gap-4 sm:grid-cols-2">
              {features.map((feature) => {
                const Icon = feature.icon

                return (
                  <Card key={feature.title} className="shadow-[0_14px_36px_rgba(15,23,42,0.06)]">
                    <CardContent className="flex gap-4 p-5">
                      <div
                        className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-lg ${feature.iconClassName}`}
                      >
                        <Icon className="h-5 w-5" />
                      </div>
                      <div>
                        <h2 className="font-semibold text-slate-950">{feature.title}</h2>
                        <p className="mt-2 text-sm leading-6 text-slate-500">{feature.description}</p>
                      </div>
                    </CardContent>
                  </Card>
                )
              })}
            </div>
          </div>

          <Card className="hidden overflow-hidden border-slate-200 shadow-[0_24px_80px_rgba(15,23,42,0.10)] lg:block">
            <CardContent className="p-0">
              <div className="border-b border-slate-200 bg-white px-6 py-5">
                <div className="flex items-center justify-between gap-4">
                  <div>
                    <p className="text-sm font-medium text-blue-600">Dashboard Preview</p>
                    <h2 className="mt-1 text-xl font-semibold text-[#15213b]">Academic Summary</h2>
                  </div>
                  <div className="rounded-lg bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700">
                    Spring
                  </div>
                </div>
              </div>

              <div className="space-y-5 bg-white p-6">
                <div className="grid grid-cols-3 gap-3">
                  {previewStats.map((stat) => (
                    <div key={stat.label} className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                      <p className="text-2xl font-semibold text-[#15213b]">{stat.value}</p>
                      <p className="mt-2 text-xs font-medium leading-5 text-slate-500">{stat.label}</p>
                    </div>
                  ))}
                </div>

                <div className="rounded-lg border border-slate-200 p-4">
                  <div className="mb-4 flex items-center justify-between gap-3">
                    <h3 className="font-semibold text-slate-950">Today's Classes</h3>
                    <span className="rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                      On track
                    </span>
                  </div>
                  <div className="space-y-3">
                    {['Database Systems', 'Software Engineering', 'Web Technologies'].map(
                      (course, index) => (
                        <div
                          key={course}
                          className="flex items-center justify-between gap-4 rounded-lg bg-slate-50 px-3 py-3"
                        >
                          <div className="min-w-0">
                            <p className="truncate text-sm font-semibold text-slate-900">{course}</p>
                            <p className="mt-1 text-xs text-slate-500">Room {204 + index}</p>
                          </div>
                          <span className="text-sm font-medium text-blue-600">
                            {9 + index}:00
                          </span>
                        </div>
                      ),
                    )}
                  </div>
                </div>

                <div className="rounded-lg border border-blue-100 bg-blue-50 p-4">
                  <p className="text-sm font-semibold text-blue-800">Next deadline</p>
                  <p className="mt-2 text-sm leading-6 text-blue-700">
                    Submit Software Engineering project milestone by Friday.
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </section>
    </main>
  )
}
