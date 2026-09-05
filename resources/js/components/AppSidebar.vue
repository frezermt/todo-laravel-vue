<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Calendar,
    Clock,
    FolderKanban,
    LayoutDashboard,
} from '@lucide/vue';
import NavFooter from '@/components/NavFooter.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';

const page = usePage();

const navItems = [
    {
        title: 'Dashboard',
        href: '/todos?view=dashboard',
        view: 'dashboard',
        icon: LayoutDashboard,
    },
    {
        title: 'Today',
        href: '/todos?view=today',
        view: 'today',
        icon: Calendar,
    },
    {
        title: 'Upcoming',
        href: '/todos?view=upcoming',
        view: 'upcoming',
        icon: Clock,
    },
    {
        title: 'Categories',
        href: '/todos?view=categories',
        view: 'categories',
        icon: FolderKanban,
    },
];

function isItemActive(item: typeof navItems[0]) {
    const searchParams = new URL(page.url, 'http://localhost').searchParams;
    const viewParam = searchParams.get('view') || 'dashboard';
    return page.url.startsWith('/todos') && viewParam === item.view;
}

const footerNavItems: any[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/todos" class="flex items-center gap-2.5">
                            <div class="flex aspect-square size-8 items-center justify-center rounded-lg bg-[#b85c38] text-white shadow-2xs">
                                <svg class="size-4 stroke-white" viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                            </div>
                            <div class="flex flex-1 items-center text-left text-sm">
                                <span class="truncate font-bold tracking-tight text-foreground">Passo</span>
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup class="px-2 py-0">
                <SidebarGroupLabel>Tasks</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem v-for="item in navItems" :key="item.title">
                        <SidebarMenuButton
                            as-child
                            :is-active="isItemActive(item)"
                            :tooltip="item.title"
                        >
                            <Link :href="item.href">
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
