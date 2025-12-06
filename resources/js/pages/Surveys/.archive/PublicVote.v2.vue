<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
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
import VotingLayout from '@/layouts/PublicLayout.vue'; // <-- Usar VotingLayout
// import { motion } from 'framer-motion'; // Importar motion de framer-motion

// Tipos: Definidos localmente para este componente
interface CharacterResource {
    id: number;
    fullname: string;
    nickname: string | null;
    slug: string;
    bio: string | null;
    dob: string | null;
    gender: number | null;
    nationality: string | null;
    occupation: string | null;
    picture: string | null;
    picture_url: string | null;
    thumbnail_url: string | null;
    status: boolean;
    meta_title: string | null;
    meta_description: string | null;
    created_at: string;
    updated_at: string;
    created_at_formatted: string;
    updated_at_formatted: string;
    category_ids: number[];
    // Añadir otros campos si son necesarios
}

interface CombinatoricResource {
    combinatoric_id: number;
    character1: CharacterResource;
    character2: CharacterResource;
    // Añadir otros campos si son necesarios
}

interface SurveyResource {
    id: number;
    category_id: number;
    title: string;
    slug: string;
    description: string | null;
    image: string | null;
    type: number;
    status: boolean;
    reverse: boolean;
    date_start: string;
    date_end: string;
    selection_strategy: string;
    max_votes_per_user: number | null;
    allow_ties: boolean;
    tie_weight: number;
    is_featured: boolean;
    sort_order: number;
    counter: number;
    meta_title: string | null;
    meta_description: string | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    date_start_formatted: string;
    date_end_formatted: string;
    created_at_formatted: string;
    updated_at_formatted: string;
    total_combinations: number | null; // <-- Nueva columna en la BD
    progress_percentage: number; // <-- Del SurveyProgressService
    total_votes: number; // <-- Del SurveyProgressService
    total_combinations_expected: number | null; // <-- Del SurveyProgressService
    is_completed: boolean; // <-- Del SurveyProgressService
    // Añadir otros campos si son necesarios
}

interface UserProgress {
    exists: boolean;
    is_completed: boolean;
    progress: number;
    total_votes: number;
    total_expected: number | null;
    pivot_id: number | null;
    // Añadir otros campos si son necesarios
}

// Props del componente
interface Props {
    survey: SurveyResource;
    characters: CharacterResource[]; // Puede no ser necesario si solo usamos los de la combinación
    // userProgress: UserProgress;
    // La combinación inicial se pasa como prop
    nextCombination: CombinatoricResource | null;
}

const props = defineProps<Props>();
console.log(props);
// fusionar props.survey + props.userProgress
// const survey = computed(() => ({ ...props.survey, ...props.userProgress }));
// --- Composables ---
const { success, error } = useToast();

// --- Estados reactivos ---
const currentCombination = ref<CombinatoricResource | null>(
    props.nextCombination,
); // <-- Estado reactivo para la combinación actual
const voting = ref(false); // Estado para deshabilitar botones durante el voto
const loadingNext = ref(false); // Estado para mostrar indicador de carga de la siguiente combinación

// --- Computed Properties ---
const progressPercentage = computed(
    () => props.survey.progress_percentage ?? 0,
);
const totalExpected = computed(
    () =>
        props.survey.total_combinations_expected ??
        props.survey.total_combinations ??
        0,
);
const totalVotes = computed(() => props.survey.total_votes ?? 0);
const votesRemaining = computed(() =>
    Math.max(0, totalExpected.value - totalVotes.value),
);
const isCompleted = computed(() => props.survey.is_completed ?? false);
const hasCombination = computed(() => !!currentCombination.value); // <-- Usar currentCombination

// --- Funciones ---

/**
 * Enviar el voto al backend y cargar la siguiente combinación.
 * @param winnerId ID del personaje ganador (puede ser null para empate)
 * @param loserId ID del personaje perdedor (puede ser null para empate)
 * @param isTie Booleano indicando si es empate
 */
const submitVote = (
    winnerId: number | null,
    loserId: number | null,
    isTie: boolean = false,
) => {
    // Prevenir votos múltiples mientras se procesa uno
    if (!currentCombination.value || voting.value) {
        return;
    }

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
        preserveScroll: true,
        onSuccess: (page) => {
            // El voto se registró correctamente
            success('Vote recorded successfully!');

            // --- CORRECCIÓN: Cargar la siguiente combinación dinámicamente ---
            // Llamar a la función para obtener la próxima combinación
            loadNextCombination();
            // --- FIN CORRECCIÓN ---
        },
        onError: (errors) => {
            console.error('Errors submitting vote:', errors);
            const errorMessage =
                errors.combinatoric_id ||
                errors.winner_id ||
                errors.loser_id ||
                errors.tie ||
                'Failed to submit vote. Please check the errors.';
            error(errorMessage);
        },
        onFinish: () => {
            // Siempre se ejecuta, útil para limpiar estados
            voting.value = false;
        },
    });
};

/**
 * Cargar la próxima combinación para votar mediante una llamada AJAX/Inertia.
 * Esta función se llama después de un voto exitoso o al montar el componente si no hay combinación inicial.
 */
const loadNextCombination = async () => {
    // Si la encuesta ya está completada, no intentar cargar más combinaciones
    if (isCompleted.value) {
        currentCombination.value = null;
        return;
    }

    loadingNext.value = true;
    try {
        // Hacer una solicitud GET al endpoint del backend para obtener la próxima combinación
        // Asumiendo que existe una ruta API: GET /api/public/surveys/{survey}/next-combination
        // Esta ruta debe devolver JSON { combination: { ... } } o { combination: null }
        /* const response = await router.get(
            route('api.public.surveys.next_combination', props.survey.id),
            {},
            {
                preserveState: false, // No preservar estado viejo
                preserveScroll: true, // Mantener scroll
                only: ['nextCombination'], // Solo pedir la nueva combinación (si el backend la devuelve así)
                // Si el backend devuelve toda la página, se recargará todo el componente.
                // Para más control, se podría usar axios directamente.
            },
        ); */

        // --- Manejo de la respuesta ---
        // Opción 1: Si el backend devuelve solo `nextCombination` en `page.props` gracias a `only: [...]`
        // currentCombination.value = response.props.nextCombination; // Asumiendo que el backend lo devuelve así

        // Opción 2: Si el backend devuelve toda la página, `Inertia` recargará el componente automáticamente
        // con las nuevas props. No necesitamos hacer nada aquí.

        // Opción 3 (Más robusta con axios): Hacer la llamada directamente con axios
        // y manejar la respuesta manualmente.

        const axiosResponse = await axios.get(
            route('api.public.surveys.next_combination', props.survey.id),
        );
        const data = axiosResponse.data;
        if (data.combination) {
            currentCombination.value = data.combination;
        } else {
            // No hay más combinaciones o encuesta completada
            currentCombination.value = null;
            // Opcional: Actualizar el estado de completado si el backend lo indica
            // props.survey.is_completed = true; // Esto no funcionará directamente porque props es inmutable
            // La mejor forma es que el backend devuelva el survey actualizado también
            // y que Inertia lo recargue.
        }
    } catch (err: any) {
        console.error('Error loading next combination:', err);
        error('Failed to load next combination. Please try again.');
        currentCombination.value = null; // Detener el flujo de votación en caso de error crítico
    } finally {
        loadingNext.value = false;
    }
};

/**
 * Manejar el voto por el personaje 1.
 */
const handleVoteCharacter1 = () => {
    if (currentCombination.value) {
        submitVote(
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
        submitVote(
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
        submitVote(null, null, true);
    }
};

/**
 * Manejar eventos de teclado para votación rápida.
 * @param e Evento de teclado
 */
const handleKeyPress = (e: KeyboardEvent) => {
    if (
        e.key === '1' &&
        currentCombination.value &&
        !voting.value &&
        !loadingNext.value
    ) {
        handleVoteCharacter1();
    } else if (
        e.key === '2' &&
        currentCombination.value &&
        !voting.value &&
        !loadingNext.value
    ) {
        handleVoteCharacter2();
    } else if (
        e.key === '3' &&
        currentCombination.value &&
        !voting.value &&
        !loadingNext.value
    ) {
        handleTie();
    }
};

// --- Lifecycle Hooks ---
onMounted(() => {
    // Si no se pasó una combinación inicial o si la encuesta ya está completada, intentar cargar una
    if (!props.nextCombination || isCompleted.value) {
        loadNextCombination();
    }
    // Agregar event listener para teclado
    window.addEventListener('keydown', handleKeyPress);
});

onUnmounted(() => {
    // Limpiar event listener
    window.removeEventListener('keydown', handleKeyPress);
});

// --- Breadcrumbs ---
const breadcrumbs = [
    {
        title: 'Surveys',
        href: route('surveys.public.index'),
    },
    {
        title: props.survey.title,
        href: route('surveys.public.show', props.survey.id),
    },
    {
        title: 'Vote',
        href: route('surveys.public.vote', props.survey.id),
    },
];
</script>

<template>

    <Head :title="`Voting: ${survey.title}`" />

    <!-- Usar VotingLayout -->
    <VotingLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header con progreso -->
            <header class="border-b pb-4">
                <div class="container flex h-16 items-center justify-between px-4">
                    <h1 class="text-xl font-semibold">{{ survey.title }}</h1>

                    <div class="flex items-center gap-6">
                        <!-- Barra de progreso -->
                        <div class="flex items-center gap-2">
                            <div class="h-2 w-32 rounded-full bg-muted">
                                <motion.div class="h-2 rounded-full bg-primary transition-all duration-500 ease-out"
                                    :animate="{
                                        width: progressPercentage + '%',
                                    }" :initial="{ width: 0 }" :transition="{
                                        type: 'spring',
                                        stiffness: 100,
                                    }"></motion.div>
                            </div>
                            <span class="text-sm text-muted-foreground">
                                {{ progressPercentage.toFixed(2) }}%
                            </span>
                        </div>

                        <div class="text-sm text-muted-foreground">
                            {{ totalVotes }} / {{ totalExpected }}
                        </div>

                        <Button asChild variant="outline">
                            <router-link :href="route('surveys.public.show', survey.id)">
                                Back to Survey
                            </router-link>
                        </Button>
                    </div>
                </div>
            </header>

            <!-- Main content -->
            <main class="container py-8">
                <div v-if="loadingNext" class="flex h-96 items-center justify-center">
                    <div class="text-muted-foreground">
                        Loading next match...
                    </div>
                </div>

                <div v-else-if="!hasCombination || isCompleted" class="text-center">
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
                                <router-link :href="route('surveys.public.show', survey.id)
                                    ">
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
                                <CardDescription v-if="
                                    currentCombination.character1.nickname
                                ">
                                    {{ currentCombination.character1.nickname }}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="relative aspect-square w-full overflow-hidden rounded-lg border">
                                    <img v-if="
                                        currentCombination.character1
                                            .picture_url
                                    " :src="currentCombination.character1
                                                .picture_url
                                            " :alt="currentCombination.character1
                                                .fullname
                                            " class="h-full w-full object-cover" />
                                    <div v-else class="flex h-full w-full items-center justify-center bg-muted">
                                        <span class="text-muted-foreground">No image</span>
                                    </div>
                                </div>
                            </CardContent>
                            <CardFooter>
                                <Button class="w-full" :disabled="voting || loadingNext" @click="handleVoteCharacter1">
                                    <span v-if="voting">Voting...</span>
                                    <span v-else>Vote for
                                        {{
                                            currentCombination.character1
                                                .fullname
                                        }}
                                        (1)</span>
                                </Button>
                            </CardFooter>
                        </Card>

                        <!-- Character 2 -->
                        <Card>
                            <CardHeader class="text-center">
                                <CardTitle>{{
                                    currentCombination.character2.fullname
                                    }}</CardTitle>
                                <CardDescription v-if="
                                    currentCombination.character2.nickname
                                ">
                                    {{ currentCombination.character2.nickname }}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="relative aspect-square w-full overflow-hidden rounded-lg border">
                                    <img v-if="
                                        currentCombination.character2
                                            .picture_url
                                    " :src="currentCombination.character2
                                                .picture_url
                                            " :alt="currentCombination.character2
                                                .fullname
                                            " class="h-full w-full object-cover" />
                                    <div v-else class="flex h-full w-full items-center justify-center bg-muted">
                                        <span class="text-muted-foreground">No image</span>
                                    </div>
                                </div>
                            </CardContent>
                            <CardFooter>
                                <Button class="w-full" :disabled="voting || loadingNext" @click="handleVoteCharacter2">
                                    <span v-if="voting">Voting...</span>
                                    <span v-else>Vote for
                                        {{
                                            currentCombination.character2
                                                .fullname
                                        }}
                                        (2)</span>
                                </Button>
                            </CardFooter>
                        </Card>
                    </div>

                    <!-- Botón de Empate -->
                    <div class="mt-8 flex justify-center">
                        <Button variant="outline" :disabled="voting || loadingNext" @click="handleTie">
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
                    <div class="mt-8 text-center text-sm text-muted-foreground">
                        <p>
                            Press
                            <kbd class="rounded bg-muted px-2 py-1">1</kbd>,
                            <kbd class="rounded bg-muted px-2 py-1">2</kbd>, or
                            <kbd class="rounded bg-muted px-2 py-1">3</kbd>
                            to vote quickly.
                        </p>
                    </div>
                </div>
            </main>
        </div>
    </VotingLayout>
</template>

<style scoped>
/* Estilos específicos para este componente si es necesario */
</style>
