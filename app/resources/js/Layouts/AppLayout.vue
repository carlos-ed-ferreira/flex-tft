<template>
    <div class="min-h-screen bg-gray-950 text-gray-100">
        <!-- Header -->
        <header class="bg-gray-900 border-b border-gray-800 sticky top-0 z-50">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
                <Link :href="route('compositions.index')" class="flex items-center gap-2 hover:opacity-80 transition">
                    <div class="w-8 h-8 bg-gradient-to-br from-yellow-400 to-amber-600 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-gray-900" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2L13.09 8.26L20 9.27L15 14.14L16.18 21.02L10 17.77L3.82 21.02L5 14.14L0 9.27L6.91 8.26L10 2Z"/>
                        </svg>
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
import Toast from '@/Components/Toast.vue';
</script>
