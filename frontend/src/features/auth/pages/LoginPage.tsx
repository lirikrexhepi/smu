import { Eye, EyeOff, HelpCircle, Lock, Mail, ShieldCheck } from 'lucide-react'
import { useState } from 'react'
import type { FormEvent } from 'react'
import { useNavigate } from 'react-router-dom'

import { Button } from '@/components/ui/button'
import { login } from '@/lib/api/auth'
import { storeAuthUser } from '@/lib/auth/session'

export function LoginPage() {
  const navigate = useNavigate()
  const [identifier, setIdentifier] = useState('')
  const [password, setPassword] = useState('')
  const [rememberMe, setRememberMe] = useState(false)
  const [showPassword, setShowPassword] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setError(null)
    setIsSubmitting(true)

    try {
      const response = await login(identifier, password)

      if (!response.success) {
        setError(response.message ?? 'Invalid credentials')
        return
      }

      storeAuthUser(response.data.user)
      navigate(response.data.redirectPath, { replace: true })
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Unable to sign in')
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <main className="relative min-h-screen overflow-hidden bg-[#f8fbff]">
      <div className="absolute inset-0 bg-[radial-gradient(circle_at_10%_90%,rgba(219,234,254,0.75)_0,rgba(219,234,254,0.55)_10%,transparent_24%),radial-gradient(circle_at_41%_93%,rgba(220,252,231,0.55)_0,rgba(220,252,231,0.35)_12%,transparent_26%)]" />
      <div className="absolute left-0 top-[42%] h-[280px] w-[58vw] opacity-70">
        <div className="absolute h-[170px] w-full rounded-[50%] border-t border-blue-200/50" />
        <div className="absolute top-4 h-[170px] w-full rounded-[50%] border-t border-blue-200/45" />
        <div className="absolute top-8 h-[170px] w-full rounded-[50%] border-t border-green-200/45" />
        <div className="absolute top-12 h-[170px] w-full rounded-[50%] border-t border-blue-200/40" />
        <div className="absolute top-16 h-[170px] w-full rounded-[50%] border-t border-blue-200/35" />
      </div>
      <div className="absolute left-[46%] top-[30%] grid grid-cols-7 gap-4 opacity-55">
        {Array.from({ length: 42 }).map((_, index) => (
          <span key={index} className="h-1 w-1 rounded-full bg-blue-400" />
        ))}
      </div>

      <section className="relative z-10 flex min-h-screen items-center justify-center px-4 py-6 lg:justify-end lg:px-[9vw]">
        <form
          onSubmit={handleSubmit}
          className="w-full max-w-[520px] rounded-[18px] border border-slate-200/80 bg-white/92 px-6 py-7 shadow-[0_24px_80px_rgba(15,23,42,0.10)] backdrop-blur-sm sm:px-10 sm:py-8 lg:px-12"
        >
          <div className="mb-7 text-center">
            <div className="mb-5 flex items-center justify-center gap-3">
              <img
                src="/logoup.gif.png"
                alt="University of Prishtina logo"
                className="h-12 w-12 object-contain"
              />
              <span className="text-[26px] font-semibold leading-none text-black">SEMS</span>
            </div>
            <h1 className="text-[27px] font-semibold leading-tight text-[#15213b] sm:text-[30px]">
              Sign in to your account
            </h1>
            <p className="mt-3 text-base text-[#61708b]">
              Access your student, professor, or admin portal
            </p>
          </div>

          <div className="space-y-5">
            <div>
              <label className="mb-2 block text-sm font-semibold text-[#17233d]" htmlFor="identifier">
                Email or ID
              </label>
              <div className="flex h-12 items-center gap-3 rounded-lg border border-slate-300 bg-white px-4 shadow-sm focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100">
                <Mail className="h-5 w-5 text-[#667696]" />
                <input
                  id="identifier"
                  value={identifier}
                  onChange={(event) => setIdentifier(event.target.value)}
                  className="h-full min-w-0 flex-1 border-0 bg-transparent text-base text-slate-900 outline-none placeholder:text-[#71809b]"
                  placeholder="Enter your email or student ID"
                  autoComplete="username"
                />
              </div>
            </div>

            <div>
              <label className="mb-2 block text-sm font-semibold text-[#17233d]" htmlFor="password">
                Password
              </label>
              <div className="flex h-12 items-center gap-3 rounded-lg border border-slate-300 bg-white px-4 shadow-sm focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100">
                <Lock className="h-5 w-5 text-[#667696]" />
                <input
                  id="password"
                  value={password}
                  onChange={(event) => setPassword(event.target.value)}
                  type={showPassword ? 'text' : 'password'}
                  className="h-full min-w-0 flex-1 border-0 bg-transparent text-base text-slate-900 outline-none placeholder:text-[#71809b]"
                  placeholder="Enter your password"
                  autoComplete="current-password"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword((current) => !current)}
                  className="rounded-md p-1 text-[#667696] hover:text-blue-700"
                  aria-label={showPassword ? 'Hide password' : 'Show password'}
                >
                  {showPassword ? <EyeOff className="h-5 w-5" /> : <Eye className="h-5 w-5" />}
                </button>
              </div>
            </div>
          </div>

          <div className="mt-6 flex items-center justify-between gap-4">
            <label className="flex items-center gap-2 text-sm font-semibold text-[#17233d]">
              <input
                checked={rememberMe}
                onChange={(event) => setRememberMe(event.target.checked)}
                type="checkbox"
                className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
              />
              Remember me
            </label>
            <button type="button" className="text-sm font-medium text-blue-600 hover:text-blue-700">
              Forgot password?
            </button>
          </div>

          {error ? (
            <div className="mt-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
              {error}
            </div>
          ) : null}

          <Button
            type="submit"
            className="mt-6 h-12 w-full rounded-lg bg-blue-600 text-base font-medium shadow-[0_10px_20px_rgba(37,99,235,0.25)] hover:bg-blue-700"
            disabled={isSubmitting}
          >
            <Lock className="h-5 w-5" />
            {isSubmitting ? 'Signing in...' : 'Sign In'}
          </Button>

          <div className="my-5 flex items-center gap-5 text-sm text-[#61708b]">
            <div className="h-px flex-1 bg-slate-200" />
            <span>or</span>
            <div className="h-px flex-1 bg-slate-200" />
          </div>

          <button
            type="button"
            className="flex h-12 w-full items-center justify-center gap-3 rounded-lg border border-slate-300 bg-white text-base font-medium text-[#61708b] hover:bg-slate-50"
          >
            <HelpCircle className="h-5 w-5" />
            Need help?
          </button>

          <div className="mx-auto mt-6 flex max-w-sm items-start justify-center gap-4 text-left text-sm leading-6 text-[#61708b]">
            <ShieldCheck className="mt-0.5 h-7 w-7 shrink-0 text-[#61708b]" />
            <p>For account assistance, contact the faculty administration.</p>
          </div>
        </form>
      </section>
    </main>
  )
}
