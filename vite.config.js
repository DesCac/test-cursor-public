import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig(({ mode }) => ({
  plugins: [vue()],
  define: {
    // Включаем Vue DevTools в development режиме
    __VUE_OPTIONS_API__: 'true',
    __VUE_PROD_DEVTOOLS__: mode === 'development' ? 'true' : 'false',
    __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: 'false',
  },
  build: {
    outDir: 'public/build',
    manifest: true,
    cssCodeSplit: false,
    // В development режиме отключаем минификацию
    minify: mode === 'production',
    rollupOptions: {
        input: {
          'npc-editor': resolve(__dirname, 'assets/npc-editor.js'),
          'quest-editor': resolve(__dirname, 'assets/quest-editor.js'),
          'skill-editor': resolve(__dirname, 'assets/skill-editor.js'),
        },
      output: {
        entryFileNames: '[name].js',
        chunkFileNames: '[name].js',
        assetFileNames: (assetInfo) => {
            if (assetInfo.name.endsWith('.css')) {
              const match = assetInfo.name.match(/npc-editor|quest-editor|skill-editor/);
            if (match) {
              return `${match[0]}.css`;
            }
          }
          return '[name].[ext]';
        }
      }
    }
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, 'assets'),
      'vue': 'vue/dist/vue.esm-bundler.js'
    }
  }
}));
