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
import { CharacterResource } from '@/types/global'; // Interfaz CharacterResource
import { Head, router, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

// --- Inicializar el composable de toast ---
const { success, error } = useToast();

// --- Inicializar el formulario de Inertia ---
// Usamos la interfaz CharacterResource, pero solo los campos relevantes para el formulario
// y proporcionamos valores iniciales para aquellos que no se usan en el formulario
const form = useForm({
    fullname: '',
    nickname: '',
    slug: '', // O se genera automáticamente si se deja vacío en el backend
    bio: '',
    dob: '', // Date string
    gender: 0, // Valor por defecto
    nationality: '',
    occupation: '',
    picture: '', // URL o path
    status: true, // Valor por defecto
    meta_title: '',
    meta_description: '',
    // Campos que no están en el formulario pero que requiere la interfaz CharacterResource
    id: 0, // No se usa en el formulario
    created_at: '', // No se usa en el formulario
    updated_at: '', // No se usa en el formulario
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
        <div class="flex h-full w-full max-w-3xl flex-1 flex-col gap-4 p-4 md:p-6">
            <form @submit.prevent="submitForm" class="w-full space-y-6 p-6">
                <div class="space-y-2">
                    <Label for="fullname">Full Name *</Label>
                    <Input
                        id="fullname"
                        type="text"
                        autoFocus
                        :tabIndex="1"
                        autocomplete="fullname"
                        placeholder="Full Name"
                        v-model="form.fullname"
                    />
                    <InputError :message="form.errors.fullname" />
                </div>

                <div class="space-y-2">
                    <Label for="nickname">Nickname</Label>
                    <Input
                        id="nickname"
                        type="text"
                        :tabIndex="2"
                        autocomplete="nickname"
                        placeholder="Nickname"
                        v-model="form.nickname"
                    />
                    <InputError :message="form.errors.nickname" />
                </div>

                <div class="space-y-2">
                    <Label for="slug">Slug</Label>
                    <Input
                        id="slug"
                        type="text"
                        :tabIndex="3"
                        autocomplete="slug"
                        placeholder="Slug (optional, auto-generated if empty)"
                        v-model="form.slug"
                    />
                    <InputError :message="form.errors.slug" />
                </div>

                <div class="space-y-2">
                    <Label for="bio">Biography</Label>
                    <Textarea
                        id="bio"
                        :tabIndex="4"
                        autocomplete="bio"
                        placeholder="Biography"
                        v-model="form.bio"
                        :rows="4"
                    />
                    <InputError :message="form.errors.bio" />
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="dob">Date of Birth</Label>
                        <Input
                            id="dob"
                            type="date"
                            :tabIndex="5"
                            autocomplete="dob"
                            v-model="form.dob"
                        />
                        <InputError :message="form.errors.dob" />
                    </div>

                    <div class="space-y-2">
                        <Label for="gender">Gender</Label>
                        <select
                            id="gender"
                            :tabIndex="6"
                            v-model.number="form.gender" // .number para convertir a número
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <option value="0">Other</option>
                            <option value="1">Male</option>
                            <option value="2">Female</option>
                            <option value="3">Non-binary</option>
                        </select>
                        <InputError :message="form.errors.gender" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="nationality">Nationality</Label>
                        <Input
                            id="nationality"
                            type="text"
                            :tabIndex="7"
                            autocomplete="nationality"
                            placeholder="Nationality"
                            v-model="form.nationality"
                        />
                        <InputError :message="form.errors.nationality" />
                    </div>

                    <div class="space-y-2">
                        <Label for="occupation">Occupation</Label>
                        <Input
                            id="occupation"
                            type="text"
                            :tabIndex="8"
                            autocomplete="occupation"
                            placeholder="Occupation"
                            v-model="form.occupation"
                        />
                        <InputError :message="form.errors.occupation" />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="picture">Picture URL *</Label>
                    <Input
                        id="picture"
                        type="text"
                        :tabIndex="9"
                        autocomplete="picture"
                        placeholder="https://example.com/image.jpg"
                        v-model="form.picture"
                    />
                    <InputError :message="form.errors.picture" />
                </div>

                <div class="space-y-2">
                    <Label for="meta_title">Meta Title</Label>
                    <Input
                        id="meta_title"
                        type="text"
                        :tabIndex="10"
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
                        :tabIndex="11"
                        autocomplete="meta_description"
                        placeholder="Meta Description (for SEO)"
                        v-model="form.meta_description"
                        :rows="2"
                    />
                    <InputError :message="form.errors.meta_description" />
                </div>

                <div class="flex items-center space-x-2">
                    <Switch id="status" v-model="form.status" :disabled="form.processing" />
                    <Label htmlFor="status">Active</Label>
                </div>
                <InputError :message="form.errors.status" />

                <Separator class="my-4" />

                <div class="flex w-full flex-col items-center space-y-4 space-x-0 md:flex-row md:justify-end md:space-y-0 md:space-x-4">
                    <Button
                        type="button"
                        variant="outline"
                        class="w-full cursor-pointer md:w-auto"
                        :disabled="form.processing"
                        @click="router.visit(route('admin.characters.index'))"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        class="w-full cursor-pointer md:w-auto"
                        :tabIndex="12"
                        :disabled="form.processing || !form.isDirty"
                    >
                        <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        <span>{{ form.processing ? 'Creating...' : 'Create Character' }}</span>
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Estilos específicos si es necesario */
</style>