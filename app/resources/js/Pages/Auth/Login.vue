<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
  canResetPassword: { type: Boolean },
  status: { type: String },
});

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <GuestLayout>
    <Head title="Login" />

    <div class="mb-8">
      <h2 class="text-2xl font-bold text-white mb-2">Bem-vindo de volta</h2>
      <p class="text-md text-gray-400">
        Acesse sua conta para salvar e gerenciar suas composições favoritas.
      </p>
    </div>

    <div
      v-if="status"
      class="mb-4 p-3 rounded-lg bg-green-500/10 border border-green-500/20 text-sm font-medium text-green-400"
    >
      {{ status }}
    </div>

    <form @submit.prevent="submit" class="space-y-5">
      <div class="space-y-1.5">
        <label for="email" class="block font-medium text-gray-300">Email</label>
        <AppInput
          id="email"
          type="email"
          v-model="form.email"
          placeholder="seu@email.com"
          autofocus
          autocomplete="username"
          :error="form.errors.email"
          class="bg-gray-900/50 focus:bg-gray-900"
        />
        <p v-if="form.errors.email" class="text-sm text-red-500">
          {{ form.errors.email }}
        </p>
      </div>

      <div class="space-y-1.5">
        <label
          for="password"
          class="block font-medium text-gray-300 flex justify-between items-center"
        >
          <span>Senha</span>
          <Link
            v-if="canResetPassword"
            :href="route('password.request')"
            class="text-blue-400 hover:text-blue-300 transition-colors"
          >
            Esqueceu a senha?
          </Link>
        </label>
        <AppInput
          id="password"
          type="password"
          v-model="form.password"
          placeholder="••••••••"
          autocomplete="current-password"
          :error="form.errors.password"
          class="bg-gray-900/50 focus:bg-gray-900"
        />
        <p v-if="form.errors.password" class="text-sm text-red-500">
          {{ form.errors.password }}
        </p>
      </div>

      <div class="flex items-center">
        <label class="flex items-center gap-2.5 cursor-pointer relative">
          <input
            type="checkbox"
            v-model="form.remember"
            class="w-4 h-4 rounded border-gray-700 bg-gray-900/50 text-blue-500 focus:ring-blue-500/50 focus:ring-offset-0 transition-all checked:border-blue-500 cursor-pointer"
          />
          <span class="text-gray-400 select-none"
            >Lembrar-me neste dispositivo</span
          >
        </label>
      </div>

      <div class="pt-2">
        <AppButton
          type="submit"
          variant="primary"
          size="md"
          :loading="form.processing"
          :disabled="form.processing"
          class="w-full justify-center py-2.5 text-base"
        >
          Entrar
        </AppButton>
      </div>

      <div class="mt-6 text-center text-gray-400">
        Não tem uma conta?
        <Link
          v-if="canResetPassword"
          :href="route('register')"
          class="text-blue-400 hover:text-blue-300 transition-colors"
        >
          Criar conta
        </Link>
      </div>
    </form>
  </GuestLayout>
</template>
