import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import fs from 'fs';
import path from 'path';

const OUT_DIR = path.resolve(__dirname, '../assets');

/**
 * `emptyOutDir` wipes assets/ on every build, so the directory silencers
 * have to be re-created afterwards rather than committed to the repo.
 */
function directorySilencers() {
  return {
    name: 't1schema-directory-silencers',
    closeBundle() {
      for (const dir of [OUT_DIR, path.join(OUT_DIR, '.vite')]) {
        if (fs.existsSync(dir)) {
          fs.writeFileSync(path.join(dir, 'index.php'), '<?php\n// Silence is golden.\n');
        }
      }
    },
  };
}

export default defineConfig({
  plugins: [react(), directorySilencers()],
  root: '.',
  base: './',
  build: {
    outDir: '../assets',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: path.resolve(__dirname, 'src/main.jsx'),
      output: {
        entryFileNames: 'app-[hash].js',
        chunkFileNames: 'chunks/[name]-[hash].js',
        assetFileNames: '[name]-[hash][extname]',
      },
    },
  },
  server: {
    port: 5173,
    strictPort: true,
    cors: true,
  },
});
