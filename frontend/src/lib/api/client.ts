export type ApiEnvelope<T> = {
  success: boolean
  data: T
  message: string | null
  errors: Record<string, string[]> | null
  meta: Record<string, unknown>
}

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? ''

function emptyEnvelope<T>(response: Response): ApiEnvelope<T> {
  return {
    success: response.ok,
    data: null as T,
    message: response.ok ? null : 'API request failed',
    errors: null,
    meta: {},
  }
}

async function parseApiResponse<T>(response: Response, fallbackMessage: string): Promise<ApiEnvelope<T>> {
  const text = await response.text()
  let payload: ApiEnvelope<T>

  try {
    payload = text.trim() === '' ? emptyEnvelope<T>(response) : (JSON.parse(text) as ApiEnvelope<T>)
  } catch {
    throw new Error(fallbackMessage)
  }

  if (!response.ok) {
    throw new Error(payload.message ?? fallbackMessage)
  }

  return payload
}

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
    credentials: 'include',
    headers: {
      Accept: 'application/json',
    },
  })

  return parseApiResponse<T>(response, 'API request failed')
}

export async function apiPost<T>(path: string, body: Record<string, unknown>): Promise<ApiEnvelope<T>> {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    method: 'POST',
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(body),
  })

  return parseApiResponse<T>(response, 'API request failed')
}

export async function apiPatch<T>(path: string, body: Record<string, unknown>): Promise<ApiEnvelope<T>> {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    method: 'PATCH',
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(body),
  })

  return parseApiResponse<T>(response, 'API request failed')
}

export async function apiUpload<T>(path: string, formData: FormData): Promise<ApiEnvelope<T>> {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    method: 'POST',
    credentials: 'include',
    headers: {
      Accept: 'application/json',
    },
    body: formData,
  })

  return parseApiResponse<T>(response, 'API upload failed')
}
