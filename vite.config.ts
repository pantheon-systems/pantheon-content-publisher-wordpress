import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import autoprefixer from "autoprefixer";
import tailwindcss from "tailwindcss";
import postcssPrefixSelector from "postcss-prefix-selector";

export default defineConfig(({ mode }) => ({
  plugins: [react()],
  build: {
    manifest: true,
    sourcemap: mode !== "production",
    outDir: "dist/build",
    assetsDir: "",
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
      plugins: [
        tailwindcss(),
        autoprefixer(),
        postcssPrefixSelector({
          prefix: "#content-pub-root",
          transform: (prefix: string, selector: string) => {
            if (selector.startsWith("#content-pub-root")) return selector;

            if (
              selector === "html" ||
              selector === "body" ||
              selector === ":root"
            ) {
              return "#content-pub-root";
            }

            let cleaned = selector
              .replace(/^html\s+/, "")
              .replace(/^body\s+/, "")
              .replace(/^:root\s+/, "");

            if (cleaned.length === 0) {
              return "#content-pub-root";
            }

            return `${prefix} ${cleaned}`;
          },
        }),
      ],
    },
  },
}));
