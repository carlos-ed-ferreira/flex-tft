<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
  password: '',
});

const submit = () => {
  form.post(route('password.confirm'), {
    onFinish: () => form.reset(),
  });
};
</script>

<template>
  <GuestLayout>
    <Head title="Confirmar Senha" />

    <div class="mb-4 text-sm text-gray-400">
      Esta é uma área segura da aplicação. Por favor, confirme sua senha antes
      de continuar.
    </div>

    <form @submit.prevent="submit">
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
          required
          autocomplete="current-password"
          autofocus
        />
        <p v-if="form.errors.password" class="mt-1 text-sm text-red-400">
          {{ form.errors.password }}
        </p>
      </div>

      <div class="mt-4 flex justify-end">
        <AppButton type="submit" :disabled="form.processing">
          Confirmar
        </AppButton>
      </div>
    </form>
  </GuestLayout>
</template>
