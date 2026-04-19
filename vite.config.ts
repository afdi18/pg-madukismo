import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '')

    let hmrHost: string | undefined
    let hmrPort: number | undefined

    if (env.VITE_DEV_SERVER_URL) {
        try {
            const devServerUrl = new URL(env.VITE_DEV_SERVER_URL)
            hmrHost = devServerUrl.hostname
            hmrPort = Number(devServerUrl.port || '5173')
        } catch {
            // Ignore invalid URL and fallback to Vite defaults.
        }
    }

    return {
        plugins: [
            laravel({
                input: ['resources/js/app.ts'],
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
        server: {
            host: '0.0.0.0',
            port: 5173,
            strictPort: true,
            cors: {
                origin: true,
                credentials: true,
            },
            hmr: hmrHost ? { host: hmrHost, port: hmrPort ?? 5173 } : undefined,
        },
        resolve: {
            alias: {
                '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
            },
        },
        build: {
            chunkSizeWarningLimit: 700,
            rollupOptions: {
                output: {
                    manualChunks(id) {
                        if (!id.includes('node_modules')) {
                            return
                        }

                        if (id.includes('apexcharts') || id.includes('vue3-apexcharts')) {
                            return 'vendor-charts'
                        }

                        if (id.includes('leaflet') || id.includes('leaflet.markercluster')) {
                            return 'vendor-maps'
                        }

                        if (id.includes('@fullcalendar')) {
                            return 'vendor-calendar'
                        }

                        if (id.includes('swiper')) {
                            return 'vendor-swiper'
                        }

                        if (
                            id.includes('/vue/') ||
                            id.includes('vue-router') ||
                            id.includes('/pinia/')
                        ) {
                            return 'vendor-vue-core'
                        }

                        return 'vendor'
                    },
                },
            },
        },
    }
})
