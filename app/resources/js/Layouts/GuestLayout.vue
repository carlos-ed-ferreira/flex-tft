<template>
  <div
    class="min-h-screen bg-[#09090b] text-gray-100 flex flex-col items-center justify-center px-4 relative overflow-hidden"
  >
    <!-- Background highlight glow -->
    <div
      class="absolute top-0 w-full max-w-2xl h-[500px] bg-blue-600/10 blur-[120px] rounded-full pointer-events-none translate-y-[-50%]"
    ></div>

    <div class="mb-10 relative z-10 flex justify-center">
      <Link
        :href="route('compositions.index')"
        class="inline-flex items-center gap-3 hover:scale-105 transform-gpu transition-transform duration-300 will-change-transform drop-shadow-sm"
        style="backface-visibility: hidden"
      >
        <div
          class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-amber-600 rounded-xl flex items-center justify-center shrink-0"
        >
          <StarIcon class="w-7 h-7 text-gray-900" />
        </div>
        <span class="text-3xl font-extrabold tracking-tight text-white pt-1"
          >FlexTFT</span
        >
      </Link>
    </div>

    <div
      class="w-full max-w-md bg-white/[0.02] border border-white/[0.05] ring-1 ring-white/5 rounded-3xl p-8 sm:p-10 backdrop-blur-xl relative z-10"
    >
      <slot />
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { StarIcon } from '@heroicons/vue/20/solid';
</script>
