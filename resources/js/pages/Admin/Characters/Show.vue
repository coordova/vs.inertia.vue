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
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { CharacterResource } from '@/types/global'; // Asumiendo que tienes una interfaz CharacterResource o similar
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Trash, User } from 'lucide-vue-next'; // Iconos

interface Props {
    character: CharacterResource; // Usamos el tipo del recurso resuelto
    categories: Array<{
        id: number;
        name: string;
        slug: string;
        status: number;
        elo_rating: number;
        pivot: {
            category_id: number;
            character_id: number;
            elo_rating: number;
            status: number;
        };
    }>;
}

const props = defineProps<Props>();

// console.log(props.categories);

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
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <!-- Character Information -->
            <div class="p-4 md:max-w-6xl md:p-6">
                <div
                    class="flex flex-col gap-4 md:flex-row md:items-start md:justify-around"
                >
                    <!-- Character Image -->
                    <div class="w-full">
                        <div class="flex items-center justify-center">
                            <img
                                v-if="props.character.picture_url"
                                :src="props.character.picture_url"
                                alt=""
                                class="size-128 rounded-lg object-cover"
                            />
                            <User
                                v-else
                                class="size-64 rounded-lg opacity-25"
                                :class="
                                    props.character.gender === 1
                                        ? 'text-blue-500'
                                        : 'text-pink-500'
                                "
                            />
                        </div>
                        <div class="flex flex-col gap-6">
                            <div class="mt-4 space-y-2 text-center">
                                <p class="font-semibold">
                                    {{ props.character.fullname }}
                                </p>
                                <p class="text-xs text-gray-500 italic">
                                    {{ props.character.nickname }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ props.character.dob_for_humans }}
                                </p>
                            </div>
                            <div
                                class="flex flex-col gap-4 md:flex-row md:justify-center"
                            >
                                <!--    Edit Button link -->
                                <Button asChild variant="outline">
                                    <Link
                                        :href="
                                            route(
                                                'admin.characters.edit',
                                                props.character.id,
                                            )
                                        "
                                        ><Pencil /> Edit</Link
                                    >
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
                                                This action cannot be undone.
                                                This will permanently delete the
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
                                                            props.character.id,
                                                        )
                                                "
                                                >Confirm Delete
                                            </AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>
                            </div>
                        </div>
                    </div>
                    <!-- Character Data -->
                    <div class="w-full">
                        <div class="px-4 sm:px-0">
                            <h3
                                class="text-base/7 font-semibold text-gray-900 dark:text-gray-100"
                            >
                                {{ props.character.fullname }}
                            </h3>
                            <p
                                class="mt-1 max-w-2xl text-sm/6 whitespace-pre-line text-gray-500 dark:text-gray-400"
                            >
                                {{ props.character.bio }}
                            </p>
                        </div>
                        <div
                            class="mt-6 border-t border-gray-100 dark:border-white/10"
                        >
                            <!-- Character Information -->
                            <dl
                                class="divide-y divide-gray-100 dark:divide-white/10"
                            >
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
                                        <div class="flex items-center gap-2">
                                            {{
                                                props.character.gender === 0
                                                    ? 'Other'
                                                    : props.character.gender ===
                                                        1
                                                      ? 'Male'
                                                      : props.character
                                                              .gender === 2
                                                        ? 'Female'
                                                        : props.character
                                                                .gender === 3
                                                          ? 'Non-binary'
                                                          : 'Unknown'
                                            }}
                                        </div>
                                    </dd>
                                </div>
                                <div
                                    class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0"
                                >
                                    <dt
                                        class="text-sm/6 font-medium text-gray-900 dark:text-gray-100"
                                    >
                                        DOB
                                    </dt>
                                    <dd
                                        class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400"
                                    >
                                        <div class="flex items-center gap-2">
                                            {{ props.character.dob || 'N/A' }}
                                        </div>
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
                                        <div class="flex items-center gap-2">
                                            {{
                                                props.character.nationality ||
                                                'N/A'
                                            }}
                                        </div>
                                    </dd>
                                </div>
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
                                            props.character.status === true
                                                ? 'Active'
                                                : 'Inactive'
                                        }}
                                    </dd>
                                </div>

                                <!-- Character Categories -->
                                <div
                                    class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0"
                                >
                                    <dt
                                        class="text-sm/6 font-medium text-gray-900 dark:text-gray-100"
                                    >
                                        Categories
                                    </dt>
                                    <dd
                                        class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400"
                                    >
                                        <ul class="flex flex-wrap gap-2">
                                            <li
                                                v-for="category in props.categories"
                                                :key="category.id"
                                            >
                                                <Badge
                                                    :variant="
                                                        category.pivot
                                                            .status === 1
                                                            ? 'default'
                                                            : 'secondary'
                                                    "
                                                >
                                                    {{ category.name }} :
                                                    {{
                                                        category.pivot.elo_rating.toFixed(
                                                            2,
                                                        )
                                                    }}
                                                </Badge>
                                            </li>
                                        </ul>
                                    </dd>
                                </div>
                                <!-- Character Created at -->
                                <div
                                    class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0"
                                >
                                    <dt
                                        class="text-sm/6 font-medium text-gray-900 dark:text-gray-100"
                                    >
                                        Created / Updated
                                    </dt>
                                    <dd
                                        class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400"
                                    >
                                        {{
                                            props.character
                                                .created_at_formatted +
                                            ' / ' +
                                            props.character.updated_at_formatted
                                        }}
                                    </dd>
                                </div>
                                <!-- Character Updated at -->
                                <!-- <div
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
                                            props.character.updated_at_formatted
                                        }}
                                    </dd>
                                </div> -->
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Estilos específicos si es necesario */
</style>
