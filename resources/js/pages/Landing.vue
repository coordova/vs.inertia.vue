<script setup lang="ts">
import VotingLayout from '@/layouts/VotingLayout.vue';
import { Head } from '@inertiajs/vue3';
// Importamos componentes de shadcn/vue
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
import { CategoryResource, SurveyResource } from '@/types/global'; // Tipos
import { Link } from '@inertiajs/vue3';

interface Props {
    featuredCategories: CategoryResource[];
    activeSurveys: SurveyResource[];
}

const props = defineProps<Props>();
</script>

<template>
    <VotingLayout>
        <Head title="Facematch Ultramoderno" />

        <div class="min-h-screen bg-background text-foreground">
            <!-- Sección Hero -->
            <section class="py-20">
                <div class="container mx-auto px-4">
                    <div class="mx-auto max-w-3xl text-center">
                        <h1 class="mb-6 text-4xl font-bold md:text-5xl">
                            Descubre quién es más...
                        </h1>
                        <p
                            class="mb-8 text-lg text-muted-foreground md:text-xl"
                        >
                            Compara y vota entre los personajes más interesantes
                            en diversas categorías.
                        </p>
                        <div
                            class="flex flex-col justify-center gap-4 sm:flex-row"
                        >
                            <Link :href="route('public.categories.index')">
                                <Button size="lg">Explorar Categorías</Button>
                            </Link>
                            <Link :href="route('public.surveys.index')">
                                <Button variant="outline" size="lg"
                                    >Ver Encuestas</Button
                                >
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Separador -->
            <Separator class="my-16" />

            <!-- Sección Categorías Destacadas -->
            <section class="py-12">
                <div class="container mx-auto px-4">
                    <h2 class="mb-12 text-center text-3xl font-bold">
                        Categorías Destacadas
                    </h2>
                    <div
                        class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <Card
                            v-for="category in props.featuredCategories"
                            :key="category.id"
                            class="flex h-full flex-col"
                        >
                            <CardHeader>
                                <CardTitle>{{ category.name }}</CardTitle>
                                <CardDescription>{{
                                    category.description
                                }}</CardDescription>
                            </CardHeader>
                            <CardContent class="flex-grow">
                                <!-- Imagen opcional de la categoría -->
                                <!-- <img
                                v-if="category.image"
                                :src="category.image"
                                :alt="category.name"
                                class="h-40 w-full rounded-md object-cover"
                            /> -->
                            </CardContent>
                            <CardFooter>
                                <Link
                                    :href="
                                        route(
                                            'public.categories.show',
                                            category.id,
                                        )
                                    "
                                    class="w-full"
                                >
                                    <Button variant="outline" class="w-full"
                                        >Ver Encuestas</Button
                                    >
                                </Link>
                            </CardFooter>
                        </Card>
                    </div>
                    <div class="mt-10 text-center">
                        <Link :href="route('public.categories.index')">
                            <Button variant="link"
                                >Ver todas las categorías</Button
                            >
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Separador -->
            <Separator class="my-16" />

            <!-- Sección Encuestas Activas -->
            <section class="py-12">
                <div class="container mx-auto px-4">
                    <h2 class="mb-12 text-center text-3xl font-bold">
                        Encuestas Activas
                    </h2>
                    <div class="space-y-6">
                        <Card
                            v-for="survey in props.activeSurveys"
                            :key="survey.id"
                        >
                            <CardHeader>
                                <div
                                    class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"
                                >
                                    <div>
                                        <CardTitle>{{
                                            survey.title
                                        }}</CardTitle>
                                        <CardDescription class="mt-1">{{
                                            survey.description
                                        }}</CardDescription>
                                        <p
                                            class="mt-2 text-sm text-muted-foreground"
                                        >
                                            Categoría:
                                            {{ survey.category.name }}
                                        </p>
                                    </div>
                                    <Badge
                                        :variant="
                                            survey.status
                                                ? 'default'
                                                : 'secondary'
                                        "
                                    >
                                        {{
                                            survey.status
                                                ? 'Activa'
                                                : 'Inactiva'
                                        }}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardFooter>
                                <Link
                                    :href="
                                        route('public.surveys.show', survey.id)
                                    "
                                    class="w-full"
                                >
                                    <Button class="w-full">Participar</Button>
                                </Link>
                            </CardFooter>
                        </Card>
                    </div>
                    <div class="mt-10 text-center">
                        <Link :href="route('public.surveys.index')">
                            <Button variant="link"
                                >Ver todas las encuestas</Button
                            >
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Separador Final -->
            <Separator class="my-16" />
        </div>
    </VotingLayout>
</template>

<style scoped>
/* Estilos específicos si es necesario */
/* Asegurar que el fondo del body use la variable CSS correspondiente */
/* Esto podría estar en un archivo global como app.css o en el layout */
/* body { */
/*   @apply bg-background; */
/* } */
</style>
