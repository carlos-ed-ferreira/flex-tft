<template>
  <div class="min-h-screen bg-gray-950 text-gray-100">
    <!-- Header -->
    <header class="bg-gray-900 border-b border-gray-800 sticky top-0 z-50">
      <div
        class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between"
      >
        <Link
          :href="route('compositions.index')"
          class="flex items-center gap-2 hover:opacity-80 transition"
        >
          <div
            class="w-8 h-8 bg-gradient-to-br from-yellow-400 to-amber-600 rounded-lg flex items-center justify-center"
          >
            <StarIcon class="w-5 h-5 text-gray-900" />
          </div>
          <span class="text-lg font-bold text-white">FlexTFT</span>
        </Link>

        <nav class="flex items-center gap-3">
          <slot name="header-actions" />
        </nav>
      </div>
    </header>

    <!-- Content -->
    <main>
      <slot />
    </main>

    <!-- Toast notifications -->
    <Toast />
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { StarIcon } from '@heroicons/vue/20/solid';
import Toast from '@/Components/Toast.vue';
</script>
