<template>
  <div class="min-h-screen flex flex-col bg-slate-950 text-slate-50">
    <header class="border-b border-slate-800 px-4 py-3">
      <h1 class="text-lg font-semibold tracking-tight">
        Mini Wallet
      </h1>
    </header>

    <main class="flex-1 px-4 py-6">
      <RouterView />
    </main>
  </div>
</template>

<script setup lang="ts">
</script>
