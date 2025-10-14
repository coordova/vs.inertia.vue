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

// --- Inicializar el composable de toast ---
const { success, error } = useToast();

// --- Inicializar el formulario de Inertia ---
// Usamos la interfaz SurveyResource, pero solo los campos relevantes para el formulario
// y proporcionamos valores iniciales para aquellos que no se usan en el formulario
const form = useForm({
    category_id: 0, // ID de la categoría, debe ser seleccionado
    title: '',
    slug: '', // O se genera automáticamente si se deja vacío en el backend
    description: '',
    image: '', // URL o path
    type: 0, // Valor por defecto (pública)
    status: true, // Valor por defecto
    date_start: '', // Date string
    date_end: '', // Date string
    selection_strategy: 'cooldown', // Valor por defecto
    max_votes_per_user: 0, // Valor por defecto (ilimitado)
    allow_ties: false, // Valor por defecto
    tie_weight: 0.5, // Valor por defecto
    is_featured: false, // Valor por defecto
    sort_order: 0, // Valor por defecto
    counter: 0, // Valor por defecto
    meta_title: '',
    meta_description: '',
    // Campos que no están en el formulario pero que requiere la interfaz SurveyResource
    id: 0, // No se usa en el formulario
    category: { id: 0, name: '' }, // No se usa en el formulario, pero está en la interfaz
    created_at: '', // No se usa en el formulario
    updated_at: '', // No se usa en el formulario
    // deleted_at: null, // No se usa en el formulario
});

// --- Manejo de envío del formulario ---
const submitForm = () => {
    form.post(route('admin.surveys.store'), {
        preserveState: true,
        onSuccess: () => {
            success('Survey created successfully.');
        },
        onError: () => {
            error('Failed to create survey. Please check the errors below.');
        },
    });
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Surveys',
        href: route('admin.surveys.index'),
    },
    {
        title: 'Create',
        href: route('admin.surveys.create'),
    },
];
</script>

<template>
    <Head title="Create Survey" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full w-full max-w-3xl flex-1 flex-col gap-4 p-4 md:p-6"
        >
            <form @submit.prevent="submitForm" class="w-full space-y-6 p-6">
                <div class="space-y-2">
                    <Label for="title">Title *</Label>
                    <Input
                        id="title"
                        type="text"
                        autoFocus
                        :tabIndex="1"
                        autocomplete="title"
                        placeholder="Survey Title"
                        v-model="form.title"
                    />
                    <InputError :message="form.errors.title" />
                </div>

                <div class="space-y-2">
                    <Label for="slug">Slug</Label>
                    <Input
                        id="slug"
                        type="text"
                        :tabIndex="2"
                        autocomplete="slug"
                        placeholder="Slug (optional, auto-generated if empty)"
                        v-model="form.slug"
                    />
                    <InputError :message="form.errors.slug" />
                </div>

                <div class="space-y-2">
                    <Label for="category_id">Category *</Label>
                    <!-- Asumiendo que se cargan categorías en el backend y se pasan como prop o se obtienen aquí -->
                    <select
                        id="category_id"
                        :tabIndex="3"
                        v-model.number="form.category_id"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option value="0">Select a Category</option>
                        <!-- Opciones de categorías se cargarían dinámicamente -->
                        <!-- <option value="1">Beauty</option> -->
                        <!-- <option value="2">Politics</option> -->
                    </select>
                    <InputError :message="form.errors.category_id" />
                </div>

                <div class="space-y-2">
                    <Label for="description">Description</Label>
                    <Textarea
                        id="description"
                        :tabIndex="4"
                        autocomplete="description"
                        placeholder="Survey Description"
                        v-model="form.description"
                        :rows="4"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="date_start">Start Date *</Label>
                        <Input
                            id="date_start"
                            type="date"
                            :tabIndex="5"
                            autocomplete="date_start"
                            v-model="form.date_start"
                        />
                        <InputError :message="form.errors.date_start" />
                    </div>

                    <div class="space-y-2">
                        <Label for="date_end">End Date *</Label>
                        <Input
                            id="date_end"
                            type="date"
                            :tabIndex="6"
                            autocomplete="date_end"
                            v-model="form.date_end"
                        />
                        <InputError :message="form.errors.date_end" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="image">Image URL</Label>
                        <Input
                            id="image"
                            type="text"
                            :tabIndex="7"
                            autocomplete="image"
                            placeholder="https://example.com/image.jpg"
                            v-model="form.image"
                        />
                        <InputError :message="form.errors.image" />
                    </div>

                    <div class="space-y-2">
                        <Label for="selection_strategy"
                            >Selection Strategy</Label
                        >
                        <select
                            id="selection_strategy"
                            :tabIndex="8"
                            v-model="form.selection_strategy"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <option value="cooldown">Cooldown</option>
                            <option value="random">Random</option>
                            <option value="elo_based">ELO Based</option>
                            <!-- Añadir otras estrategias si existen -->
                        </select>
                        <InputError :message="form.errors.selection_strategy" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="max_votes_per_user"
                            >Max Votes per User (0=Unlimited)</Label
                        >
                        <Input
                            id="max_votes_per_user"
                            type="number"
                            :tabIndex="9"
                            min="0"
                            autocomplete="max_votes_per_user"
                            v-model.number="form.max_votes_per_user"
                        />
                        <InputError :message="form.errors.max_votes_per_user" />
                    </div>

                    <div class="space-y-2">
                        <Label for="tie_weight"
                            >Tie Weight (if ties allowed)</Label
                        >
                        <Input
                            id="tie_weight"
                            type="number"
                            :tabIndex="10"
                            min="0"
                            max="1"
                            step="0.01"
                            autocomplete="tie_weight"
                            v-model.number="form.tie_weight"
                        />
                        <InputError :message="form.errors.tie_weight" />
                    </div>
                </div>

                <div class="flex flex-wrap gap-4">
                    <div class="flex items-center space-x-2">
                        <Switch
                            id="status"
                            v-model="form.status"
                            :disabled="form.processing"
                        />
                        <Label htmlFor="status">Active</Label>
                    </div>
                    <div class="flex items-center space-x-2">
                        <Switch
                            id="allow_ties"
                            v-model="form.allow_ties"
                            :disabled="form.processing"
                        />
                        <Label htmlFor="allow_ties">Allow Ties</Label>
                    </div>
                    <div class="flex items-center space-x-2">
                        <Switch
                            id="is_featured"
                            v-model="form.is_featured"
                            :disabled="form.processing"
                        />
                        <Label htmlFor="is_featured">Featured</Label>
                    </div>
                </div>
                <InputError :message="form.errors.status" />
                <InputError :message="form.errors.allow_ties" />
                <InputError :message="form.errors.is_featured" />

                <div class="space-y-2">
                    <Label for="meta_title">Meta Title</Label>
                    <Input
                        id="meta_title"
                        type="text"
                        :tabIndex="11"
                        autocomplete="meta_title"
                        placeholder="Meta Title (for SEO)"
                        v-model="form.meta_title"
                    />
                    <InputError :message="form.errors.meta_title" />
                </div>

                <div class="space-y-2">
                    <Label for="meta_description">Meta Description</Label>
                    <Textarea
                        id="meta_description"
                        :tabIndex="12"
                        autocomplete="meta_description"
                        placeholder="Meta Description (for SEO)"
                        v-model="form.meta_description"
                        :rows="2"
                    />
                    <InputError :message="form.errors.meta_description" />
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
                        @click="router.visit(route('admin.surveys.index'))"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        class="w-full cursor-pointer md:w-auto"
                        :tabIndex="13"
                        :disabled="form.processing || !form.isDirty"
                    >
                        <LoaderCircle
                            v-if="form.processing"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        <span>{{
                            form.processing ? 'Creating...' : 'Create Survey'
                        }}</span>
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Estilos específicos si es necesario */
</style>
