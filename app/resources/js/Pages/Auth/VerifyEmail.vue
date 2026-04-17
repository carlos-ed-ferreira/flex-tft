<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
  status: {
    type: String,
  },
});

const form = useForm({});

const submit = () => {
  form.post(route('verification.send'));
};

const verificationLinkSent = computed(
  () => props.status === 'verification-link-sent',
);
</script>

<template>
  <GuestLayout>
    <Head title="Verificar E-mail" />

    <div class="mb-8">
      <h2 class="text-2xl font-bold text-white mb-2">Verifique seu E-mail</h2>
      <p class="text-md text-gray-400">
        Obrigado por se registrar! Antes de começar, verifique seu endereço de
        e-mail clicando no link que acabamos de enviar. Se não recebeu o e-mail,
        enviaremos outro com prazer.
      </p>
    </div>

    <div
      v-if="verificationLinkSent"
      class="mb-4 p-3 rounded-lg bg-green-500/10 border border-green-500/20 text-sm font-medium text-green-400"
    >
      Um novo link de verificação foi enviado para o endereço de e-mail
      informado durante o registro.
    </div>

    <form @submit.prevent="submit" class="space-y-5">
      <div class="pt-2">
        <AppButton
          type="submit"
          variant="primary"
          size="md"
          :loading="form.processing"
          :disabled="form.processing"
          class="w-full justify-center py-2.5 text-base"
        >
          Reenviar E-mail de Verificação
        </AppButton>
      </div>

      <div class="mt-6 text-center text-gray-400">
        <Link
          :href="route('logout')"
          method="post"
          as="button"
          class="text-blue-400 hover:text-blue-300 transition-colors"
        >
          Sair da conta
        </Link>
      </div>
    </form>
  </GuestLayout>
</template>
