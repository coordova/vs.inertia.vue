<script setup lang="ts">
import { useToast } from '@/composables/useToast'; // Importar el composable de toast
import VotingLayout from '@/layouts/VotingLayout.vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';

// Layouts & Components
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'; // Componente de alerta de shadcn
import { Button } from '@/components/ui/button';
import {
    Card,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Progress } from '@/components/ui/progress'; // Componente de progreso de shadcn
import { AlertCircle, Link } from 'lucide-vue-next'; // Iconos

// Tipos (asumiendo que están definidos en global.d.ts o en otro lugar)
import type { CombinatoricResource, SurveyResource } from '@/types/global';

import axios from 'axios';

interface ProgressData {
    exists: boolean;
    is_completed: boolean;
    progress: number;
    total_votes: number;
    total_expected: number;
}

// --- Props del componente ---
interface Props {
    survey: SurveyResource; // Datos de la encuesta actual, incluyendo progreso
    nextCombination: CombinatoricResource | null; // La próxima combinación a votar
    userProgress: ProgressData; // Progreso del usuario en la encuesta
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
const isCompleted = computed(() => props.userProgress.is_completed); // Calcular si la encuesta está completada localmente
const userProgress = ref<ProgressData>({ ...props.userProgress }); // Estado local para el progreso del usuario

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
    console.log('loadCombination');
    loadingNext.value = true;
    try {
        const response = await axios.get(
            route('ajax.surveys.combination4voto', props.survey.id),
        );
        const data = response.data;
        console.log('data', data);
        if (data.combination) {
            nextCombination.value = data.combination;
        } else {
            // No hay más combinaciones o encuesta completada
            nextCombination.value = null;
            // Opcional: Actualizar el estado de completado si el backend lo indica
            // props.survey.is_completed = true; // Esto no funcionará directamente porque props es inmutable
            // La mejor forma es que el backend devuelva el survey actualizado también
            // y que Inertia lo recargue.
        }
    } catch (err: any) {
        // console.error('Error loading combination:', err);

        if (err.response?.status === 404) {
            // ✅ No llamar a updateProgress, usar datos locales
            nextCombination.value = null;
            // Los datos de progreso ya están actualizados en props.survey
        } else {
            // ✅ Mostrar mensaje de error del backend o fallback
            const errorMessage =
                err.response?.data?.message || 'Failed to load combination';
            error(errorMessage);
            // error('Failed to load combination');
        }
    } finally {
        loadingNext.value = false;
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
    // Cargar combinación inicial
    loadCombination();
    // Agregar event listener para teclado
    window.addEventListener('keydown', handleKeyPress);
});

onUnmounted(() => {
    // Limpiar event listener
    window.removeEventListener('keydown', handleKeyPress);
});
</script>
<template>
    <Head :title="`Voting: ${surveyData.title}`" />
    <VotingLayout :survey-title="surveyData.title" :survey-id="surveyData.id">
        <div class="container mx-auto py-8">
            <div
                class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
            >
                <!-- Header con información de la encuesta y progreso -->
                <header class="border-b pb-4">
                    <div
                        class="container flex h-16 items-center justify-between px-4"
                    >
                        <h1 class="text-xl font-semibold">
                            {{ surveyData.title }}
                        </h1>

                        <div class="flex items-center gap-6">
                            <!-- Barra de progreso -->
                            <div class="flex items-center gap-2">
                                <div class="w-32">
                                    <Progress
                                        :model-value="userProgress.progress"
                                        :max="100"
                                    />
                                </div>
                                <span class="text-sm text-muted-foreground">
                                    {{ userProgress.progress.toFixed(2) }}%
                                </span>
                            </div>

                            <div class="text-sm text-muted-foreground">
                                {{ userProgress.total_votes }} /
                                {{ userProgress.total_expected }}
                            </div>

                            <Button
                                variant="outline"
                                @click="
                                    router.visit(
                                        route(
                                            'public.surveys.show',
                                            surveyData.id,
                                        ),
                                    )
                                "
                            >
                                Back to Survey
                            </Button>
                        </div>
                    </div>
                </header>

                <!-- Main content -->
                <main class="container py-8">
                    <!-- Alerta de encuesta completada -->
                    <Alert v-if="isCompleted" class="mb-6">
                        <AlertCircle class="h-4 w-4" />
                        <AlertTitle>Congratulations!</AlertTitle>
                        <AlertDescription>
                            You have completed this survey. Thank you for
                            participating!
                        </AlertDescription>
                    </Alert>

                    <!-- Indicador de Carga (para la primera carga si aplica) -->
                    <div
                        v-if="loadingNext && !nextCombination"
                        class="flex h-96 items-center justify-center"
                    >
                        <div class="text-muted-foreground">
                            Loading first match...
                        </div>
                    </div>

                    <!-- Mensaje de Fin de Encuesta o Sin Combinaciones -->
                    <div
                        v-else-if="noMoreCombinations || !nextCombination"
                        class="text-center"
                    >
                        <Card>
                            <CardHeader>
                                <CardTitle>
                                    {{
                                        isCompleted
                                            ? 'Survey Completed!'
                                            : 'No More Combinations!'
                                    }}
                                </CardTitle>
                                <CardDescription>
                                    {{
                                        isCompleted
                                            ? 'You have finished all available matches for this survey.'
                                            : 'There are no more available matches to vote on right now.'
                                    }}
                                </CardDescription>
                            </CardHeader>
                            <CardFooter class="flex justify-center">
                                <Button asChild>
                                    <Link
                                        :href="
                                            route(
                                                'public.surveys.show',
                                                surveyData.id,
                                            )
                                        "
                                    >
                                        {{
                                            isCompleted
                                                ? 'View Results'
                                                : 'View Survey'
                                        }}
                                    </Link>
                                </Button>
                            </CardFooter>
                        </Card>
                    </div>
                </main>
            </div>
        </div>
    </VotingLayout>
</template>
