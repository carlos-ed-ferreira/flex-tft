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

    <h2 class="text-xl font-bold text-white mb-6">Entrar</h2>

    <div v-if="status" class="mb-4 text-sm font-medium text-green-400">
      {{ status }}
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label for="email" class="block text-sm font-medium text-gray-300 mb-1"
          >Email</label
        >
        <AppInput
          id="email"
          type="email"
          v-model="form.email"
          placeholder="seu@email.com"
          required
          autofocus
          autocomplete="username"
        />
        <p v-if="form.errors.email" class="mt-1 text-sm text-red-400">
          {{ form.errors.email }}
        </p>
      </div>

      <div>
        <label
          for="password"
          class="block text-sm font-medium text-gray-300 mb-1"
          >Senha</label
        >
        <AppInput
          id="password"
          type="password"
          v-model="form.password"
          placeholder="••••••••"
          required
          autocomplete="current-password"
        />
        <p v-if="form.errors.password" class="mt-1 text-sm text-red-400">
          {{ form.errors.password }}
        </p>
      </div>

      <div class="flex items-center justify-between">
        <label class="flex items-center gap-2 cursor-pointer">
          <input
            type="checkbox"
            v-model="form.remember"
            class="rounded border-gray-600 bg-gray-800 text-blue-600 focus:ring-blue-500 focus:ring-offset-0"
          />
          <span class="text-sm text-gray-400">Lembrar de mim</span>
        </label>

        <Link
          v-if="canResetPassword"
          :href="route('password.request')"
          class="text-sm text-gray-400 hover:text-blue-400 transition"
        >
          Esqueceu a senha?
        </Link>
      </div>

      <AppButton
        type="submit"
        variant="primary"
        size="md"
        :loading="form.processing"
        :disabled="form.processing"
        class="w-full justify-center"
      >
        Entrar
      </AppButton>

      <p class="text-center text-sm text-gray-400">
        Não tem conta?
        <Link
          :href="route('register')"
          class="text-blue-400 hover:text-blue-300 transition"
        >
          Criar conta
        </Link>
      </p>
    </form>
  </GuestLayout>
</template>
