<script setup lang="ts">
import InputError from '@/components/InputError.vue';
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
import { Head, router, useForm } from '@inertiajs/vue3';

// --- Tipado de datos del formulario ---
interface FormData {
    name: string;
    description: string;
    status: boolean;
    is_featured: boolean;
    slug: string;
    // Añadir otros campos si es necesario (slug, color, icon, etc.)
}

// --- Inicializar el composable de toast ---
const { success: toastSuccess, error: toastError } = useToast();

// --- Inicializar el formulario de Inertia ---
const form = useForm<FormData>({
    name: '',
    description: '',
    status: true, // Valor por defecto
    is_featured: false, // Valor por defecto
    slug: '', // Valor por defecto
    // Inicializar otros campos si es necesario
});

// --- Manejo de envío del formulario ---
const submitForm = () => {
    form.post(route('admin.categories.store'), {
        preserveState: true, // Mantiene el estado del formulario en caso de error de validación
        onSuccess: () => {
            toastSuccess('Category created successfully.');
            // Opcional: Resetear el formulario si se desea
            // form.reset(); // Esto resetearía name, description, status
            // Opcional: Redirigir a la lista
            // router.visit(route('admin.categories.index'));
        },
        onError: (errors) => {
            console.error('Errors creating category:', errors);
            // El helper `useForm` maneja automáticamente la visualización de errores
            // en el componente si se usa `form.errors.fieldName`
            toastError(
                'Failed to create category. Please check the errors below.',
            );
        },
    });
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categories',
        href: route('admin.categories.index'),
    },
    {
        title: 'Create',
        href: route('admin.categories.create'),
    },
];
</script>

<template>
    <Head title="Create Category" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Card class="card-no-border mx-auto mt-4 w-full max-w-2xl">
            <CardHeader>
                <CardTitle>Create Category</CardTitle>
                <CardDescription>
                    Add a new category to the system.
                </CardDescription>
            </CardHeader>
            <form @submit.prevent="submitForm">
                <CardContent class="space-y-4">
                    <!-- Campo: Name -->
                    <div class="grid gap-2">
                        <Label htmlFor="name">Name *</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="Enter category name"
                            :disabled="form.processing"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <!-- Campo: Description -->
                    <div class="grid gap-2">
                        <Label htmlFor="description">Description</Label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            placeholder="Enter category description"
                            :disabled="form.processing"
                            :rows="4"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.description"
                        />
                    </div>

                    <!-- <div class="grid gap-2">
                        <Label htmlFor="slug">Slug *</Label>
                        <Input
                            id="slug"
                            v-model="form.slug"
                            type="text"
                            placeholder="Enter category slug"
                            :disabled="form.processing"
                        />
                        <InputError class="mt-2" :message="form.errors.slug" />
                    </div> -->

                    <div class="grid gap-2 space-y-2">
                        <!-- Campo: Status -->
                        <div class="flex items-center space-x-2">
                            <Switch
                                id="status"
                                v-model:checked="form.status"
                                :disabled="form.processing"
                            />
                            <Label htmlFor="status">Active</Label>
                        </div>
                        <InputError
                            class="mt-2"
                            :message="form.errors.status"
                        />

                        <!-- Campo: Featured -->
                        <div class="flex items-center space-x-2">
                            <Switch
                                id="is_featured"
                                v-model:checked="form.is_featured"
                                :disabled="form.processing"
                            />
                            <Label htmlFor="is_featured">Featured</Label>
                        </div>
                        <InputError
                            class="mt-2"
                            :message="form.errors.is_featured"
                        />
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
                            form.processing ? 'Creating...' : 'Create Category'
                        }}
                    </Button>
                </CardFooter>
            </form>
        </Card>
    </AppLayout>
</template>

<style scoped>
/* Estilos específicos si es necesario */
/* In Card.vue or a global CSS file */
.card-no-border {
    border: none !important; /* Use !important if necessary to override existing styles */
    box-shadow: none !important; /* If you also want to remove shadows */
}
</style>
