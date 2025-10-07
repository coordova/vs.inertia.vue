<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import MaskDateEdit from '@/components/ui/oox/TDateMaskEdit.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch/';
import { Textarea } from '@/components/ui/textarea/';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { CategoryResource, CharacterResource } from '@/types/global';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

// --- Inicializar el composable de toast ---
const { success, error } = useToast();

// CategoryResource
// const categories = ref<CategoryResource[]>([]);

// props
const props = defineProps({
    categories: {
        type: Array as () => CategoryResource[],
        required: true,
    },
});

// --- Inicializar el formulario de Inertia ---
// Usamos la interfaz CharacterResource, pero solo los campos relevantes para el formulario
// y proporcionamos valores iniciales para aquellos que no se usan en el formulario
const form = useForm<CharacterResource>({
    fullname: '',
    nickname: '',
    slug: '', // O se genera automáticamente si se deja vacío en el backend
    bio: '',
    dob: '', // Date string
    dob_for_humans: '', // Date string
    gender: null, // Valor por defecto
    nationality: '',
    occupation: '',
    picture: null, // URL o path
    picture_url: undefined,
    status: true, // Valor por defecto
    meta_title: '',
    meta_description: '',
    // Campos que no están en el formulario pero que requiere la interfaz CharacterResource
    id: 0, // No se usa en el formulario
    created_at: '', // No se usa en el formulario
    updated_at: '', // No se usa en el formulario

    // Campos adicionales
    category_ids: [],
});

// --- Manejo de envío del formulario ---
const submitForm = () => {
    form.post(route('admin.characters.store'), {
        preserveState: true,
        onSuccess: () => {
            success('Character created successfully.');
        },
        onError: () => {
            error('Failed to create character. Please check the errors below.');
        },
    });
};

// --- Manejo de envío del formulario ---
const imagePreview = ref<string | null>(null);
const onFileChange = (event: Event) => {
    const files = (event.target as HTMLInputElement).files;
    form.picture = files && files.length > 0 ? files[0] : null;
    imagePreview.value =
        files && files.length > 0 ? URL.createObjectURL(files[0]) : null;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Characters',
        href: route('admin.characters.index'),
    },
    {
        title: 'Create',
        href: route('admin.characters.create'),
    },
];
</script>

<template>
    <Head title="Create Character" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-3xl flex-1 flex-col gap-4 p-4 md:p-6">
            <form @submit.prevent="submitForm" class="w-full space-y-6 p-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="gender">Gender</Label>
                        <Select v-model="form.gender">
                            <SelectTrigger
                                ><SelectValue placeholder="Select gender"
                            /></SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    :value="0"
                                    :selected="form.gender === 0"
                                    >Other</SelectItem
                                >
                                <SelectItem
                                    :value="1"
                                    :selected="form.gender === 1"
                                    >Male</SelectItem
                                >
                                <SelectItem
                                    :value="2"
                                    :selected="form.gender === 2"
                                    >Female</SelectItem
                                >
                                <SelectItem
                                    :value="3"
                                    :selected="form.gender === 3"
                                    >No-binario</SelectItem
                                >
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.gender" />
                    </div>
                    <div class="space-y-2">
                        <Label for="category_id">Category</Label>
                        <Select v-model="form.category_ids" multiple>
                            <SelectTrigger
                                ><SelectValue placeholder="Select category"
                            /></SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="category in props.categories"
                                    :key="category.id"
                                    :value="category.id"
                                    >{{ category.name }}</SelectItem
                                >
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.category_ids" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="fullname">Fullname</Label>
                        <Input
                            id="fullname"
                            type="text"
                            autoFocus
                            v-model="form.fullname"
                        />
                        <InputError :message="form.errors.fullname" />
                    </div>

                    <div class="space-y-2">
                        <Label for="nickname">Nickname</Label>
                        <Input
                            id="nickname"
                            type="text"
                            v-model="form.nickname"
                        />
                        <InputError :message="form.errors.nickname" />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="bio">Biography</Label>
                    <Textarea id="bio" v-model="form.bio" />
                    <InputError :message="form.errors.bio" />
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="dob">Date of Birth</Label>
                        <MaskDateEdit id="dob" v-model="form.dob" />
                        <InputError :message="form.errors.dob" />
                    </div>

                    <div class="space-y-2">
                        <Label for="image">Image</Label>
                        <Input
                            id="image"
                            type="file"
                            @input="onFileChange"
                            accept="image/*"
                        />
                        <img
                            v-if="imagePreview"
                            :src="imagePreview"
                            alt="Image Preview"
                            class="mt-2 max-h-40 w-auto"
                        />
                        <InputError :message="form.errors.picture" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <div class="flex items-center space-x-2 pt-2">
                            <Switch id="status" v-model="form.status" />
                            <Label
                                for="status"
                                class="cursor-pointer text-sm leading-none font-medium"
                            >
                                Enable this character upon creation
                            </Label>
                        </div>
                        <InputError
                            :message="form.errors.status"
                            class="mt-1"
                        />
                    </div>
                </div>

                <Button type="submit" class="w-full">Create Character</Button>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Date picker */
input[type='date']::-webkit-inner-spin-button,
input[type='date']::-webkit-calendar-picker-indicator {
    display: none;
    -webkit-appearance: none;
}

/* 
input[type='date'] {
    -webkit-appearance: none;
    appearance: none;
    border: 1px solid #ccc;
    border-radius: 4px;
    padding: 8px;
    width: 100%;
} */
</style>
