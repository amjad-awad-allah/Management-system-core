<template>
  <div class="min-h-screen flex items-center justify-center relative overflow-hidden bg-gray-900 px-4">
    <!-- Animated Glow Backgrounds -->
    <div class="absolute top-1/4 left-1/4 w-96 h-96 rounded-full bg-purple-600/20 blur-[120px] pointer-events-none animate-pulse"></div>
    <div class="absolute bottom-1/4 right-1/4 w-96 h-96 rounded-full bg-blue-600/20 blur-[120px] pointer-events-none animate-pulse" style="animation-delay: 2s;"></div>

    <div class="relative z-10 max-w-lg w-full text-center p-8 sm:p-12 rounded-3xl bg-white/10 dark:bg-black/30 backdrop-blur-2xl border border-white/15 shadow-2xl">
      <!-- 404 Big Gradient Number -->
      <div class="inline-block mb-4">
        <span class="text-8xl sm:text-9xl font-black bg-gradient-to-r from-purple-400 via-indigo-300 to-blue-400 bg-clip-text text-transparent tracking-tighter drop-shadow-sm">
          404
        </span>
      </div>

      <!-- Icon & Title -->
      <h1 class="text-2xl sm:text-3xl font-extrabold text-white mb-2">
        Seite nicht gefunden
      </h1>
      <p class="text-sm text-gray-300 mb-8 leading-relaxed">
        Die von Ihnen angeforderte Seite existiert nicht oder wurde verschoben. Überprüfen Sie die URL oder kehren Sie zur Übersicht zurück.
      </p>

      <!-- Action Buttons -->
      <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
        <router-link
          to="/"
          class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl bg-purple-600 hover:bg-purple-700 active:scale-95 text-white text-xs font-bold shadow-lg shadow-purple-500/25 transition-all cursor-pointer"
        >
          <HomeIcon class="w-4 h-4" />
          <span>Zum Dashboard</span>
        </router-link>

        <router-link
          to="/guide"
          class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl bg-white/10 hover:bg-white/20 active:scale-95 text-white text-xs font-semibold backdrop-blur-md border border-white/20 transition-all cursor-pointer"
        >
          <BookOpenIcon class="w-4 h-4" />
          <span>Handbuch öffnen</span>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { HomeIcon, BookOpenIcon } from '@heroicons/vue/24/outline'
</script>
