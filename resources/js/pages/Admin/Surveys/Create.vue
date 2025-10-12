<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

// Layouts & Components
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import DatePicker from '@/components/ui/oox/TDatePicker.vue'; // <-- Importa el nuevo componente
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';

// Composables & Types
import { useToast } from '@/composables/useToast';
import { type BreadcrumbItem } from '@/types';

// Importar nuevo componente
import TCharacterTagsInput from '@/components/ui/oox/TCharacterTagsInput.vue';
import { SurveyResourceForm } from '@/types/global';
import { watch } from 'vue';

// Tipos locales
interface Category {
    id: number;
    name: string;
}

interface SelectionStrategy {
    value: string;
    label: string;
    description?: string;
}

/* type SurveyForm = {
    title: string;
    description: string;
    status: boolean;
    is_featured: boolean;
    category_id: number | null;
    type: number;
    reverse: boolean;
    date_start: DateValue | null;
    date_end: DateValue | null;
    characters: number[];
    selection_strategy: string; // ✅ Nuevo campo
    // [key: string]: string | number | boolean | null; // <- Añadido
}; */

// Props
const props = defineProps<{
    categories: Category[];
    selectionStrategies: SelectionStrategy[]; // ✅ Nueva prop
}>();

// Composable de notificaciones
const { success, error } = useToast();

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Surveys', href: route('admin.surveys.index') },
    { title: 'Create', href: route('admin.surveys.create') },
];

// Formulario con tipos de datos corregidos
const form = useForm<SurveyResourceForm>({
    category_id: null as number | null, // Tipado explícito para claridad
    title: '',
    description: '',
    type: 0, // 0 = public, 1 = private
    status: true, // 1 = enabled, 0 = disabled
    reverse: false, // 0 = no reverse, 1 = reverse
    date_start: '',
    date_end: '',
    selection_strategy: 'cooldown' as string, // ✅ Valor por defecto
    is_featured: false,
    characters: [] as number[], // ✅ Array de números
    // status: Boolean(1), // Convertir a boolean, no string, para el checkbox, sino vue muestra warning
    // reverse: Boolean(0), // Convertir a boolean, no string, para el checkbox, sino vue muestra warning
    // duration: null,
});

// Envío del formulario
const handleSubmit = () => {
    form.post(route('admin.surveys.store'), {
        onSuccess: () => {
            success('Survey created successfully');
            form.reset();
        },
        onError: () => {
            // console.log(form.errors);
            error(
                'An error occurred while creating the survey. Please check the fields.',
            );
        },
    });
};

// Observar cambios en categoría para resetear personajes si es necesario
watch(
    () => form.category_id,
    (newVal) => {
        if (!newVal) {
            form.characters = [];
        }
    },
);

// En Create.vue -- para depuración
/* watch(
    () => form.characters,
    (newVal) => {
        console.log('Form characters updated:', newVal);
    },
    { deep: true },
); */
</script>

<template>
    <Head title="Create Survey" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-3xl flex-1 flex-col gap-4 p-4 md:p-6">
            <!-- <form @submit.prevent="handleSubmit" class="mx-auto w-full max-w-2xl space-y-6 rounded-lg border p-6 shadow-sm"> -->
            <form @submit.prevent="handleSubmit" class="w-full space-y-6 p-6">
                <!-- Title -->
                <div class="space-y-2">
                    <Label for="title">Survey Title</Label>
                    <Input
                        id="title"
                        type="text"
                        autoFocus
                        v-model="form.title"
                    />
                    <InputError :message="form.errors.title" />
                </div>

                <!-- Description -->
                <div class="space-y-2">
                    <Label for="description">Description</Label>
                    <Textarea id="description" v-model="form.description" />
                    <InputError :message="form.errors.description" />
                </div>

                <!-- Category -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="category_id">Category</Label>
                        <Select v-model="form.category_id">
                            <SelectTrigger
                                ><SelectValue placeholder="Select a category"
                            /></SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="category in props.categories"
                                    :key="category.id"
                                    :value="category.id"
                                >
                                    {{ category.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.category_id" />
                    </div>

                    <div class="space-y-2">
                        <Label for="type">Type</Label>
                        <Select v-model="form.type">
                            <SelectTrigger
                                ><SelectValue placeholder="Select type"
                            /></SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="0">Public</SelectItem>
                                <SelectItem :value="1">Private</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.type" />
                    </div>
                </div>

                <!-- Selection Strategy -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="selection_strategy"
                            >Selection Strategy</Label
                        >
                        <Select v-model="form.selection_strategy">
                            <SelectTrigger
                                ><SelectValue placeholder="Select a strategy"
                            /></SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="strategy in props.selectionStrategies"
                                    :key="strategy.value"
                                    :value="strategy.value"
                                >
                                    {{ strategy.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.selection_strategy" />
                    </div>
                </div>

                <!-- Characters in survey -->
                <div class="space-y-2">
                    <!-- <Label for="characters">Characters in survey</Label> -->
                    <TCharacterTagsInput
                        v-model="form.characters"
                        :category-id="form.category_id"
                    />
                    <InputError :message="form.errors.characters" />
                </div>

                <!-- Dates -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="date_start">Start Date</Label>
                        <DatePicker v-model="form.date_start" />
                        <!-- Mostrar fecha formateada para verificación -->
                        <!-- <p class="mt-1 text-xs text-gray-500">Selected: {{ form.date_start ? new Date(form.date_start).toUTCString() : '' }}</p> -->
                        <InputError :message="form.errors.date_start" />
                    </div>

                    <div class="space-y-2">
                        <Label for="date_end">End Date</Label>
                        <DatePicker v-model="form.date_end" />
                        <!-- Mostrar fecha formateada para verificación -->
                        <!-- <p class="mt-1 text-xs text-gray-500">Selected: {{ form.date_end ? new Date(form.date_end).toUTCString() : '' }}</p> -->
                        <InputError :message="form.errors.date_end" />
                    </div>
                </div>

                <!-- Switches -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <div class="flex items-center space-x-2 pt-2">
                            <Switch id="status" v-model="form.status" />
                            <Label
                                for="status"
                                class="cursor-pointer text-sm leading-none font-medium"
                            >
                                Enable this survey upon creation
                            </Label>
                        </div>
                        <InputError
                            :message="form.errors.status"
                            class="mt-1"
                        />
                    </div>

                    <div>
                        <div class="flex items-center space-x-2 pt-2">
                            <Switch id="reverse" v-model="form.reverse" />
                            <Label
                                for="reverse"
                                class="cursor-pointer text-sm leading-none font-medium"
                            >
                                Reverse this survey upon creation
                            </Label>
                        </div>
                        <InputError
                            :message="form.errors.reverse"
                            class="mt-1"
                        />
                    </div>
                </div>

                <!-- Submit -->
                <Button
                    type="submit"
                    class="w-full cursor-pointer"
                    :disabled="form.processing"
                >
                    <LoaderCircle
                        v-if="form.processing"
                        class="mr-2 h-4 w-4 animate-spin"
                    />
                    Create Survey
                </Button>
            </form>
        </div>
        <!-- Verifica que el formulario tenga el campo -->
        <!-- <pre>{{ form }}</pre> -->
        <!-- Agrega esto temporalmente para debug -->
    </AppLayout>
</template>
