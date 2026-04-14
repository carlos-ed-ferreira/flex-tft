<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
  nickname: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<template>
  <GuestLayout>
    <Head title="Criar Conta" />

    <h2 class="text-xl font-bold text-white mb-6">Criar Conta</h2>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label
          for="nickname"
          class="block text-sm font-medium text-gray-300 mb-1"
          >Nickname</label
        >
        <AppInput
          id="nickname"
          type="text"
          v-model="form.nickname"
          placeholder="seunick"
          required
          autofocus
          autocomplete="username"
        />
        <p class="mt-1 text-xs text-gray-500">
          Apenas letras, números, traços e underscores. Máximo 20 caracteres.
        </p>
        <p v-if="form.errors.nickname" class="mt-1 text-sm text-red-400">
          {{ form.errors.nickname }}
        </p>
      </div>

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
          autocomplete="email"
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
          autocomplete="new-password"
        />
        <p v-if="form.errors.password" class="mt-1 text-sm text-red-400">
          {{ form.errors.password }}
        </p>
      </div>

      <div>
        <label
          for="password_confirmation"
          class="block text-sm font-medium text-gray-300 mb-1"
          >Confirmar Senha</label
        >
        <AppInput
          id="password_confirmation"
          type="password"
          v-model="form.password_confirmation"
          placeholder="••••••••"
          required
          autocomplete="new-password"
        />
        <p
          v-if="form.errors.password_confirmation"
          class="mt-1 text-sm text-red-400"
        >
          {{ form.errors.password_confirmation }}
        </p>
      </div>

      <AppButton
        type="submit"
        variant="primary"
        size="md"
        :loading="form.processing"
        :disabled="form.processing"
        class="w-full justify-center"
      >
        Criar Conta
      </AppButton>

      <p class="text-center text-sm text-gray-400">
        Já tem conta?
        <Link
          :href="route('login')"
          class="text-blue-400 hover:text-blue-300 transition"
        >
          Entrar
        </Link>
      </p>
    </form>
  </GuestLayout>
</template>
