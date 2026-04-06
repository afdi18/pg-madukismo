<script setup lang="ts">
import AppToaster from '@/components/AppToaster.vue'
import { useThemeProvider } from '@/composables/useTheme'
useThemeProvider()
</script>

<template>
  <RouterView />
  <AppToaster />
</template>
