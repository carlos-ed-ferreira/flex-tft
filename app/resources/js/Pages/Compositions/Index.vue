<template>
    <AppLayout>
        <template #header-actions>
            <Link
                :href="route('compositions.create')"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition"
            >
                + Nova Composição
            </Link>
        </template>

        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Empty state -->
            <div v-if="compositions.length === 0" class="text-center py-20">
                <div class="w-20 h-20 mx-auto mb-6 bg-gray-800 rounded-2xl flex items-center justify-center">
                    <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-300 mb-2">Nenhuma composição ainda</h2>
                <p class="text-gray-500 mb-6">Crie sua primeira composição para começar a planejar.</p>
                <Link
                    :href="route('compositions.create')"
                    class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition"
                >
                    + Nova Composição
                </Link>
            </div>

            <!-- Compositions grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <div
                    v-for="comp in compositions"
                    :key="comp.id"
                    class="bg-gray-900 border border-gray-800 rounded-xl p-5 hover:border-gray-700 transition group"
                >
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h3 class="text-lg font-semibold text-white">{{ comp.name }}</h3>
                            <p class="text-xs text-gray-500">{{ comp.updated_at }}</p>
                        </div>
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition">
                            <Link
                                :href="route('compositions.edit', comp.id)"
                                class="p-2 text-gray-400 hover:text-blue-400 hover:bg-gray-800 rounded-lg transition"
                                title="Editar"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </Link>
                            <button
                                @click="confirmDelete(comp)"
                                class="p-2 text-gray-400 hover:text-red-400 hover:bg-gray-800 rounded-lg transition"
                                title="Excluir"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Level summary -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <div
                            v-for="level in comp.levels"
                            :key="level.level"
                            class="flex items-center gap-1 px-2 py-1 rounded text-xs"
                            :class="level.championCount > 0 ? 'bg-blue-900/40 text-blue-300' : 'bg-gray-800/50 text-gray-600'"
                        >
                            <span class="font-medium">Lv{{ level.level }}</span>
                            <span v-if="level.championCount > 0">{{ level.championCount }}u</span>
                        </div>
                    </div>

                    <!-- Notes preview -->
                    <p v-if="comp.notes" class="mt-3 text-sm text-gray-500 line-clamp-2">{{ comp.notes }}</p>

                    <!-- Click to open -->
                    <Link
                        :href="route('compositions.edit', comp.id)"
                        class="mt-3 block w-full text-center py-2 text-sm text-gray-400 hover:text-white bg-gray-800/50 hover:bg-gray-800 rounded-lg transition"
                    >
                        Abrir Editor
                    </Link>
                </div>
            </div>
        </div>

        <!-- Delete confirmation modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" @click.self="showDeleteModal = false">
                <div class="bg-gray-900 border border-gray-700 rounded-xl p-6 max-w-sm w-full">
                    <h3 class="text-lg font-semibold text-white mb-2">Excluir composição?</h3>
                    <p class="text-gray-400 text-sm mb-6">
                        Tem certeza que deseja excluir "<strong class="text-gray-200">{{ deleteTarget?.name }}</strong>"? Essa ação não pode ser desfeita.
                    </p>
                    <div class="flex justify-end gap-3">
                        <button
                            @click="showDeleteModal = false"
                            class="px-4 py-2 text-sm text-gray-400 hover:text-white bg-gray-800 hover:bg-gray-700 rounded-lg transition"
                        >
                            Cancelar
                        </button>
                        <button
                            @click="executeDelete"
                            class="px-4 py-2 text-sm text-white bg-red-600 hover:bg-red-700 rounded-lg transition"
                        >
                            Excluir
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    compositions: {
        type: Array,
        default: () => [],
    },
});

const showDeleteModal = ref(false);
const deleteTarget = ref(null);

function confirmDelete(comp) {
    deleteTarget.value = comp;
    showDeleteModal.value = true;
}

function executeDelete() {
    if (!deleteTarget.value) return;
    router.delete(route('compositions.destroy', deleteTarget.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
            deleteTarget.value = null;
        },
    });
}
</script>
