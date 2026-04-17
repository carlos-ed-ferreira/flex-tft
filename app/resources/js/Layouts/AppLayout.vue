<template>
  <div class="min-h-screen text-gray-100" style="background-color: #0b0d0f">
    <!-- Header -->
    <header
      class="border-b border-gray-700 sticky top-0 z-50"
      style="background-color: #0a0b0d"
    >
      <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="h-14 flex items-center justify-between gap-4">
          <!-- Left: Logo -->
          <div class="flex flex-1 items-center min-w-0">
            <Link
              :href="route('compositions.index')"
              class="inline-flex items-center gap-2.5 hover:scale-105 transform-gpu transition-transform duration-300 will-change-transform"
              style="backface-visibility: hidden"
            >
              <div
                class="w-9 h-9 bg-gradient-to-br from-yellow-400 to-amber-600 rounded-lg flex items-center justify-center shrink-0 shadow-md shadow-yellow-500/10"
              >
                <StarIcon class="w-5 h-5 text-gray-900" />
              </div>

              <span class="text-xl font-bold tracking-tight text-white pt-1"
                >FlexTFT</span
              >
            </Link>
          </div>

          <!-- Center: Links -->
          <nav class="hidden md:flex items-center justify-center gap-8 px-4">
            <slot name="header-links" />
            <!-- Users link for admins (no visible role badge) -->
            <Link
              v-if="auth.user && auth.user.role === 'a'"
              :href="route('admin.users.index')"
              class="text-md text-white hover:text-yellow-400 transition"
            >
              Usuários
            </Link>
          </nav>

          <!-- Right: Buttons / Auth -->
          <div class="flex flex-1 items-center justify-end gap-3 min-w-0">
            <slot name="header-actions" />

            <!-- Auth section -->
            <template v-if="auth.user">
              <AppButton
                variant="secondary"
                pill
                class="text-sm"
                @click="showLogoutModal = true"
              >
                Sair
              </AppButton>
            </template>

            <template v-else>
              <AppButton
                variant="secondary"
                pill
                :href="route('login')"
                class="text-sm"
              >
                Entrar
              </AppButton>
            </template>
          </div>
        </div>

        <!-- Mobile links -->
        <div
          v-if="$slots['header-links'] || (auth.user && auth.user.role === 'a')"
          class="md:hidden border-t border-gray-800 py-3"
        >
          <nav class="flex flex-wrap items-center gap-6">
            <slot name="header-links" />
            <Link
              v-if="auth.user && auth.user.role === 'a'"
              :href="route('admin.users.index')"
              class="text-md text-white hover:text-yellow-400 transition"
            >
              Usuários
            </Link>
          </nav>
        </div>
      </div>
    </header>

    <!-- Content -->
    <main>
      <slot />
    </main>

    <!-- Toast notifications -->
    <Toast />

    <!-- Logout confirmation modal -->
    <AppModal
      :show="showLogoutModal"
      title="Sair da conta"
      max-width="sm"
      @close="showLogoutModal = false"
    >
      <p class="text-sm text-gray-300">
        Tem certeza que deseja sair da sua conta?
      </p>

      <template #footer>
        <div class="flex items-center justify-end gap-3">
          <AppButton
            variant="secondary"
            size="sm"
            @click="showLogoutModal = false"
          >
            Cancelar
          </AppButton>
          <AppButton variant="danger" size="sm" @click="logout">
            Sair
          </AppButton>
        </div>
      </template>
    </AppModal>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { StarIcon } from '@heroicons/vue/20/solid';
import Toast from '@/Components/Toast.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppModal from '@/Components/UI/AppModal.vue';

const page = usePage();
const auth = computed(() => page.props.auth);

const showLogoutModal = ref(false);

function logout() {
  showLogoutModal.value = false;
  router.post(route('logout'));
}
</script>
