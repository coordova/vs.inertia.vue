<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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
import AppLayout from '@/layouts/AppLayout.vue';
// import { motion } from 'framer-motion'; // Importar motion de framer-motion

// Tipos: Definidos localmente para este componente
interface CharacterResource {
    id: number;
    fullname: string;
    nickname: string | null;
    slug: string;
    bio: string | null;
    dob: string | null; // Formato ISO
    gender: number | null; // 0=otro, 1=masculino, 2=femenino, 3=no-binario
    nationality: string | null;
    occupation: string | null;
    picture: string | null; // Path relativo
    picture_url: string | null; // URL completa
    thumbnail_url: string | null; // URL miniatura
    status: boolean;
    meta_title: string | null;
    meta_description: string | null;
    created_at: string; // Formato ISO
    updated_at: string; // Formato ISO
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
    type: number; // 0=pública, 1=privada
    status: boolean; // 1=activa, 0=inactiva
    reverse: boolean;
    date_start: string; // Formato ISO
    date_end: string; // Formato ISO
    selection_strategy: string; // Ej: 'cooldown', 'random', 'elo_based'
    max_votes_per_user: number | null; // 0=ilimitado
    allow_ties: boolean; // 1=sí, 0=no
    tie_weight: number; // Peso de empate (0.0-1.0)
    is_featured: boolean; // 1=sí, 0=no
    sort_order: number;
    counter: number;
    meta_title: string | null;
    meta_description: string | null;
    created_at: string; // Formato ISO
    updated_at: string; // Formato ISO
    deleted_at: string | null; // Formato ISO

    // Datos de fechas formateadas
    date_start_formatted: string; // 'd-m-Y'
    date_end_formatted: string; // 'd-m-Y'
    created_at_formatted: string; // 'd-m-Y H:i:s'
    updated_at_formatted: string; // 'd-m-Y H:i:s'

    // Relación con la categoría
    category: {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        image: string | null;
        color: string; // Hex
        icon: string;
        sort_order: number;
        status: boolean; // 1=activo, 0=inactivo
        is_featured: boolean; // 1=sí, 0=no
        meta_title: string | null;
        meta_description: string | null;
        created_at: string; // Formato ISO
        updated_at: string; // Formato ISO
        deleted_at: string | null; // Formato ISO
    } | null;

    // --- Datos Calculados de Progreso (usando columnas nuevas en BD) ---
    total_combinations: number | null; // De la tabla surveys
    progress_percentage: number; // Del SurveyProgressService
    total_votes: number; // Del SurveyProgressService
    total_combinations_expected: number | null; // Del SurveyProgressService o fallback
    is_completed: boolean; // Del SurveyProgressService
    started_at: string | null; // Del SurveyProgressService
    completed_at: string | null; // Del SurveyProgressService
    last_activity_at: string | null; // Del SurveyProgressService
    completion_time: number | null; // Del SurveyProgressService

    // --- Datos de la Próxima Combinación ---
    // next_combination: CombinatoricResource | null; // <-- No lo pasamos como parte de survey, sino como prop separada
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
    survey: SurveyResource; // El objeto survey con datos de progreso incluidos
    characters: CharacterResource[]; // Personajes activos en la encuesta
    // userProgress: UserProgress; // Ya está incluido en survey, pero lo dejamos por si se necesita aparte
    currentCombination: CombinatoricResource | null; // La próxima combinación a votar
}

const props = defineProps<Props>();
console.log('Props received by PublicVote.vue:', props); // Para depuración

// --- Composables ---
const { success, error } = useToast();

// --- Estados reactivos ---
const voting = ref(false); // Estado para deshabilitar botones durante el voto

// --- Computed Properties ---
// Progreso calculado (usando datos del backend)
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
const hasCombination = computed(() => !!props.currentCombination); // <-- Usar props.currentCombination

// --- Funciones ---

/**
 * Enviar el voto al backend.
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
    if (!props.currentCombination || voting.value) {
        return;
    }

    voting.value = true;

    // Preparar datos para el voto
    const voteData = {
        combinatoric_id: props.currentCombination.combinatoric_id,
        winner_id: isTie ? null : winnerId,
        loser_id: isTie ? null : loserId,
        tie: isTie,
    };

    // Usar router.post de Inertia para enviar el voto
    router.post(route('surveys.vote.store', props.survey.id), voteData, {
        preserveScroll: true, // Mantener posición de scroll
        onSuccess: (page) => {
            // El backend maneja la lógica y redirige de vuelta a esta misma página
            // con datos actualizados (survey, currentCombination) en page.props
            // Inertia recargará el componente con las nuevas props
            success('Vote recorded successfully!');
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
            // Siempre se ejecuta, útil para limpiar estados
            voting.value = false;
        },
    });
};

/**
 * Manejar el voto por el personaje 1.
 */
const handleVoteCharacter1 = () => {
    if (props.currentCombination) {
        submitVote(
            props.currentCombination.character1.id,
            props.currentCombination.character2.id,
            false,
        );
    }
};

/**
 * Manejar el voto por el personaje 2.
 */
const handleVoteCharacter2 = () => {
    if (props.currentCombination) {
        submitVote(
            props.currentCombination.character2.id,
            props.currentCombination.character1.id,
            false,
        );
    }
};

/**
 * Manejar el voto de empate.
 */
const handleTie = () => {
    if (props.currentCombination) {
        submitVote(null, null, true);
    }
};

// --- Breadcrumbs ---
const breadcrumbs = [
    {
        title: 'Surveys',
        href: route('surveys.public.index'),
    },
    {
        title: props.survey.title,
        // href: route('surveys.public.show', props.survey.id), // O props.survey.id
    },
    {
        title: 'Vote',
        // href: route('surveys.public.vote', props.survey.id), // O props.survey.id
    },
];
</script>

<template>
    <Head :title="`Voting: ${survey.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Header con progreso -->
            <header class="border-b pb-4">
                <div
                    class="container flex h-16 items-center justify-between px-4"
                >
                    <h1 class="text-xl font-semibold">{{ survey.title }}</h1>

                    <div class="flex items-center gap-6">
                        <!-- Barra de progreso -->
                        <div class="flex items-center gap-2">
                            <div class="h-2 w-32 rounded-full bg-muted">
                                <motion.div
                                    class="h-2 rounded-full bg-primary transition-all duration-500 ease-out"
                                    :animate="{
                                        width: progressPercentage + '%',
                                    }"
                                    :initial="{ width: 0 }"
                                    :transition="{
                                        type: 'spring',
                                        stiffness: 100,
                                    }"
                                ></motion.div>
                            </div>
                            <span class="text-sm text-muted-foreground">
                                {{ progressPercentage.toFixed(2) }}%
                            </span>
                        </div>

                        <div class="text-sm text-muted-foreground">
                            {{ totalVotes }} / {{ totalExpected }}
                        </div>

                        <Button asChild variant="outline">
                            <!-- <router-link
                                :href="route('surveys.public.show', survey.id)"
                            >
                                Back to Survey
                            </router-link> -->
                        </Button>
                    </div>
                </div>
            </header>

            <!-- Main content -->
            <main class="container py-8">
                <div v-if="!hasCombination || isCompleted" class="text-center">
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
                                        route('surveys.public.show', survey.id)
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
                                        currentCombination.character1.nickname
                                    "
                                >
                                    {{ currentCombination.character1.nickname }}
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
                                        currentCombination.character2.nickname
                                    "
                                >
                                    {{ currentCombination.character2.nickname }}
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
    </AppLayout>
</template>

<style scoped>
/* Estilos específicos para este componente si es necesario */
</style>
