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
  authorizer: (channel: any) => {
    return {
      authorize: (socketId: string, callback: Function) => {
        const token = localStorage.getItem('token')
        fetch('/api/v1/broadcasting/auth', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': token ? `Bearer ${token}` : '',
          },
          body: JSON.stringify({
            socket_id: socketId,
            channel_name: channel.name,
          }),
        })
          .then(async (res) => {
            if (!res.ok) {
              const err = await res.json().catch(() => ({ message: 'Forbidden' }))
              callback(err, null)
            } else {
              const data = await res.json()
              callback(null, data)
            }
          })
          .catch((err) => callback(err, null))
      },
    }
  },
})
// ─────────────────────────────────────────────────────────────────────────

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')
