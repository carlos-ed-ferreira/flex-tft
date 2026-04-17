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

    <div class="mb-8">
      <h2 class="text-2xl font-bold text-white mb-2">Criar Conta</h2>
      <p class="text-md text-gray-400">
        Crie sua conta para salvar e gerenciar suas composições favoritas.
      </p>
    </div>

    <form @submit.prevent="submit" class="space-y-5">
      <div class="space-y-1.5">
        <label for="nickname" class="block font-medium text-gray-300"
          >Nickname</label
        >
        <AppInput
          id="nickname"
          type="text"
          v-model="form.nickname"
          placeholder="seu_nickname"
          autofocus
          autocomplete="username"
          :error="form.errors.nickname"
          class="bg-gray-900/50 focus:bg-gray-900"
        />
        <p class="text-xs text-gray-400">
          Apenas letras, números, traços e underscores. Máximo 20 caracteres.
        </p>
        <p v-if="form.errors.nickname" class="text-sm text-red-500">
          {{ form.errors.nickname }}
        </p>
      </div>

      <div class="space-y-1.5">
        <label for="email" class="block font-medium text-gray-300">Email</label>
        <AppInput
          id="email"
          type="email"
          v-model="form.email"
          placeholder="seu@email.com"
          autocomplete="email"
          :error="form.errors.email"
          class="bg-gray-900/50 focus:bg-gray-900"
        />
        <p v-if="form.errors.email" class="text-sm text-red-500">
          {{ form.errors.email }}
        </p>
      </div>

      <div class="space-y-1.5">
        <label for="password" class="block font-medium text-gray-300"
          >Senha</label
        >
        <AppInput
          id="password"
          type="password"
          v-model="form.password"
          placeholder="••••••••"
          autocomplete="new-password"
          :error="form.errors.password"
          class="bg-gray-900/50 focus:bg-gray-900"
        />
        <p v-if="form.errors.password" class="text-sm text-red-500">
          {{ form.errors.password }}
        </p>
      </div>

      <div class="space-y-1.5">
        <label
          for="password_confirmation"
          class="block font-medium text-gray-300"
          >Confirmar Senha</label
        >
        <AppInput
          id="password_confirmation"
          type="password"
          v-model="form.password_confirmation"
          placeholder="••••••••"
          autocomplete="new-password"
          :error="form.errors.password_confirmation"
          class="bg-gray-900/50 focus:bg-gray-900"
        />
        <p
          v-if="form.errors.password_confirmation"
          class="text-sm text-red-500"
        >
          {{ form.errors.password_confirmation }}
        </p>
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
          Criar Conta
        </AppButton>
      </div>

      <div class="mt-6 text-center text-gray-400">
        Já tem conta?
        <Link
          :href="route('login')"
          class="text-blue-400 hover:text-blue-300 transition-colors"
        >
          Entrar
        </Link>
      </div>
    </form>
  </GuestLayout>
</template>
