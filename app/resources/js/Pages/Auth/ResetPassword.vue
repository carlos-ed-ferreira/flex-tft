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

    <h2 class="text-xl font-bold text-white mb-6">Redefinir Senha</h2>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label for="email" class="block text-sm font-medium text-gray-300 mb-1"
          >Email</label
        >
        <AppInput
          id="email"
          type="email"
          v-model="form.email"
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
          >Nova Senha</label
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
        Redefinir Senha
      </AppButton>
    </form>
  </GuestLayout>
</template>
