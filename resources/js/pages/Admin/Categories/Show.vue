<script setup lang="ts">
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
import { Separator } from '@/components/ui/separator';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { CategoryResource, CharacterResource } from '@/types/global'; // Asumiendo que tienes una interfaz CategoryResource o similar
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Trash } from 'lucide-vue-next'; // Iconos

interface Props {
    category: CategoryResource; // Usamos el tipo del recurso
    characters: CharacterResource[]; // Usamos el tipo del recurso
}

const props = defineProps<Props>();

// console.log(props.characters);

// --- Inicializar el composable de toast ---
// const { success: toastSuccess, error: toastError } = useToast();

const { success, error } = useToast();

// --- Manejo de eliminación ---
/* const handleDelete1 = () => {
    if (
        confirm(
            `Are you sure you want to delete the category "${props.category.name}"?`,
        )
    ) {
        router.delete(route('admin.categories.destroy', props.category.id), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                success('Category deleted successfully.');
                // Opcional: Redirigir a la lista
                router.visit(route('admin.categories.index'));
            },
            onError: () => {
                error('Failed to delete category.');
            },
        });
    }
}; */

const handleDelete = (e: Event, id: number) => {
    // alert(id);
    // deleting[id] = true;
    router.delete(route('admin.categories.destroy', id), {
        // preserveState: true,
        // preserveScroll: true,
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
            // deleting[id] = false;
        },
    });
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categories',
        href: route('admin.categories.index'),
    },
    {
        title: props.category.name, // Nombre dinámico de la categoría
        href: route('admin.categories.show', props.category.id),
    },
];
</script>

<template>

    <Head :title="`View ${props.category.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-2xl flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Category Information -->
            <div class="max-w-3xl p-4 md:p-6">
                <div class="px-4 sm:px-0">
                    <h3 class="text-base/7 font-semibold text-gray-900 dark:text-gray-100">
                        {{ props.category.name }}
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm/6 text-gray-500 dark:text-gray-400">
                        {{ props.category.description }}
                    </p>
                </div>
                <div class="mt-6 border-t border-gray-100 dark:border-white/10">
                    <dl class="divide-y divide-gray-100 dark:divide-white/10">
                        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm/6 font-medium text-gray-900 dark:text-gray-100">
                                Status
                            </dt>
                            <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400">
                                {{
                                    props.category.status === true
                                        ? 'Active'
                                        : 'Inactive'
                                }}
                            </dd>
                        </div>
                        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm/6 font-medium text-gray-900 dark:text-gray-100">
                                Featured
                            </dt>
                            <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400">
                                {{
                                    props.category.is_featured === true
                                        ? 'Active'
                                        : 'Inactive'
                                }}
                            </dd>
                        </div>
                        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm/6 font-medium text-gray-900 dark:text-gray-100">
                                Created at
                            </dt>
                            <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400">
                                {{ props.category.created_at_formatted }}
                            </dd>
                        </div>
                        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm/6 font-medium text-gray-900 dark:text-gray-100">
                                Updated at
                            </dt>
                            <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400">
                                {{ props.category.updated_at_formatted }}
                            </dd>
                        </div>
                    </dl>
                </div>
                <Separator class="my-4" />
                <div class="flex flex-col gap-4 md:flex-row md:justify-center">
                    <!--    Edit Button link -->
                    <Button asChild variant="outline">
                        <Link :href="route(
                            'admin.categories.edit',
                            props.category.id,
                        )
                            ">
                            <Pencil /> Edit
                        </Link>
                    </Button>
                    <!-- delete -->
                    <AlertDialog>
                        <AlertDialogTrigger asChild>
                            <Button as-child variant="outline" class="cursor-pointer">
                                <span>
                                    <Trash /> Delete
                                </span>
                            </Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                                <AlertDialogDescription>
                                    This action cannot be undone. This will
                                    permanently delete the category.
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel>Cancel</AlertDialogCancel>
                                <AlertDialogAction @click="
                                    (e: Event) =>
                                        handleDelete(e, props.category.id)
                                ">Confirm Delete
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                </div>
            </div>

            <!-- Characters list belongs to this category -->
            <div class="max-w-3xl space-y-4 p-4 md:p-6">
                <div class="px-4 sm:px-0">
                    <h3 class="text-base/7 font-semibold text-gray-900 dark:text-gray-100">
                        Characters
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm/6 text-gray-500 dark:text-gray-400">
                        Characters list belongs to this category
                    </p>
                </div>
                <div v-if="props.characters?.length">
                    <ul class="flex flex-wrap gap-2">
                        <li v-for="character in props.characters" :key="character.id">
                            <!-- Badge -->
                            <Badge :variant="character?.status === true
                                    ? 'default'
                                    : 'secondary'
                                ">
                                <Link :href="route(
                                    'admin.characters.show',
                                    character.id,
                                )
                                    ">
                                    {{ character.fullname }}</Link>
                            </Badge>
                        </li>
                    </ul>
                </div>
                <div v-else>
                    <p class="mt-1 max-w-2xl text-sm/6 text-gray-500 dark:text-gray-400">
                        No characters found
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Estilos específicos si es necesario */
.card-no-border {
    border: none !important;
    /* Use !important if necessary to override existing styles */
    box-shadow: none !important;
    /* If you also want to remove shadows */
}
</style>
