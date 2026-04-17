<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
  email: { type: String, required: true },
  token: { type: String, required: true },
});

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post(route('password.store'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<template>
  <GuestLayout>
    <Head title="Redefinir Senha" />

    <div class="mb-8">
      <h2 class="text-2xl font-bold text-white mb-2">Redefinir Senha</h2>
      <p class="text-md text-gray-400">
        Escolha uma nova senha para sua conta.
      </p>
    </div>

    <form @submit.prevent="submit" class="space-y-5">
      <div class="space-y-1.5">
        <label for="email" class="block font-medium text-gray-300">Email</label>
        <AppInput
          id="email"
          type="email"
          v-model="form.email"
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
        <label for="password" class="block font-medium text-gray-300"
          >Nova Senha</label
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
          Redefinir Senha
        </AppButton>
      </div>
    </form>
  </GuestLayout>
</template>
