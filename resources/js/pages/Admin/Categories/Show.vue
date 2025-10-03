<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { CategoryResource } from '@/types/global'; // Asumiendo que tienes una interfaz CategoryResource o similar
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Trash } from 'lucide-vue-next'; // Iconos

/* interface Category {
    data: {
        id: number;
        name: string;
        description: string;
        status: boolean;
        created_at_formatted: string;
        // Añade otros campos según CategoryResource
    };
} */

interface Props {
    category: CategoryResource; // Usamos el tipo del recurso
}

const props = defineProps<Props>();

// console.log(props.category);

// --- Inicializar el composable de toast ---
const { success: toastSuccess, error: toastError } = useToast();

// --- Manejo de eliminación ---
const handleDelete = () => {
    if (
        confirm(
            `Are you sure you want to delete the category "${props.category.name}"?`,
        )
    ) {
        router.delete(route('admin.categories.destroy', props.category.id), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                toastSuccess('Category deleted successfully.');
                // Opcional: Redirigir a la lista
                router.visit(route('admin.categories.index'));
            },
            onError: () => {
                toastError('Failed to delete category.');
            },
        });
    }
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categories',
        href: route('admin.categories.index'),
    },
    /* {
        title: props.category.name, // Nombre dinámico de la categoría
        href: route('admin.categories.show', props.category.id),
    }, */
];
</script>

<template>
    <Head :title="`View ${props.category.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Card class="card-no-border mx-auto mt-4 w-full max-w-2xl">
            <CardHeader>
                <CardTitle>{{ props.category.name }}</CardTitle>
                <CardDescription> Details for the category. </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium">Details</h3>
                    <!-- Puedes añadir acciones aquí si es necesario -->
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">ID</p>
                        <p class="font-medium">{{ props.category.id }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Name</p>
                        <p class="font-medium">
                            {{ props.category.name }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Status</p>
                        <Badge
                            :variant="
                                props.category.status ? 'default' : 'secondary'
                            "
                        >
                            {{ props.category.status ? 'Active' : 'Inactive' }}
                        </Badge>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Created At</p>
                        <p class="font-medium">
                            {{ props.category.created_at_formatted }}
                        </p>
                    </div>
                    <!-- Añadir otros campos aquí -->
                    <div class="col-span-2">
                        <p class="text-sm text-gray-500">Description</p>
                        <p class="font-medium">
                            {{
                                props.category.description ||
                                'No description provided.'
                            }}
                        </p>
                    </div>
                </div>
            </CardContent>
            <CardFooter class="flex justify-end space-x-2">
                <Button
                    variant="outline"
                    @click="router.visit(route('admin.categories.index'))"
                >
                    Back to List
                </Button>
                <Button asChild variant="outline">
                    <Link
                        :href="
                            route('admin.categories.edit', props.category.id)
                        "
                    >
                        <Pencil class="mr-2 h-4 w-4" />
                        Edit
                    </Link>
                </Button>
                <Button variant="destructive" @click="handleDelete">
                    <Trash class="mr-2 h-4 w-4" />
                    Delete
                </Button>
            </CardFooter>
        </Card>
    </AppLayout>
</template>

<style scoped>
/* Estilos específicos si es necesario */
.card-no-border {
    border: none !important; /* Use !important if necessary to override existing styles */
    box-shadow: none !important; /* If you also want to remove shadows */
}
</style>
