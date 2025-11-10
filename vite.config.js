import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
  plugins: [vue()],
  build: {
    outDir: 'public/build',
    manifest: true,
    rollupOptions: {
      input: {
        'npc-editor': resolve(__dirname, 'assets/npc-editor.js'),
        'quest-editor': resolve(__dirname, 'assets/quest-editor.js'),
      },
      output: {
        entryFileNames: '[name].js',
        chunkFileNames: '[name].js',
        assetFileNames: '[name].[ext]'
      }
    }
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, 'assets'),
      'vue': 'vue/dist/vue.esm-bundler.js'
    }
  }
});
