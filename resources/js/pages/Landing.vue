<script setup lang="ts">
import { Badge } from '@/components/ui/badge'; // Componente de shadcn
import { Button } from '@/components/ui/button'; // Componente de shadcn
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'; // Componente de shadcn
import { CategoryResource, SurveyResource } from '@/types/global'; // Tipos
import { Head, Link } from '@inertiajs/vue3';
import { motion } from 'framer-motion'; // Importar motion de framer-motion

interface Props {
    featuredCategories: CategoryResource[];
    activeSurveys: SurveyResource[];
}

const props = defineProps<Props>();

// Variantes de animación para Framer Motion
const containerVariants = {
    hidden: { opacity: 0 },
    visible: {
        opacity: 1,
        transition: {
            staggerChildren: 0.1, // Retraso entre hijos
        },
    },
};

const itemVariants = {
    hidden: { y: 20, opacity: 0 },
    visible: {
        y: 0,
        opacity: 1,
        transition: {
            duration: 0.5, // Duración de la animación
            ease: 'easeOut', // Tipo de transición
        },
    },
};

// Variantes para la sección hero (más dramática)
const heroVariants = {
    hidden: { scale: 0.8, opacity: 0 },
    visible: {
        scale: 1,
        opacity: 1,
        transition: {
            duration: 0.8,
            ease: 'easeOut',
        },
    },
};
</script>

<template>
    <Head title="Facematch Ultramoderno" />

    <!-- Sección Hero -->
    <motion.section
        :initial="heroVariants.hidden"
        :animate="heroVariants.visible"
        class="relative bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 py-20 text-white"
    >
        <div class="absolute inset-0 bg-black opacity-20"></div>
        <!-- Overlay oscuro opcional -->
        <div class="relative z-10 container mx-auto px-4">
            <div class="mx-auto max-w-3xl text-center">
                <motion.h1
                    class="mb-4 text-4xl font-bold md:text-6xl"
                    :initial="{ y: -20, opacity: 0 }"
                    :animate="{ y: 0, opacity: 1 }"
                    :transition="{ duration: 0.7, ease: 'easeOut' }"
                >
                    Descubre quién es más...
                </motion.h1>
                <motion.p
                    class="mb-8 text-xl md:text-2xl"
                    :initial="{ y: -20, opacity: 0 }"
                    :animate="{ y: 0, opacity: 1 }"
                    :transition="{ duration: 0.7, delay: 0.2, ease: 'easeOut' }"
                >
                    Compara y vota entre los personajes más interesantes en
                    diversas categorías.
                </motion.p>
                <motion.div
                    :initial="{ y: -20, opacity: 0 }"
                    :animate="{ y: 0, opacity: 1 }"
                    :transition="{ duration: 0.7, delay: 0.4, ease: 'easeOut' }"
                >
                    <Link :href="route('categories.public.index')">
                        <Button size="lg" class="mr-4"
                            >Explorar Categorías</Button
                        >
                    </Link>
                    <Link :href="route('surveys.public.index')">
                        <Button
                            variant="outline"
                            size="lg"
                            class="border-white text-white hover:bg-white hover:text-indigo-600"
                            >Ver Encuestas</Button
                        >
                    </Link>
                </motion.div>
            </div>
        </div>
    </motion.section>

    <!-- Sección Categorías Destacadas -->
    <section class="bg-gray-50 py-16">
        <div class="container mx-auto px-4">
            <motion.h2
                class="mb-12 text-center text-3xl font-bold"
                :initial="itemVariants.hidden"
                :animate="itemVariants.visible"
                :transition="{ duration: 0.5, ease: 'easeOut' }"
            >
                Categorías Destacadas
            </motion.h2>
            <motion.div
                :variants="containerVariants"
                :initial="containerVariants.hidden"
                :animate="containerVariants.visible"
                class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3"
            >
                <motion.div
                    v-for="category in props.featuredCategories"
                    :key="category.id"
                    :variants="itemVariants"
                >
                    <Card class="flex h-full flex-col">
                        <CardHeader>
                            <CardTitle>{{ category.name }}</CardTitle>
                            <CardDescription>{{
                                category.description
                            }}</CardDescription>
                        </CardHeader>
                        <CardContent class="flex-grow">
                            <!-- Imagen opcional de la categoría -->
                            <img
                                v-if="category.image"
                                :src="category.image"
                                :alt="category.name"
                                class="h-32 w-full rounded-t-md object-cover"
                            />
                        </CardContent>
                        <CardFooter>
                            <Link
                                :href="
                                    route(
                                        'categories.public.show',
                                        category.slug,
                                    )
                                "
                            >
                                <!-- Asumiendo slug o id en la ruta -->
                                <Button variant="outline" class="w-full"
                                    >Ver Encuestas</Button
                                >
                            </Link>
                        </CardFooter>
                    </Card>
                </motion.div>
            </motion.div>
            <div class="mt-8 text-center">
                <Link :href="route('categories.public.index')">
                    <Button variant="link">Ver todas las categorías</Button>
                </Link>
            </div>
        </div>
    </section>

    <!-- Sección Encuestas Activas -->
    <section class="bg-white py-16">
        <div class="container mx-auto px-4">
            <motion.h2
                class="mb-12 text-center text-3xl font-bold"
                :initial="itemVariants.hidden"
                :animate="itemVariants.visible"
                :transition="{ duration: 0.5, ease: 'easeOut' }"
            >
                Encuestas Activas
            </motion.h2>
            <motion.div
                :variants="containerVariants"
                :initial="containerVariants.hidden"
                :animate="containerVariants.visible"
                class="space-y-4"
            >
                <motion.div
                    v-for="survey in props.activeSurveys"
                    :key="survey.id"
                    :variants="itemVariants"
                >
                    <Card>
                        <CardHeader>
                            <div class="flex items-start justify-between">
                                <div>
                                    <CardTitle>{{ survey.title }}</CardTitle>
                                    <CardDescription>{{
                                        survey.description
                                    }}</CardDescription>
                                </div>
                                <Badge
                                    :variant="
                                        survey.status ? 'default' : 'secondary'
                                    "
                                >
                                    {{ survey.status ? 'Activa' : 'Inactiva' }}
                                </Badge>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">
                                Categoría: {{ survey.category.name }}
                            </p>
                        </CardHeader>
                        <CardFooter>
                            <Link
                                :href="
                                    route('surveys.public.show', survey.slug)
                                "
                            >
                                <!-- Asumiendo slug o id en la ruta -->
                                <Button class="w-full">Participar</Button>
                            </Link>
                        </CardFooter>
                    </Card>
                </motion.div>
            </motion.div>
            <div class="mt-8 text-center">
                <Link :href="route('surveys.public.index')">
                    <Button variant="link">Ver todas las encuestas</Button>
                </Link>
            </div>
        </div>
    </section>
</template>

<style scoped>
/* Estilos específicos si es necesario */
</style>
