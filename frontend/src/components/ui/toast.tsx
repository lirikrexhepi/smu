import React, { createContext, useContext, useState, useCallback } from 'react'
import { X, CheckCircle, AlertTriangle, AlertCircle, Info } from 'lucide-react'

type ToastType = 'success' | 'error' | 'warning' | 'info'

interface Toast {
  id: string
  message: string
  type: ToastType
  title?: string
}

interface ToastContextType {
  toast: (message: string, type?: ToastType, title?: string) => void
  success: (message: string, title?: string) => void
  error: (message: string, title?: string) => void
  warning: (message: string, title?: string) => void
  info: (message: string, title?: string) => void
}

const ToastContext = createContext<ToastContextType | undefined>(undefined)

export const useToast = () => {
  const context = useContext(ToastContext)
  if (!context) {
    throw new Error('useToast must be used within a ToastProvider')
  }
  return context
}

export const ToastProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [toasts, setToasts] = useState<Toast[]>([])

  const toast = useCallback((message: string, type: ToastType = 'info', title?: string) => {
    const id = Math.random().toString(36).substring(2, 9)
    setToasts((prev) => [...prev, { id, message, type, title }])
    setTimeout(() => {
      setToasts((prev) => prev.filter((t) => t.id !== id))
    }, 4000)
  }, [])

  const success = useCallback((message: string, title?: string) => toast(message, 'success', title), [toast])
  const error = useCallback((message: string, title?: string) => toast(message, 'error', title), [toast])
  const warning = useCallback((message: string, title?: string) => toast(message, 'warning', title), [toast])
  const info = useCallback((message: string, title?: string) => toast(message, 'info', title), [toast])

  const remove = (id: string) => {
    setToasts((prev) => prev.filter((t) => t.id !== id))
  }

  return (
    <ToastContext.Provider value={{ toast, success, error, warning, info }}>
      {children}
      {/* Toast Portal/Container */}
      <div className="fixed bottom-5 right-5 z-50 flex flex-col gap-3 max-w-md w-full">
        {toasts.map((t) => {
          const icon = {
            success: <CheckCircle className="h-5 w-5 text-emerald-500" />,
            error: <AlertCircle className="h-5 w-5 text-rose-500" />,
            warning: <AlertTriangle className="h-5 w-5 text-amber-500" />,
            info: <Info className="h-5 w-5 text-sky-500" />,
          }[t.type]

          const borderColors = {
            success: 'border-emerald-500/20 bg-emerald-950/80',
            error: 'border-rose-500/20 bg-rose-950/80',
            warning: 'border-amber-500/20 bg-amber-950/80',
            info: 'border-sky-500/20 bg-sky-950/80',
          }[t.type]

          return (
            <div
              key={t.id}
              className={`flex gap-3 p-4 rounded-xl border backdrop-blur-md shadow-2xl animate-in slide-in-from-right duration-300 text-white ${borderColors}`}
            >
              <div className="flex-shrink-0 mt-0.5">{icon}</div>
              <div className="flex-grow">
                {t.title && <h4 className="font-semibold text-sm mb-0.5">{t.title}</h4>}
                <p className="text-sm opacity-90 leading-relaxed">{t.message}</p>
              </div>
              <button
                onClick={() => remove(t.id)}
                className="flex-shrink-0 h-5 w-5 rounded-lg flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-white/10 transition-all self-start"
              >
                <X className="h-3.5 w-3.5" />
              </button>
            </div>
          )
        })}
      </div>
    </ToastContext.Provider>
  )
}
