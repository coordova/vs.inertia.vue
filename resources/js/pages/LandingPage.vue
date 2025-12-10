<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Calendar, Users, Tag } from 'lucide-vue-next';
import PublicAppLayout from '@/layouts/PublicAppLayout.vue'; // <-- Importar el nuevo layout
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
        href: route('public.home'), // Asumiendo que esta es la ruta para '/'
    },
];
</script>

<template>

    <Head title="Facematch Ultramoderno" />

    <PublicAppLayout> <!-- <-- Usar el nuevo layout -->
        <div class="min-h-screen bg-background">
            <!-- Hero Section -->
            <section
                class="relative bg-gradient-to-r from-secondary text-accent via-muted-foreground to-background  dark:from-background dark:via-primary dark:to-secondary py-20 md:py-32 lg:py-40">
                <div class="absolute inset-0 bg-black opacity-20"></div> <!-- Overlay oscuro opcional -->
                <div class="container mx-auto px-4 relative z-10">
                    <div class="max-w-3xl mx-auto text-center">
                        <h1 class="text-4xl md:text-6xl font-bold mb-6">
                            Discover Who's More...
                        </h1>
                        <p class="text-xl md:text-2xl mb-8 text-accent/50">
                            Compare and vote on your favorite characters across various categories.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <Link :href="route('public.surveys.index')">
                                <button
                                    class="inline-flex items-center justify-center gap-2 whitespace-nowrap font-medium focus:outline-none focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 bg-secondary text-secondary-foreground hover:bg-secondary/80 rounded-full h-12 px-8 text-base cursor-pointer shadow-md hover:shadow-lg transition-all duration-300 hover:translate-y-[-2px]">Explore
                                    Surveys
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="lucide lucide-arrow-right ml-2 size-4">
                                        <path d="M5 12h14"></path>
                                        <path d="m12 5 7 7-7 7"></path>
                                    </svg>
                                </button>
                            </Link>
                            <Link :href="route('public.categories.index')">
                                <button
                                    class="inline-flex items-center justify-center gap-2 whitespace-nowrap font-medium focus:outline-none focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 border border-input shadow-sm hover:bg-accent hover:text-accent-foreground rounded-full bg-transparent h-12 px-8 text-base transition-all duration-300 hover:translate-y-[-2px]">Browse
                                    Categories</button>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Featured Categories Section -->
            <section v-if="props.featuredCategories.length > 0" class=" py-20 md:py-32 lg:py-40 bg-muted">
                <div class="container mx-auto px-4">
                    <h2 class="text-3xl font-bold text-center mb-12">Featured Categories</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <Card v-for="category in props.featuredCategories" :key="category.id"
                            class="h-full flex flex-col overflow-hidden transition-shadow duration-300 hover:shadow-lg">
                            <!-- <div v-if="category.image" class="h-40 w-full overflow-hidden">
                                <img :src="category.image" :alt="category.name" class="w-full h-full object-cover" />
                            </div>
                            <div v-else class="h-40 w-full bg-muted flex items-center justify-center">
                                <Tag class="h-12 w-12 text-muted-foreground" />
                            </div> -->
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
            <section v-if="props.recentSurveys.length > 0" class=" py-20 md:py-32 lg:py-40 bg-background">
                <div class="container mx-auto px-4">
                    <h2 class="text-3xl font-bold text-center mb-12">Recent Surveys</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <Card v-for="survey in props.recentSurveys" :key="survey.id"
                            class="h-full flex flex-col overflow-hidden transition-shadow duration-300 hover:shadow-lg">
                            <!-- <div v-if="survey.image" class="h-40 w-full overflow-hidden">
                                <img :src="survey.image" :alt="survey.title" class="w-full h-full object-cover" />
                            </div>
                            <div v-else class="h-40 w-full bg-muted flex items-center justify-center">
                                <Users class="h-12 w-12 text-muted-foreground" />
                            </div> -->
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
                                        <span>{{ survey.date_start_formatted }} - {{ survey.date_end_formatted }}</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <!-- Opcional: Mostrar número de personajes o combinaciones -->
                                        <Users class="h-4 w-4" />
                                        <span>{{ survey.character_count }} chars</span>
                                    </div>
                                </div>
                            </CardContent>
                            <CardFooter class="flex flex-col gap-2">
                                <Separator class="my-2 w-full" />
                                <div class="flex justify-between gap-2 w-full">
                                    <!-- Asegurar que los botones ocupen el ancho -->
                                    <Link :href="route('public.surveys.show', survey.id)"> <!-- O survey.id -->
                                        <Button variant="outline" class="flex-grow">View Details</Button>
                                        <!-- Botón con crecimiento -->
                                    </Link>
                                    <Link :href="route('public.surveys.vote', survey.id)"> <!-- O survey.id -->
                                        <Button class="flex-grow" :disabled="!survey.is_active">
                                            <!-- Asumiendo is_active calculado en el backend -->
                                            Participate
                                        </Button>
                                    </Link>
                                </div>
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

            <!-- How it works -->
            <section class="py-20 md:py-32 lg:py-40 bg-muted">
                <div class="container mx-auto px-4 text-center">
                    <h2 class="text-3xl font-bold mb-4">How it works</h2>
                    <p class="text-xl mb-8 max-w-2xl mx-auto">
                        Join thousands of users comparing and ranking characters across different categories.
                    </p>
                </div>
                <div class="grid md:grid-cols-3 gap-8 md:gap-12 relative">
                    <div class="relative z-10 flex flex-col items-center text-center space-y-4"
                        style="opacity: 1; transform: none;">
                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-primary to-primary/70 text-primary-foreground text-xl font-bold shadow-lg relative">
                            01<div class="absolute inset-0 rounded-full bg-primary/20 animate-ping opacity-75"
                                style="animation-duration: 3s; animation-delay: 0s;"></div>
                        </div>
                        <h3 class="text-xl font-bold">Category</h3>
                        <p class="text-muted-foreground">Choose the category you want to vote on.</p>
                    </div>
                    <div class="relative z-10 flex flex-col items-center text-center space-y-4"
                        style="opacity: 1; transform: none;">
                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-primary to-primary/70 text-primary-foreground text-xl font-bold shadow-lg relative">
                            02<div class="absolute inset-0 rounded-full bg-primary/20 animate-ping opacity-75"
                                style="animation-duration: 3s; animation-delay: 0.5s;"></div>
                        </div>
                        <h3 class="text-xl font-bold">Select Survey</h3>
                        <p class="text-muted-foreground">Choose the survey you want to vote on.</p>
                    </div>
                    <div class="relative z-10 flex flex-col items-center text-center space-y-4"
                        style="opacity: 1; transform: none;">
                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-primary to-primary/70 text-primary-foreground text-xl font-bold shadow-lg relative">
                            03<div class="absolute inset-0 rounded-full bg-primary/20 animate-ping opacity-75"
                                style="animation-duration: 3s; animation-delay: 1s;"></div>
                        </div>
                        <h3 class="text-xl font-bold">Vote for your favorite</h3>
                        <p class="text-muted-foreground">Vote for your favorite characters.</p>
                    </div>
                </div>
            </section>

            <!-- CTA Section -->
            <!-- <section class=" py-20 md:py-32 lg:py-40 bg-foreground text-white"> -->
            <section
                class="w-full py-20 md:py-32 bg-gradient-to-br from-primary to-primary/80 text-primary-foreground relative overflow-hidden isolate">
                <div
                    class="absolute inset-0 -z-10 bg-[linear-gradient(to_right,rgba(from_var(--primary-foreground)_r_g_b_/_0.075)_1px,transparent_1px),linear-gradient(to_bottom,rgba(from_var(--primary-foreground)_r_g_b_/_0.075)_1px,transparent_1px)] bg-[size:4rem_4rem]">
                </div>
                <div class="absolute -top-24 -left-24 w-64 h-64 bg-foreground/15 rounded-full blur-3xl animate-pulse">
                </div>
                <div class="absolute -bottom-24 -right-24 w-64 h-64 bg-foreground/15 rounded-full blur-3xl animate-pulse"
                    style="animation-delay: 1.5s;"></div>
                <div class="">
                    <div class="container mx-auto px-4 text-center ">
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
                                <button
                                    class="inline-flex items-center justify-center gap-2 whitespace-nowrap font-medium focus:outline-none focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 bg-secondary text-secondary-foreground hover:bg-secondary/80 rounded-full h-12 px-8 text-base cursor-pointer shadow-md hover:shadow-lg transition-all duration-300 hover:translate-y-[-2px]">Explore
                                    Surveys<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round"
                                        class="lucide lucide-arrow-right ml-2 size-4">
                                        <path d="M5 12h14"></path>
                                        <path d="m12 5 7 7-7 7"></path>
                                    </svg></button>
                            </Link>
                            <Link href="https://github.com/coordova/vs.inertia.vue">
                                <button
                                    class="inline-flex items-center justify-center gap-2 whitespace-nowrap font-medium focus:outline-none focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 border border-input shadow-sm hover:bg-accent hover:text-accent-foreground rounded-full bg-transparent h-12 px-8 text-base transition-all duration-300 hover:translate-y-[-2px]">View
                                    on GitHub</button>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </PublicAppLayout>
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