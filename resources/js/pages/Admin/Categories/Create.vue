<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Switch } from '@/components/ui/switch/';
import { Textarea } from '@/components/ui/textarea/';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

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
const { success, error } = useToast();
// const { success: toastSuccess, error: toastError } = useToast();

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
            success('Category created successfully.');
            // Opcional: Resetear el formulario si se desea
            // form.reset(); // Esto resetearía name, description, status
            // Opcional: Redirigir a la lista
            // router.visit(route('admin.categories.index'));
        },
        onError: (errors) => {
            // console.error('Errors creating category:', errors);
            // El helper `useForm` maneja automáticamente la visualización de errores
            // en el componente si se usa `form.errors.fieldName`
            error('Failed to create category. Please check the errors below.');
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
        <div
            class="flex h-full w-full max-w-3xl flex-1 flex-col gap-4 p-4 md:p-6"
        >
            <form @submit.prevent="submitForm" class="w-full space-y-6 p-6">
                <div class="space-y-2">
                    <Label for="name">Category name</Label>
                    <Input
                        id="name"
                        type="text"
                        autoFocus
                        :tabIndex="1"
                        autocomplete="name"
                        placeholder="Category name"
                        v-model="form.name"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="space-y-2">
                    <Label for="description">Description</Label>
                    <Textarea
                        id="description"
                        :tabIndex="2"
                        autocomplete="description"
                        placeholder="Category description"
                        v-model="form.description"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="flex items-center space-x-6">
                    <!-- Campo: Status -->
                    <div>
                        <div class="flex items-center space-x-2">
                            <Switch
                                id="status"
                                v-model="form.status"
                                :disabled="form.processing"
                            />
                            <Label htmlFor="status">Active</Label>
                        </div>
                        <InputError
                            class="mt-2"
                            :message="form.errors.status"
                        />
                    </div>

                    <!-- Campo: Featured -->
                    <div>
                        <div class="flex items-center space-x-2">
                            <Switch
                                id="is_featured"
                                v-model="form.is_featured"
                                :disabled="form.processing"
                            />
                            <Label htmlFor="is_featured">Featured</Label>
                        </div>
                        <InputError
                            class="mt-2"
                            :message="form.errors.is_featured"
                        />
                    </div>
                </div>
                <Separator class="my-4" />
                <div
                    class="flex w-full flex-col items-center space-y-4 space-x-0 md:flex-row md:justify-end md:space-y-0 md:space-x-4"
                >
                    <Button
                        type="button"
                        variant="outline"
                        class="w-full cursor-pointer md:w-auto"
                        :disabled="form.processing"
                        @click="router.visit(route('admin.categories.index'))"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        class="w-full cursor-pointer md:w-auto"
                        :tabIndex="4"
                        :disabled="form.processing || !form.isDirty"
                    >
                        <LoaderCircle
                            v-if="form.processing"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        <span>{{
                            form.processing ? 'Creating...' : 'Create category'
                        }}</span>
                    </Button>
                </div>
            </form>
        </div>
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
