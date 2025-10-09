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
import { UserResource, UsersData } from '@/types/global';
import { debounce } from 'lodash';
import { Eye, Pencil, Power, RotateCw, Search, Trash } from 'lucide-vue-next';
import { reactive, ref, watch } from 'vue'; // Para manejar estado local (ID de categoría a borrar, estado de diálogo)

// --- Tipado de datos recibidos ---
interface User extends UserResource {
    id: number;
    type: string;
    name: string;
    email: string;
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
    users: UsersData;
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
const perPage = ref(props.filters?.per_page ?? '15');

watch(
    search,
    debounce(function (value: string) {
        // console.log(value);
        router.get(
            route('admin.users.index'),
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
        title: 'Users',
        href: route('admin.users.index'),
    },
];

/**
 * Función para confirmar y ejecutar la eliminación
 * @param e Evento del click
 * @param id ID de la categoría a eliminar
 */
const handleDelete = (e: Event, id: number) => {
    deleting[id] = true;
    router.delete(route('admin.users.destroy', id), {
        data: {
            page: page.value, // se envia a admin.users.destroy el valor de la pagina para que se agregue a la url (complementado con ->withQueryString() del controlador)
            search: search.value, // se envia a admin.users.destroy el valor de la busqueda para que se agregue a la url (complementado con ->withQueryString() del controlador)
            per_page: perPage.value, // se envia a admin.users.destroy el valor de la paginacion para que se agregue a la url (complementado con ->withQueryString() del controlador)
        },
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            // Mensaje de éxito
            success('User deleted successfully');
            // router.reload();
        },
        onError: () => {
            error('Failed to delete user');
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
        route('admin.users.index'),
        {
            page, // se envia a admin.users.index el valor de la pagina para que se agregue a la url (complementado con ->withQueryString() del controlador)
            search: search.value, // se envia a admin.users.index el valor de la busqueda para que se agregue a la url (complementado con ->withQueryString() del controlador)
            per_page: perPage.value, // se envia a admin.users.index el valor de la paginacion para que se agregue a la url (complementado con ->withQueryString() del controlador)
        },
        { preserveState: true, preserveScroll: true },
    );
}

/**
 * Función para cambiar el estado del usuario
 * @param e Evento del click
 * @param id ID del usuario a cambiar
 */
const handleStatus = (e: Event, id: number) => {
    e.preventDefault();
    router.put(
        route('users.change-status', id),
        {},
        {
            preserveState: true,
            preserveScroll: true,

            // mensaje de éxito
            onSuccess: () => {
                success('User status changed successfully');
            },
            onError: () => {
                error('Failed to change user status');
            },
            onFinish: () => {
                // Siempre se ejecuta, útil para limpiar estados
                // router.reload();
            },
        },
    );
};
</script>

<template>
    <Head title="Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Users</h1>
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
                                    value="15"
                                    :selected="perPage === '15'"
                                    >15</SelectItem
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
                    <!-- <Button asChild>
                        <Link :href="route('admin.users.create')"
                            >Create User</Link
                        >
                    </Button> -->
                </div>
            </div>

            <Table>
                <TableCaption class="text-right"
                    >Showing {{ props.users?.meta.from }} to
                    {{ props.users?.meta.to }} of
                    {{ props.users?.meta.total }} users</TableCaption
                >
                <TableHeader>
                    <TableRow>
                        <TableHead>Type</TableHead>
                        <TableHead> Name</TableHead>
                        <TableHead>Email</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Created At</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="props.users?.data.length === 0">
                        <TableCell colspan="5" class="h-24 text-center"
                            >No users found.
                        </TableCell>
                    </TableRow>
                    <TableRow
                        v-else
                        v-for="user in props.users?.data"
                        :key="user.id"
                        :class="{
                            'pointer-events-none text-gray-500 opacity-50':
                                deleting[user.id],
                        }"
                    >
                        <TableCell class="font-medium">
                            {{ user.type }}</TableCell
                        >
                        <TableCell class="font-medium">
                            {{ user.name }}</TableCell
                        >
                        <TableCell>{{ user.email }}</TableCell>
                        <TableCell>
                            <Badge
                                :variant="
                                    user.status === true
                                        ? 'default'
                                        : 'secondary'
                                "
                                >{{
                                    user.status === true ? 'Active' : 'Inactive'
                                }}
                            </Badge>
                        </TableCell>
                        <TableCell>{{ user.created_at_formatted }}</TableCell>
                        <TableCell class="flex items-center justify-end gap-2">
                            <!-- acciones -->
                            <Button asChild variant="outline">
                                <Link
                                    :href="route('admin.users.show', user.id)"
                                >
                                    <Eye />
                                </Link>
                            </Button>
                            <Button asChild variant="outline">
                                <Link
                                    :href="route('admin.users.edit', user.id)"
                                >
                                    <Pencil />
                                </Link>
                            </Button>
                            <!-- change status -->
                            <Button
                                asChild
                                variant="outline"
                                size="sm"
                                @click="(e: Event) => handleStatus(e, user.id)"
                                class="cursor-pointer"
                            >
                                <span>
                                    <Power />
                                </span>
                            </Button>
                            <!-- delete -->
                            <AlertDialog>
                                <AlertDialogTrigger asChild>
                                    <Button
                                        as-child
                                        variant="outline"
                                        size="sm"
                                        class="cursor-pointer"
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
                                            will permanently delete the user.
                                        </AlertDialogDescription>
                                    </AlertDialogHeader>
                                    <AlertDialogFooter>
                                        <AlertDialogCancel
                                            >Cancel</AlertDialogCancel
                                        >
                                        <AlertDialogAction
                                            @click="
                                                (e: Event) =>
                                                    handleDelete(e, user.id)
                                            "
                                            >Confirm Delete
                                        </AlertDialogAction>
                                    </AlertDialogFooter>
                                </AlertDialogContent>
                            </AlertDialog>
                            <!--<Button asChild variant="outline" class="text-red-500" @click="(e) => handleDelete__(e, category.id)">
                                <span>
                                    <Trash />
                                </span>
                            </Button>-->
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            <!-- Pagination -->
            <TPagination
                :current-page="props.users.meta.current_page"
                :total-items="props.users.meta.total"
                :items-per-page="props.users.meta.per_page"
                @page-change="goToPage"
            />
        </div>
    </AppLayout>
</template>
