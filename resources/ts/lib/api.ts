export interface ApiErrorResponse {
  message?: string
  errors?: Record<string, string[]>
}

async function handleResponse<T>(response: Response): Promise<T> {
  if (!response.ok) {
    const data = (await response.json().catch(() => ({}))) as ApiErrorResponse
    const error = new Error(data.message || response.statusText)
    ;(error as any).details = data
    throw error
  }

  return (await response.json()) as T
}

export async function apiGet<T>(path: string): Promise<T> {
  const response = await fetch(`/api${path}`, {
    method: 'GET',
    headers: {
      Accept: 'application/json',
    },
    credentials: 'same-origin',
  })

  return handleResponse<T>(response)
}

export async function apiPost<T>(
  path: string,
  body: unknown,
): Promise<T> {
  const response = await fetch(`/api${path}`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    credentials: 'same-origin',
    body: JSON.stringify(body),
  })

  return handleResponse<T>(response)
}
