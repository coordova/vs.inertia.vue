<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import TPagination from '@/components/oox/TPagination.vue'; // Asumiendo que existe
import { type BreadcrumbItem } from '@/types';
import { CategoriesData } from '@/types/global'; // Tipos actualizados
import { Search, Tag, RotateCw } from 'lucide-vue-next'; // Iconos
import { debounce } from 'lodash';
import { ref, watch } from 'vue';
import PublicAppLayout from '@/layouts/PublicAppLayout.vue';

// --- Tipos ---
interface Props {
    categories: CategoriesData; // Colección paginada de categorías con conteo
    filters?: Record<string, any>; // Filtros aplicados (search, per_page, page)
}
const props = defineProps<Props>();

console.log(props.categories);

/*-------------- Watch --------------*/
const search = ref(props.filters?.search);
const page = ref(props.filters?.page);
const perPage = ref(props.filters?.per_page || '15');
// const categoryId = ref(props.filters?.category_id); // Opcional: Filtro por categoría

watch(
    search,
    debounce(function (value: string) {
        router.get(
            route('public.surveys.index'),
            {
                search: value,
                /* category_id: categoryId.value, */ per_page: perPage.value,
            },
            { preserveState: true, replace: true },
        );
    }, 300),
);

// watch para actualizar la variable page
watch(
    () => props.filters?.page,
    (value) => {
        page.value = value;
    },
);

// watch para actualizar la variable per_page
watch(
    () => props.filters?.per_page,
    (value) => {
        perPage.value = value;
    },
);

// --- Computed Properties ---
// const filteredCategories = computed(() => { ... }); // Si se filtra en el frontend

// --- Watchers ---
const debouncedSearch = debounce((value: string) => {
    // Usar Inertia para mantener la paginación y filtros
    router.get(
        route('public.categories.index'),
        { search: value, per_page: props.filters?.per_page },
        { preserveState: true, replace: true }
    );
}, 300);

watch(search, (newValue) => {
    debouncedSearch(newValue);
});

// --- Breadcrumbs ---
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categories',
        href: route('public.categories.index'),
    },
];

/**
 * Navegar a una página específica
 * @param page número de la página
 */
function goToPage(page: number) {
    router.get(
        route('public.categories.index'),
        {
            page,
            search: search.value,
            // category_id: categoryId.value, // Si se implementa filtro por categoría
            per_page: perPage.value,
        },
        { preserveState: true, preserveScroll: true },
    );
}
</script>

<template>

    <Head title="Categories" />

    <PublicAppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-8">
            <div class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <header class="border-b pb-4">
                    <div class="container flex h-16 items-center justify-between px-4">
                        <h1 class="text-xl font-semibold">Categories</h1>
                        <span class="text-sm text-gray-500">Browse all available categories</span>

                        <div class="flex items-center gap-4">
                            <!-- Reload -->
                            <Button type="button" variant="outline"
                                @click="router.visit(route('public.categories.index'))">
                                <RotateCw />
                            </Button>
                            <!-- Per page -->
                            <div class="flex items-center justify-end">
                                <Select v-model="perPage" @update:modelValue="goToPage(1)">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select a page size" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="15" :selected="perPage === '15'">15</SelectItem>
                                        <SelectItem value="25" :selected="perPage === '25'">25</SelectItem>
                                        <SelectItem value="50" :selected="perPage === '50'">50</SelectItem>
                                        <SelectItem value="100" :selected="perPage === '100'">100</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <!-- Search -->
                            <div class="relative w-full max-w-sm items-center">
                                <Input v-model="search" id="search" type="text" placeholder="Search..." class="pl-10" />
                                <span class="absolute inset-y-0 start-0 flex items-center justify-center px-2">
                                    <Search class="size-6 text-muted-foreground" />
                                </span>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="container py-8">
                    <!-- Lista de Categorías -->
                    <div v-if="props.categories.data.length > 0"
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <Card v-for="category in props.categories.data" :key="category.id"
                            class="h-full flex flex-col overflow-hidden transition-shadow duration-300 hover:shadow-lg">
                            <div v-if="category.image_url" class="h-40 w-full overflow-hidden">
                                <img :src="category.image_url" :alt="category.name"
                                    class="w-full h-full object-cover" />
                            </div>
                            <div v-else class="h-40 w-full bg-muted flex items-center justify-center">
                                <Tag class="h-12 w-12 text-muted-foreground" />
                            </div>
                            <CardHeader>
                                <CardTitle class="text-xl">{{ category.name }}</CardTitle>
                                <CardDescription class="line-clamp-2">{{ category.description }}</CardDescription>
                            </CardHeader>
                            <CardContent class="flex-grow">
                                <!-- Opcional: Mostrar color o icono -->
                                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                    <span :style="{ backgroundColor: category.color }"
                                        class="w-3 h-3 rounded-full inline-block"></span>
                                    <span>{{ category.icon }}</span>
                                </div>
                            </CardContent>
                            <CardFooter class="flex justify-between">
                                <div class="text-sm text-muted-foreground">
                                    {{ category.character_count }} characters
                                </div>
                                <div class="text-sm text-muted-foreground">
                                    {{ category.survey_count }} surveys
                                </div>
                                <Button asChild>
                                    <Link :href="route('public.categories.show', category.id)">
                                        View Category
                                    </Link>
                                </Button>
                            </CardFooter>
                        </Card>
                    </div>

                    <!-- Mensaje si no hay categorías -->
                    <div v-else class="text-center py-12">
                        <h3 class="text-lg font-medium">No categories found</h3>
                        <p class="text-sm text-muted-foreground mt-1">
                            There are no categories matching your search criteria.
                        </p>
                    </div>

                    <!-- Pagination -->
                    <TPagination v-if="props.categories.meta.total > props.categories.meta.per_page"
                        :current-page="props.categories.meta.current_page" :total-items="props.categories.meta.total"
                        :items-per-page="props.categories.meta.per_page" @page-change="goToPage" />

                    <!-- Paginación -->
                    <!-- <div v-if="props.categories.meta.links.length > 2" class="mt-8">
                        <TPagination :current-page="props.categories.meta.current_page"
                            :total-items="props.categories.meta.total" :items-per-page="props.categories.meta.per_page"
                            @page-change="(page) => router.get(route('public.categories.index', { page, search: search, per_page: props.filters?.per_page }), {}, { preserveState: true, preserveScroll: true })" />
                    </div> -->
                </main>
            </div>
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