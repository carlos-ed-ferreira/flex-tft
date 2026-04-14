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

    <h2 class="text-xl font-bold text-white mb-4">Esqueci a Senha</h2>

    <p class="mb-4 text-sm text-gray-400">
      Informe seu email e enviaremos um link para redefinir sua senha.
    </p>

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

      <AppButton
        type="submit"
        variant="primary"
        size="md"
        :loading="form.processing"
        :disabled="form.processing"
        class="w-full justify-center"
      >
        Enviar Link
      </AppButton>

      <p class="text-center text-sm text-gray-400">
        <Link
          :href="route('login')"
          class="text-blue-400 hover:text-blue-300 transition"
        >
          Voltar ao login
        </Link>
      </p>
    </form>
  </GuestLayout>
</template>
