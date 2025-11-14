import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import VueRouter from 'unplugin-vue-router/vite'
import tailwindcss from '@tailwindcss/vite'
import path from 'path'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/ts/main.ts', 'resources/css/app.css'],
      refresh: true,
    }),
    VueRouter({
      dts: 'resources/ts/typed-router.d.ts',
      routesFolder: 'resources/ts/pages',
    }),
    vue(),
    tailwindcss(),
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'resources/ts'),
    },
  },
})
