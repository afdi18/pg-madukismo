<template>
  <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black/30 dark:bg-black/60 backdrop-blur-sm" @click="close"></div>
    <div :class="['relative w-full mx-4 overflow-hidden', sizeClass]">
      <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-2xl ring-1 ring-black/5 dark:ring-white/5">
        <div class="relative">
          <slot name="header"></slot>
          <slot></slot>
          <slot name="footer"></slot>
        </div>
        <button v-if="showClose" @click="close" class="absolute -top-4 -right-4 bg-white dark:bg-gray-700 rounded-full p-2 shadow-lg ring-1 ring-gray-100 dark:ring-gray-600">
          <slot name="close">✕</slot>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { defineEmits, defineProps } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  sizeClass: { type: String, default: 'max-w-2xl' },
  showClose: { type: Boolean, default: true },
})
const emit = defineEmits(['update:modelValue'])
function close() {
  emit('update:modelValue', false)
}
</script>
