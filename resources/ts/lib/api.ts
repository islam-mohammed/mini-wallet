export interface ApiErrorResponse {
  message?: string;
  errors?: Record<string, string[]>;
}

async function handleResponse<T>(response: Response): Promise<T> {
  const contentType = response.headers.get('content-type') ?? '';
  const isJson = contentType.includes('application/json');

  let data: any = null;

  if (isJson) {
    // Safely try to parse JSON once
    data = await response.json().catch(() => null);
  }

  //  Global unauthorized handling
  if (response.status === 401) {
    // Clear token so app knows user is logged out
    localStorage.removeItem('token');

    // Avoid redirect loop if we're already on /login (file-based routing)
    if (!window.location.pathname.startsWith('/login')) {
      window.location.assign('/login');
    }

    const error = new Error('Unauthorized');
    (error as any).status = 401;
    (error as any).details = data as ApiErrorResponse | null;
    throw error;
  }

  // Generic non-OK handling
  if (!response.ok) {
    const apiError = (data || {}) as ApiErrorResponse;
    const error = new Error(apiError.message || response.statusText);
    (error as any).status = response.status;
    (error as any).details = apiError;
    throw error;
  }

  // Successful response
  if (isJson) {
    return data as T;
  }

  // If  API is always JSON throw here,
  // but returning `undefined as T` keeps it flexible.
  return undefined as T;
}

export async function apiGet<T>(path: string): Promise<T> {
  const response = await fetch(`/api${path}`, {
    method: 'GET',
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${getToken()}`,
    },
  });

  return handleResponse<T>(response);
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
  });

  return handleResponse<T>(response);
}

function getToken() {
  return localStorage.getItem('token') ?? '';
}
