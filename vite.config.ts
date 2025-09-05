import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import autoprefixer from "autoprefixer";
import tailwindcss from "tailwindcss";

export default defineConfig(({ mode }) => ({
  plugins: [react()],
  build: {
    manifest: true,
    sourcemap: mode !== "production",
    outDir: "dist/build",
    assetsDir: "",
    emptyOutDir: false,
    rollupOptions: {
      input: { "admin-app": "src/admin/main.tsx" },
    },
  },
  server: {
    port: 5173,
    strictPort: true,
    hmr: { host: "localhost" },
    cors: {
      origin: /^(https?:\/\/).+/, // allow any http(s) origin (local WP)
      methods: ["GET", "POST", "PUT", "DELETE", "PATCH", "OPTIONS"],
      allowedHeaders: ["Content-Type"],
      credentials: true,
    },
  },
  css: {
    postcss: {
      plugins: [tailwindcss(), autoprefixer()],
    },
  },
}));
