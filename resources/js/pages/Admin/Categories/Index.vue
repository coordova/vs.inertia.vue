<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
// Suponiendo que los componentes shadcn estén en resources/js/Components/ui/
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog/';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import TPagination from '@/components/ui/oox/TPagination.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useToast } from '@/composables/useToast'; // Importar el composable
import { CategoriesData, CategoryResource } from '@/types/global';
import { debounce } from 'lodash';
import { Eye, Pencil, RotateCw, Search, Trash } from 'lucide-vue-next';
import { reactive, ref, watch } from 'vue'; // Para manejar estado local (ID de categoría a borrar, estado de diálogo)

// --- Tipado de datos recibidos ---
interface Category extends CategoryResource {
    id: number;
    name: string;
    description: string;
    status: boolean;
    created_at_formatted: string;
    // Añade otros campos según CategoryResource
}

// Tipo para un enlace de paginación (Laravel)
/* interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
} */

// Tipo para la información de meta de paginación (Laravel)
/* interface PaginationMeta {
    current_page: number;
    from: number;
    last_page: number;
    path: string;
    per_page: number;
    to: number;
    total: number;
} */

// Tipo para la respuesta paginada
/* interface CategoriesData {
    data: Category[]; // Importante: la clave 'data' contiene el array
    links: PaginationLink[];
    meta: PaginationMeta;
} */

// Props del componente
interface Props {
    categories: CategoriesData;
    filters?: Record<string, any>; // Filtros opcionales
}

const props = defineProps<Props>();

// --- Manejo de mensajes flash ---
// Acceder a props.flash de forma segura sin tipar usePage explícitamente
// const page = usePage();
// const flashSuccess = page.props.flash?.success;

/*-------------- Watch --------------*/
const search = ref(props.filters?.search);
const page = ref(props.filters?.page);
const perPage = ref(props.filters?.per_page);

watch(
    search,
    debounce(function (value: string) {
        // console.log(value);
        router.get(
            route('admin.categories.index'),
            { search: value, /* page: 1,  */ per_page: perPage.value },
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

/*-------------- Sonner --------------*/
// --- Inicializar el composable de toast ---
// const { success: toastSuccess, error: toastError } = useToast();

// const { props } = usePage()
const { success, error } = useToast();

// if (props.flash?.success) {
//     success(props.flash.success);
// } else if (props.flash?.error) {
//     error(props.flash.error);
// }
/*-------------- /Sonner -------------*/

const deleting = reactive<Record<number, boolean>>({});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categories',
        href: route('admin.categories.index'),
    },
];

/**
 * Función para confirmar y ejecutar la eliminación
 * @param e Evento del click
 * @param id ID de la categoría a eliminar
 */
const handleDelete = (e: Event, id: number) => {
    deleting[id] = true;
    router.delete(route('admin.categories.destroy', id), {
        data: {
            page: page.value, // se envia a admin.categories.destroy el valor de la pagina para que se agregue a la url (complementado con ->withQueryString() del controlador)
            search: search.value, // se envia a admin.categories.destroy el valor de la busqueda para que se agregue a la url (complementado con ->withQueryString() del controlador)
            per_page: perPage.value, // se envia a admin.categories.destroy el valor de la paginacion para que se agregue a la url (complementado con ->withQueryString() del controlador)
        },
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            // Mensaje de éxito
            success('Category deleted successfully');
            // router.reload();
        },
        onError: () => {
            error('Failed to delete category');
        },
        onFinish: () => {
            // Siempre se ejecuta, útil para limpiar estados
            // router.reload();
            deleting[id] = false;
        },
    });
};

/**
 * Navegar a una página específica
 * @param page número de la página
 */
function goToPage(page: number) {
    router.get(
        route('admin.categories.index'),
        {
            page, // se envia a admin.categories.index el valor de la pagina para que se agregue a la url (complementado con ->withQueryString() del controlador)
            search: search.value, // se envia a admin.categories.index el valor de la busqueda para que se agregue a la url (complementado con ->withQueryString() del controlador)
            per_page: perPage.value, // se envia a admin.categories.index el valor de la paginacion para que se agregue a la url (complementado con ->withQueryString() del controlador)
        },
        { preserveState: true, preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Categories</h1>
                <span class="text-sm text-gray-500"> </span>

                <div class="flex items-center gap-4">
                    <!-- Reload -->
                    <Button
                        type="button"
                        variant="outline"
                        @click="router.visit(route('admin.categories.index'))"
                    >
                        <RotateCw />
                    </Button>
                    <!-- Per page -->
                    <div class="flex items-center justify-end">
                        <Select
                            v-model="perPage"
                            @update:modelValue="goToPage(1)"
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select a page size" />
                            </SelectTrigger>
                            <SelectContent>
                                <!-- valor por defecto -->
                                <SelectItem
                                    value="10"
                                    :selected="perPage === '10'"
                                    >10</SelectItem
                                >
                                <SelectItem
                                    value="25"
                                    :selected="perPage === '25'"
                                    >25</SelectItem
                                >
                                <SelectItem
                                    value="50"
                                    :selected="perPage === '50'"
                                    >50</SelectItem
                                >
                                <SelectItem
                                    value="100"
                                    :selected="perPage === '100'"
                                    >100</SelectItem
                                >
                            </SelectContent>
                        </Select>
                    </div>
                    <!-- Search -->
                    <div class="relative w-full max-w-sm items-center">
                        <Input
                            v-model="search"
                            id="search"
                            type="text"
                            placeholder="Search..."
                            class="pl-10"
                        />
                        <span
                            class="absolute inset-y-0 start-0 flex items-center justify-center px-2"
                        >
                            <Search class="size-6 text-muted-foreground" />
                        </span>
                    </div>
                    <!-- Create Category -->
                    <Button asChild>
                        <Link :href="route('admin.categories.create')"
                            >Create Category</Link
                        >
                    </Button>
                </div>
            </div>
            <Table>
                <TableCaption class="text-right"
                    >Showing {{ props.categories?.meta.from }} to
                    {{ props.categories?.meta.to }} of
                    {{ props.categories?.meta.total }} categories</TableCaption
                >
                <TableHeader>
                    <TableRow>
                        <TableHead> Name</TableHead>
                        <TableHead>Description</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Created At</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="props.categories?.data.length === 0">
                        <TableCell colspan="5" class="h-24 text-center"
                            >No categories found.
                        </TableCell>
                    </TableRow>
                    <TableRow
                        v-else
                        v-for="category in props.categories?.data"
                        :key="category.id"
                        :class="{
                            'pointer-events-none text-gray-500 opacity-50':
                                deleting[category.id],
                        }"
                    >
                        <TableCell class="font-medium">
                            {{ category.name }}</TableCell
                        >
                        <TableCell>{{ category.description }}</TableCell>
                        <TableCell>
                            <Badge
                                :variant="
                                    category.status === true
                                        ? 'default'
                                        : 'secondary'
                                "
                                >{{
                                    category.status === true
                                        ? 'Active'
                                        : 'Inactive'
                                }}
                            </Badge>
                        </TableCell>
                        <TableCell>{{
                            category.created_at_formatted
                        }}</TableCell>
                        <TableCell class="flex items-center justify-end gap-2">
                            <!-- acciones -->
                            <Button asChild variant="outline">
                                <Link
                                    :href="
                                        route(
                                            'admin.categories.show',
                                            category.id,
                                        )
                                    "
                                >
                                    <Eye />
                                </Link>
                            </Button>
                            <Button asChild variant="outline">
                                <Link
                                    :href="
                                        route(
                                            'admin.categories.edit',
                                            category.id,
                                        )
                                    "
                                >
                                    <Pencil />
                                </Link>
                            </Button>
                            <!-- delete -->
                            <AlertDialog>
                                <AlertDialogTrigger asChild>
                                    <Button
                                        as-child
                                        variant="outline"
                                        size="sm"
                                    >
                                        <span>
                                            <Trash />
                                        </span>
                                    </Button>
                                </AlertDialogTrigger>
                                <AlertDialogContent>
                                    <AlertDialogHeader>
                                        <AlertDialogTitle
                                            >Are you sure?</AlertDialogTitle
                                        >
                                        <AlertDialogDescription>
                                            This action cannot be undone. This
                                            will permanently delete the
                                            category.
                                        </AlertDialogDescription>
                                    </AlertDialogHeader>
                                    <AlertDialogFooter>
                                        <AlertDialogCancel
                                            >Cancel</AlertDialogCancel
                                        >
                                        <AlertDialogAction
                                            @click="
                                                (e: Event) =>
                                                    handleDelete(e, category.id)
                                            "
                                            >Confirm Delete
                                        </AlertDialogAction>
                                    </AlertDialogFooter>
                                </AlertDialogContent>
                            </AlertDialog>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            <!-- Pagination -->
            <TPagination
                :current-page="props.categories.meta.current_page"
                :total-items="props.categories.meta.total"
                :items-per-page="props.categories.meta.per_page"
                @page-change="goToPage"
            />
        </div>
    </AppLayout>
</template>

<style scoped>
/* Estilos específicos para este componente si es necesario */
</style>
