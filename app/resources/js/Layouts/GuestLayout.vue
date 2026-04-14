<template>
  <div
    class="min-h-screen bg-gray-950 text-gray-100 flex flex-col items-center justify-center px-4"
  >
    <div class="mb-8">
      <Link
        :href="route('compositions.index')"
        class="flex items-center gap-2 hover:opacity-80 transition"
      >
        <div
          class="w-10 h-10 bg-gradient-to-br from-yellow-400 to-amber-600 rounded-lg flex items-center justify-center"
        >
          <StarIcon class="w-6 h-6 text-gray-900" />
        </div>
        <span class="text-2xl font-bold text-white">FlexTFT</span>
      </Link>
    </div>

    <div
      class="w-full max-w-md bg-gray-900 border border-gray-800 rounded-xl p-8"
    >
      <slot />
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { StarIcon } from '@heroicons/vue/20/solid';
</script>
