import {defineConfig, loadEnv} from 'vite'
import laravel from 'laravel-vite-plugin'
import iife from 'rollup-plugin-iife';
import tailwindcss from "@tailwindcss/vite";


export default defineConfig(({mode}) => {
    const env = loadEnv(mode, process.cwd())

  return {
    base: '/vendor/moonshine/',
    plugins: [
      tailwindcss(),
      laravel({
        input: ['resources/css/nested-set.css','resources/js/app.js'],
        refresh: true,
      }),
      iife({
       include: ['resources/js/app.js']
      })
    ],
    server: {
      host: env.VITE_SERVER_HOST,
      hmr: {
        host: env.VITE_SERVER_HMR_HOST,
      },
    },
    css: {
      devSourcemap: true,
    },
    build: {
      emptyOutDir: true,
      outDir: 'public',
        rollupOptions: {
            output: {
                entryFileNames: `assets/[name].js`,
                assetFileNames: chunk => {
                    if (chunk.name.endsWith('.woff2')) {
                        return 'fonts/[name].[ext]'
                    }

                    return 'assets/[name].css'
                },
            },
        },
    },
  }
})
