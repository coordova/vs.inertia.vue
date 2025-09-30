<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
// Suponiendo que los componentes shadcn estén en resources/js/Components/ui/
import { Button } from '@/components/ui/button';
import {
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    PaginationContent,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import {
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useToast } from '@/composables/useToast';
import { ref } from 'vue'; // Para manejar estado local (ID de categoría a borrar, estado de diálogo)

// import { TableRoot, TableHeader, TableBody, TableRow, TableHead, TableCell, TableCaption } from '@/Components/ui/table/Table.vue';
// import { PaginationRoot, PaginationContent, PaginationItem, PaginationLink, PaginationPrevious, PaginationNext } from '@/Components/ui/pagination/Pagination.vue';
// import { DialogRoot, DialogTrigger, DialogPortal, DialogOverlay, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter, DialogClose } from '@/Components/ui/dialog/Dialog.vue';
// import { Button } from '@/Components/ui/button/Button.vue';
// import { useToast } from '@/Composables/useToast'; // Importar el composable

// --- Tipado de datos recibidos ---
interface Category {
    id: number;
    name: string;
    slug: string;
    status: boolean;
    // Añade otros campos según CategoryResource
}

// Tipo para un enlace de paginación (Laravel)
interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

// Tipo para la información de meta de paginación (Laravel)
interface PaginationMeta {
    current_page: number;
    from: number;
    last_page: number;
    path: string;
    per_page: number;
    to: number;
    total: number;
}

// Tipo para la respuesta paginada
interface CategoriesData {
    data: Category[]; // Importante: la clave 'data' contiene el array
    links: PaginationLink[];
    meta: PaginationMeta;
}

// Props del componente
interface Props {
    categories: CategoriesData;
    filters?: Record<string, any>; // Filtros opcionales
}

const props = defineProps<Props>();

// --- Estado local para manejar la confirmación de borrado ---
const categoryToDelete = ref<number | null>(null);
const isDeleteDialogOpen = ref(false);

// --- Manejo de mensajes flash ---
// Acceder a props.flash de forma segura sin tipar usePage explícitamente
const page = usePage();
const flashSuccess = page.props.flash?.success;

// --- Inicializar el composable de toast ---
const { success: toastSuccess, error: toastError } = useToast();

// Función para abrir el diálogo de confirmación
const openDeleteDialog = (id: number) => {
    categoryToDelete.value = id;
    isDeleteDialogOpen.value = true;
};

// Función para cerrar el diálogo
const closeDeleteDialog = () => {
    isDeleteDialogOpen.value = false;
    categoryToDelete.value = null; // Limpiar ID al cerrar
};

// Función para confirmar y ejecutar la eliminación
const confirmDelete = () => {
    if (categoryToDelete.value !== null) {
        router.delete(
            route('admin.categories.destroy', categoryToDelete.value),
            {
                onSuccess: () => {
                    // Usar el composable para mostrar notificación
                    toastSuccess('Category deleted successfully');
                    console.log('Category deleted successfully');
                    // El mensaje flash también se mostrará en la página actualizada si se configura el middleware correctamente
                },
                onError: (errors) => {
                    console.error('Errors deleting category:', errors);
                    // Usar el composable para mostrar notificación de error
                    // Podrías querer mostrar un mensaje genérico o uno específico de los errores devueltos
                    toastError(
                        'Failed to delete category. Please check the logs.',
                    );
                },
                onFinish: () => {
                    closeDeleteDialog(); // Cerrar diálogo siempre al finalizar
                },
            },
        );
    }
};
</script>

<template>
    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="mb-6 text-2xl font-semibold">Categories</h1>

                    <!-- Mensaje de éxito (ejemplo) - Puedes mantenerlo o usar solo toast -->
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
                            as="button"
                            class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out hover:bg-gray-700 focus:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none active:bg-gray-900"
                        >
                            Create Category
                        </Link>
                    </div>

                    <!-- Tabla de Categorías -->
                    <div class="overflow-x-auto">
                        <TableRoot>
                            <TableCaption>A list of categories.</TableCaption>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-[100px]">ID</TableHead>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Slug</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="category in props.categories.data"
                                    :key="category.id"
                                >
                                    <TableCell class="font-medium">{{
                                        category.id
                                    }}</TableCell>
                                    <TableCell>{{ category.name }}</TableCell>
                                    <TableCell>{{ category.slug }}</TableCell>
                                    <TableCell>
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
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex space-x-2">
                                            <Link
                                                :href="
                                                    route(
                                                        'admin.categories.edit',
                                                        category.id,
                                                    )
                                                "
                                                class="text-indigo-600 hover:text-indigo-900"
                                            >
                                                Edit
                                            </Link>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="
                                                    openDeleteDialog(
                                                        category.id,
                                                    )
                                                "
                                            >
                                                Delete
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </TableRoot>
                    </div>

                    <!-- Paginación -->
                    <div
                        v-if="props.categories.meta.last_page > 1"
                        class="mt-4"
                    >
                        <PaginationRoot>
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious
                                        v-if="
                                            props.categories.meta.current_page >
                                            1
                                        "
                                        :href="
                                            props.categories.links.find(
                                                (l) => l.label === 'Previous',
                                            )?.url || undefined
                                        "
                                        :aria-disabled="
                                            !props.categories.links.find(
                                                (l) => l.label === 'Previous',
                                            )?.url
                                        "
                                        :class="
                                            !props.categories.links.find(
                                                (l) => l.label === 'Previous',
                                            )?.url
                                                ? 'pointer-events-none opacity-50'
                                                : ''
                                        "
                                    />
                                </PaginationItem>

                                <!-- Iterar sobre los enlaces de paginación -->
                                <PaginationItem
                                    v-for="link in props.categories.links"
                                    :key="link.label"
                                >
                                    <!-- Excluir prev/next de la iteración principal -->
                                    <PaginationLink
                                        v-if="
                                            link.label !== 'Previous' &&
                                            link.label !== 'Next'
                                        "
                                        :href="link.url || undefined"
                                        :aria-current="
                                            link.active ? 'page' : undefined
                                        "
                                        class="link.active ? 'bg-indigo-600 text-white' : ''"
                                        :aria-disabled="!link.url"
                                        :class="
                                            !link.url
                                                ? 'cursor-not-allowed opacity-50'
                                                : ''
                                        "
                                        v-html="link.label"
                                    />
                                </PaginationItem>

                                <PaginationItem>
                                    <PaginationNext
                                        v-if="
                                            props.categories.meta.current_page <
                                            props.categories.meta.last_page
                                        "
                                        :href="
                                            props.categories.links.find(
                                                (l) => l.label === 'Next',
                                            )?.url || undefined
                                        "
                                        :aria-disabled="
                                            !props.categories.links.find(
                                                (l) => l.label === 'Next',
                                            )?.url
                                        "
                                        :class="
                                            !props.categories.links.find(
                                                (l) => l.label === 'Next',
                                            )?.url
                                                ? 'pointer-events-none opacity-50'
                                                : ''
                                        "
                                    />
                                </PaginationItem>
                            </PaginationContent>
                        </PaginationRoot>
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
                </div>
            </div>
        </div>
    </div>

    <!-- Diálogo de Confirmación de Eliminación -->
    <DialogRoot v-model:open="isDeleteDialogOpen">
        <DialogPortal>
            <DialogOverlay />
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Confirm Delete</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete the category with ID
                        {{ categoryToDelete }}? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button" variant="outline">Cancel</Button>
                    </DialogClose>
                    <Button
                        type="button"
                        variant="destructive"
                        @click="confirmDelete"
                        >Delete</Button
                    >
                </DialogFooter>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>

<style scoped>
/* Estilos específicos para este componente si es necesario */
</style>
