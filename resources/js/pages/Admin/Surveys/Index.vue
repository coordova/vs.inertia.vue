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
import { SurveysData } from '@/types/global'; // Tipos actualizados
import { debounce } from 'lodash';
import { Eye, Pencil, RotateCw, Search, Trash } from 'lucide-vue-next';
import { reactive, ref, watch } from 'vue';

// Props del componente
interface Props {
    surveys: SurveysData;
    filters?: Record<string, any>; // Filtros opcionales (search, category_id, per_page, page)
}

const props = defineProps<Props>();

// --- Manejo de mensajes flash ---
// const page = usePage();
// const flashSuccess = page.props.flash?.success;

/*-------------- Watch --------------*/
const search = ref(props.filters?.search);
const page = ref(props.filters?.page);
const perPage = ref(props.filters?.per_page || '15');
// const categoryId = ref(props.filters?.category_id); // Opcional: Filtro por categoría

watch(
    search,
    debounce(function (value: string) {
        router.get(
            route('admin.surveys.index'),
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

/*-------------- Sonner --------------*/
const { success, error } = useToast();

/*-------------- /Sonner -------------*/
const deleting = reactive<Record<number, boolean>>({});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Surveys',
        href: route('admin.surveys.index'),
    },
];

/**
 * Función para confirmar y ejecutar la eliminación
 * @param e Evento del click
 * @param id ID de la encuesta a eliminar
 */
const handleDelete = (e: Event, id: number) => {
    deleting[id] = true;
    router.delete(route('admin.surveys.destroy', id), {
        data: {
            page: page.value,
            search: search.value,
            // category_id: categoryId.value, // Si se implementa filtro por categoría
            per_page: perPage.value,
        },
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            success('Survey deleted successfully');
        },
        onError: () => {
            error('Failed to delete survey');
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
        route('admin.surveys.index'),
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
    <Head title="Surveys" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Surveys</h1>
                <span class="text-sm text-gray-500"> </span>

                <div class="flex items-center gap-4">
                    <!-- Reload -->
                    <Button
                        type="button"
                        variant="outline"
                        @click="router.visit(route('admin.surveys.index'))"
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
                    <!-- Create Survey -->
                    <Button asChild>
                        <Link :href="route('admin.surveys.create')"
                            >Create Survey</Link
                        >
                    </Button>
                </div>
            </div>
            <Table>
                <TableCaption
                    v-if="props.surveys?.meta.total > 0"
                    class="text-right"
                >
                    Showing {{ props.surveys?.meta.from }} to
                    {{ props.surveys?.meta.to }} of
                    {{ props.surveys?.meta.total }} surveys
                </TableCaption>
                <TableHeader>
                    <TableRow>
                        <TableHead>Title</TableHead>
                        <TableHead>Category</TableHead>
                        <TableHead>Start Date</TableHead>
                        <TableHead>End Date</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="props.surveys?.data.length === 0">
                        <TableCell colspan="6" class="h-24 text-center"
                            >No surveys found.</TableCell
                        >
                    </TableRow>
                    <TableRow
                        v-else
                        v-for="survey in props.surveys?.data"
                        :key="survey.id"
                        :class="{
                            'pointer-events-none text-gray-500 opacity-50':
                                deleting[survey.id],
                        }"
                    >
                        <TableCell class="font-medium">{{
                            survey.title
                        }}</TableCell>
                        <TableCell>{{ survey.category?.name }}</TableCell
                        ><!-- Acceder al nombre de la categoría -->
                        <TableCell>{{
                            new Date(survey.date_start).toLocaleDateString()
                        }}</TableCell>
                        <TableCell>{{
                            new Date(survey.date_end).toLocaleDateString()
                        }}</TableCell>
                        <TableCell>
                            <Badge
                                :variant="
                                    survey.status ? 'default' : 'secondary'
                                "
                            >
                                {{ survey.status ? 'Active' : 'Inactive' }}
                            </Badge>
                        </TableCell>
                        <TableCell class="flex items-center justify-end gap-2">
                            <!-- acciones -->
                            <Button asChild variant="outline">
                                <Link
                                    :href="
                                        route('admin.surveys.show', survey.id)
                                    "
                                >
                                    <Eye />
                                </Link>
                            </Button>
                            <Button asChild variant="outline">
                                <Link
                                    :href="
                                        route('admin.surveys.edit', survey.id)
                                    "
                                >
                                    <Pencil />
                                </Link>
                            </Button>
                            <!-- delete -->
                            <AlertDialog>
                                <AlertDialogTrigger asChild>
                                    <Button asChild variant="outline" size="sm">
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
                                            will permanently delete the survey.
                                        </AlertDialogDescription>
                                    </AlertDialogHeader>
                                    <AlertDialogFooter>
                                        <AlertDialogCancel
                                            >Cancel</AlertDialogCancel
                                        >
                                        <AlertDialogAction
                                            @click="
                                                (e: Event) =>
                                                    handleDelete(e, survey.id)
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
            <!-- <TPagination
                :current-page="props.surveys.meta.current_page"
                :total-items="props.surveys.meta.total"
                :items-per-page="props.surveys.meta.per_page"
                @page-change="goToPage"
            /> -->
        </div>
    </AppLayout>
</template>

<style scoped>
/* Estilos específicos para este componente si es necesario */
</style>
