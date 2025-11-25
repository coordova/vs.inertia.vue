<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Toaster } from 'vue-sonner';
import { BreadcrumbItem } from '@/types'; // Asumiendo que tienes este tipo

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

defineProps<Props>();
</script>

<template>
    <div class="min-h-screen bg-background">
        <!-- Header Público -->
        <header class="sticky top-0 z-10 border-b bg-background/80 backdrop-blur-sm">
            <div class="container mx-auto flex h-16 items-center justify-between px-4">
                <!-- Logo/Nombre del Sitio -->
                <Link :href="route('home')" class="text-xl font-bold text-primary">
                VS
                </Link>

                <!-- Breadcrumbs (si se proporcionan) -->
                <nav v-if="breadcrumbs && breadcrumbs.length > 0" class="hidden md:block ml-6">
                    <!-- Ocultar en móviles -->
                    <ol class="flex items-center space-x-2 text-sm">
                        <li v-for="(crumb, index) in breadcrumbs" :key="index">
                            <Link v-if="crumb.href" :href="crumb.href"
                                class="text-muted-foreground hover:text-foreground">
                            {{ crumb.title }}
                            </Link>
                            <span v-else class="font-medium text-foreground">{{ crumb.title }}</span>
                            <span v-if="index < breadcrumbs.length - 1" class="mx-2 text-muted-foreground">/</span>
                        </li>
                    </ol>
                </nav>

                <!-- Navegación Principal -->
                <div class="flex items-center gap-4">
                    <Link :href="route('public.surveys.index')">
                    <Button variant="ghost">Surveys</Button>
                    </Link>
                    <Link :href="route('public.categories.index')">
                    <Button variant="ghost">Categories</Button>
                    </Link>
                    <Link :href="route('login')" v-if="!$page.props.auth?.user">
                    <Button variant="outline">Log in</Button>
                    </Link>
                    <Link :href="route('dashboard')" v-else>
                    <Button variant="outline">Dashboard</Button>
                    </Link>
                </div>
            </div>
        </header>

        <!-- Contenido Principal -->
        <main class="">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="border-t bg-background py-6">
            <div class="container mx-auto px-4 text-center text-sm text-muted-foreground">
                <p>&copy; {{ new Date().getFullYear() }} Facematch Ultramoderno. All rights reserved.</p>
                <div class="mt-2 flex justify-center space-x-4">
                    <Link :href="route('home')">Terms</Link>
                    <Link :href="route('home')">Privacy</Link>
                    <Link :href="route('home')">Contact</Link>
                </div>
            </div>
        </footer>

        <!-- Toaster para notificaciones -->
        <Toaster position="bottom-right" :rich-colors="true" :duration="2000" />
    </div>
</template>

<style scoped>
/* Estilos específicos del layout si es necesario */
</style>