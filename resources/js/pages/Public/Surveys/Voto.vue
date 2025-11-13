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

// --- Logs ---
console.log('props', props);
console.log('surveyData', surveyData.value);
console.log('userProgress', userProgress.value);
console.log('nextCombination', nextCombination.value);
console.log('voting', voting.value);
console.log('loadingNext', loadingNext.value);
console.log('noMoreCombinations', noMoreCombinations.value);
console.log('isCompleted', isCompleted.value);

// --- Funciones ---

const vote = async (winnerId: number, loserId: number) => {
    if (!nextCombination.value || voting.value) return;

    voting.value = true;
    try {
        const response = await axios.post(
            route('surveys.store-vote', props.survey.id),
            {
                combination_id: nextCombination.value.id,
                winner_id: winnerId,
                loser_id: loserId,
            },
        );

        // ✅ Mostrar toast del backend o mensaje por defecto
        const message = response.data.message || 'Vote recorded successfully!';
        success(message);

        // ✅ Actualizar datos locales
        if (response.data.survey) {
            surveyData.value = {
                ...surveyData.value,
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
    loadingNext.value = true;
    try {
        const response = await axios.get(
            route('public.ajax.surveys.combination4voto', props.survey.id),
        );
        const data = response.data;
        // console.log('data', data);
        if (data.combination) {
            nextCombination.value = data.combination;
            noMoreCombinations.value = false;

            console.log('nextCombination', nextCombination.value);
        } else {
            // No hay más combinaciones o encuesta completada
            nextCombination.value = null;
            noMoreCombinations.value = true;
            // Opcional: Actualizar el estado de completado si el backend lo indica
            // props.survey.is_completed = true; // Esto no funcionará directamente porque props es inmutable
            // La mejor forma es que el backend devuelva el survey actualizado también
            // y que Inertia lo recargue.
        }
    } catch (err: any) {
        // console.error('Error loading combination:', err); // Añadir log para debug

        if (err.response?.status === 401) {
            // Usuario no autenticado
            error('Authentication required to load combinations.');
            // Opcional: Redirigir al login
            // router.visit(route('login'));
        } else if (err.response?.status === 404) {
            // Encuesta no encontrada o no activa, o usuario ya completó
            const serverMessage =
                err.response?.data?.message ||
                'Survey not found or not active.';
            error(serverMessage);
            nextCombination.value = null; // Indicar que no hay combinación
        } else {
            // Otro error (500, problemas de red, etc.)
            const errorMessage =
                err.response?.data?.message || 'Failed to load combination';
            error(errorMessage);
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

                            <Link
                                as="button"
                                :href="
                                    route('public.surveys.show', surveyData.id)
                                "
                            >
                                <Button variant="outline">
                                    Back to Survey
                                </Button>
                            </Link>
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

                    <!-- Interfaz de Votación -->
                    <div v-else class="mx-auto max-w-4xl">
                        <!-- Characters comparison -->
                        <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                            <!-- Character 1 -->
                            <Card>
                                <CardHeader class="text-center">
                                    <CardTitle>{{
                                        nextCombination.character1.fullname
                                    }}</CardTitle>
                                    <CardDescription
                                        v-if="
                                            nextCombination.character1.nickname
                                        "
                                    >
                                        {{
                                            nextCombination.character1.nickname
                                        }}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div
                                        class="relative aspect-square w-full overflow-hidden rounded-lg border"
                                    >
                                        <img
                                            v-if="
                                                nextCombination.character1
                                                    .picture_url
                                            "
                                            :src="
                                                nextCombination.character1
                                                    .picture_url
                                            "
                                            :alt="
                                                nextCombination.character1
                                                    .fullname
                                            "
                                            class="h-full w-full object-cover"
                                        />
                                        <div
                                            v-else
                                            class="flex h-full w-full items-center justify-center bg-muted"
                                        >
                                            <span class="text-muted-foreground"
                                                >No image</span
                                            >
                                        </div>
                                    </div>
                                </CardContent>
                                <CardFooter>
                                    <Button
                                        class="w-full"
                                        :disabled="voting"
                                        @click="handleVoteCharacter1"
                                    >
                                        <span v-if="voting">Voting...</span>
                                        <span v-else
                                            >Vote for
                                            {{
                                                nextCombination.character1
                                                    .fullname
                                            }}
                                            (1)</span
                                        >
                                    </Button>
                                </CardFooter>
                            </Card>

                            <!-- Character 2 -->
                            <Card>
                                <CardHeader class="text-center">
                                    <CardTitle>{{
                                        nextCombination.character2.fullname
                                    }}</CardTitle>
                                    <CardDescription
                                        v-if="
                                            nextCombination.character2.nickname
                                        "
                                    >
                                        {{
                                            nextCombination.character2.nickname
                                        }}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div
                                        class="relative aspect-square w-full overflow-hidden rounded-lg border"
                                    >
                                        <img
                                            v-if="
                                                nextCombination.character2
                                                    .picture_url
                                            "
                                            :src="
                                                nextCombination.character2
                                                    .picture_url
                                            "
                                            :alt="
                                                nextCombination.character2
                                                    .fullname
                                            "
                                            class="h-full w-full object-cover"
                                        />
                                        <div
                                            v-else
                                            class="flex h-full w-full items-center justify-center bg-muted"
                                        >
                                            <span class="text-muted-foreground"
                                                >No image</span
                                            >
                                        </div>
                                    </div>
                                </CardContent>
                                <CardFooter>
                                    <Button
                                        class="w-full"
                                        :disabled="voting"
                                        @click="handleVoteCharacter2"
                                    >
                                        <span v-if="voting">Voting...</span>
                                        <span v-else
                                            >Vote for
                                            {{
                                                nextCombination.character2
                                                    .fullname
                                            }}
                                            (2)</span
                                        >
                                    </Button>
                                </CardFooter>
                            </Card>
                        </div>

                        <!-- Botón de Empate -->
                        <div class="mt-8 flex justify-center">
                            <Button
                                variant="outline"
                                :disabled="voting"
                                @click="handleTie"
                            >
                                {{ voting ? 'Voting...' : "It's a Tie! (3)" }}
                            </Button>
                        </div>

                        <!-- Información adicional - Estadisticas -->
                        <div class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-3">
                            <Card>
                                <CardContent class="pt-4 text-center">
                                    <div class="text-2xl font-bold">
                                        {{ votesRemaining }}
                                    </div>
                                    <div class="text-sm text-muted-foreground">
                                        Remaining
                                    </div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="pt-4 text-center">
                                    <!-- <div class="text-2xl font-bold">
                                        {{ progressPercentage.toFixed(2) }}%
                                    </div> -->
                                    <div class="text-sm text-muted-foreground">
                                        Completed
                                    </div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="pt-4 text-center">
                                    <div class="text-2xl font-bold">
                                        {{ totalVotes }}
                                    </div>
                                    <div class="text-sm text-muted-foreground">
                                        Voted
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Instrucciones -->
                        <div
                            class="mt-8 text-center text-sm text-muted-foreground"
                        >
                            <p>
                                Press
                                <kbd class="rounded bg-muted px-2 py-1">1</kbd>
                                to vote for the first character,
                                <kbd class="rounded bg-muted px-2 py-1">2</kbd>
                                for the second character, or
                                <kbd class="rounded bg-muted px-2 py-1">3</kbd>
                                for a tie.
                            </p>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </VotingLayout>
</template>
