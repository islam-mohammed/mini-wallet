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
      Authorization: `Bearer ${getToken()}`,
    },
  })

  if (!response.ok) {
    throw new Error(`GET ${path} failed`)
  }

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
       Authorization: `Bearer ${getToken()}`,
    },
    body: JSON.stringify(body),
  })

   if (!response.ok) {
    throw new Error(`POST ${path} failed`)
  }

  return handleResponse<T>(response)
}


function getToken() {
  return localStorage.getItem('token') ?? ''
}
