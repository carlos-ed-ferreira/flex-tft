<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
  status: { type: String },
});

const form = useForm({
  email: '',
});

const submit = () => {
  form.post(route('password.email'));
};
</script>

<template>
  <GuestLayout>
    <Head title="Esqueci a Senha" />

    <div class="mb-8">
      <h2 class="text-2xl font-bold text-white mb-2">Esqueci a Senha</h2>
      <p class="text-md text-gray-400">
        Informe seu email e enviaremos um link para redefinir sua senha.
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

      <div class="pt-2">
        <AppButton
          type="submit"
          variant="primary"
          size="md"
          :loading="form.processing"
          :disabled="form.processing"
          class="w-full justify-center py-2.5 text-base"
        >
          Enviar Link
        </AppButton>
      </div>

      <div class="mt-6 text-center text-gray-400">
        <Link
          :href="route('login')"
          class="text-blue-400 hover:text-blue-300 transition-colors"
        >
          Voltar ao login
        </Link>
      </div>
    </form>
  </GuestLayout>
</template>
