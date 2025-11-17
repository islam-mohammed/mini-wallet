<template>
  <div
    class="inline-flex items-center justify-center"
    :class="wrapperClass"
    role="status"
  >
    <svg
      class="animate-spin text-slate-300"
      :class="sizeClass"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle
        class="opacity-25"
        cx="12"
        cy="12"
        r="10"
        stroke="currentColor"
        stroke-width="4"
      />
      <path
        class="opacity-75"
        fill="currentColor"
        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
      />
    </svg>

    <span v-if="label" class="ml-2 text-xs text-slate-300">
      {{ label }}
    </span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  size?: 'sm' | 'md' | 'lg'
  label?: string | null
  full?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  label: null,
  full: false,
})

const sizeClass = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'w-4 h-4'
    case 'lg':
      return 'w-8 h-8'
    default:
      return 'w-6 h-6'
  }
})

const wrapperClass = computed(() => {
  return props.full ? 'w-full py-6' : ''
})
</script>
