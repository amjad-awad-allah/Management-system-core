/// <reference types="vite/client" />
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import './style.css'
import App from './App.vue'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// ── Laravel Echo + Reverb WebSocket Setup ─────────────────────────────────
;(window as any).Pusher = Pusher

;(window as any).Echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST ?? 'localhost',
  wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
  wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
  forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
  enabledTransports: ['ws', 'wss'],
  authEndpoint: '/api/v1/broadcasting/auth',
})
// ─────────────────────────────────────────────────────────────────────────

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')
