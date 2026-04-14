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

          <!-- Auth section -->
          <template v-if="auth.user">
            <Link
              v-if="auth.user.role === 'a'"
              :href="route('admin.users.index')"
              class="text-sm text-amber-400 hover:text-amber-300 transition"
            >
              Admin
            </Link>
            <span class="text-sm text-gray-400">{{ auth.user.nickname }}</span>
            <Link
              :href="route('logout')"
              method="post"
              as="button"
              class="text-sm text-gray-400 hover:text-white transition"
            >
              Sair
            </Link>
          </template>
          <template v-else>
            <Link
              :href="route('login')"
              class="text-sm text-gray-400 hover:text-white transition"
            >
              Entrar
            </Link>
            <Link
              :href="route('register')"
              class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition"
            >
              Criar Conta
            </Link>
          </template>
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
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { StarIcon } from '@heroicons/vue/20/solid';
import Toast from '@/Components/Toast.vue';

const auth = computed(() => usePage().props.auth);
</script>
