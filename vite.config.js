import {defineConfig, loadEnv} from 'vite'
import laravel from 'laravel-vite-plugin'
export default defineConfig(({mode}) => {
  const env = loadEnv(mode, process.cwd())

  return {
    base: '/vendor/moonshine/',
    plugins: [
      laravel({
        input: ['resources/css/nested-set.css','resources/js/app.js'],
        refresh: true,
      }),
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
