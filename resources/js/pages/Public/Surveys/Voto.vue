<script setup lang="ts">
import { useToast } from '@/composables/useToast'; // Importar el composable de toast
import { computed, onMounted, onUnmounted, ref } from 'vue';

// Layouts & Components

// Tipos (asumiendo que están definidos en global.d.ts o en otro lugar)
import type { CombinatoricResource, SurveyResource } from '@/types/global';

// --- Props del componente ---
interface Props {
    survey: SurveyResource; // Datos de la encuesta actual, incluyendo progreso
    nextCombination: CombinatoricResource | null; // La próxima combinación a votar
    userProgress: any; // Progreso del usuario en la encuesta
}

const props = defineProps<Props>();
console.log(props);
// --- Composables ---
const { success, error } = useToast();

// --- Estados reactivos ---
const nextCombination = ref<CombinatoricResource | null>(props.nextCombination); // Estado local para la combinación actual
const surveyData = ref<SurveyResource>({ ...props.survey }); // Estado local para los datos de la encuesta (progreso, etc.)
const voting = ref(false); // Estado para deshabilitar botones durante el voto
const loadingNext = ref(false); // Estado para mostrar indicador de carga de la siguiente combinación
const noMoreCombinations = ref(!props.nextCombination); // Estado para indicar si no hay más combinaciones
const isCompleted = computed(() => surveyData.value.is_completed); // Calcular si la encuesta está completada localmente
const userProgress = ref<any>({ ...props.userProgress }); // Estado local para el progreso del usuario

// --- Funciones ---

const vote = async (winnerId: number, loserId: number) => {
    if (!currentCombination.value || voting.value) return;

    voting.value = true;
    try {
        const response = await axios.post(
            route('surveys.store-vote', props.survey.id),
            {
                combination_id: currentCombination.value.id,
                winner_id: winnerId,
                loser_id: loserId,
            },
        );

        // ✅ Mostrar toast del backend o mensaje por defecto
        const message = response.data.message || 'Vote recorded successfully!';
        success(message);

        // ✅ Actualizar datos locales
        if (response.data.survey) {
            survey.value = {
                ...survey.value,
                ...response.data.survey,
            };
        }

        // Cargar siguiente combinación
        await loadCombination();
    } catch (err: any) {
        // ✅ Mostrar mensaje de error del backend o fallback
        const errorMessage =
            err.response?.data?.message || 'Failed to record vote';
        error(errorMessage);
        // error('Failed to record vote: ' + (err.response?.data?.message || 'Unknown error'));
        // console.error('Vote error:', err);
    } finally {
        voting.value = false;
    }
};

// Obtener combinación inicial
const loadCombination = async () => {
    loading.value = true;
    try {
        const response = await axios.get(
            route('surveys.combination', props.survey.id),
        );
        currentCombination.value = response.data;
    } catch (err: any) {
        // console.error('Error loading combination:', err);

        if (err.response?.status === 404) {
            // ✅ No llamar a updateProgress, usar datos locales
            currentCombination.value = null;
            // Los datos de progreso ya están actualizados en props.survey
        } else {
            // ✅ Mostrar mensaje de error del backend o fallback
            const errorMessage =
                err.response?.data?.message || 'Failed to load combination';
            error(errorMessage);
            // error('Failed to load combination');
        }
    } finally {
        loading.value = false;
    }
};

/**
 * Manejar eventos de teclado para votación rápida.
 * @param e Evento de teclado
 */
const handleKeyPress = (e: KeyboardEvent) => {
    if (
        e.key === '1' &&
        nextCombination.value &&
        !voting.value &&
        !loadingNext.value
    ) {
        handleVoteCharacter1();
    } else if (
        e.key === '2' &&
        nextCombination.value &&
        !voting.value &&
        !loadingNext.value
    ) {
        handleVoteCharacter2();
    } else if (
        e.key === '3' &&
        nextCombination.value &&
        !voting.value &&
        !loadingNext.value
    ) {
        // Opcional: '3' para empate
        handleTie();
    }
};

// --- Lifecycle Hooks ---
onMounted(() => {
    // Agregar event listener para teclado
    window.addEventListener('keydown', handleKeyPress);
});

onUnmounted(() => {
    // Limpiar event listener
    window.removeEventListener('keydown', handleKeyPress);
});
</script>
<template></template>
