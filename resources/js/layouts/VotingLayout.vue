<!-- resources/js/layouts/VotingLayout.vue -->
<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Link } from '@inertiajs/vue3';
import { Toaster } from 'vue-sonner';

// Props para personalización
const props = defineProps<{
    surveyTitle?: string;
    surveyId?: number | string;
    showProgress?: boolean;
}>();
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-background to-muted">
        <!-- Header de votación -->
        <header
            class="sticky top-0 z-10 border-b bg-background/80 backdrop-blur-sm"
        >
            <div
                class="container mx-auto flex h-16 items-center justify-between px-4"
            >
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('dashboard')"
                        class="text-xl font-bold text-primary"
                    >
                        VS
                    </Link>
                    <Separator orientation="vertical" class="h-6" />
                    <div class="flex items-center gap-2">
                        <span
                            v-if="surveyTitle"
                            class="text-sm font-medium text-muted-foreground"
                        >
                            {{ surveyTitle }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Button variant="ghost" size="sm" as-child>
                        <Link
                            :href="route('public.surveys.show', surveyId)"
                            v-if="surveyId"
                        >
                            Survey Home
                        </Link>
                    </Button>
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="route('public.surveys.index')">
                            All Surveys
                        </Link>
                    </Button>
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="route('dashboard')"> Dashboard </Link>
                    </Button>
                </div>
            </div>
        </header>

        <!-- Contenido principal -->
        <main>
            <slot />
        </main>

        <!-- Footer minimalista -->
        <footer class="mt-8 border-t bg-background/50 py-3">
            <div
                class="container px-4 text-center text-xs text-muted-foreground"
            >
                <p>Versus Voting System • Focus Mode</p>
            </div>
        </footer>

        <!-- ✅ Componente Toaster para notificaciones -->
        <!-- <Toaster position="top-center" :rich-colors="true" :duration="2000" close-button /> -->
        <Toaster position="bottom-right" :rich-colors="true" :duration="2000" />
    </div>
</template>
