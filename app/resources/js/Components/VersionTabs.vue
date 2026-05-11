<template>
  <div
    class="flex min-h-9 w-full max-w-[42rem] flex-wrap items-center gap-1 rounded-lg border border-gray-800 bg-gray-950/80 p-1 shadow-inner shadow-black/30"
  >
    <div class="flex min-w-0 flex-wrap items-center gap-1">
      <div
        v-for="v in versions"
        :key="v.version"
        class="relative flex min-w-0 items-center"
      >
        <template v-if="editingVersion === v.version">
          <input
            ref="renameInput"
            v-model="editingLabel"
            @keydown.enter.prevent="confirmRename"
            @keydown.escape.prevent="cancelRename"
            @blur="confirmRename"
            class="h-7 w-36 min-w-0 rounded-md border border-blue-400/60 bg-blue-950 px-2 text-xs font-semibold text-white outline-none placeholder:text-blue-200/50 focus:border-blue-300"
            :placeholder="`${level}.${v.version}`"
          />
        </template>
        <template v-else>
          <button
            @click="emit('select', v.version)"
            class="flex h-7 max-w-40 items-center rounded-md border px-2.5 text-left text-xs font-semibold transition-all"
            :class="[
              activeVersion === v.version
                ? 'border-blue-400/60 bg-blue-600 text-white shadow-sm shadow-blue-600/20'
                : 'border-gray-800 bg-gray-950 text-gray-300 hover:border-gray-700 hover:bg-gray-900 hover:text-white',
            ]"
          >
            <span class="truncate">{{
              v.label || `${level}.${v.version}`
            }}</span>
          </button>
        </template>
      </div>
    </div>

    <div class="ml-auto flex shrink-0 items-center gap-1">
      <button
        @click.stop="startActiveRename"
        class="flex h-7 w-7 items-center justify-center rounded-md border border-gray-800 bg-gray-950 text-white transition-colors hover:border-blue-700 hover:bg-gray-900 hover:text-blue-300"
        :title="`Renomear versão ${level}.${activeVersion}`"
      >
        <PencilSquareIcon class="h-3.5 w-3.5" />
      </button>
      <button
        @click.stop="emit('delete', activeVersion)"
        :disabled="versions.length <= 1"
        class="flex h-7 w-7 items-center justify-center rounded-md border border-red-900/70 bg-red-950/70 text-red-200 transition-colors hover:border-red-700 hover:bg-red-900/70 hover:text-red-100 disabled:cursor-not-allowed disabled:border-gray-800 disabled:bg-gray-950 disabled:text-gray-600"
        :title="`Remover versão ${level}.${activeVersion}`"
      >
        <TrashIcon class="h-3.5 w-3.5" />
      </button>
    </div>

    <button
      @click="$emit('add')"
      class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-gray-700 bg-gray-950 text-white transition-colors hover:border-blue-600 hover:bg-gray-900 hover:text-blue-300"
      title="Adicionar nova versão"
    >
      <PlusIcon class="h-4 w-4" />
    </button>
  </div>
</template>

<script setup>
import { ref, nextTick } from 'vue';
import {
  PencilSquareIcon,
  TrashIcon,
  PlusIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  versions: { type: Array, required: true },
  activeVersion: { type: Number, required: true },
  level: { type: Number, required: true },
});

const emit = defineEmits(['select', 'add', 'delete', 'rename']);

const editingVersion = ref(null);
const editingLabel = ref('');
const renameInput = ref(null);

function startRename(v) {
  editingVersion.value = v.version;
  editingLabel.value = v.label || '';
  nextTick(() => {
    if (renameInput.value) {
      const el = Array.isArray(renameInput.value)
        ? renameInput.value[0]
        : renameInput.value;
      if (el) el.select();
    }
  });
}

function startActiveRename() {
  const version = props.versions.find((v) => v.version === props.activeVersion);
  if (!version) return;

  startRename(version);
}

function confirmRename() {
  if (editingVersion.value === null) return;
  emit('rename', editingVersion.value, editingLabel.value.trim() || null);
  editingVersion.value = null;
  editingLabel.value = '';
}

function cancelRename() {
  editingVersion.value = null;
  editingLabel.value = '';
}
</script>
