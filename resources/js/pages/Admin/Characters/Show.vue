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
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { CharacterResource } from '@/types/global'; // Asumiendo que tienes una interfaz CharacterResource o similar
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Trash } from 'lucide-vue-next'; // Iconos

interface Props {
    character: CharacterResource; // Usamos el tipo del recurso resuelto
}

const props = defineProps<Props>();

// --- Inicializar el composable de toast ---
const { success, error } = useToast();

// --- Manejo de eliminación ---
const handleDelete = (e: Event, id: number) => {
    router.delete(route('admin.characters.destroy', id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            success('Character deleted successfully.');
            router.visit(route('admin.characters.index'));
        },
        onError: () => {
            error('Failed to delete character.');
        },
    });
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Characters',
        href: route('admin.characters.index'),
    },
    {
        title: props.character.fullname, // Nombre dinámico del personaje
        href: route('admin.characters.show', props.character.id),
    },
];
</script>

<template>
    <Head :title="`View ${props.character.fullname}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-2xl flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Character Information -->
            <div class="max-w-3xl p-4 md:p-6">
                <div class="px-4 sm:px-0">
                    <h3
                        class="text-base/7 font-semibold text-gray-900 dark:text-gray-100"
                    >
                        {{ props.character.fullname }}
                    </h3>
                    <p
                        class="mt-1 max-w-2xl text-sm/6 text-gray-500 dark:text-gray-400"
                    >
                        {{ props.character.nickname || 'No nickname' }}
                    </p>
                </div>
                <div class="mt-6 border-t border-gray-100 dark:border-white/10">
                    <dl class="divide-y divide-gray-100 dark:divide-white/10">
                        <div
                            class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0"
                        >
                            <dt
                                class="text-sm/6 font-medium text-gray-900 dark:text-gray-100"
                            >
                                Status
                            </dt>
                            <dd
                                class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400"
                            >
                                {{
                                    props.character.status
                                        ? 'Active'
                                        : 'Inactive'
                                }}
                            </dd>
                        </div>
                        <div
                            class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0"
                        >
                            <dt
                                class="text-sm/6 font-medium text-gray-900 dark:text-gray-100"
                            >
                                Occupation
                            </dt>
                            <dd
                                class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400"
                            >
                                {{ props.character.occupation || 'N/A' }}
                            </dd>
                        </div>
                        <div
                            class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0"
                        >
                            <dt
                                class="text-sm/6 font-medium text-gray-900 dark:text-gray-100"
                            >
                                Nationality
                            </dt>
                            <dd
                                class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400"
                            >
                                {{ props.character.nationality || 'N/A' }}
                            </dd>
                        </div>
                        <div
                            class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0"
                        >
                            <dt
                                class="text-sm/6 font-medium text-gray-900 dark:text-gray-100"
                            >
                                Date of Birth
                            </dt>
                            <dd
                                class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400"
                            >
                                {{
                                    props.character.dob
                                        ? new Date(
                                              props.character.dob,
                                          ).toLocaleDateString()
                                        : 'N/A'
                                }}
                            </dd>
                        </div>
                        <div
                            class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0"
                        >
                            <dt
                                class="text-sm/6 font-medium text-gray-900 dark:text-gray-100"
                            >
                                Gender
                            </dt>
                            <dd
                                class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400"
                            >
                                <!-- Mapear el número a texto -->
                                {{
                                    props.character.gender === 0
                                        ? 'Other'
                                        : props.character.gender === 1
                                          ? 'Male'
                                          : props.character.gender === 2
                                            ? 'Female'
                                            : props.character.gender === 3
                                              ? 'Non-binary'
                                              : 'Unknown'
                                }}
                            </dd>
                        </div>
                        <div
                            class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0"
                        >
                            <dt
                                class="text-sm/6 font-medium text-gray-900 dark:text-gray-100"
                            >
                                Created at
                            </dt>
                            <dd
                                class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400"
                            >
                                {{
                                    new Date(
                                        props.character.created_at,
                                    ).toLocaleString()
                                }}
                            </dd>
                        </div>
                        <div
                            class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0"
                        >
                            <dt
                                class="text-sm/6 font-medium text-gray-900 dark:text-gray-100"
                            >
                                Updated at
                            </dt>
                            <dd
                                class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400"
                            >
                                {{
                                    new Date(
                                        props.character.updated_at,
                                    ).toLocaleString()
                                }}
                            </dd>
                        </div>
                        <div
                            class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0"
                        >
                            <dt
                                class="text-sm/6 font-medium text-gray-900 dark:text-gray-100"
                            >
                                Biography
                            </dt>
                            <dd
                                class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400"
                            >
                                {{
                                    props.character.bio ||
                                    'No biography provided.'
                                }}
                            </dd>
                        </div>
                        <div
                            class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0"
                        >
                            <dt
                                class="text-sm/6 font-medium text-gray-900 dark:text-gray-100"
                            >
                                Picture
                            </dt>
                            <dd
                                class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400"
                            >
                                <img
                                    :src="props.character.picture"
                                    :alt="props.character.fullname"
                                    class="h-32 w-32 rounded object-cover"
                                />
                            </dd>
                        </div>
                    </dl>
                </div>
                <Separator class="my-4" />
                <div class="flex flex-col gap-4 md:flex-row md:justify-center">
                    <!-- Edit Button link -->
                    <Button asChild variant="outline">
                        <Link
                            :href="
                                route(
                                    'admin.characters.edit',
                                    props.character.id,
                                )
                            "
                        >
                            <Pencil /> Edit
                        </Link>
                    </Button>
                    <!-- delete -->
                    <AlertDialog>
                        <AlertDialogTrigger asChild>
                            <Button
                                as-child
                                variant="outline"
                                class="cursor-pointer"
                            >
                                <span><Trash /> Delete </span>
                            </Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle
                                    >Are you sure?</AlertDialogTitle
                                >
                                <AlertDialogDescription>
                                    This action cannot be undone. This will
                                    permanently delete the character.
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel>Cancel</AlertDialogCancel>
                                <AlertDialogAction
                                    @click="
                                        (e: Event) =>
                                            handleDelete(e, props.character.id)
                                    "
                                >
                                    Confirm Delete
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Estilos específicos si es necesario */
</style>
