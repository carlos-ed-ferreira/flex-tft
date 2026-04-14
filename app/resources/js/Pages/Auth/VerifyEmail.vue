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

    <div class="mb-4 text-sm text-gray-400">
      Obrigado por se registrar! Antes de começar, verifique seu endereço de
      e-mail clicando no link que acabamos de enviar. Se não recebeu o e-mail,
      enviaremos outro com prazer.
    </div>

    <div
      class="mb-4 text-sm font-medium text-green-400"
      v-if="verificationLinkSent"
    >
      Um novo link de verificação foi enviado para o endereço de e-mail
      informado durante o registro.
    </div>

    <form @submit.prevent="submit">
      <div class="mt-4 flex items-center justify-between">
        <AppButton type="submit" :disabled="form.processing">
          Reenviar E-mail de Verificação
        </AppButton>

        <Link
          :href="route('logout')"
          method="post"
          as="button"
          class="text-sm text-gray-400 underline hover:text-white transition"
        >
          Sair
        </Link>
      </div>
    </form>
  </GuestLayout>
</template>
