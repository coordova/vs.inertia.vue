<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
// import { Button } from '@/components/ui/button';
import { Toaster } from 'vue-sonner';
import { BreadcrumbItem } from '@/types'; // Asumiendo que tienes este tipo
import Breadcrumbs from '@/components/Breadcrumbs.vue';

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <div class="min-h-screen bg-background">
        <!-- Header Público -->
        <header class="sticky top-0 z-100 border-b bg-background/80 backdrop-blur-sm">
            <div class="container mx-auto flex h-16 items-center justify-between px-4">
                <!-- Logo/Nombre del Sitio -->
                <Link :href="route('public.home')" class="text-xl font-bold text-primary">
                    VS
                </Link>

                <!-- Breadcrumbs (si se proporcionan) Ocultar en móviles -->
                <!-- <nav v-if="breadcrumbs && breadcrumbs.length > 0" class="hidden md:block ml-6">
                    
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
                </nav> -->

                <!-- Navegación Principal -->
                <div class="hidden items-center gap-4 md:flex lg:gap-8">
                    <Link :href="route('public.surveys.index')"
                        class="text-muted-foreground hover:text-foreground group relative text-xs font-medium transition-colors lg:text-sm">
                        <span>Surveys</span>
                        <span
                            class="bg-primary absolute -bottom-1 left-0 h-0.5 w-0 transition-all duration-300 group-hover:w-full"></span>
                    </Link>
                    <Link :href="route('public.categories.index')"
                        class="text-muted-foreground hover:text-foreground group relative text-xs font-medium transition-colors lg:text-sm">
                        <span>Categories</span>
                        <span
                            class="bg-primary absolute -bottom-1 left-0 h-0.5 w-0 transition-all duration-300 group-hover:w-full"></span>
                    </Link>
                    <Link :href="route('login')" v-if="!$page.props.auth?.user"
                        class="text-muted-foreground hover:text-foreground group relative text-xs font-medium transition-colors lg:text-sm">
                        <span>Log in</span>
                        <span
                            class="bg-primary absolute -bottom-1 left-0 h-0.5 w-0 transition-all duration-300 group-hover:w-full"></span>
                    </Link>
                    <Link :href="route('dashboard')" v-else
                        class="text-muted-foreground hover:text-foreground group relative text-xs font-medium transition-colors lg:text-sm">
                        <span>Dashboard</span>
                        <span
                            class="bg-primary absolute -bottom-1 left-0 h-0.5 w-0 transition-all duration-300 group-hover:w-full"></span>
                    </Link>
                </div>
            </div>
        </header>

        <!-- Contenido Principal -->
        <main class="">
            <div v-if="breadcrumbs && breadcrumbs.length > 0" class="container mx-auto pt-4 pl-4">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
            <slot />
        </main>

        <!-- Footer -->
        <footer class="border-t bg-background py-6">
            <div class="container mx-auto px-4 text-center text-sm text-muted-foreground">
                <p>&copy; {{ new Date().getFullYear() }} Facematch Ultramoderno. All rights reserved.</p>
                <div class="mt-2 flex justify-center space-x-4">
                    <Link :href="route('public.home')">Terms</Link>
                    <Link :href="route('public.home')">Privacy</Link>
                    <Link :href="route('public.home')">Contact</Link>
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