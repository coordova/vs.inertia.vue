<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import PublicAppLayout from '@/layouts/PublicAppLayout.vue';
import { CharacterResource, SurveyResource } from '@/types/global'; // Tipos actualizados
import { Head, Link } from '@inertiajs/vue3';
// import { ref } from 'vue';
import TCharacterDialog from '@/components/oox/TCharacterDialog.vue'
import { BreadcrumbItem } from '@/types';
// import { format } from 'date-fns'; // O dayjs, o formateo nativo

interface ProgressData {
    exists: boolean;
    is_completed: boolean; // Puede ser boolean o integer (0/1)
    progress: number; // Porcentaje
    total_votes: number;
    total_expected: number | null; // Puede ser null si no se pudo calcular
    // Añadir otros campos si el backend los envía
}

interface Props {
    survey: SurveyResource; // Datos de la encuesta, incluyendo categoría y personajes
    characters: CharacterResource[]; // Personajes activos en la encuesta
    userProgress: ProgressData; // Progreso del usuario en la encuesta
    // Opcional: También puedes pasar estadísticas generales de la encuesta desde el backend
    // generalStats: { totalVotes: number, totalParticipants: number, ... }
}

const props = defineProps<Props>();
console.log(props);
// --- Breadcrumbs ---
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Surveys',
        href: route('public.surveys.index'),
    },
    {
        title: props.survey.title,
        href: route('public.surveys.show', props.survey.slug), // O props.survey.id
    },
];

// --- Data ---
// const modalOpen = ref(false);
// const selectedCharacter = ref<CharacterResource | null>(null);

// --- Functions ---
/* function openModal(character: CharacterResource) {
    selectedCharacter.value = character;
    modalOpen.value = true;
} */
</script>

<template>

    <Head :title="`Survey: ${survey.title}`" />

    <PublicAppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-8">
            <div class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <header class="border-b pb-4">
                    <div class="container flex h-16 items-center justify-between px-4">
                        <h1 class="text-xl font-semibold">
                            {{ survey.title }}
                        </h1>
                        <Badge :variant="survey.status ? 'default' : 'secondary'">
                            {{ survey.status ? 'Active' : 'Inactive' }}
                        </Badge>
                    </div>
                </header>

                <main class="container py-8">
                    <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                        <!-- Información Principal de la Encuesta -->
                        <Card class="md:col-span-2">
                            <CardHeader>
                                <CardTitle>Survey Details</CardTitle>
                                <CardDescription>
                                    Information about the "{{ survey.title }}"
                                    survey.
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4 text-card-foreground">
                                <!-- <div
                                    v-if="survey.image_url"
                                    class="relative aspect-video w-full overflow-hidden rounded-lg border"
                                >
                                    <img
                                        :src="survey.image_url"
                                        :alt="survey.title"
                                        class="h-full w-full object-cover"
                                    />
                                </div>
                                <div
                                    v-else
                                    class="flex h-40 w-full items-center justify-center rounded-lg bg-muted"
                                >
                                    <span class="text-muted-foreground"
                                        >No Image</span
                                    >
                                </div> -->

                                <div>
                                    <h3 class="text-sm font-medium text-muted-foreground">
                                        Description
                                    </h3>
                                    <p class="mt-1 text-sm">
                                        {{
                                            survey.description ||
                                            'No description provided.'
                                        }}
                                    </p>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <h3 class="text-sm font-medium text-muted-foreground">
                                            Category
                                        </h3>
                                        <p class="mt-1 text-sm ">
                                            {{ survey.category?.name || 'N/A' }}
                                        </p>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-medium text-muted-foreground">
                                            Strategy
                                        </h3>
                                        <p class="mt-1 text-sm">
                                            {{ survey.selection_strategy }}
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <h3 class="text-sm font-medium text-muted-foreground">
                                            Start Date
                                        </h3>
                                        <p class="mt-1 text-sm">
                                            {{ survey.date_start_formatted }}
                                        </p>
                                        <!-- Ej: Oct 22, 2025 -->
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-medium text-muted-foreground">
                                            End Date
                                        </h3>
                                        <p class="mt-1 text-sm">
                                            {{ survey.date_end_formatted }}
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <h3 class="text-sm font-medium text-muted-foreground">
                                            Duration
                                        </h3>
                                        <p class="mt-1 text-sm">
                                            {{ survey.duration }} days
                                        </p>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-medium text-muted-foreground">
                                            Duration Left
                                        </h3>
                                        <p class="mt-1 text-sm">
                                            {{ survey.duration_left }}
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <h3 class="text-sm font-medium text-muted-foreground">
                                            Max Votes per User
                                        </h3>
                                        <p class="mt-1 text-sm">
                                            {{
                                                survey.max_votes_per_user === 0
                                                    ? 'Unlimited'
                                                    : survey.max_votes_per_user
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-medium text-muted-foreground">
                                            Allows Ties
                                        </h3>
                                        <p class="mt-1 text-sm">
                                            {{
                                                survey.allow_ties ? 'Yes' : 'No'
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Estadísticas Progreso del usuario (si se pasan desde el backend) -->
                                <Separator class="my-4" />
                                <h3 class="text-sm font-medium text-muted-foreground">
                                    Progress
                                </h3>
                                <div v-if="userProgress" class="grid grid-cols-3 gap-4 text-center">
                                    <div>
                                        <div class="text-2xl font-bold">{{ userProgress?.total_votes || 0 }}</div>
                                        <div class="text-xs text-muted-foreground">Total Votes</div>
                                    </div>
                                    <div>
                                        <div class="text-2xl font-bold">{{ userProgress?.total_expected || 0 }}</div>
                                        <div class="text-xs text-muted-foreground">Expected Votes</div>
                                    </div>
                                    <div>
                                        <div class="text-2xl font-bold">{{ userProgress?.progress.toFixed(2) || 0 }}%
                                        </div>
                                        <div class="text-xs text-muted-foreground">Progress</div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Acciones y Personajes Participantes -->
                        <div class="space-y-6">
                            <!-- Acción: Iniciar Votación -->
                            <Card>
                                <CardHeader>
                                    <CardTitle>Participate</CardTitle>
                                    <CardDescription>
                                        Start voting in this survey now.
                                    </CardDescription>
                                </CardHeader>
                                <CardFooter class="flex flex-col gap-4">
                                    <Button asChild class="w-full">
                                        <Link :href="route(
                                            'public.surveys.vote',
                                            survey.id,
                                        )
                                            ">
                                            <!-- O survey.id -->
                                            Start Voting
                                        </Link>
                                    </Button>
                                    <!-- Opcional: Botón para ver ranking si está disponible -->
                                    <Button asChild variant="outline" class="w-full">
                                        <Link :href="route(
                                            'public.surveys.results',
                                            survey.id,
                                        )
                                            ">
                                            <!-- O survey.id -->
                                            View Rankings
                                        </Link>
                                    </Button>
                                </CardFooter>
                            </Card>

                            <!-- Personajes Participantes -->
                            <Card>
                                <CardHeader>
                                    <CardTitle>Participants ({{
                                        props.characters.length
                                    }})</CardTitle>
                                    <CardDescription>
                                        Characters competing in this survey.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent class="max-h-96 overflow-y-auto">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div v-for="character in props.characters" :key="character.id"
                                            class="flex items-center gap-3">
                                            <TCharacterDialog :character="character" />
                                        </div>
                                    </div>

                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </PublicAppLayout>
</template>

<style scoped>
/* Estilos específicos si es necesario */
</style>
