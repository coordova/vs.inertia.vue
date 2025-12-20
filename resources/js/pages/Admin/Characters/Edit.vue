<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import MaskDateEdit from '@/components/oox/TDateMaskEdit.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Switch } from '@/components/ui/switch/';
import { Textarea } from '@/components/ui/textarea/';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { CategoryResource, CharacterResourceForm } from '@/types/global'; // Interfaz CharacterResource
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Props {
    character: CharacterResourceForm; // Usamos el tipo del recurso resuelto
    categories: CategoryResource[]; // Categorias disponibles
    characterCategories: CategoryResource[]; // Categorias del personaje
}

const props = defineProps<Props>();
// console.log(props);
// --- Inicializar el composable de toast ---
const { success, error } = useToast();

// --- Inicializar el formulario de Inertia con los datos iniciales ---
// Solo incluimos los campos que se editan en el formulario
const form = useForm<CharacterResourceForm>({
    fullname: props.character.fullname,
    nickname: props.character.nickname ?? '', // Manejar null
    slug: props.character.slug,
    bio: props.character.bio ?? '', // Manejar null
    dob: props.character.dob,
    gender: props.character.gender,
    nationality: props.character.nationality ?? '', // Manejar null
    occupation: props.character.occupation ?? '', // Manejar null
    picture: null as File | null, // Aseguramos que picture sea un File o null
    picture_url: props.character.picture_url, // Aseguramos que picture_url sea una string o undefined
    status: props.character.status,
    meta_title: props.character.meta_title ?? '', // Manejar null
    meta_description: props.character.meta_description ?? '', // Manejar null
    id: props.character.id,
    category_ids: props.characterCategories.map((category) => category.id),
});
// console.log(form);
// --- Manejo de envío del formulario ---
const submitForm = () => {
    form.transform((data) => ({
        ...data,
        _method: 'put', // Para que Laravel lo entienda como update
    })).post(route('admin.characters.update', props.character.id), {
        forceFormData: true, // ¡Obligatorio cuando hay archivos!
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            success('Character updated successfully.');
        },
        onError: (errors) => {
            error('Failed to update character. Please check the errors below.' + errors);
        },
    });
};

/* const submitForm = () => {
    form.put(route('admin.characters.update', props.character.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            success('Character updated successfully.');
        },
        onError: (errors) => {
            console.error('Errors updating character:', errors);
            error('Failed to update character. Please check the errors below.');
        },
    });
}; */

// Preview dinámico
const imagePreview = ref<string | null>(null);

const existingImage = computed(() => props.character.picture_url);

const onFileChange = (e: Event) => {
    const files = (e.target as HTMLInputElement).files;
    if (files && files.length) {
        form.picture = files[0];
        imagePreview.value = URL.createObjectURL(files[0]);
    } else {
        form.picture = null;
        imagePreview.value = null;
    }
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
    {
        title: 'Edit',
        href: route('admin.characters.edit', props.character.id),
    },
];
</script>

<template>

    <Head title="Edit Character" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-3xl flex-1 flex-col gap-4 p-4 md:p-6">
            <form @submit.prevent="submitForm" class="w-full space-y-6 p-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- Gender -->
                    <div class="space-y-2">
                        <Label>Gender</Label>
                        <Select v-model="form.gender">
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="0" :selected="form.gender === 0">Other</SelectItem>
                                <SelectItem :value="1" :selected="form.gender === 1">Male</SelectItem>
                                <SelectItem :value="2" :selected="form.gender === 2">Female</SelectItem>
                                <SelectItem :value="3" :selected="form.gender === 3">No-binario</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.gender" />
                    </div>
                    <div class="space-y-2">
                        <Label for="categories">Category</Label>
                        <Select v-model="form.category_ids" multiple>
                            <SelectTrigger>
                                <SelectValue :placeholder="form.category_ids.length
                                    ? `${form.category_ids.length} selected`
                                    : 'Select categories'
                                    " />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="category in props.categories" :key="category.id"
                                    :value="category.id">{{ category.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.category_ids" />
                    </div>
                </div>

                <!-- Fullname & Nickname -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="fullname">Fullname</Label>
                        <Input id="fullname" v-model="form.fullname" autofocus />
                        <InputError :message="form.errors.fullname" />
                    </div>

                    <div class="space-y-2">
                        <Label for="nickname">Nickname</Label>
                        <Input id="nickname" v-model="form.nickname" />
                        <InputError :message="form.errors.nickname" />
                    </div>
                </div>

                <!-- Bio -->
                <div class="space-y-2">
                    <Label for="bio">Biography</Label>
                    <Textarea id="bio" v-model="form.bio" />
                    <InputError :message="form.errors.bio" />
                </div>

                <!-- DOB & Image -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="dob">Date of Birth</Label>
                        <MaskDateEdit id="dob" v-model="form.dob" />
                        <InputError :message="form.errors.dob" />
                    </div>

                    <div class="space-y-2">
                        <Label for="image">Image</Label>
                        <Input id="image" type="file" accept="image/*" @input="onFileChange" />
                        <div class="flex items-center space-x-2">
                            <img v-if="existingImage" :src="existingImage" alt="Current"
                                :class="[imagePreview ? 'opacity-30 transition-opacity duration-300' : 'opacity-100', 'mt-2 max-h-40 w-auto rounded object-cover']" />
                            <img v-if="imagePreview" :src="imagePreview" alt="Preview"
                                class="mt-2 max-h-40 w-auto rounded object-cover" />
                        </div>
                        <InputError :message="form.errors.picture" />
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <div class="flex items-center space-x-2 pt-2">
                        <Switch id="status" v-model="form.status" />
                        <Label for="status" class="cursor-pointer text-sm font-medium">Enable character</Label>
                    </div>
                    <InputError :message="form.errors.status" class="mt-1" />
                </div>

                <Separator class="my-4" />
                <div
                    class="flex w-full flex-col items-center space-y-4 space-x-0 md:flex-row md:justify-end md:space-y-0 md:space-x-4">
                    <Button type="button" variant="outline" class="w-full cursor-pointer md:w-auto"
                        :disabled="form.processing" @click="
                            router.visit(
                                route(
                                    'admin.characters.show',
                                    props.character.id,
                                ),
                            )
                            ">
                        Cancel
                    </Button>
                    <Button type="submit" class="w-full cursor-pointer md:w-auto"
                        :disabled="form.processing || !form.isDirty">
                        {{ form.processing ? 'Saving...' : 'Update' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
