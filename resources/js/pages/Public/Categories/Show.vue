<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { CategoryResource, CharacterResource } from '@/types/global'; // Tipos actualizados
import { Mars, Venus, VenusAndMars, NonBinary, CircleSmall, User } from 'lucide-vue-next'; // Iconos para género
import { ref, computed } from 'vue';
import PublicAppLayout from '@/layouts/PublicAppLayout.vue';

// --- Tipos ---
interface Props {
    category: CategoryResource; // Detalles de la categoría
    characters: CharacterResource[]; // Lista de personajes asociados a la categoría
    activeCharacterCount: number; // Número total de personajes activos
}

const props = defineProps<Props>();
console.log(props);
// --- Computed Properties ---
const hasManyCharacters = computed(() => props.activeCharacterCount > 20);

// --- Funciones ---
const getGenderIcon = (gender: number) => {
    switch (gender) {
        case 1: return Mars; // Male
        case 2: return Venus; // Female
        case 3: return NonBinary; // Non-binary
        default: return CircleSmall; // Other/Unknown
    }
};

// --- Breadcrumbs ---
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categories',
        href: route('public.categories.index'),
    },
    {
        title: props.category.name, // Nombre dinámico de la categoría
        href: route('public.categories.show', props.category.slug),
    },
];
</script>

<template>

    <Head :title="`Category: ${category.name}`" />

    <PublicAppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-8">
            <div class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <header class="border-b pb-4">
                    <div class="container flex h-16 items-center justify-between px-4">
                        <h1 class="text-xl font-semibold">{{ category.name }}</h1>
                        <Badge :variant="category.status ? 'default' : 'secondary'">
                            {{ category.status ? 'Active' : 'Inactive' }}
                        </Badge>
                    </div>
                </header>

                <main class="container py-8">
                    <!-- Detalles de la Categoría -->
                    <Card class="mb-8">
                        <CardHeader>
                            <CardTitle>Category Details</CardTitle>
                            <CardDescription>
                                Information about the "{{ category.name }}" category.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <h4 class="font-medium text-muted-foreground">Description</h4>
                                    <p>{{ category.description || 'No description provided.' }}</p>
                                </div>
                                <div>
                                    <h4 class="font-medium text-muted-foreground">Color</h4>
                                    <div class="flex items-center gap-2">
                                        <span :style="{ backgroundColor: category.color }"
                                            class="w-6 h-6 rounded-full inline-block border"></span>
                                        <span>{{ category.color }}</span>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="font-medium text-muted-foreground">Icon</h4>
                                    <p>{{ category.icon }}</p>
                                </div>
                                <div>
                                    <h4 class="font-medium text-muted-foreground">Created At</h4>
                                    <p>{{ category.created_at_formatted }}</p>
                                </div>
                                <div>
                                    <h4 class="font-medium text-muted-foreground">Updated At</h4>
                                    <p>{{ category.updated_at_formatted }}</p>
                                </div>
                                <div>
                                    <h4 class="font-medium text-muted-foreground">Featured</h4>
                                    <p>{{ category.is_featured ? 'Yes' : 'No' }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Lista de Personajes Asociados -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Characters in Category ({{ activeCharacterCount }})</CardTitle>
                            <CardDescription>
                                List of characters participating in this category.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <!-- Vista Bento Grid (<= 20 personajes) -->
                            <div v-if="!hasManyCharacters && props.characters.length > 0"
                                class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                                <div v-for="character in props.characters" :key="character.id"
                                    class="aspect-square overflow-hidden rounded-lg border flex flex-col items-center justify-center p-2 bg-card text-card-foreground shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                                    <div class="relative aspect-square w-full overflow-hidden rounded-full border">
                                        <img v-if="character.picture_url" :src="character.picture_url"
                                            :alt="character.fullname" class="h-full w-full object-cover" />
                                        <div v-else class="flex h-full w-full items-center justify-center bg-muted">
                                            <span class="text-muted-foreground text-xs">No image</span>
                                        </div>
                                    </div>
                                    <div class="mt-2 text-center">
                                        <p class="text-sm font-medium truncate w-full">{{ character.fullname }}</p>
                                        <p v-if="character.nickname"
                                            class="text-xs text-muted-foreground truncate w-full">{{ character.nickname
                                            }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Vista Compacta (> 20 personajes) -->
                            <div v-else-if="hasManyCharacters && props.characters.length > 0"
                                class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                                <Badge v-for="character in props.characters" :key="character.id" variant="outline"
                                    class="flex flex-col items-center p-2 cursor-pointer hover:bg-accent" asChild>
                                    <Link :href="route('public.characters.show', character.slug)" class="w-full">
                                        <div
                                            class="relative aspect-square w-10 overflow-hidden rounded-full border mx-auto">
                                            <img v-if="character.picture_url" :src="character.picture_url"
                                                :alt="character.fullname" class="h-full w-full object-cover" />
                                            <div v-else class="flex h-full w-full items-center justify-center bg-muted">
                                                <User class="h-4 w-4 text-muted-foreground" />
                                            </div>
                                        </div>
                                        <div class="mt-1">
                                            <p class="text-xs font-medium truncate">{{ character.fullname }}</p>
                                            <p v-if="character.nickname" class="text-xs text-muted-foreground truncate">
                                                {{ character.nickname }}</p>
                                        </div>
                                        <component :is="getGenderIcon(character.gender)"
                                            class="h-3 w-3 mt-1 text-muted-foreground" />
                                    </Link>
                                </Badge>
                            </div>

                            <!-- Mensaje si no hay personajes -->
                            <div v-else class="text-center py-8">
                                <p class="text-sm text-muted-foreground">No characters found in this category.</p>
                            </div>
                        </CardContent>
                        <CardFooter>
                            <Link :href="route('public.categories.index')">
                                <Button variant="outline">Back to Categories</Button>
                            </Link>
                        </CardFooter>
                    </Card>
                </main>
            </div>
        </div>
    </PublicAppLayout>
</template>

<style scoped>
/* Estilos específicos si es necesario */
</style>