<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Calendar, Users, Tag } from 'lucide-vue-next';
import PublicAppLayout from '@/layouts/PublicAppLayout.vue'; // Asumiendo el layout público general
import { type BreadcrumbItem } from '@/types';
import { SurveyResource, CategoryResource } from '@/types/global'; // Asumiendo interfaces definidas

// --- Tipos ---
interface Props {
    recentSurveys: SurveyResource[];
    activeCategories: CategoryResource[];
    // globalRankings?: CharacterResource[]; // Opcional, si se carga en index
    // filters?: Record<string, any>; // Filtros aplicados (opcional)
}

const props = defineProps<Props>();

// --- Breadcrumbs ---
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Statistics',
        href: route('public.statistics.index'),
    },
];
</script>

<template>

    <Head title="Statistics & Rankings" />

    <PublicAppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-background">
            <!-- Hero Section -->
            <section class="relative bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white py-16">
                <div class="absolute inset-0 bg-black opacity-20"></div> <!-- Overlay oscuro opcional -->
                <div class="container mx-auto px-4 relative z-10">
                    <div class="max-w-3xl mx-auto text-center">
                        <h1 class="text-3xl md:text-5xl font-bold mb-4">Statistics & Rankings</h1>
                        <p class="text-lg md:text-xl mb-6">
                            Explore the latest rankings, results, and insights from our surveys.
                        </p>
                        <!-- Opcional: Botón de acción -->
                        <!-- <Button variant="secondary" size="lg">Learn More</Button> -->
                    </div>
                </div>
            </section>

            <!-- Main Content -->
            <main class="container mx-auto py-12 px-4">
                <!-- Overview Section -->
                <section class="mb-16">
                    <h2 class="text-2xl font-bold mb-8 text-center">Overview</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <!-- Metric Card 1: Active Surveys -->
                        <Card>
                            <CardHeader class="pb-2">
                                <CardDescription>Active Surveys</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="text-3xl font-bold">{{ props.recentSurveys.length }}</div>
                                <p class="text-xs text-muted-foreground mt-1">Currently running</p>
                            </CardContent>
                        </Card>

                        <!-- Metric Card 2: Active Categories -->
                        <Card>
                            <CardHeader class="pb-2">
                                <CardDescription>Active Categories</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="text-3xl font-bold">{{ props.activeCategories.length }}</div>
                                <p class="text-xs text-muted-foreground mt-1">With active surveys</p>
                            </CardContent>
                        </Card>

                        <!-- Metric Card 3: Top Category -->
                        <Card v-if="props.activeCategories.length > 0">
                            <CardHeader class="pb-2">
                                <CardDescription>Most Active Category</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="text-xl font-semibold">{{ props.activeCategories[0].name }}</div>
                                <p class="text-xs text-muted-foreground mt-1">
                                    {{ props.activeCategories[0].surveys_count }} active surveys
                                </p>
                            </CardContent>
                        </Card>
                        <Card v-else>
                            <CardHeader class="pb-2">
                                <CardDescription>Most Active Category</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="text-xl font-semibold">-</div>
                                <p class="text-xs text-muted-foreground mt-1">No active surveys</p>
                            </CardContent>
                        </Card>
                    </div>
                </section>

                <!-- Recent Surveys Section -->
                <section v-if="props.recentSurveys.length > 0" class="mb-16">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold">Recent Surveys</h2>
                        <Link :href="route('public.surveys.index')">
                        <Button variant="link">View All Surveys</Button>
                        </Link>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <Card v-for="survey in props.recentSurveys" :key="survey.id"
                            class="h-full flex flex-col overflow-hidden transition-shadow duration-300 hover:shadow-lg">
                            <CardHeader>
                                <div class="flex justify-between items-start">
                                    <div>
                                        <CardTitle class="text-lg">{{ survey.title }}</CardTitle>
                                        <CardDescription class="line-clamp-2 mt-1">{{ survey.description }}
                                        </CardDescription>
                                    </div>
                                    <Badge :variant="survey.status ? 'default' : 'secondary'" class="ml-2">
                                        {{ survey.status ? 'Active' : 'Inactive' }}
                                    </Badge>
                                </div>
                                <div class="mt-2 flex items-center gap-2 text-sm text-muted-foreground">
                                    <Tag class="h-4 w-4" />
                                    <span>{{ survey.category?.name || 'N/A' }}</span>
                                </div>
                            </CardHeader>
                            <CardContent class="flex-grow">
                                <div class="flex items-center justify-between text-sm text-muted-foreground">
                                    <div class="flex items-center gap-1">
                                        <Calendar class="h-4 w-4" />
                                        <span>{{ new Date(survey.date_start_formatted).toLocaleDateString() }} - {{ new
                                            Date(survey.date_end_formatted).toLocaleDateString() }}</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <Users class="h-4 w-4" />
                                        <span>{{ survey.character_count }} chars</span>
                                    </div>
                                </div>
                            </CardContent>
                            <CardFooter class="flex flex-col gap-2">
                                <Separator class="my-2 w-full" />
                                <div class="flex justify-between gap-2 w-full">
                                    <Link :href="route('public.surveys.show', survey.slug)">
                                    <Button variant="outline" class="flex-grow">Details</Button>
                                    </Link>
                                    <Link :href="route('public.surveys.vote', survey.slug)">
                                    <Button class="flex-grow" :disabled="!survey.is_active">
                                        Participate
                                    </Button>
                                    </Link>
                                </div>
                            </CardFooter>
                        </Card>
                    </div>
                </section>

                <!-- Active Categories Section -->
                <section v-if="props.activeCategories.length > 0" class="mb-16">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold">Active Categories</h2>
                        <Link :href="route('public.categories.index')">
                        <Button variant="link">View All Categories</Button>
                        </Link>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <Card v-for="category in props.activeCategories" :key="category.id"
                            class="h-full flex flex-col overflow-hidden transition-shadow duration-300 hover:shadow-lg">
                            <CardHeader>
                                <CardTitle class="text-xl">{{ category.name }}</CardTitle>
                                <CardDescription class="line-clamp-2">{{ category.description }}</CardDescription>
                            </CardHeader>
                            <CardContent class="flex-grow">
                                <!-- Opcional: Mostrar un contador de encuestas o personajes en la categoría -->
                                <p class="text-sm text-muted-foreground">{{ category.surveys_count }} active surveys</p>
                            </CardContent>
                            <CardFooter>
                                <Link :href="route('public.categories.show', category.slug)">
                                <Button variant="outline" class="w-full">View Category</Button>
                                </Link>
                            </CardFooter>
                        </Card>
                    </div>
                </section>

                <!-- Global Rankings Section (Enlace a página específica) -->
                <section class="mb-16">
                    <h2 class="text-2xl font-bold mb-6">Global Rankings</h2>
                    <div class="text-center">
                        <p class="text-muted-foreground mb-4">
                            View overall character rankings across all categories.
                        </p>
                        <Link :href="route('public.statistics.global-rankings')">
                        <Button variant="outline">View Global Rankings</Button>
                        </Link>
                    </div>
                </section>

                <!-- CTA Section -->
                <section class="py-12 bg-indigo-50 rounded-xl">
                    <div class="text-center">
                        <h3 class="text-2xl font-bold mb-2">Want to See More?</h3>
                        <p class="text-muted-foreground mb-6">
                            Explore detailed statistics for specific surveys or characters.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <Link :href="route('public.surveys.index')">
                            <Button>Browse Surveys</Button>
                            </Link>
                            <Link :href="route('public.categories.index')">
                            <Button variant="outline">Browse Categories</Button>
                            </Link>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </PublicAppLayout>
</template>

<style scoped>
/* line-clamp para truncar texto */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>