<template>
  <div class="max-w-sm mx-auto mt-24 space-y-6">
    <h2 class="text-2xl font-semibold text-center">Login</h2>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="block text-sm text-slate-300">Email</label>
        <input
          v-model="email"
          type="email"
          class="bg-slate-800 px-3 py-2 rounded w-full disabled:opacity-50"
          :disabled="isLoading"
          required
        />
      </div>

      <div>
        <label class="block text-sm text-slate-300">Password</label>
        <input
          v-model="password"
          type="password"
          class="bg-slate-800 px-3 py-2 rounded w-full disabled:opacity-50"
          :disabled="isLoading"
          required
        />
      </div>

      <button
        type="submit"
        class="w-full bg-blue-600 hover:bg-blue-500 text-white py-2 rounded disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center justify-center"
        :disabled="isLoading"
      >
        <AppLoader v-if="isLoading" size="sm" />
        <span v-else>Login</span>
      </button>
    </form>

    <p v-if="error" class="text-center text-red-400 text-sm">{{ error }}</p>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AppLoader from '@/components/AppLoader.vue'

const email = ref('')
const password = ref('')
const error = ref('')
const isLoading = ref(false)

const router = useRouter()

async function submit() {
  error.value = ''
  isLoading.value = true

  try {
    const response = await fetch('/api/login', {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        email: email.value,
        password: password.value,
      }),
    })

    const data = await response.json()

    if (!response.ok) {
      error.value = data.message || 'Invalid credentials'
      return
    }

    localStorage.setItem('token', data.token)

    router.push('/wallet')
  } catch (err) {
    error.value = 'Something went wrong. Please try again.'
  } finally {
    isLoading.value = false
  }
}
</script>

<route lang="yaml">
meta:
  requiresAuth: false
</route>
