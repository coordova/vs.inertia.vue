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
import AppLayout from '@/layouts/AppLayout.vue';
import { SurveyResource } from '@/types/global'; // Asumiendo que SurveyIndexResource está definido y optimizado para esta vista
import { Head } from '@inertiajs/vue3';
import { Calendar, Tag } from 'lucide-vue-next'; // Iconos
// import { format } from 'date-fns'; // O usar day.js o el formateo nativo de JS
import { Link } from '@inertiajs/vue3';

// --- Tipos ---
interface Props {
    surveys: {
        data: SurveyResource[]; // Array de encuestas paginadas
        meta: {
            current_page: number;
            from: number;
            last_page: number;
            path: string;
            per_page: number;
            to: number;
            total: number;
        };
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters?: Record<string, any>; // Filtros aplicados (search, category, etc.)
}

const props = defineProps<Props>();

// --- Breadcrumbs ---
const breadcrumbs = [
    {
        title: 'Surveys',
        href: route('surveys.public.index'), // Asumiendo que esta ruta apunta a este componente
    },
];
</script>

<template>
    <Head title="Surveys" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-8">
            <div
                class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
            >
                <header class="border-b pb-4">
                    <div
                        class="container flex h-16 items-center justify-between px-4"
                    >
                        <h1 class="text-xl font-semibold">Available Surveys</h1>
                        <span class="text-sm text-muted-foreground"
                            >Discover and participate in various surveys.</span
                        >
                    </div>
                </header>

                <main class="container">
                    <!-- Lista de Encuestas -->
                    <div
                        v-if="props.surveys.data.length > 0"
                        class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
                    >
                        <Card
                            v-for="survey in props.surveys.data"
                            :key="survey.id"
                            class="flex flex-col"
                        >
                            <CardHeader>
                                <div class="flex items-start justify-between">
                                    <div>
                                        <CardTitle class="text-lg">{{
                                            survey.title
                                        }}</CardTitle>
                                        <CardDescription
                                            v-if="survey.description"
                                            class="truncate"
                                        >
                                            {{ survey.description }}
                                        </CardDescription>
                                    </div>
                                    <Badge
                                        :variant="
                                            survey.status
                                                ? 'default'
                                                : 'secondary'
                                        "
                                        class="ml-2"
                                    >
                                        {{
                                            survey.status
                                                ? 'Active'
                                                : 'Inactive'
                                        }}
                                    </Badge>
                                </div>
                                <div
                                    v-if="survey.category"
                                    class="mt-2 flex items-center gap-2 text-sm text-muted-foreground"
                                >
                                    <Tag class="h-4 w-4" />
                                    <span>{{ survey.category.name }}</span>
                                </div>
                            </CardHeader>
                            <CardContent class="flex-grow">
                                <!-- <div
                                    v-if="survey.image"
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
                                    class="flex h-40 w-full items-center justify-center bg-muted"
                                >
                                    <span class="text-muted-foreground"
                                        >No image</span
                                    >
                                </div> -->

                                <!-- Estadísticas de la encuesta -->
                                <div
                                    class="mt-4 grid grid-cols-3 gap-2 text-center text-sm"
                                >
                                    <div>
                                        <div class="font-semibold">
                                            {{ survey.duration ?? 'N/A' }} Days
                                        </div>
                                        <div class="text-muted-foreground">
                                            Duration
                                        </div>
                                    </div>
                                    <div>
                                        <div class="font-semibold">
                                            {{ survey.character_count ?? 0 }}
                                        </div>
                                        <div class="text-muted-foreground">
                                            Characters
                                        </div>
                                    </div>
                                    <div>
                                        <div class="font-semibold">
                                            {{ survey.combinations_count ?? 0 }}
                                        </div>
                                        <div class="text-muted-foreground">
                                            Matches
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                            <CardFooter class="flex flex-col items-start gap-2">
                                <div
                                    class="flex w-full items-center justify-between text-sm text-muted-foreground"
                                >
                                    <div class="flex items-center gap-1">
                                        <Calendar class="h-4 w-4" />
                                        <span>
                                            {{ survey.date_start_formatted }}
                                            -
                                            {{ survey.date_end_formatted }}
                                        </span>
                                    </div>
                                    <Badge
                                        variant="outline"
                                        :class="{
                                            'border-orange-600 text-orange-600':
                                                survey.is_featured,
                                        }"
                                    >
                                        {{
                                            survey.is_featured
                                                ? 'Featured'
                                                : 'Regular'
                                        }}
                                    </Badge>
                                </div>
                                <Separator class="my-2 w-full" />
                                <div class="flex w-full justify-end gap-2">
                                    <Button variant="outline" size="sm" asChild>
                                        <Link
                                            :href="
                                                route(
                                                    'surveys.public.show',
                                                    survey.slug,
                                                )
                                            "
                                        >
                                            <!-- O survey.id -->
                                            View Details
                                        </Link>
                                    </Button>
                                    <Button
                                        size="sm"
                                        asChild
                                        :disabled="
                                            !survey.status ||
                                            survey.is_active === false
                                        "
                                    >
                                        <!-- Asumiendo is_active calculado en el backend -->
                                        <Link
                                            :href="
                                                route(
                                                    'surveys.public.vote',
                                                    survey.slug,
                                                )
                                            "
                                        >
                                            <!-- O survey.id -->
                                            Participate
                                        </Link>
                                    </Button>
                                </div>
                            </CardFooter>
                        </Card>
                    </div>

                    <!-- Mensaje si no hay encuestas -->
                    <div v-else class="py-12 text-center">
                        <h3 class="text-lg font-medium">
                            No surveys available
                        </h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Check back later for new surveys.
                        </p>
                    </div>

                    <!-- Paginación (usando TPagination o componente propio) -->
                    <!-- <TPagination
                        :current-page="props.surveys.meta.current_page"
                        :total-items="props.surveys.meta.total"
                        :items-per-page="props.surveys.meta.per_page"
                        @page-change="goToPage" // Asumiendo una función goToPage que use router.get
                    /> -->
                    <!-- O implementar paginación simple con Inertia Link -->
                    <div
                        v-if="props.surveys.links.length > 2"
                        class="mt-8 flex justify-center"
                    >
                        <nav class="flex items-center space-x-1">
                            <Link
                                v-for="(link, index) in props.surveys.links"
                                :key="index"
                                :href="link.url ?? '#'"
                                :class="[
                                    'flex h-9 w-9 items-center justify-center rounded-full text-sm font-medium',
                                    link.active
                                        ? 'bg-primary text-primary-foreground'
                                        : 'text-muted-foreground hover:bg-muted',
                                    !link.url
                                        ? 'cursor-not-allowed opacity-50'
                                        : '',
                                ]"
                                :aria-disabled="!link.url"
                                :tabindex="link.url ? 0 : -1"
                                v-html="link.label"
                            />
                        </nav>
                    </div>
                </main>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Estilos específicos si es necesario */
</style>
