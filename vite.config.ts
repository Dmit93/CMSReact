import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react-swc'
import tailwindcss from '@tailwindcss/vite'
// https://vite.dev/config/
export default defineConfig({
  plugins: [react(), tailwindcss()],
  server: {
    proxy: {
      // Проксирование всех запросов к API через прокси-сервер
      '/api': {
        target: 'http://localhost/cms/backend/api',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api/, ''),
        configure: (proxy) => {
          // Включаем детальное логирование для отладки
          proxy.on('error', (err) => {
            console.error('Proxy error:', err);
          });
          proxy.on('proxyReq', (proxyReq, req) => {
            console.log('Request:', req.method, req.url);
          });
          proxy.on('proxyRes', (proxyRes, req) => {
            console.log('Response:', proxyRes.statusCode, req.url);
          });
        }
      },
      // Проксирование запросов для модуля магазина
      '/shop': {
        target: 'http://localhost/cms/backend/api',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/shop/, '/shop'),
        configure: (proxy) => {
          proxy.on('error', (err) => {
            console.error('Shop API proxy error:', err);
          });
          proxy.on('proxyReq', (proxyReq, req) => {
            console.log('Shop request:', req.method, req.url);
          });
          proxy.on('proxyRes', (proxyRes, req) => {
            console.log('Shop response:', proxyRes.statusCode, req.url);
          });
        }
      }
    }
  }
})
