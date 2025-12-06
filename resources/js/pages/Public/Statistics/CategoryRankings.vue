<script setup lang="ts">
import { Head, /* Link, */ router } from '@inertiajs/vue3';
import { /* onMounted, */ ref, watch } from 'vue';
// import { useToast } from '@/composables/useToast';
import PublicAppLayout from '@/layouts/PublicAppLayout.vue';
import { type BreadcrumbItem } from '@/types';
// Importar recursos de shadcn-vue
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
// import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
// import { Badge } from '@/components/ui/badge';
// import { Separator } from '@/components/ui/separator';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'; // <-- Añadir Select
import { debounce } from 'lodash';
import { Search } from 'lucide-vue-next'; // <-- Añadir Search icon
import TCharacterDialogAjax from '@/components/oox/TCharacterDialogAjax.vue';

// Importar tipos
import { CategoryResource, CategoryRankingData } from '@/types/global'; // <-- Importar las interfaces correctas

// --- Props ---
interface Props {
    category: CategoryResource; // Datos de la categoría
    ranking: CategoryRankingData; // Ranking paginado (con data, meta, links)
    filters?: Record<string, any>; // Filtros aplicados (search, sort, per_page, page)
}

const props = defineProps<Props>();

console.log(props);

// --- Composables ---
// const { success, error } = useToast();

// --- Estados reactivos ---
const search = ref(props.filters?.search || '');
const page = ref(props.filters?.page || 1);
const perPage = ref(parseInt(props.filters?.per_page || '50'));
// const sort = ref(props.filters?.sort || 'elo_rating'); // Opcional: manejar sort localmente si se desea
// const direction = ref(props.filters?.direction || 'desc'); // Opcional: manejar direction localmente

// --- Computed Properties ---
// Calcula la posición inicial de los resultados mostrados en la página actual
// (Ya no es necesario si el servicio lo calcula y lo incluye en el objeto CategoryCharacter)
// const startingPosition = computed(() => {
//     return (props.ranking.current_page - 1) * props.ranking.per_page + 1;
// });

// --- Funciones ---

/**
 * Navegar a una página específica del ranking.
 * @param pageNum Número de la página.
 */
const goToPage = (pageNum: number) => {
    router.get(
        route('public.statistics.category.rankings', props.category.id),
        {
            page: pageNum,
            search: search.value, // Mantener búsqueda
            sort: props.filters?.sort, // Mantener ordenamiento
            direction: props.filters?.direction, // Mantener dirección
            per_page: perPage.value, // Mantener paginación
        },
        { preserveState: true, preserveScroll: true }
    );
};

/**
 * Actualizar el tamaño de la página y volver a la primera página.
 */
const updatePerPage = () => {
    // Volver a la primera página al cambiar el tamaño de página
    goToPage(1);
};

// --- Watchers ---
// Aplicar búsqueda con debounce
const debouncedSearch = debounce(() => {
    // Volver a la primera página al buscar
    goToPage(1);
}, 300);

watch(search, debouncedSearch);

// Actualizar 'page' y 'perPage' si cambian en los filtros recibidos (por ejemplo, al navegar)
watch(() => props.filters?.page, (newPage) => {
    if (newPage && newPage !== page.value) {
        page.value = newPage;
    }
});

watch(() => props.filters?.per_page, (newPerPage) => {
    if (newPerPage && parseInt(newPerPage) !== perPage.value) {
        perPage.value = parseInt(newPerPage);
    }
});

// --- Breadcrumbs ---
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categories',
        href: route('public.categories.index'),
    },
    {
        title: props.category.name,
        href: route('public.categories.show', props.category.id),
    },
    {
        title: 'Rankings',
        href: route('public.statistics.category.rankings', props.category.id),
    },
];
</script>

<template>

    <Head :title="`${category.name} Rankings`" />

    <PublicAppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-background">
            <div class="container mx-auto py-8 px-4">
                <div class="flex flex-col gap-4 overflow-x-auto rounded-xl p-4">
                    <!-- Header -->
                    <header class="border-b pb-4">
                        <div class="container flex h-16 items-center justify-between px-4">
                            <h1 class="text-xl font-semibold">{{ category.name }} Rankings</h1>
                            <span class="text-sm text-gray-500"> </span>

                            <div class="flex items-center gap-4">
                                <!-- Search -->
                                <div class="relative w-full max-w-sm items-center">
                                    <Input v-model="search" id="search" type="text" placeholder="Search characters..."
                                        class="pl-10" />
                                    <span class="absolute inset-y-0 start-0 flex items-center justify-center px-2">
                                        <Search class="size-6 text-muted-foreground" />
                                    </span>
                                </div>

                                <!-- Per Page Selector -->
                                <div class="flex items-center justify-end">
                                    <Select v-model="perPage" @update:modelValue="updatePerPage">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Items per page" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="10">10</SelectItem>
                                            <SelectItem value="25">25</SelectItem>
                                            <SelectItem value="50">50</SelectItem>
                                            <SelectItem value="100">100</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </div>
                    </header>

                    <!-- Main Content -->
                    <main class="container py-8">
                        <!-- Tabla de Rankings -->
                        <div class="overflow-x-auto rounded-lg border">
                            <Table>
                                <TableCaption>
                                    Showing {{ props.ranking.from }} to {{ props.ranking.to }} of {{
                                        props.ranking.total }} characters.
                                </TableCaption>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-[100px]">Position</TableHead>
                                        <TableHead>Character</TableHead>
                                        <TableHead>ELO Rating</TableHead>
                                        <TableHead>Matches</TableHead>
                                        <TableHead>Wins</TableHead>
                                        <TableHead>Losses</TableHead>
                                        <TableHead>Ties</TableHead> <!-- Nueva columna -->
                                        <TableHead>Win Rate</TableHead>
                                        <TableHead>Last Match</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-if="props.ranking.data.length === 0">
                                        <TableCell :colSpan="9" class="h-24 text-center">
                                            No characters found.
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-else v-for="(characterRating) in props.ranking.data"
                                        :key="characterRating.character_id">
                                        <!-- Acceder a la posición calculada por el servicio -->
                                        <TableCell class="font-medium">{{ characterRating.position }}</TableCell>

                                        <TableCell>
                                            <TCharacterDialogAjax :character-id="characterRating.character.id">
                                                <template #trigger>
                                                    <div class="flex items-center gap-3">
                                                        <img v-if="characterRating.character.picture"
                                                            :src="'/storage/' + characterRating.character.picture"
                                                            :alt="characterRating.character.fullname"
                                                            class="h-10 w-10 rounded-full object-cover" />
                                                        <div v-else
                                                            class="bg-muted h-10 w-10 rounded-full flex items-center justify-center">
                                                            <span class="text-muted-foreground text-xs">N/A</span>
                                                        </div>
                                                        <div>
                                                            <div class="font-medium">{{
                                                                characterRating.character.fullname }}
                                                            </div>
                                                            <div v-if="characterRating.character.nickname"
                                                                class="text-sm text-muted-foreground">{{
                                                                    characterRating.character.nickname }}</div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </TCharacterDialogAjax>
                                        </TableCell>

                                        <TableCell>{{ characterRating.elo_rating }}</TableCell>
                                        <TableCell>{{ characterRating.matches_played }}</TableCell>
                                        <TableCell>{{ characterRating.wins }}</TableCell>
                                        <TableCell>{{ characterRating.losses }}</TableCell>
                                        <TableCell>{{ characterRating.ties }}</TableCell> <!-- Mostrar ties -->
                                        <TableCell>{{ characterRating.win_rate ?
                                            parseFloat(characterRating.win_rate).toFixed(2) +
                                            '%' : '0.00%' }}</TableCell>
                                        <TableCell>{{ characterRating.last_match_at ? new
                                            Date(characterRating.last_match_at).toLocaleDateString() : 'Never' }}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <!-- Paginación -->
                        <div v-if="props.ranking.links.length > 2" class="mt-6 flex items-center justify-between">
                            <Button variant="outline" :disabled="props.ranking.current_page <= 1"
                                @click="goToPage(props.ranking.current_page - 1)">
                                Previous
                            </Button>
                            <div class="text-sm text-muted-foreground">
                                Page {{ props.ranking.current_page }} of {{ props.ranking.last_page }}
                            </div>
                            <Button variant="outline" :disabled="props.ranking.current_page >= props.ranking.last_page"
                                @click="goToPage(props.ranking.current_page + 1)">
                                Next
                            </Button>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </PublicAppLayout>
</template>

<style scoped>
/* Estilos específicos si es necesario */
</style>