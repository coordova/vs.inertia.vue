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
import { SurveyResource } from '@/types/global'; // Asumiendo que tienes una interfaz SurveyResource o similar
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Trash } from 'lucide-vue-next'; // Iconos

interface Props {
    survey: SurveyResource; // Usamos el tipo del recurso resuelto
}

const props = defineProps<Props>();

// --- Inicializar el composable de toast ---
const { success, error } = useToast();

// --- Manejo de eliminación ---
const handleDelete = (e: Event, id: number) => {
    router.delete(route('admin.surveys.destroy', id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            success('Survey deleted successfully.');
            router.visit(route('admin.surveys.index'));
        },
        onError: () => {
            error('Failed to delete survey.');
        },
    });
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Surveys',
        href: route('admin.surveys.index'),
    },
    {
        title: props.survey.title, // Nombre dinámico de la encuesta
        href: route('admin.surveys.show', props.survey.id),
    },
];
</script>

<template>
    <Head :title="`View ${props.survey.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-2xl flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Survey Information -->
            <div class="max-w-3xl p-4 md:p-6">
                <div class="px-4 sm:px-0">
                    <h3
                        class="text-base/7 font-semibold text-gray-900 dark:text-gray-100"
                    >
                        {{ props.survey.title }}
                    </h3>
                    <p
                        class="mt-1 max-w-2xl text-sm/6 text-gray-500 dark:text-gray-400"
                    >
                        {{ props.survey.description }}
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
                                    props.survey.status ? 'Active' : 'Inactive'
                                }}
                            </dd>
                        </div>
                        <div
                            class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0"
                        >
                            <dt
                                class="text-sm/6 font-medium text-gray-900 dark:text-gray-100"
                            >
                                Featured
                            </dt>
                            <dd
                                class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400"
                            >
                                {{ props.survey.is_featured ? 'Yes' : 'No' }}
                            </dd>
                        </div>
                        <div
                            class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0"
                        >
                            <dt
                                class="text-sm/6 font-medium text-gray-900 dark:text-gray-100"
                            >
                                Type
                            </dt>
                            <dd
                                class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400"
                            >
                                {{
                                    props.survey.type === 0
                                        ? 'Public'
                                        : 'Private'
                                }}
                            </dd>
                        </div>
                        <div
                            class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0"
                        >
                            <dt
                                class="text-sm/6 font-medium text-gray-900 dark:text-gray-100"
                            >
                                Category
                            </dt>
                            <dd
                                class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400"
                            >
                                <Link
                                    :href="
                                        route(
                                            'admin.categories.show',
                                            props.survey.category.id,
                                        )
                                    "
                                    class="text-indigo-600 hover:text-indigo-900"
                                >
                                    {{ props.survey.category.name }}
                                </Link>
                            </dd>
                        </div>
                        <div
                            class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0"
                        >
                            <dt
                                class="text-sm/6 font-medium text-gray-900 dark:text-gray-100"
                            >
                                Start Date
                            </dt>
                            <dd
                                class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400"
                            >
                                {{
                                    new Date(
                                        props.survey.date_start,
                                    ).toLocaleDateString()
                                }}
                            </dd>
                        </div>
                        <div
                            class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0"
                        >
                            <dt
                                class="text-sm/6 font-medium text-gray-900 dark:text-gray-100"
                            >
                                End Date
                            </dt>
                            <dd
                                class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400"
                            >
                                {{
                                    new Date(
                                        props.survey.date_end,
                                    ).toLocaleDateString()
                                }}
                            </dd>
                        </div>
                        <div
                            class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0"
                        >
                            <dt
                                class="text-sm/6 font-medium text-gray-900 dark:text-gray-100"
                            >
                                Selection Strategy
                            </dt>
                            <dd
                                class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400"
                            >
                                {{ props.survey.selection_strategy }}
                            </dd>
                        </div>
                        <div
                            class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0"
                        >
                            <dt
                                class="text-sm/6 font-medium text-gray-900 dark:text-gray-100"
                            >
                                Max Votes per User
                            </dt>
                            <dd
                                class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400"
                            >
                                {{
                                    props.survey.max_votes_per_user === 0
                                        ? 'Unlimited'
                                        : props.survey.max_votes_per_user
                                }}
                            </dd>
                        </div>
                        <div
                            class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0"
                        >
                            <dt
                                class="text-sm/6 font-medium text-gray-900 dark:text-gray-100"
                            >
                                Allow Ties
                            </dt>
                            <dd
                                class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400"
                            >
                                {{ props.survey.allow_ties ? 'Yes' : 'No' }}
                            </dd>
                        </div>
                        <div
                            class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0"
                        >
                            <dt
                                class="text-sm/6 font-medium text-gray-900 dark:text-gray-100"
                            >
                                Tie Weight
                            </dt>
                            <dd
                                class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400"
                            >
                                {{ props.survey.tie_weight }}
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
                                        props.survey.created_at,
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
                                        props.survey.updated_at,
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
                                Image
                            </dt>
                            <dd
                                class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400"
                            >
                                <img
                                    v-if="props.survey.image"
                                    :src="props.survey.image"
                                    :alt="props.survey.title"
                                    class="h-32 w-32 rounded object-cover"
                                />
                                <span v-else>N/A</span>
                            </dd>
                        </div>
                    </dl>
                </div>
                <Separator class="my-4" />
                <div class="flex flex-col gap-4 md:flex-row md:justify-center">
                    <!-- Edit Button link -->
                    <Button asChild variant="outline">
                        <Link
                            :href="route('admin.surveys.edit', props.survey.id)"
                        >
                            <Pencil /> Edit
                        </Link>
                    </Button>
                    <!-- delete -->
                    <AlertDialog>
                        <AlertDialogTrigger asChild>
                            <Button
                                asChild
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
                                    permanently delete the survey.
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel>Cancel</AlertDialogCancel>
                                <AlertDialogAction
                                    @click="
                                        (e: Event) =>
                                            handleDelete(e, props.survey.id)
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
