<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useTheme } from '@/composables/useTheme'
import { EyeIcon, EyeOffIcon, MoonIcon, SunIcon } from 'lucide-vue-next'

const router    = useRouter()
const route     = useRoute()
const authStore = useAuthStore()
const { theme, toggleTheme } = useTheme()

const username   = ref('')
const password   = ref('')
const showPass   = ref(false)
const rememberMe = ref(false)

async function handleLogin() {
    if (!username.value || !password.value) return
    try {
        await authStore.login(username.value, password.value)
        const redirect = route.query.redirect as string ?? authStore.getDefaultRoute()
        router.push(redirect)
    } catch {
        // error sudah tersimpan di authStore.loginError
    }
}
</script>

<template>
  <div class="min-h-screen flex bg-white dark:bg-gray-900 transition-colors duration-300">

    <!-- ====================================================
         KIRI — Branding Panel
    ===================================================== -->
    <div class="hidden lg:flex lg:w-1/2 xl:w-[55%] flex-col items-center justify-center
                bg-gradient-to-br from-yellow-400 via-yellow-500 to-amber-600 relative overflow-hidden">

      <!-- Pattern dekoratif -->
      <div class="absolute inset-0 opacity-10">
        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
              <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="0.5"/>
            </pattern>
          </defs>
          <rect width="100%" height="100%" fill="url(#grid)" />
        </svg>
      </div>

      <!-- Konten tengah -->
      <div class="relative z-10 text-center px-12">
        <!-- Logo Madu Baru -->
        <div class="flex justify-center mb-8">
          <div class="w-32 h-32 rounded-full bg-white shadow-2xl flex items-center justify-center p-2">
            <img src="/images/logo/madubaru.png" alt="Logo Madu Baru" class="w-full h-full object-contain" />
          </div>
        </div>

        <h1 class="text-4xl font-bold text-white mb-3 drop-shadow">
          PG Madukismo
        </h1>
        <p class="text-xl text-yellow-100 font-medium mb-2">
          Dashboard Monitoring
        </p>
        <p class="text-yellow-200 text-sm max-w-xs mx-auto leading-relaxed">
          Sistem pemantauan produksi, peta kebun, dan kualitas tebu secara terpadu.
        </p>

        <!-- Decorative dots -->
        <div class="mt-12 flex justify-center gap-3">
          <span class="w-2 h-2 rounded-full bg-white opacity-80"></span>
          <span class="w-2 h-2 rounded-full bg-white opacity-50"></span>
          <span class="w-2 h-2 rounded-full bg-white opacity-30"></span>
        </div>
      </div>

      <!-- Footer branding -->
      <p class="absolute bottom-6 text-yellow-200 text-xs">
        KSO Madubaru — Yogyakarta
      </p>
    </div>

    <!-- ====================================================
         KANAN — Form Login
    ===================================================== -->
    <div class="flex-1 flex flex-col">

      <!-- Header top -->
      <div class="flex justify-between items-center px-6 pt-6">
        <!-- Mobile logo -->
        <div class="flex items-center gap-3 lg:hidden">
          <img src="/images/logo/madubaru.png" alt="Logo" class="w-8 h-8 object-contain" />
          <span class="font-semibold text-gray-700 dark:text-white text-sm">PG Madukismo</span>
        </div>

        <!-- Theme toggle -->
        <button
          @click="toggleTheme"
          class="ml-auto p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 transition-colors"
          :title="theme === 'dark' ? 'Mode Terang' : 'Mode Gelap'"
        >
          <SunIcon v-if="theme === 'dark'" class="w-5 h-5" />
          <MoonIcon v-else class="w-5 h-5" />
        </button>
      </div>

      <!-- Form area -->
      <div class="flex-1 flex items-center justify-center px-6 py-10">
        <div class="w-full max-w-md">

          <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-1">
              Masuk ke Sistem
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              Masukkan username dan password Anda
            </p>
          </div>

          <!-- Error alert -->
          <div
            v-if="authStore.loginError"
            class="mb-5 flex items-start gap-3 p-4 bg-red-50 dark:bg-red-900/20
                   border border-red-200 dark:border-red-800 rounded-xl"
          >
            <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <p class="text-sm text-red-600 dark:text-red-400">{{ authStore.loginError }}</p>
          </div>

          <!-- Form -->
          <form @submit.prevent="handleLogin" class="space-y-5" novalidate>

            <!-- Username -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                Username
              </label>
              <input
                v-model="username"
                type="text"
                autocomplete="username"
                placeholder="Masukkan username"
                :disabled="authStore.isLoading"
                class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700
                       bg-white dark:bg-gray-800 text-gray-900 dark:text-white
                       placeholder-gray-400 dark:placeholder-gray-500
                       focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent
                       disabled:opacity-60 transition-all duration-150"
              />
            </div>

            <!-- Password -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                Password
              </label>
              <div class="relative">
                <input
                  v-model="password"
                  :type="showPass ? 'text' : 'password'"
                  autocomplete="current-password"
                  placeholder="Masukkan password"
                  :disabled="authStore.isLoading"
                  class="w-full px-4 py-3 pr-12 rounded-xl border border-gray-300 dark:border-gray-700
                         bg-white dark:bg-gray-800 text-gray-900 dark:text-white
                         placeholder-gray-400 dark:placeholder-gray-500
                         focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent
                         disabled:opacity-60 transition-all duration-150"
                />
                <button
                  type="button"
                  @click="showPass = !showPass"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600
                         dark:hover:text-gray-300 transition-colors"
                >
                  <EyeOffIcon v-if="showPass" class="w-5 h-5" />
                  <EyeIcon v-else class="w-5 h-5" />
                </button>
              </div>
            </div>

            <!-- Remember me -->
            <div class="flex items-center">
              <input
                id="remember"
                v-model="rememberMe"
                type="checkbox"
                class="w-4 h-4 rounded border-gray-300 dark:border-gray-600
                       text-yellow-500 focus:ring-yellow-500 dark:bg-gray-800"
              />
              <label for="remember" class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                Ingat saya
              </label>
            </div>

            <!-- Submit -->
            <button
              type="submit"
              :disabled="authStore.isLoading || !username || !password"
              class="w-full py-3 px-4 rounded-xl font-semibold text-white
                     bg-yellow-500 hover:bg-yellow-600 active:bg-yellow-700
                     disabled:opacity-60 disabled:cursor-not-allowed
                     transition-all duration-150 flex items-center justify-center gap-2
                     shadow-lg shadow-yellow-200 dark:shadow-yellow-900/30"
            >
              <svg
                v-if="authStore.isLoading"
                class="animate-spin w-5 h-5 text-white"
                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
              >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              <span>{{ authStore.isLoading ? 'Memverifikasi...' : 'Masuk' }}</span>
            </button>

          </form>

          <!-- Footer note -->
          <p class="mt-8 text-center text-xs text-gray-400 dark:text-gray-600">
            Tidak bisa masuk? Hubungi Administrator sistem.
          </p>

        </div>
      </div>

      <!-- Bottom copyright -->
      <p class="text-center text-xs text-gray-400 dark:text-gray-600 py-4">
        &copy; {{ new Date().getFullYear() }} ITPGRAB — Dashboard Monitoring PG Madukismo
      </p>
    </div>

  </div>
</template>
