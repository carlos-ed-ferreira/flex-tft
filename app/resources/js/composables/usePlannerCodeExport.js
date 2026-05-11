import { ref } from 'vue';

export function usePlannerCodeExport() {
  const exportingPlannerCompositionId = ref(null);
  const plannerExportCode = ref('');
  const plannerExportMessage = ref('');
  const plannerExportError = ref('');
  const showPlannerExportModal = ref(false);

  async function exportPlannerCode(composition) {
    exportingPlannerCompositionId.value = composition.id;
    plannerExportMessage.value = '';
    plannerExportError.value = '';

    try {
      const response = await fetch(
        route('compositions.planner.export', composition.id),
        { headers: { Accept: 'application/json' } },
      );
      const data = await response.json().catch(() => ({}));

      if (!response.ok) {
        throw new Error(
          data.message || 'Não foi possível exportar o código do planner.',
        );
      }

      plannerExportCode.value = data.code;

      if (await copyPlannerCode(data.code)) {
        plannerExportMessage.value = 'Código do planner copiado.';
      } else {
        plannerExportMessage.value = 'Copie o código do planner manualmente.';
        showPlannerExportModal.value = true;
      }
    } catch (error) {
      plannerExportError.value =
        error.message || 'Não foi possível exportar o código do planner.';
    } finally {
      exportingPlannerCompositionId.value = null;
    }
  }

  async function copyPlannerCode(code) {
    if (!navigator.clipboard?.writeText) return false;

    try {
      await navigator.clipboard.writeText(code);

      return true;
    } catch {
      return false;
    }
  }

  function closePlannerExportModal() {
    showPlannerExportModal.value = false;
  }

  return {
    exportingPlannerCompositionId,
    plannerExportCode,
    plannerExportMessage,
    plannerExportError,
    showPlannerExportModal,
    exportPlannerCode,
    closePlannerExportModal,
  };
}
