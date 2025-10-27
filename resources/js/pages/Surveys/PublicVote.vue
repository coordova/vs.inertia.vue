<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
// import axios from 'axios'; // No necesario si usamos router.post/get de Inertia
import { computed, onMounted, onUnmounted, ref } from 'vue';

// Layouts & Components
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useToast } from '@/composables/useToast';
import VotingLayout from '@/layouts/VotingLayout.vue'; // Asumiendo AppLayout como layout principal

// Tipos
import type { CharacterResource, SurveyResource } from '@/types/global'; // Asumiendo que CharacterResource tiene id, fullname, picture, etc.

// Interfaces específicas para este componente
interface Combination {
    combinatoric_id: number;
    character1: CharacterResource;
    character2: CharacterResource;
}

interface Props {
    survey: SurveyResource; // El objeto survey con datos de progreso incluidos
    // initialCombination?: Combination | null; // Opcional: Pasar la primera combinación desde el controlador
}

const props = defineProps<Props>();

// --- Composables ---
const { success, error } = useToast();
const page = usePage(); // Para acceder a props globales si es necesario

// --- Estados reactivos ---
const currentCombination = ref<Combination | null>(null);
const loading = ref(false);
const voting = ref(false);
// Ref para mantener una copia reactiva de survey que pueda actualizarse
const surveyData = ref<SurveyResource>({ ...props.survey });

// --- Computed Properties ---
// Progreso calculado (por si no viene directamente del backend o para reactividad)
const progressPercentage = computed(
    () => surveyData.value.progress_percentage ?? 0,
);
const votesRemaining = computed(
    () =>
        (surveyData.value.total_combinations_expected ?? 0) -
        (surveyData.value.total_votes ?? 0),
);
const isCompleted = computed(() => surveyData.value.is_completed ?? false);
const totalExpected = computed(
    () => surveyData.value.total_combinations_expected ?? 0,
);
const totalVotes = computed(() => surveyData.value.total_votes ?? 0);

// --- Funciones ---

/**
 * Votar por un personaje o empate.
 * @param winnerId ID del personaje ganador (puede ser null para empate)
 * @param loserId ID del personaje perdedor (puede ser null para empate)
 * @param isTie Booleano indicando si es empate
 */
const vote = (
    winnerId: number | null,
    loserId: number | null,
    isTie: boolean = false,
) => {
    if (!currentCombination.value || voting.value) return;

    voting.value = true;

    // Preparar datos para el voto
    const voteData = {
        combinatoric_id: currentCombination.value.combinatoric_id,
        winner_id: isTie ? null : winnerId,
        loser_id: isTie ? null : loserId,
        tie: isTie,
    };

    // Usar router.post de Inertia para enviar el voto
    router.post(route('surveys.vote.store', props.survey.id), voteData, {
        preserveState: false, // No preservar estado, ya que queremos recargar datos
        preserveScroll: true,
        onSuccess: (page) => {
            // page.props contiene los datos actualizados devueltos por el controlador
            // Asumiendo que el controlador devuelve el survey actualizado y la próxima combinación
            // en la respuesta de redirección (por ejemplo, con ->with('survey', ...) o ->with('nextCombination', ...))
            // O mejor aún, si el controlador devuelve una respuesta JSON (aunque sea una redirección),
            // Inertia puede manejarla y actualizar page.props.

            // Si el controlador actualiza page.props.survey con el nuevo progreso:
            if (page.props.survey) {
                surveyData.value = {
                    ...surveyData.value,
                    ...page.props.survey,
                };
            }

            success('Vote recorded successfully!');

            // Cargar la siguiente combinación
            // Si el controlador devuelve la siguiente combinación, úsala.
            // Si no, vuelve a cargarla.
            // Asumamos que el controlador no la devuelve directamente y hay que recargarla.
            loadNextCombination();
        },
        onError: (errors) => {
            console.error('Errors submitting vote:', errors);
            // Mostrar mensaje de error específico si existe, o uno genérico
            const errorMessage =
                errors.combinatoric_id ||
                errors.winner_id ||
                errors.loser_id ||
                errors.tie ||
                'Failed to submit vote. Please check the errors.';
            error(errorMessage);
        },
        onFinish: () => {
            voting.value = false;
        },
    });
};

/**
 * Cargar la próxima combinación para votar.
 */
const loadNextCombination = async () => {
    if (isCompleted.value) {
        currentCombination.value = null;
        return;
    }

    loading.value = true;
    try {
        // Usar router.get o axios.get para obtener la próxima combinación
        // Asumiendo que existe un endpoint API: GET /api/public/surveys/{survey}/next-combination
        const response = await fetch(
            route('api.public.surveys.next_combination', props.survey.id),
        );
        const data = await response.json();

        if (data.combination) {
            currentCombination.value = data.combination;
        } else {
            // No hay más combinaciones o encuesta completada
            currentCombination.value = null;
            // surveyData.value.is_completed = true; // O actualizar desde el backend
        }
    } catch (err: any) {
        console.error('Error loading next combination:', err);
        error('Failed to load next combination. Please try again.');
        currentCombination.value = null;
    } finally {
        loading.value = false;
    }
};

/**
 * Manejar el voto por el personaje 1.
 */
const handleVoteCharacter1 = () => {
    if (currentCombination.value) {
        vote(
            currentCombination.value.character1.id,
            currentCombination.value.character2.id,
            false,
        );
    }
};

/**
 * Manejar el voto por el personaje 2.
 */
const handleVoteCharacter2 = () => {
    if (currentCombination.value) {
        vote(
            currentCombination.value.character2.id,
            currentCombination.value.character1.id,
            false,
        );
    }
};

/**
 * Manejar el voto de empate.
 */
const handleTie = () => {
    if (currentCombination.value) {
        vote(null, null, true);
    }
};

/**
 * Manejar eventos de teclado para votación rápida.
 * @param e Evento de teclado
 */
const handleKeyPress = (e: KeyboardEvent) => {
    if (e.key === '1' && currentCombination.value && !voting.value) {
        handleVoteCharacter1();
    } else if (e.key === '2' && currentCombination.value && !voting.value) {
        handleVoteCharacter2();
    } else if (e.key === '3' && currentCombination.value && !voting.value) {
        // Opcional: asignar '3' para empate
        handleTie();
    }
};

// --- Lifecycle Hooks ---
onMounted(() => {
    // Cargar la primera combinación al montar el componente
    loadNextCombination();
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

    <!-- ✅ Usar layout especializado para votación -->
    <VotingLayout
        :survey-title="survey.title"
        :survey-id="survey.id"
        :show-progress="true"
    >
        <!-- Asumiendo que AppLayout maneja breadcrumbs si es necesario -->
        <div class="container mx-auto py-8">
            <div
                class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
            >
                <!-- Header con progreso -->
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
                                <div class="h-2 w-32 rounded-full bg-muted">
                                    <div
                                        class="h-2 rounded-full bg-primary transition-all duration-300"
                                        :style="{
                                            width: progressPercentage + '%',
                                        }"
                                    ></div>
                                </div>
                                <span class="text-sm text-muted-foreground">
                                    {{ progressPercentage.toFixed(2) }}%
                                </span>
                            </div>

                            <div class="text-sm text-muted-foreground">
                                {{ totalVotes }} / {{ totalExpected }}
                            </div>

                            <Button asChild variant="outline">
                                <router-link
                                    :href="
                                        route(
                                            'surveys.public.show',
                                            surveyData.id,
                                        )
                                    "
                                >
                                    Back to Survey
                                </router-link>
                            </Button>
                        </div>
                    </div>
                </header>

                <!-- Main content -->
                <main class="container py-8">
                    <div
                        v-if="loading"
                        class="flex h-96 items-center justify-center"
                    >
                        <div class="text-muted-foreground">
                            Loading next match...
                        </div>
                    </div>

                    <div
                        v-else-if="!currentCombination || isCompleted"
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
                                            ? 'Congratulations! You have completed this survey.'
                                            : "You've voted on all available combinations for now."
                                    }}
                                </CardDescription>
                            </CardHeader>
                            <CardFooter class="flex justify-center">
                                <Button asChild>
                                    <router-link
                                        :href="
                                            route(
                                                'surveys.public.show',
                                                surveyData.id,
                                            )
                                        "
                                    >
                                        {{
                                            isCompleted
                                                ? 'View Results'
                                                : 'View Survey'
                                        }}
                                    </router-link>
                                </Button>
                            </CardFooter>
                        </Card>
                    </div>

                    <div v-else class="mx-auto max-w-4xl">
                        <!-- Characters comparison -->
                        <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                            <!-- Character 1 -->
                            <Card>
                                <CardHeader class="text-center">
                                    <CardTitle>{{
                                        currentCombination.character1.fullname
                                    }}</CardTitle>
                                    <CardDescription
                                        v-if="
                                            currentCombination.character1
                                                .nickname
                                        "
                                    >
                                        {{
                                            currentCombination.character1
                                                .nickname
                                        }}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div
                                        class="relative aspect-square w-full overflow-hidden rounded-lg border"
                                    >
                                        <img
                                            v-if="
                                                currentCombination.character1
                                                    .picture_url
                                            "
                                            :src="
                                                currentCombination.character1
                                                    .picture_url
                                            "
                                            :alt="
                                                currentCombination.character1
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
                                                currentCombination.character1
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
                                        currentCombination.character2.fullname
                                    }}</CardTitle>
                                    <CardDescription
                                        v-if="
                                            currentCombination.character2
                                                .nickname
                                        "
                                    >
                                        {{
                                            currentCombination.character2
                                                .nickname
                                        }}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div
                                        class="relative aspect-square w-full overflow-hidden rounded-lg border"
                                    >
                                        <img
                                            v-if="
                                                currentCombination.character2
                                                    .picture_url
                                            "
                                            :src="
                                                currentCombination.character2
                                                    .picture_url
                                            "
                                            :alt="
                                                currentCombination.character2
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
                                                currentCombination.character2
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
                                    <div class="text-2xl font-bold">
                                        {{ progressPercentage.toFixed(2) }}%
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
                                <kbd class="rounded bg-muted px-2 py-1">1</kbd>,
                                <kbd class="rounded bg-muted px-2 py-1">2</kbd>,
                                or
                                <kbd class="rounded bg-muted px-2 py-1">3</kbd>
                                to vote quickly.
                            </p>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </VotingLayout>
</template>

<style scoped>
/* Estilos específicos si es necesario */
</style>
