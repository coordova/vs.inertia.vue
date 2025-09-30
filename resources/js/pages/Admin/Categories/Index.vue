<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';

// Definimos las props que recibe el componente
interface Category {
    id: number;
    name: string;
    slug: string;
    status: boolean;
    // Añade otros campos según CategoryResource
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface CategoriesData {
    data: Category[]; // Asegúrate de que 'data' esté aquí
    links: PaginationLink[]; // Tipo más preciso para links de paginación
    meta: {
        current_page: number;
        from: number;
        last_page: number;
        path: string;
        per_page: number;
        to: number;
        total: number;
    }; // Estructura de meta de Laravel Paginator
    // Añade otros campos de paginación según Laravel
}

interface Props {
    categories: CategoriesData;
    filters?: Record<string, any>; // Filtros opcionales
}

const props = defineProps<Props>();

// Acceder a props.flash de forma segura sin tipar usePage explícitamente
const page = usePage();
const flashSuccess = page.props.flash?.success; // Encadenamiento opcional maneja la ausencia de 'flash' o 'success'

// Función para eliminar una categoría
const deleteCategory = (id: number) => {
    if (confirm('Are you sure you want to delete this category?')) {
        // Inertia.router.delete maneja la solicitud DELETE y la redirección
        router.delete(route('admin.categories.destroy', id), {
            // Opcional: Manejar errores o éxitos específicos aquí
            onSuccess: () => {
                // Mensaje de éxito ya se maneja en el template con flash
                console.log('Category deleted successfully');
            },
            onError: (errors) => {
                console.error('Errors deleting category:', errors);
                // Manejar errores de validación si es necesario
            },
        });
    }
};
</script>

<template>
    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="mb-6 text-2xl font-semibold">Categories</h1>

                    <!-- Mensaje de éxito (ejemplo) -->
                    <div
                        v-if="flashSuccess"
                        class="mb-4 rounded bg-green-100 p-4 text-green-700"
                    >
                        {{ flashSuccess }}
                    </div>

                    <!-- Botón para crear nueva categoría -->
                    <div class="mb-4">
                        <Link
                            :href="route('admin.categories.create')"
                            class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out hover:bg-gray-700 focus:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none active:bg-gray-900"
                        >
                            Create Category
                        </Link>
                    </div>

                    <!-- Tabla de Categorías -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                    >
                                        ID
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                    >
                                        Name
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                    >
                                        Slug
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                    >
                                        Status
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr
                                    v-for="category in props.categories.data"
                                    :key="category.id"
                                >
                                    <td
                                        class="px-6 py-4 text-sm whitespace-nowrap text-gray-500"
                                    >
                                        {{ category.id }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900"
                                    >
                                        {{ category.name }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm whitespace-nowrap text-gray-500"
                                    >
                                        {{ category.slug }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm whitespace-nowrap text-gray-500"
                                    >
                                        <span
                                            :class="{
                                                'text-green-600':
                                                    category.status,
                                                'text-red-600':
                                                    !category.status,
                                            }"
                                        >
                                            {{
                                                category.status
                                                    ? 'Active'
                                                    : 'Inactive'
                                            }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm font-medium whitespace-nowrap"
                                    >
                                        <Link
                                            :href="
                                                route(
                                                    'admin.categories.edit',
                                                    category.id,
                                                )
                                            "
                                            class="mr-2 text-indigo-600 hover:text-indigo-900"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            @click="deleteCategory(category.id)"
                                            type="button"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div
                        v-if="props.categories.meta.last_page > 1"
                        class="mt-4 flex items-center justify-between"
                    >
                        <!-- Enlace Anterior -->
                        <div>
                            <Link
                                v-if="props.categories.meta.current_page > 1"
                                :href="
                                    props.categories.links.find(
                                        (l) => l.label === 'Previous',
                                    )?.url
                                "
                                class="rounded bg-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-300"
                            >
                                Previous
                            </Link>
                        </div>

                        <!-- Números de Página -->
                        <div class="flex space-x-1">
                            <Link
                                v-for="link in props.categories.links"
                                :key="link.label"
                                :href="link.url"
                                :class="
                                    link.active
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                                "
                                v-html="link.label"
                            />
                        </div>

                        <!-- Enlace Siguiente -->
                        <div>
                            <Link
                                v-if="
                                    props.categories.meta.current_page <
                                    props.categories.meta.last_page
                                "
                                :href="
                                    props.categories.links.find(
                                        (l) => l.label === 'Next',
                                    )?.url
                                "
                                class="rounded bg-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-300"
                            >
                                Next
                            </Link>
                        </div>
                    </div>

                    <!-- Mostrar info de paginación -->
                    <div
                        v-if="props.categories.meta.total > 0"
                        class="mt-2 text-sm text-gray-600"
                    >
                        Showing {{ props.categories.meta.from }} to
                        {{ props.categories.meta.to }} of
                        {{ props.categories.meta.total }} results
                    </div>

                    <!-- Paginación (ejemplo simple, se puede mejorar) -->
                    <div
                        v-if="props.categories.links.length > 2"
                        class="mt-4 flex items-center justify-between"
                    >
                        <div>
                            <Link
                                v-if="props.categories.prev_page_url"
                                :href="props.categories.prev_page_url"
                                class="rounded bg-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-300"
                            >
                                Previous
                            </Link>
                        </div>
                        <div class="text-sm text-gray-600">
                            Page {{ props.categories.current_page }} of
                            {{ props.categories.last_page }}
                        </div>
                        <div>
                            <Link
                                v-if="props.categories.next_page_url"
                                :href="props.categories.next_page_url"
                                class="rounded bg-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-300"
                            >
                                Next
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Estilos específicos para este componente si es necesario */
</style>
