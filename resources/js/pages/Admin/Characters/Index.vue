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
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
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
import { CharactersData } from '@/types/global'; // Tipos actualizados
import { debounce } from 'lodash';
import { Eye, Pencil, RotateCw, Search, Trash } from 'lucide-vue-next';
import { reactive, ref, watch } from 'vue';

// Props del componente
interface Props {
    characters: CharactersData;
    filters?: Record<string, any>; // Filtros opcionales
}

const props = defineProps<Props>();

// --- Manejo de mensajes flash ---
// const page = usePage();
// const flashSuccess = page.props.flash?.success;

/*-------------- Watch --------------*/
const search = ref(props.filters?.search);
const page = ref(props.filters?.page);
const perPage = ref(props.filters?.per_page ? props.filters?.per_page : '15');

watch(
    search,
    debounce(function (value: string) {
        router.get(
            route('admin.characters.index'),
            { search: value, per_page: perPage.value },
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
const { success, error } = useToast();

/*-------------- /Sonner -------------*/
const deleting = reactive<Record<number, boolean>>({});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Characters',
        href: route('admin.characters.index'),
    },
];

/**
 * Función para confirmar y ejecutar la eliminación
 * @param e Evento del click
 * @param id ID del personaje a eliminar
 */
const handleDelete = (e: Event, id: number) => {
    deleting[id] = true;
    router.delete(route('admin.characters.destroy', id), {
        data: {
            page: page.value,
            search: search.value,
            per_page: perPage.value,
        },
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            success('Character deleted successfully');
        },
        onError: () => {
            error('Failed to delete character');
        },
        onFinish: () => {
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
        route('admin.characters.index'),
        {
            page,
            search: search.value,
            per_page: perPage.value,
        },
        { preserveState: true, preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Characters" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Characters</h1>
                <span class="text-sm text-gray-500"> </span>

                <div class="flex items-center gap-4">
                    <!-- Reload -->
                    <Button
                        type="button"
                        variant="outline"
                        @click="router.visit(route('admin.characters.index'))"
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
                    <!-- Create Character -->
                    <Button asChild>
                        <Link :href="route('admin.characters.create')"
                            >Create Character</Link
                        >
                    </Button>
                </div>
            </div>
            <Table>
                <TableCaption class="text-right">
                    Showing {{ props.characters?.meta.from }} to
                    {{ props.characters?.meta.to }} of
                    {{ props.characters?.meta.total }} characters
                </TableCaption>
                <TableHeader>
                    <TableRow>
                        <TableHead>Picture</TableHead>
                        <TableHead>Full Name</TableHead>
                        <TableHead>Nickname</TableHead>
                        <!-- <TableHead>Occupation</TableHead> -->
                        <TableHead>Status</TableHead>
                        <TableHead>Created At</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="props.characters?.data.length === 0">
                        <TableCell colspan="6" class="h-24 text-center"
                            >No characters found.</TableCell
                        >
                    </TableRow>
                    <TableRow
                        v-else
                        v-for="character in props.characters?.data"
                        :key="character.id"
                        :class="{
                            'pointer-events-none text-gray-500 opacity-50':
                                deleting[character.id],
                        }"
                    >
                        <TableCell>
                            <Avatar>
                                <AvatarImage :src="character.thumbnail_url" />
                                <AvatarFallback>
                                    {{
                                        character.fullname
                                            .charAt(0)
                                            .toUpperCase()
                                    }}
                                </AvatarFallback>
                            </Avatar>
                        </TableCell>
                        <TableCell class="font-medium">{{
                            character.fullname
                        }}</TableCell>
                        <TableCell>{{ character.nickname || '-' }}</TableCell>
                        <!-- Mostrar '-' si nickname es null -->
                        <!-- <TableCell>{{ character.occupation || '-' }}</TableCell> -->
                        <TableCell>
                            <Badge
                                :variant="
                                    character.status ? 'default' : 'secondary'
                                "
                            >
                                {{ character.status ? 'Active' : 'Inactive' }}
                            </Badge>
                        </TableCell>
                        <!-- <TableCell>{{
                            character.created_at
                                ? new Date(
                                      character.created_at,
                                  ).toLocaleDateString()
                                : '-'
                        }}</TableCell> -->
                        <TableCell>{{
                            character.created_at_formatted
                        }}</TableCell>
                        <TableCell class="flex items-center justify-end gap-2">
                            <!-- acciones -->
                            <Button asChild variant="outline">
                                <Link
                                    :href="
                                        route(
                                            'admin.characters.show',
                                            character.id,
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
                                            'admin.characters.edit',
                                            character.id,
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
                                        <span><Trash /></span>
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
                                            character.
                                        </AlertDialogDescription>
                                    </AlertDialogHeader>
                                    <AlertDialogFooter>
                                        <AlertDialogCancel
                                            >Cancel</AlertDialogCancel
                                        >
                                        <AlertDialogAction
                                            @click="
                                                (e: Event) =>
                                                    handleDelete(
                                                        e,
                                                        character.id,
                                                    )
                                            "
                                        >
                                            Confirm Delete
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
                :current-page="props.characters.meta.current_page"
                :total-items="props.characters.meta.total"
                :items-per-page="props.characters.meta.per_page"
                @page-change="goToPage"
            />
        </div>
    </AppLayout>
</template>

<style scoped>
/* Estilos específicos para este componente si es necesario */
</style>
