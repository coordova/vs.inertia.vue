<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import {
    BookCheck,
    BookOpen,
    Folder,
    Layers,
    LayoutGrid,
    Presentation,
    Users,
    UsersRound,
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Landing',
        href: route('home'),
        icon: Presentation,
        isActive: route().current('home'),
    },
    {
        title: 'Users',
        href: route('admin.users.index'),
        icon: Users,
        isActive: route().current('admin.users.index'),
    },
    {
        title: 'Categories',
        href: route('admin.categories.index'),
        icon: Layers,
        isActive: route().current('admin.categories.index'),
    },
    {
        title: 'Characters',
        href: route('admin.characters.index'),
        icon: UsersRound,
        isActive: route().current('admin.characters.index'),
    },
    {
        title: 'Surveys',
        href: route('admin.surveys.index'),
        icon: BookCheck,
        isActive: route().current('admin.surveys.index'),
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
