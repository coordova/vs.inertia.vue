<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch/';
import { Textarea } from '@/components/ui/textarea/';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { CategoryResource } from '@/types/global'; // Asumiendo que tienes una interfaz CategoryResource o similar
import { Head, router, useForm } from '@inertiajs/vue3';

interface Props {
    category: CategoryResource; // Usamos el tipo del recurso
}

const props = defineProps<Props>();

// --- Inicializar el composable de toast ---
const { success, error } = useToast();

// --- Inicializar el formulario de Inertia con los datos iniciales ---
const form = useForm({
    name: props.category.name,
    description: props.category.description ?? '',
    status: props.category.status,
    is_featured: props.category.is_featured,
    slug: props.category.slug,
    // Cargar otros campos iniciales si es necesario
});

// --- Manejo de envío del formulario ---
const submitForm = () => {
    form.put(route('admin.categories.update', props.category.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            success('Category updated successfully.');
            // Opcional: Redirigir a la lista o a la vista de la categoría
            // router.visit(route('admin.categories.index'));
        },
        onError: (errors) => {
            console.error('Errors updating category:', errors);
            error('Failed to update category. Please check the errors below.');
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
    {
        title: 'Edit',
        href: route('admin.categories.edit', props.category.id),
    },
];
</script>

<template>
    <Head :title="`Edit ${category.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Card class="mx-auto mt-4 w-full max-w-2xl">
            <CardHeader>
                <CardTitle>Edit Category: {{ category.name }}</CardTitle>
                <CardDescription>
                    Update the details of the category.
                </CardDescription>
            </CardHeader>
            <form @submit.prevent="submitForm">
                <CardContent class="space-y-4">
                    <!-- Campo: Name -->
                    <div>
                        <Label htmlFor="name">Name *</Label>
                        <Input
                            id="name"
                            v-model="form.data.name"
                            type="text"
                            placeholder="Enter category name"
                            :disabled="form.processing"
                        />
                        <div
                            v-if="form.errors.name"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <!-- Campo: Description -->
                    <div>
                        <Label htmlFor="description">Description</Label>
                        <Textarea
                            id="description"
                            v-model="form.data.description"
                            placeholder="Enter category description"
                            :disabled="form.processing"
                            :rows="4"
                        />
                        <div
                            v-if="form.errors.description"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.description }}
                        </div>
                    </div>

                    <!-- Campo: Status -->
                    <div class="flex items-center space-x-2">
                        <Switch
                            id="status"
                            v-model:checked="form.data.status"
                            :disabled="form.processing"
                        />
                        <Label htmlFor="status">Active</Label>
                    </div>
                    <div
                        v-if="form.errors.status"
                        class="mt-1 text-sm text-red-500"
                    >
                        {{ form.errors.status }}
                    </div>

                    <!-- Añadir otros campos aquí -->
                    <!-- Ejemplo: Slug -->

                    <div>
                        <Label htmlFor="slug">Slug *</Label>
                        <Input
                            id="slug"
                            v-model="form.data.slug"
                            type="text"
                            placeholder="Enter category slug"
                            :disabled="form.processing"
                        />
                        <div
                            v-if="form.errors.slug"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.slug }}
                        </div>
                    </div>
                </CardContent>
                <CardFooter class="flex justify-end space-x-2">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="form.processing"
                        @click="router.visit(route('admin.categories.index'))"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing || !form.isDirty"
                    >
                        {{
                            form.processing ? 'Updating...' : 'Update Category'
                        }}
                    </Button>
                </CardFooter>
            </form>
        </Card>
    </AppLayout>
</template>

<style scoped>
/* Estilos específicos si es necesario */
</style>
