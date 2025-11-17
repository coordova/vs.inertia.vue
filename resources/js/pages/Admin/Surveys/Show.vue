<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog/';
import Badge from '@/components/ui/badge/Badge.vue';
import { Button } from '@/components/ui/button';
import DetailItem from '@/components/ui/oox/TDetailItem.vue';
import { Separator } from '@/components/ui/separator';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { SurveyResource } from '@/types/global';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Calendar,
    Mars,
    Pencil,
    Settings,
    Trash,
    Users,
    Venus,
} from 'lucide-vue-next'; // Iconos

interface ProgressData {
    exists: boolean;
    is_completed: boolean; // Puede ser boolean o integer (0/1)
    progress: number; // Porcentaje
    total_votes: number;
    total_expected: number | null; // Puede ser null si no se pudo calcular
    // Añadir otros campos si el backend los envía
}

interface Props {
    survey: SurveyResource; // Usamos el tipo del recurso resuelto
    selectionStrategyInfo: {
        name: string;
        description: string;
        metadata: {
            [key: string]: any;
        };
    };
    userProgress: ProgressData;
}

const props = defineProps<Props>();
// console.log(props);
// --- Inicializar el composable de toast ---
const { success, error } = useToast();

// --- Manejo de eliminación ---
const handleDelete = (e: Event, id: number) => {
    router.delete(route('admin.surveys.destroy', id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            success('Survey deleted successfully.');
            router.visit(route('admin.surveys.index'));
        },
        onError: () => {
            error('Failed to delete survey.');
        },
    });
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Surveys',
        href: route('admin.surveys.index'),
    },
    {
        title: props.survey.title, // Nombre dinámico de la encuesta
        href: route('admin.surveys.show', props.survey.id),
    },
];
</script>

<template>
    <Head :title="props.survey?.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <!-- Header con título y acciones -->
            <div
                class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        {{ props.survey?.title }}
                    </h1>
                    <p class="text-muted-foreground">
                        {{ props.survey?.description }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button asChild variant="outline">
                        <Link
                            :href="
                                route('admin.surveys.edit', props.survey?.id)
                            "
                        >
                            <Pencil class="mr-2 h-4 w-4" /> Edit
                        </Link>
                    </Button>
                    <Button asChild variant="default">
                        <Link
                            :href="
                                route('public.surveys.show', props.survey?.id)
                            "
                        >
                            Start Voting
                        </Link>
                    </Button>
                </div>
            </div>

            <Separator />

            <!-- Información principal en grid -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Columna izquierda - Información básica -->
                <div class="space-y-4">
                    <div class="rounded-lg border p-4">
                        <h3 class="mb-3 font-semibold">Basic Information</h3>
                        <dl class="space-y-3">
                            <DetailItem
                                label="Status"
                                :content="
                                    props.survey?.status ? 'Active' : 'Inactive'
                                "
                            />
                            <DetailItem
                                label="Type"
                                :content="
                                    props.survey?.type === 0
                                        ? 'Public'
                                        : 'Private'
                                "
                            />
                            <DetailItem
                                label="Category"
                                :content="props.survey?.category?.name"
                            />
                            <DetailItem
                                label="Reverse"
                                :content="props.survey?.reverse ? 'Yes' : 'No'"
                                :showBorder="false"
                            />
                        </dl>
                    </div>

                    <!-- Fechas -->
                    <div class="rounded-lg border p-4">
                        <h3 class="mb-3 font-semibold">Schedule</h3>
                        <dl class="space-y-3">
                            <DetailItem label="Start Date">
                                <div class="flex items-center">
                                    <Calendar
                                        class="mr-2 h-4 w-4 text-muted-foreground"
                                    />
                                    {{ props.survey?.date_start_formatted }}
                                </div>
                            </DetailItem>
                            <DetailItem label="End Date">
                                <div class="flex items-center">
                                    <Calendar
                                        class="mr-2 h-4 w-4 text-muted-foreground"
                                    />
                                    {{ props.survey?.date_end_formatted }}
                                </div>
                            </DetailItem>
                            <DetailItem
                                label="Duration"
                                :content="`${props.survey?.duration} days`"
                                :showBorder="false"
                            />
                        </dl>
                    </div>
                </div>

                <!-- Columna derecha - Estrategia y estadísticas -->
                <div class="space-y-4">
                    <!-- Estrategia de selección mejorada -->
                    <div class="rounded-lg border p-4">
                        <h3 class="mb-3 font-semibold">Selection Strategy</h3>
                        <div v-if="selectionStrategyInfo" class="space-y-2">
                            <div class="flex items-center gap-2">
                                <Settings
                                    class="h-4 w-4 text-muted-foreground"
                                />
                                <div>
                                    <div class="font-medium">
                                        {{ selectionStrategyInfo?.name }}
                                    </div>
                                    <p class="text-sm text-muted-foreground">
                                        {{ selectionStrategyInfo?.description }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="
                                    Object.keys(
                                        selectionStrategyInfo?.metadata || {},
                                    ).length > 0
                                "
                                class="mt-2 text-xs"
                            >
                                <div class="mb-1 font-medium">
                                    Strategy Details:
                                </div>
                                <ul class="space-y-1">
                                    <li
                                        v-for="(
                                            value, key
                                        ) in selectionStrategyInfo?.metadata"
                                        :key="key"
                                        class="flex"
                                    >
                                        <span
                                            class="text-muted-foreground capitalize"
                                        >
                                            {{ key }}:
                                            <!-- ✅ Sin replace, ya viene limpio -->
                                        </span>
                                        <span class="ml-2">{{
                                            String(value)
                                        }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div v-else class="text-sm text-muted-foreground">
                            {{ props.survey?.selection_strategy }}
                        </div>
                    </div>

                    <!-- Estadísticas -->
                    <div class="rounded-lg border p-4">
                        <h3 class="mb-3 font-semibold">Statistics</h3>
                        <dl class="space-y-3">
                            <DetailItem label="Characters">
                                <div class="flex items-center">
                                    <Users
                                        class="mr-2 h-4 w-4 text-muted-foreground"
                                    />
                                    {{
                                        (
                                            survey?.character_count || 0
                                        ).toString()
                                    }}
                                </div>
                            </DetailItem>
                            <DetailItem label="Possible Combinations">
                                <div class="flex items-center">
                                    <Settings
                                        class="mr-2 h-4 w-4 text-muted-foreground"
                                    />
                                    {{
                                        (
                                            survey.combinations_count || 0
                                        ).toString()
                                    }}
                                </div>
                            </DetailItem>
                            <!-- revisar esta info, parece que no es correcta, parece que es la misma que las combinaciones posibles, mejor mostrar nro de veces actualizada, ie, modificado cuando se cambia/amplia la fecha del survey -->
                            <DetailItem label="Combinations Generated">
                                <div class="flex items-center">
                                    <Users
                                        class="mr-2 h-4 w-4 text-muted-foreground"
                                    />
                                    {{
                                        (
                                            survey.combinations_count || 0
                                        ).toString()
                                    }}
                                </div>
                            </DetailItem>
                            <DetailItem
                                label="Slug"
                                :content="survey?.slug"
                                :showBorder="false"
                            />
                        </dl>
                    </div>
                    <!-- ... en la sección de estadísticas ... -->
                    <div class="rounded-lg border p-4">
                        <h3 class="mb-3 font-semibold">Your Progress</h3>
                        <dl class="space-y-3">
                            <DetailItem label="Characters">
                                <div class="flex items-center">
                                    <Users
                                        class="mr-2 h-4 w-4 text-muted-foreground"
                                    />
                                    {{
                                        (
                                            survey?.character_count || 0
                                        ).toString()
                                    }}
                                </div>
                            </DetailItem>
                            <DetailItem label="Total Combinations">
                                <div class="flex items-center">
                                    <Settings
                                        class="mr-2 h-4 w-4 text-muted-foreground"
                                    />
                                    {{
                                        (
                                            survey.combinations_count || 0
                                        ).toString()
                                    }}
                                </div>
                            </DetailItem>
                            <DetailItem
                                label="Your Votes"
                                :content="
                                    (userProgress?.total_votes || 0).toString()
                                "
                            />
                            <DetailItem
                                label="Progress"
                                :content="`${userProgress?.progress.toFixed(2) || 0}%`"
                            />
                            <DetailItem
                                label="Status"
                                :content="
                                    userProgress?.is_completed
                                        ? 'Completed'
                                        : 'In Progress'
                                "
                                :showBorder="false"
                            />
                        </dl>

                        <!-- Barra de progreso visual -->
                        <div v-if="survey.combinations_count" class="mt-4">
                            <div class="flex justify-between text-sm">
                                <span>Progress</span>
                                <span
                                    >{{
                                        userProgress?.progress.toFixed(2) || 0
                                    }}%</span
                                >
                            </div>
                            <div class="mt-1 h-2 w-full rounded-full bg-muted">
                                <div
                                    class="h-2 rounded-full bg-primary transition-all duration-300"
                                    :style="{
                                        width:
                                            (userProgress?.progress.toFixed(
                                                2,
                                            ) || 0) + '%',
                                    }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Separator />

            <!-- Personajes -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold">Characters in Survey</h3>
                <div class="rounded-lg border p-4">
                    <DetailItem
                        v-if="survey?.characters?.length > 0"
                        label=""
                        :showBorder="false"
                    >
                        <ul class="flex flex-wrap gap-2 py-2">
                            <li
                                v-for="character in survey?.characters"
                                :key="character.id"
                            >
                                <Link
                                    :href="
                                        route(
                                            'admin.characters.show',
                                            character.id,
                                        )
                                    "
                                    class="cursor-pointer"
                                >
                                    <Badge
                                        :variant="
                                            character?.status === true
                                                ? 'default'
                                                : 'secondary'
                                        "
                                    >
                                        <Mars
                                            v-if="character.gender === 1"
                                            class="mr-1 h-4 w-4 text-blue-500"
                                        />
                                        <Venus
                                            v-else
                                            class="mr-1 h-4 w-4 text-pink-500"
                                        />
                                        {{ character.fullname }}
                                    </Badge>
                                </Link>
                            </li>
                        </ul>
                    </DetailItem>
                    <DetailItem
                        v-else
                        label=""
                        content="No characters assigned"
                        :showBorder="false"
                    />
                </div>
            </div>

            <!-- Fechas de sistema -->
            <div class="rounded-lg border p-4">
                <h3 class="mb-3 font-semibold">System Information</h3>
                <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <DetailItem
                        label="Created"
                        :content="survey?.created_at_formatted"
                        :showBorder="false"
                    />
                    <DetailItem
                        label="Last Updated"
                        :content="survey?.updated_at_formatted"
                        :showBorder="false"
                    />
                </dl>
            </div>

            <!-- Acciones finales -->
            <div class="flex flex-col gap-4 md:flex-row md:justify-center">
                <Button asChild variant="outline">
                    <Link :href="route('admin.surveys.index')">
                        Back to Surveys
                    </Link>
                </Button>
                <Button asChild variant="default">
                    <Link :href="route('public.surveys.show', survey?.id)">
                        Start Voting
                    </Link>
                </Button>

                <!-- Delete con confirmación -->
                <AlertDialog>
                    <AlertDialogTrigger asChild>
                        <Button variant="outline" class="cursor-pointer">
                            <Trash class="mr-2 h-4 w-4" /> Delete
                        </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                            <AlertDialogDescription>
                                This action cannot be undone. This will
                                permanently delete the survey "{{
                                    survey?.title
                                }}".
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                            <AlertDialogAction
                                @click="
                                    (e: Event) => handleDelete(e, survey?.id)
                                "
                            >
                                Confirm Delete
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </div>
        </div>
    </AppLayout>
</template>
