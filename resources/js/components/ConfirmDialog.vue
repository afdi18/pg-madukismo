<template>
  <ModalCard v-model="visible" :sizeClass="sizeClass">
    <template #header>
      <div class="p-6 border-b dark:border-gray-800">
        <h3 class="text-lg font-semibold">{{ title }}</h3>
      </div>
    </template>

    <div class="p-6">
      <p class="text-sm text-gray-600 dark:text-gray-300">{{ message }}</p>
    </div>

    <template #footer>
      <div class="p-6 border-t dark:border-gray-800 flex justify-end gap-2">
        <button @click="cancel" class="px-3 py-2 bg-gray-100 dark:bg-gray-800 rounded">{{ cancelText }}</button>
        <button @click="confirm" class="px-3 py-2 bg-yellow-500 text-white rounded">{{ confirmText }}</button>
      </div>
    </template>
  </ModalCard>
</template>

<script setup lang="ts">
import { ref, watch, defineProps, defineEmits } from 'vue'
import ModalCard from '@/components/ModalCard.vue'

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  title: { type: String, default: 'Konfirmasi' },
  message: { type: String, required: true },
  confirmText: { type: String, default: 'Ya' },
  cancelText: { type: String, default: 'Batal' },
  sizeClass: { type: String, default: 'max-w-md' },
})
const emit = defineEmits(['update:modelValue', 'confirm'])

const visible = ref(props.modelValue)
watch(() => props.modelValue, v => (visible.value = v))
watch(visible, v => emit('update:modelValue', v))

function confirm() {
  emit('confirm')
  visible.value = false
}
function cancel() {
  visible.value = false
}
</script>
