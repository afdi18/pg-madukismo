<script setup lang="ts">
import { CheckCircle2Icon, InfoIcon, XIcon, AlertTriangleIcon } from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'

const { toasts, remove } = useToast()

function toastClasses(type: 'success' | 'error' | 'info') {
  switch (type) {
    case 'success':
      return 'border-green-200 bg-green-50 text-green-800 dark:border-green-800/80 dark:bg-green-950/90 dark:text-green-200'
    case 'error':
      return 'border-red-200 bg-red-50 text-red-800 dark:border-red-800/80 dark:bg-red-950/90 dark:text-red-200'
    default:
      return 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800/80 dark:bg-blue-950/90 dark:text-blue-200'
  }
}

function iconFor(type: 'success' | 'error' | 'info') {
  if (type === 'success') return CheckCircle2Icon
  if (type === 'error') return AlertTriangleIcon
  return InfoIcon
}
</script>

<template>
  <Teleport to="body">
    <div class="pointer-events-none fixed right-4 top-4 z-[100] flex w-full max-w-sm flex-col gap-3">
      <TransitionGroup
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-1 opacity-0"
      >
        <div
          v-for="item in toasts"
          :key="item.id"
          :class="[
            'pointer-events-auto overflow-hidden rounded-xl border shadow-lg backdrop-blur-sm',
            toastClasses(item.type),
          ]"
        >
          <div class="flex items-start gap-3 px-4 py-3">
            <component :is="iconFor(item.type)" class="mt-0.5 h-5 w-5 shrink-0" />
            <p class="flex-1 text-sm font-medium leading-5">{{ item.message }}</p>
            <button
              type="button"
              class="rounded-md p-1 opacity-70 transition hover:bg-black/5 hover:opacity-100 dark:hover:bg-white/10"
              @click="remove(item.id)"
            >
              <XIcon class="h-4 w-4" />
            </button>
          </div>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>
