<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { SurveyResource } from '@/types/global';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Encuestas',
        href: 'surveys.public.index',
    },
];

interface Props {
    surveys: SurveyResource[];
}

const props = defineProps<Props>();
</script>

<template>
    <Head title="Encuestas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Listado de encuestas -->
            <div v-for="survey in props.surveys" :key="survey.id">
                <h2>{{ survey.title }}</h2>
                <p>{{ survey.description }}</p>
            </div>
        </div>
    </AppLayout>
</template>
