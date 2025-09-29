<template>
    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="mb-6 text-2xl font-semibold">Categories</h1>

                    <!-- Mensaje de éxito (ejemplo) -->
                    <!--  <div
                        v-if="$page.props.flash.success"
                        class="mb-4 rounded bg-green-100 p-4 text-green-700"
                    >
                        {{ $page.props.flash.success }}
                    </div> -->

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
                                    v-for="category in categories.data"
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

                    <!-- Paginación (ejemplo simple, se puede mejorar) -->
                    <div
                        v-if="categories.links.length > 2"
                        class="mt-4 flex items-center justify-between"
                    >
                        <div>
                            <Link
                                v-if="categories.prev_page_url"
                                :href="categories.prev_page_url"
                                class="rounded bg-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-300"
                            >
                                Previous
                            </Link>
                        </div>
                        <div class="text-sm text-gray-600">
                            Page {{ categories.current_page }} of
                            {{ categories.last_page }}
                        </div>
                        <div>
                            <Link
                                v-if="categories.next_page_url"
                                :href="categories.next_page_url"
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

<script setup>
import { Link } from '@inertiajs/vue3';

// Definir las props que recibe el componente
const props = defineProps({
    categories: {
        type: Object, // La colección paginada desde el backend
        required: true,
    },
    filters: {
        type: Object, // Filtros aplicados (opcional)
        default: () => ({}),
    },
});

// Función para eliminar una categoría
const deleteCategory = (id) => {
    if (confirm('Are you sure you want to delete this category?')) {
        // Inertia.delete maneja la solicitud DELETE
        // Usamos el helper `route` para generar la URL correcta
        // y pasamos `_method: 'delete'` si es necesario (aunque Inertia lo maneja internamente para DELETE)
        axios
            .delete(route('admin.categories.destroy', id))
            .then(() => {
                // Inertia maneja la redirección automática después del delete
                // El mensaje de éxito 'Category deleted successfully.' se mostrará en la siguiente carga de la página
            })
            .catch((error) => {
                console.error('Error deleting category:', error);
                // Manejar error si es necesario
            });
    }
};
</script>

<style scoped>
/* Estilos específicos para este componente si es necesario */
</style>
