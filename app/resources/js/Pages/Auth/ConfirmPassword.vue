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

    <div class="mb-8">
      <h2 class="text-2xl font-bold text-white mb-2">Área Segura</h2>
      <p class="text-md text-gray-400">
        Esta é uma área segura da aplicação. Por favor, confirme sua senha antes
        de continuar.
      </p>
    </div>

    <form @submit.prevent="submit" class="space-y-5">
      <div class="space-y-1.5">
        <label for="password" class="block font-medium text-gray-300"
          >Senha</label
        >
        <AppInput
          id="password"
          type="password"
          v-model="form.password"
          placeholder="••••••••"
          autocomplete="current-password"
          autofocus
          :error="form.errors.password"
          class="bg-gray-900/50 focus:bg-gray-900"
        />
        <p v-if="form.errors.password" class="text-sm text-red-500">
          {{ form.errors.password }}
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
          Confirmar
        </AppButton>
      </div>
    </form>
  </GuestLayout>
</template>
