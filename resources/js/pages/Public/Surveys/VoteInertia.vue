<script setup lang="ts">
import { useToast } from '@/composables/useToast'; // Importar el composable de toast
import { Head, router } from '@inertiajs/vue3';
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
import AppLayout from '@/layouts/AppLayout.vue';
import { AlertCircle, Link } from 'lucide-vue-next'; // Iconos

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

// --- Computed Properties para UI ---
/* const progressPercentage = computed(
    () => surveyData.value.userProgress.progress_percentage ?? 0,
);
const totalExpected = computed(
    () =>
        surveyData.value.userProgress.total_combinations_expected ??
        surveyData.value.userProgress.total_combinations ??
        0,
);
const totalVotes = computed(
    () => surveyData.value.userProgress.total_votes ?? 0,
);
const votesRemaining = computed(() =>
    Math.max(0, totalExpected.value - totalVotes.value),
); */

const progressPercentage = computed(() => userProgress.value.progress ?? 0);
const totalExpected = computed(() => userProgress.value.total_expected ?? 0);
const totalVotes = computed(() => userProgress.value.total_votes ?? 0);
const votesRemaining = computed(() =>
    Math.max(0, totalExpected.value - totalVotes.value),
);

// --- Funciones ---

/**
 * Enviar el voto al backend y actualizar el estado local con la respuesta.
 * @param selectedCharacterId ID del personaje seleccionado (puede ser null para empate)
 * @param isTie Booleano indicando si es empate
 */
const submitVote = (
    selectedCharacterId: number | null,
    isTie: boolean = false,
) => {
    if (!nextCombination.value || voting.value) return;

    voting.value = true;

    let winnerId: number | null = null;
    let loserId: number | null = null;

    if (isTie) {
        // Para empate, ambos IDs son null
        winnerId = null;
        loserId = null;
    } else {
        // Verificar que el ID seleccionado sea uno de los dos personajes de la combinación
        const validCharacterIds = [
            nextCombination.value.character1.id,
            nextCombination.value.character2.id,
        ];
        // console.log(validCharacterIds, selectedCharacterId);
        if (!validCharacterIds.includes(selectedCharacterId)) {
            error('Invalid character selection.');
            voting.value = false;
            return;
        }

        // Determinar ganador y perdedor
        winnerId = selectedCharacterId;
        loserId =
            selectedCharacterId === nextCombination.value.character1.id
                ? nextCombination.value.character2.id
                : nextCombination.value.character1.id;
    }

    // Preparar datos para el voto
    const voteData = {
        combinatoric_id: nextCombination.value.id, // Usar el ID de la combinación actual
        winner_id: winnerId,
        loser_id: loserId,
        tie: isTie,
    };

    // Usar router.post de Inertia para enviar el voto y recibir JSON
    router.post(
        route('public.surveys.vote.store', surveyData.value.id),
        voteData,
        {
            // preserveState: true, // No es necesario si se actualiza el estado local
            preserveScroll: true, // Mantener la posición de desplazamiento
            onSuccess: (page) => {
                // page.props contiene la respuesta JSON del backend
                // Asumiendo que el backend devuelve un objeto como:
                // { message: '...', survey_data: { progress_percentage, total_votes, is_completed, ... }, next_combination: { ... } }
                const responseData = page.props;

                // Actualizar estado local con los datos recibidos del backend
                if (responseData.survey_data) {
                    surveyData.value = {
                        ...surveyData.value,
                        ...responseData.survey_data,
                    };
                }

                if (responseData.next_combination) {
                    // Si hay una próxima combinación, actualizarla
                    nextCombination.value = responseData.next_combination;
                    noMoreCombinations.value = false; // Asegurar que el flag esté en false si hay combinación
                } else {
                    // Si no hay próxima combinación, la encuesta ha terminado para este usuario (o se han completado las disponibles)
                    nextCombination.value = null;
                    noMoreCombinations.value = true;
                }

                // Mostrar mensaje de éxito
                success(responseData.message || 'Vote recorded successfully!');

                // Opcional: Recargar datos del progreso del usuario si el backend no los devuelve explícitamente
                // router.reload({ only: ['userProgress'] }); // Si se pasa como prop separada
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
        },
    );
};

/**
 * Manejar el voto por el personaje 1.
 */
const handleVoteCharacter1 = () => {
    if (nextCombination.value) {
        submitVote(nextCombination.value.character1.id, false);
    }
};

/**
 * Manejar el voto por el personaje 2.
 */
const handleVoteCharacter2 = () => {
    if (nextCombination.value) {
        submitVote(nextCombination.value.character2.id, false);
    }
};

/**
 * Manejar el voto de empate.
 */
const handleTie = () => {
    if (nextCombination.value) {
        submitVote(null, true); // Enviar null para ambos IDs
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

// --- Breadcrumbs (ejemplo) ---
const breadcrumbs = [
    {
        title: 'Surveys',
        href: route('public.surveys.index'),
    },
    {
        title: surveyData.value.title, // Nombre dinámico de la encuesta
        href: route('public.surveys.show', surveyData.value.id),
    },
    {
        title: 'Vote',
        href: route('public.surveys.vote', surveyData.value.id),
    },
];
</script>

<template>
    <Head :title="`Voting: ${surveyData.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
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
                                        :model-value="progressPercentage"
                                        :max="100"
                                    />
                                </div>
                                <span class="text-sm text-muted-foreground">
                                    {{ progressPercentage.toFixed(2) }}%
                                </span>
                            </div>

                            <div class="text-sm text-muted-foreground">
                                {{ totalVotes }} / {{ totalExpected }}
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
    </AppLayout>
</template>

<style scoped>
/* Estilos específicos si es necesario */
</style>
