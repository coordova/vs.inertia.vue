<script setup lang="ts">
import TFlashMessages from '@/components/ui/oox/TFlashMessages.vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
        <Toaster />
        <!-- Proveedor de Sonner/Toast -->
        <TFlashMessages />
        <!-- Componente para mostrar mensajes flash de Laravel -->
    </AppLayout>
</template>
