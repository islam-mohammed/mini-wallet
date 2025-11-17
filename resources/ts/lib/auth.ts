export function getToken(): string | null {
  return localStorage.getItem('token')
}

export function isLoggedIn(): boolean {
  return !!localStorage.getItem('token')
}

export function logout() {
  localStorage.removeItem('token')
}
