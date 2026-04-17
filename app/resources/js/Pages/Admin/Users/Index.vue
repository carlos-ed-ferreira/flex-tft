<template>
  <AppLayout>
    <template #header-links>
      <Link
        :href="route('compositions.index')"
        class="text-md text-white hover:text-yellow-400 transition"
      >
        Composições Recomendadas
      </Link>

      <Link
        :href="route('compositions.my')"
        class="text-md text-white hover:text-yellow-400 transition"
      >
        Minhas Composições
      </Link>

      <Link
        :href="route('simulator.index')"
        class="text-md text-white hover:text-yellow-400 transition"
      >
        Simular Caminhos
      </Link>
    </template>

    <div class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <h1 class="text-2xl font-bold text-white mb-6">Gerenciar Usuários</h1>

      <!-- Filters -->
      <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <AppInput
          v-model="filters.search"
          type="text"
          placeholder="Buscar por nickname ou email..."
          class="flex-1"
          @keyup.enter="applyFilters"
        />
        <select
          v-model="filters.role"
          class="bg-gray-800 border border-gray-700 focus:border-blue-500 focus:ring-0 text-gray-200 rounded-lg px-3 py-2 text-sm"
          @change="applyFilters"
        >
          <option value="">Todas as roles</option>
          <option value="u">Usuário</option>
          <option value="a">Admin</option>
        </select>
        <AppButton variant="primary" size="md" @click="applyFilters">
          Filtrar
        </AppButton>
        <AppButton
          v-if="filters.search || filters.role"
          variant="ghost"
          size="md"
          @click="clearFilters"
        >
          Limpar
        </AppButton>
      </div>

      <!-- Table -->
      <div
        class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden"
      >
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead>
              <tr class="border-b border-gray-800">
                <th
                  class="px-4 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider"
                >
                  ID
                </th>
                <th
                  class="px-4 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider"
                >
                  Nickname
                </th>
                <th
                  class="px-4 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider"
                >
                  Email
                </th>
                <th
                  class="px-4 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider"
                >
                  Role
                </th>
                <th
                  class="px-4 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider"
                >
                  Criado em
                </th>
                <th
                  class="px-4 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider"
                >
                  Ações
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-800">
              <tr
                v-for="user in users.data"
                :key="user.id"
                class="hover:bg-gray-800/50 transition"
              >
                <td class="px-4 py-3 text-gray-400">#{{ user.id }}</td>
                <td class="px-4 py-3 text-white font-medium">
                  {{ user.nickname }}
                </td>
                <td class="px-4 py-3 text-gray-300">{{ user.email }}</td>
                <td class="px-4 py-3">
                  <span
                    class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium"
                    :class="
                      user.role === 'a'
                        ? 'bg-amber-900/40 text-amber-300'
                        : 'bg-gray-700 text-gray-300'
                    "
                  >
                    {{ user.role === 'a' ? 'Admin' : 'Usuário' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-gray-400">
                  {{ formatDate(user.created_at) }}
                </td>
                <td class="px-4 py-3">
                  <AppButton
                    v-if="user.role === 'u'"
                    variant="ghost"
                    size="xs"
                    @click="confirmRoleChange(user, 'a')"
                  >
                    Tornar Admin
                  </AppButton>
                  <AppButton
                    v-else
                    variant="ghost"
                    size="xs"
                    @click="confirmRoleChange(user, 'u')"
                  >
                    Tornar Usuário
                  </AppButton>
                </td>
              </tr>
              <tr v-if="users.data.length === 0">
                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                  Nenhum usuário encontrado.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          v-if="users.last_page > 1"
          class="flex items-center justify-between px-4 py-3 border-t border-gray-800"
        >
          <p class="text-sm text-gray-400">
            Mostrando {{ users.from }}–{{ users.to }} de {{ users.total }}
          </p>
          <div class="flex gap-1">
            <Link
              v-for="link in users.links"
              :key="link.label"
              :href="link.url || '#'"
              class="px-3 py-1 rounded-md text-sm transition"
              :class="[
                link.active
                  ? 'bg-blue-600 text-white'
                  : link.url
                    ? 'bg-gray-800 text-gray-300 hover:bg-gray-700'
                    : 'bg-gray-800/50 text-gray-600 cursor-not-allowed',
              ]"
              v-html="link.label"
              :preserve-scroll="true"
            />
          </div>
        </div>
      </div>

      <!-- Confirm Modal -->
      <AppModal
        :show="showConfirmModal"
        title="Confirmar alteração"
        @close="showConfirmModal = false"
      >
        <p class="text-gray-300">
          Tem certeza que deseja alterar a role de
          <strong class="text-white">{{ targetUser?.nickname }}</strong>
          para
          <strong class="text-white">{{
            targetRole === 'a' ? 'Admin' : 'Usuário'
          }}</strong
          >?
        </p>
        <template #footer>
          <div class="flex justify-end gap-3">
            <AppButton
              variant="secondary"
              size="md"
              @click="showConfirmModal = false"
            >
              Cancelar
            </AppButton>
            <AppButton
              variant="primary"
              size="md"
              :loading="roleForm.processing"
              @click="submitRoleChange"
            >
              Confirmar
            </AppButton>
          </div>
        </template>
      </AppModal>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppModal from '@/Components/UI/AppModal.vue';

const props = defineProps({
  users: Object,
  filters: Object,
});

const filters = reactive({
  search: props.filters.search,
  role: props.filters.role,
});

const showConfirmModal = ref(false);
const targetUser = ref(null);
const targetRole = ref('');
const roleForm = useForm({ role: '' });

function applyFilters() {
  router.get(
    route('admin.users.index'),
    {
      search: filters.search || undefined,
      role: filters.role || undefined,
    },
    { preserveState: true },
  );
}

function clearFilters() {
  filters.search = '';
  filters.role = '';
  router.get(route('admin.users.index'));
}

function confirmRoleChange(user, role) {
  targetUser.value = user;
  targetRole.value = role;
  showConfirmModal.value = true;
}

function submitRoleChange() {
  roleForm.role = targetRole.value;
  roleForm.put(route('admin.users.updateRole', targetUser.value.id), {
    onSuccess: () => {
      showConfirmModal.value = false;
    },
  });
}

function formatDate(dateStr) {
  if (!dateStr) return '—';
  return new Date(dateStr).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
}
</script>
