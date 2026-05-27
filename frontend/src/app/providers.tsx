import type { PropsWithChildren } from 'react'
import { ToastProvider } from '@/components/ui/toast'

export function AppProviders({ children }: PropsWithChildren) {
  return <ToastProvider>{children}</ToastProvider>
}

