<script setup lang="ts">
import { useToast } from '@/composables/useToast'; // Importar el composable de toast
import VotingLayout from '@/layouts/VotingLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

// Layouts & Components
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'; // Componente de alerta de shadcn
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Progress } from '@/components/ui/progress'; // Componente de progreso de shadcn
import { AlertCircle } from 'lucide-vue-next'; // Iconos

// Tipos (asumiendo que están definidos en global.d.ts o en otro lugar)
import type { CombinatoricResource, SurveyResource } from '@/types/global';

import axios from 'axios';

// --- Props del componente ---
interface Props {
    survey: SurveyResource; // Datos de la encuesta actual, incluyendo progreso
    // nextCombination: CombinatoricResource | null; // La próxima combinación a votar
    // userProgress: ProgressData; // Progreso del usuario en la encuesta
}

const props = defineProps<Props>();

// --- Composables ---
const { success, error } = useToast();

// --- Tipos ---
// Estado local para el progreso del usuario (devuelto por el backend)
// Asumiendo que el backend devuelve un objeto con esta estructura
interface ProgressData {
    exists: boolean;
    is_completed: boolean; // Puede ser boolean o integer (0/1)
    progress: number; // Porcentaje
    total_votes: number;
    total_expected: number | null; // Puede ser null si no se pudo calcular
    // Añadir otros campos si el backend los envía
}

// --- Estados reactivos ---
const nextCombination = ref<CombinatoricResource | null>(null); // Estado local para la combinación actual
const surveyData = ref<SurveyResource>({ ...props.survey }); // Estado local para los datos de la encuesta (progreso, etc.)
const voting = ref(false); // Estado para deshabilitar botones durante el voto
const loadingNext = ref(false); // Estado para mostrar indicador de carga de la siguiente combinación
const noMoreCombinations = ref(false); // Estado para indicar si no hay más combinaciones
const isCompleted = computed(() => surveyData.value.is_completed); // Calcular si la encuesta está completada localmente
const userProgress = ref<ProgressData | null>(null); // Estado local para el progreso del usuario

// --- Propiedades Computadas para Datos Derivados del Progreso ---
// Estas propiedades se recalculan automáticamente cuando userProgress.value cambia
const progressPercentage = computed(() => userProgress.value?.progress ?? 0); // Porcentaje de progreso
const totalVotes = computed(() => userProgress.value?.total_votes ?? 0); // Votos totales del usuario
// const totalExpected = computed(() => userProgress.value?.total_expected ?? 0); // Total esperado de combinaciones para el usuario
const totalExpected = computed(() => surveyData.value.combinatorics_count ?? 0); // Total esperado de combinaciones para el usuario
const votesRemaining = computed(() =>
    Math.max(0, totalExpected.value - totalVotes.value),
); // Votos restantes

const isTieSelected = ref(false);

// --- Logs ---
// console.log('props', props);
// console.log('surveyData', surveyData.value);
// console.log('userProgress', userProgress.value);
// console.log('nextCombination', nextCombination.value);
// console.log('voting', voting.value);
// console.log('loadingNext', loadingNext.value);
// console.log('noMoreCombinations', noMoreCombinations.value);
// console.log('isCompleted', isCompleted.value);

// console.log('progressPercentage', progressPercentage.value);
// console.log('totalVotes', totalVotes.value);
// console.log('totalExpected', totalExpected.value);
// console.log('votesRemaining', votesRemaining.value);

// --- Funciones ---

/**
 * Enviar un voto para la combinación actual.
 * @param winnerId ID del personaje ganador
 * @param loserId ID del personaje perdedor
 */
const vote = async (winnerId: number | null, loserId: number | null) => {
    if (!nextCombination.value || voting.value) return;

    voting.value = true;
    try {
        const response = await axios.post(
            route('public.surveys.vote.store', props.survey.id), // Asumiendo nombre de ruta correcto
            {
                combinatoric_id: nextCombination.value.id, // Asumiendo que el backend espera 'combinatoric_id'
                winner_id: winnerId,
                loser_id: loserId,
                tie: isTieSelected.value,
            },
        );

        // Mostrar mensaje de éxito del backend
        const message = response.data.message || 'Vote recorded successfully!';
        success(message);

        // Actualizar datos locales devueltos por el backend (progreso de la encuesta)
        if (response.data.survey_data) {
            // Asumiendo que el backend devuelve 'survey_data'
            surveyData.value = {
                ...surveyData.value,
                ...response.data.survey_data,
            };
        }

        // Opcional: Actualizar ratings locales si el backend los devuelve
        if (response.data.character_ratings) {
            // Lógica para actualizar ratings locales si es necesario
        }

        // Cargar la siguiente combinación
        await loadCombination();
    } catch (err: any) {
        console.error('Error submitting vote:', err); // Log para debugging
        // Mostrar mensaje de error del backend o fallback
        const errorMessage =
            err.response?.data?.message || 'Failed to record vote';
        error(errorMessage);
    } finally {
        voting.value = false;
    }
};

/**
 * Cargar la próxima combinación para votar.
 */
const loadCombination = async () => {
    loadingNext.value = true;
    try {
        // Hacer la solicitud GET al endpoint del backend para obtener la próxima combinación
        const response = await axios.get(
            route('public.ajax.surveys.combination4voto', props.survey.id), // Asegurar nombre de ruta correcto
        );
        const data = response.data;
        console.log('Data received from backend:', data); // Log para debugging

        if (data.combination) {
            // Actualizar la combinación actual
            nextCombination.value = data.combination;
            // Actualizar el progreso del usuario (si el backend lo devuelve)
            userProgress.value = data.progress; // <-- Asignación reactiva
            // Indicar que hay combinaciones disponibles
            noMoreCombinations.value = false;
            // Actualizar isCompleted local si el backend lo indica
            // surveyData.value.is_completed = data.progress?.is_completed ?? surveyData.value.is_completed;

            console.log('Updated nextCombination:', nextCombination.value);
            console.log('Updated userProgress:', userProgress.value);
            // Ahora, al acceder a las propiedades computadas, deberían reflejar los nuevos valores
            console.log(
                'Computed progressPercentage:',
                progressPercentage.value,
            );
            console.log('Computed totalVotes:', totalVotes.value);
            console.log('Computed totalExpected:', totalExpected.value);
            console.log('Computed votesRemaining:', votesRemaining.value);
        } else {
            // No hay más combinaciones disponibles o la encuesta está completada para el usuario
            nextCombination.value = null;
            userProgress.value = null; // O mantener el último estado de progreso
            noMoreCombinations.value = true;
            // Opcional: Actualizar isCompleted local si el backend lo indica explícitamente
            // surveyData.value.is_completed = true;
        }
    } catch (err: any) {
        console.error('Error loading next combination:', err); // Log para debugging

        if (err.response?.status === 401) {
            // Usuario no autenticado
            error('Authentication required to load combinations.');
            // Opcional: Redirigir al login
            // router.visit(route('login'));
        } else if (err.response?.status === 404) {
            // Encuesta no encontrada, inactiva o usuario ya completó
            const serverMessage =
                err.response?.data?.message ||
                'Survey not found, not active, or already completed.';
            error(serverMessage);
            nextCombination.value = null; // Indicar que no hay combinación
            noMoreCombinations.value = true; // Indicar que no hay más
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
 * Manejar el voto por el personaje 1.
 */
const handleVoteCharacter1 = () => {
    if (nextCombination.value) {
        vote(
            nextCombination.value.character1.id,
            nextCombination.value.character2.id,
        );
    }
};

/**
 * Manejar el voto por el personaje 2.
 */
const handleVoteCharacter2 = () => {
    if (nextCombination.value) {
        vote(
            nextCombination.value.character2.id,
            nextCombination.value.character1.id,
        );
    }
};

/**
 * Manejar el voto de empate.
 */
const handleTie = () => {
    if (nextCombination.value) {
        isTieSelected.value = true;
        // Para empates, enviar IDs nulos o un flag especial, dependiendo de la API backend
        // Asumiendo que el backend maneja empates con winner_id=null, loser_id=null, tie=true
        // y que el formulario Inertia o la solicitud axios lo envía así.
        // Opción 1: Enviar un objeto diferente para empates
        // submitTie(nextCombination.value.id);
        // Opción 2: Modificar la función vote para manejar empates
        vote(null, null); // O usar un ID especial o null si la API lo soporta y se adapta la lógica de `vote`
        // La mejor práctica es tener una función específica para empates si la API lo requiere distinto
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
        // Opcional: tecla para empate
        handleTie();
    }
};

// --- Lifecycle Hooks ---
onMounted(() => {
    // Cargar la primera combinación al montar el componente
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
                                        :model-value="userProgress?.progress"
                                        :max="100"
                                    />
                                </div>
                                <span class="text-sm text-muted-foreground">
                                    {{ userProgress?.progress.toFixed(2) }}%
                                </span>
                            </div>

                            <div class="text-sm text-muted-foreground">
                                {{ userProgress?.total_votes }} /
                                <!-- {{ userProgress?.total_expected }} -->
                                {{ totalExpected }}
                            </div>
                            <Link
                                as="button"
                                :href="
                                    route('public.surveys.show', surveyData.id)
                                "
                            >
                                <Button
                                    variant="outline"
                                    class="cursor-pointer"
                                >
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
                        <div
                            v-if="surveyData.allow_ties"
                            class="mt-8 flex justify-center"
                        >
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
                                    <div class="text-2xl font-bold">
                                        {{ progressPercentage?.toFixed(2) }}%
                                    </div>
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
                                for the second character
                                <span v-if="surveyData.allow_ties"
                                    >, or
                                    <kbd class="rounded bg-muted px-2 py-1"
                                        >3</kbd
                                    >
                                    for a tie.</span
                                >
                            </p>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </VotingLayout>
</template>
