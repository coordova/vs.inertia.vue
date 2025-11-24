<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Calendar, Users, Tag } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue'; // Asumiendo que usas AppLayout
import { CategoryResource, SurveyResource } from '@/types/global'; // Asumiendo interfaces definidas

// --- Tipos ---
interface Props {
    featuredCategories: CategoryResource[];
    recentSurveys: SurveyResource[];
    // popularSurveys?: SurveyResource[]; // Opcional
}

const props = defineProps<Props>();

// --- Breadcrumbs ---
const breadcrumbs = [
    {
        title: 'Home',
        href: route('home'), // Asumiendo que esta es la ruta para '/'
    },
];
</script>

<template>

    <Head title="Facematch Ultramoderno" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-background">
            <!-- Hero Section -->
            <section class="relative bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white py-20">
                <div class="absolute inset-0 bg-black opacity-20"></div> <!-- Overlay oscuro opcional -->
                <div class="container mx-auto px-4 relative z-10">
                    <div class="max-w-3xl mx-auto text-center">
                        <h1 class="text-4xl md:text-6xl font-bold mb-6">
                            Discover Who's More...
                        </h1>
                        <p class="text-xl md:text-2xl mb-8">
                            Compare and vote on your favorite characters across various categories.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <Link :href="route('public.surveys.index')">
                            <Button size="lg" class="bg-white text-indigo-600 hover:bg-gray-100">
                                Explore Surveys
                            </Button>
                            </Link>
                            <Link :href="route('public.categories.index')">
                            <Button size="lg" variant="outline"
                                class="border-white text-white hover:bg-white hover:text-indigo-600">
                                Browse Categories
                            </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Featured Categories Section -->
            <section v-if="props.featuredCategories.length > 0" class="py-16 bg-muted">
                <div class="container mx-auto px-4">
                    <h2 class="text-3xl font-bold text-center mb-12">Featured Categories</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <Card v-for="category in props.featuredCategories" :key="category.id"
                            class="h-full flex flex-col overflow-hidden transition-shadow duration-300 hover:shadow-lg">
                            <div v-if="category.image" class="h-40 w-full overflow-hidden">
                                <img :src="category.image_url" // Asumiendo que CategoryResource incluye image_url
                                    :alt="category.name" class="w-full h-full object-cover" />
                            </div>
                            <div v-else class="h-40 w-full bg-muted flex items-center justify-center">
                                <Tag class="h-12 w-12 text-muted-foreground" />
                            </div>
                            <CardHeader>
                                <CardTitle class="text-xl">{{ category.name }}</CardTitle>
                                <CardDescription class="line-clamp-2">{{ category.description }}</CardDescription>
                            </CardHeader>
                            <CardContent class="flex-grow">
                                <!-- Opcional: Mostrar un contador de encuestas o personajes en la categoría -->
                                <!-- <p class="text-sm text-muted-foreground">{{ category.surveys_count }} surveys</p> -->
                            </CardContent>
                            <CardFooter>
                                <Link :href="route('public.categories.show', category.slug)"> <!-- O category.id -->
                                <Button variant="outline" class="w-full">View Category</Button>
                                </Link>
                            </CardFooter>
                        </Card>
                    </div>
                    <div class="text-center mt-8">
                        <Link :href="route('public.categories.index')">
                        <Button variant="link">View All Categories</Button>
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Recent Surveys Section -->
            <section v-if="props.recentSurveys.length > 0" class="py-16 bg-white">
                <div class="container mx-auto px-4">
                    <h2 class="text-3xl font-bold text-center mb-12">Recent Surveys</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <Card v-for="survey in props.recentSurveys" :key="survey.id"
                            class="h-full flex flex-col overflow-hidden transition-shadow duration-300 hover:shadow-lg">
                            <div v-if="survey.image" class="h-40 w-full overflow-hidden">
                                <img :src="survey.image_url" // Asumiendo que SurveyIndexResource incluye image_url
                                    :alt="survey.title" class="w-full h-full object-cover" />
                            </div>
                            <div v-else class="h-40 w-full bg-muted flex items-center justify-center">
                                <Users class="h-12 w-12 text-muted-foreground" />
                            </div>
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
                                    <!-- Acceder a la categoría anidada -->
                                </div>
                            </CardHeader>
                            <CardContent class="flex-grow">
                                <div class="flex items-center justify-between text-sm text-muted-foreground">
                                    <div class="flex items-center gap-1">
                                        <Calendar class="h-4 w-4" />
                                        <span>{{ new Date(survey.date_start_formatted).toLocaleDateString() }} - {{ new
                                            Date(survey.date_end_formatted).toLocaleDateString() }}</span>
                                    </div>
                                    <div>
                                        <!-- Opcional: Mostrar número de personajes o combinaciones -->
                                        <!-- <span>{{ survey.character_count }} chars</span> -->
                                    </div>
                                </div>
                            </CardContent>
                            <CardFooter class="flex flex-col gap-2">
                                <Link :href="route('public.surveys.show', survey.slug)"> <!-- O survey.id -->
                                <Button variant="outline" class="w-full">View Details</Button>
                                </Link>
                                <Link :href="route('public.surveys.vote', survey.slug)"> <!-- O survey.id -->
                                <Button class="w-full" :disabled="!survey.is_active">
                                    <!-- Asumiendo is_active calculado en el backend -->
                                    Participate
                                </Button>
                                </Link>
                            </CardFooter>
                        </Card>
                    </div>
                    <div class="text-center mt-8">
                        <Link :href="route('public.surveys.index')">
                        <Button variant="link">View All Surveys</Button>
                        </Link>
                    </div>
                </div>
            </section>

            <!-- CTA Section -->
            <section class="py-16 bg-indigo-600 text-white">
                <div class="container mx-auto px-4 text-center">
                    <h2 class="text-3xl font-bold mb-4">Ready to Start Voting?</h2>
                    <p class="text-xl mb-8 max-w-2xl mx-auto">
                        Join thousands of users comparing and ranking characters across different categories.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <Link :href="route('register')" v-if="!$page.props.auth?.user">
                        <Button size="lg" variant="secondary">Sign Up</Button>
                        </Link>
                        <Link :href="route('login')" v-if="!$page.props.auth?.user">
                        <Button size="lg">Log In</Button>
                        </Link>
                        <Link :href="route('public.surveys.index')" v-else>
                        <Button size="lg">Explore Surveys</Button>
                        </Link>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<style scoped>
/* line-clamp para truncar texto */
/* .line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
} */
</style>