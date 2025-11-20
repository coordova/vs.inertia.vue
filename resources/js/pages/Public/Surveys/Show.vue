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
import {
    Dialog,
    DialogContent,
    DialogClose,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog'
import VotingLayout from '@/layouts/VotingLayout.vue';
import { CharacterResource, SurveyResource } from '@/types/global'; // Tipos actualizados
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
// import { format } from 'date-fns'; // O dayjs, o formateo nativo

interface Props {
    survey: SurveyResource; // Datos de la encuesta, incluyendo categoría y personajes
    characters: CharacterResource[]; // Personajes activos en la encuesta
    // Opcional: También puedes pasar estadísticas generales de la encuesta desde el backend
    // generalStats: { totalVotes: number, totalParticipants: number, ... }
}

const props = defineProps<Props>();
console.log(props);
// --- Breadcrumbs ---
/* const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Surveys',
        href: route('public.surveys.index'),
    },
    {
        title: props.survey.title,
        href: route('public.surveys.show', props.survey.slug), // O props.survey.id
    },
]; */

// --- Data ---
const modalOpen = ref(false);
const selectedCharacter = ref<CharacterResource | null>(null);

// --- Functions ---
function openModal(character: CharacterResource) {
    selectedCharacter.value = character;
    modalOpen.value = true;
}
</script>

<template>

    <Head :title="`Survey: ${survey.title}`" />

    <VotingLayout>
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
                            <CardContent class="space-y-4">
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
                                        <p class="mt-1 text-sm">
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

                                <!-- Estadísticas Generales (si se pasan desde el backend) -->
                                <!--
                                <Separator class="my-4" />
                                <div class="grid grid-cols-3 gap-4 text-center">
                                    <div>
                                        <div class="text-2xl font-bold">{{ generalStats?.totalVotes || 0 }}</div>
                                        <div class="text-xs text-muted-foreground">Total Votes</div>
                                    </div>
                                    <div>
                                        <div class="text-2xl font-bold">{{ generalStats?.totalParticipants || 0 }}</div>
                                        <div class="text-xs text-muted-foreground">Participants</div>
                                    </div>
                                    <div>
                                        <div class="text-2xl font-bold">{{ props.characters.length }}</div>
                                        <div class="text-xs text-muted-foreground">Characters</div>
                                    </div>
                                </div>
                                -->
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
                                            'public.surveys.voto',
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
                                            'public.surveys.ranking',
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
                                            class="flex items-center gap-3 rounded-lg border p-3 hover:bg-accent">
                                            <Dialog>
                                                <DialogTrigger asChild>
                                                    <div
                                                        class="relative aspect-square w-12 overflow-hidden rounded-full border">
                                                        <img v-if="character.picture_url" :src="character.picture_url"
                                                            :alt="character.fullname"
                                                            class="h-full w-full object-cover cursor-pointer"
                                                            @click="openModal(character)" />
                                                        <div v-else
                                                            class="flex h-full w-full items-center justify-center bg-muted">
                                                            <span class="text-xs text-muted-foreground">No img</span>
                                                        </div>
                                                    </div>
                                                    <div class="flex-1 truncate">
                                                        <p class="truncate text-sm font-medium">
                                                            {{ character.fullname }}
                                                        </p>
                                                        <p v-if="character.nickname"
                                                            class="truncate text-xs text-muted-foreground">
                                                            {{ character.nickname }}
                                                        </p>
                                                    </div>
                                                </DialogTrigger>
                                                <DialogContent>
                                                    <DialogHeader>
                                                        <DialogTitle>{{ selectedCharacter?.fullname }}</DialogTitle>
                                                        <DialogDescription>
                                                            {{ selectedCharacter?.nickname }}
                                                        </DialogDescription>
                                                    </DialogHeader>
                                                    <!-- DialogBody -->
                                                    <div class="flex flex-col text-sm gap-4 items-center">
                                                        <img :src="selectedCharacter?.picture_url"
                                                            :alt="selectedCharacter?.fullname"
                                                            class="h-64 w-64 rounded-full object-cover" />
                                                        <div>
                                                            <p class="text-sm text-muted-foreground">{{
                                                                selectedCharacter?.bio
                                                                }}</p>
                                                            <!-- Character Information -->
                                                            <dl class="divide-y divide-gray-100 dark:divide-white/10">
                                                                <div
                                                                    class="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                                                    <dt
                                                                        class="text-sm/6 font-medium text-gray-900 dark:text-gray-100">
                                                                        Gender
                                                                    </dt>
                                                                    <dd
                                                                        class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400">
                                                                        <div class="flex items-center gap-2">
                                                                            {{
                                                                                selectedCharacter?.gender === 0
                                                                                    ? 'Other'
                                                                                    : selectedCharacter?.gender === 1
                                                                                        ? 'Male'
                                                                                        : selectedCharacter?.gender === 2
                                                                                            ? 'Female'
                                                                                            : selectedCharacter?.gender === 3
                                                                                                ? 'Non-binary'
                                                                                                : 'Unknown'
                                                                            }}
                                                                        </div>
                                                                    </dd>
                                                                </div>
                                                                <div
                                                                    class="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                                                    <dt
                                                                        class="text-sm/6 font-medium text-gray-900 dark:text-gray-100">
                                                                        DOB
                                                                    </dt>
                                                                    <dd
                                                                        class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400">
                                                                        <div class="flex items-center gap-2">
                                                                            {{ selectedCharacter?.dob_formatted || 'N/A'
                                                                            }} <span class="text-xs text-gray-500">({{
                                                                                selectedCharacter?.dob_for_humans ||
                                                                                'N/A'
                                                                            }})</span>
                                                                        </div>
                                                                    </dd>
                                                                </div>
                                                                <div
                                                                    class="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                                                    <dt
                                                                        class="text-sm/6 font-medium text-gray-900 dark:text-gray-100">
                                                                        Nationality
                                                                    </dt>
                                                                    <dd
                                                                        class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400">
                                                                        <div class="flex items-center gap-2">
                                                                            {{ selectedCharacter?.nationality || 'N/A'
                                                                            }}
                                                                        </div>
                                                                    </dd>
                                                                </div>
                                                                <div
                                                                    class="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                                                    <dt
                                                                        class="text-sm/6 font-medium text-gray-900 dark:text-gray-100">
                                                                        Status
                                                                    </dt>
                                                                    <dd
                                                                        class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400">
                                                                        {{
                                                                            selectedCharacter?.status === true
                                                                                ? 'Active'
                                                                                : 'Inactive'
                                                                        }}
                                                                    </dd>
                                                                </div>
                                                            </dl>

                                                        </div>
                                                    </div>
                                                    <DialogFooter>
                                                        <DialogClose as-child>
                                                            <Button type="button" variant="outline">
                                                                Close
                                                            </Button>
                                                        </DialogClose>
                                                    </DialogFooter>
                                                </DialogContent>
                                            </Dialog>

                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
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
