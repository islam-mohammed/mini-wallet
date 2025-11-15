<template>
  <div class="min-h-screen flex flex-col bg-slate-950 text-slate-50">
    <header class="border-b border-slate-800 px-4 py-3 flex items-center justify-between">
      <h1 class="text-lg font-semibold tracking-tight">
        Mini Wallet
      </h1>

      <nav class="flex items-center gap-4 text-sm text-slate-300">
        <RouterLink
          to="/"
          class="hover:text-white transition"
          active-class="text-white font-semibold"
          exact-active-class="text-white font-semibold"
        >
          Home
        </RouterLink>

        <RouterLink
          to="/wallet"
          class="hover:text-white transition"
          active-class="text-white font-semibold"
        >
          Wallet
        </RouterLink>
      </nav>
    </header>

    <main class="flex-1 px-4 py-6">
      <RouterView />
    </main>
  </div>
</template>

<script setup lang="ts">
import { RouterView, RouterLink } from 'vue-router/auto'
</script>
