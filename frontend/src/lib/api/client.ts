export type ApiEnvelope<T> = {
  success: boolean
  data: T
  message: string | null
  errors: Record<string, string[]> | null
  meta: Record<string, unknown>
}

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? ''

export async function apiGet<T>(path: string): Promise<ApiEnvelope<T>> {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    headers: {
      Accept: 'application/json',
    },
  })

  const payload = (await response.json()) as ApiEnvelope<T>

  if (!response.ok) {
    throw new Error(payload.message ?? 'API request failed')
  }

  return payload
}

export async function apiPost<T>(path: string, body: Record<string, unknown>): Promise<ApiEnvelope<T>> {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(body),
  })

  const payload = (await response.json()) as ApiEnvelope<T>

  if (!response.ok) {
    throw new Error(payload.message ?? 'API request failed')
  }

  return payload
}
