export type ApiEnvelope<T> = {
  success: boolean
  data: T
  message: string | null
  errors: Record<string, string[]> | null
  meta: Record<string, unknown>
}

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? ''

export function apiAssetUrl(path: string | null | undefined): string | null {
  if (!path) {
    return null
  }

  if (/^https?:\/\//.test(path)) {
    return path
  }

  if (!API_BASE_URL) {
    return path
  }

  return `${API_BASE_URL.replace(/\/$/, '')}/${path.replace(/^\//, '')}`
}

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

export async function apiPatch<T>(path: string, body: Record<string, unknown>): Promise<ApiEnvelope<T>> {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    method: 'PATCH',
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

export async function apiUpload<T>(path: string, formData: FormData): Promise<ApiEnvelope<T>> {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
    },
    body: formData,
  })

  const payload = (await response.json()) as ApiEnvelope<T>

  if (!response.ok) {
    throw new Error(payload.message ?? 'API upload failed')
  }

  return payload
}
