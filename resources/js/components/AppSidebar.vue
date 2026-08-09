<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronRight, LayoutDashboard, Users, ShieldCheck, KeyRound, Building2, Activity, UserRoundCog, Palette, CreditCard, type LucideIcon } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const iconMap: Record<string, LucideIcon> = {
    LayoutDashboard,
    Users,
    ShieldCheck,
    KeyRound,
    Building2,
    Activity,
    UserRoundCog,
    Palette,
    CreditCard,
    Settings: UserRoundCog,
};

const page = usePage<SharedData>();
const groups = page.props.navigation?.groups ?? [];

function iconFor(name?: string): LucideIcon {
    return (name && iconMap[name]) || LayoutDashboard;
}
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup class="px-0 py-0">
                <SidebarGroupContent>
                    <SidebarMenu class="px-2">
                        <template v-for="group in groups" :key="group.id">
                            <Collapsible
                                :default-open="group.id === 'overview' || group.items.some((i) => i.href === page.url)"
                                class="group/collapsible"
                            >
                                <SidebarMenuItem>
                                    <CollapsibleTrigger as-child>
                                        <SidebarMenuButton>
                                            <component :is="iconFor(group.icon)" />
                                            <span>{{ group.title }}</span>
                                            <ChevronRight class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                                        </SidebarMenuButton>
                                    </CollapsibleTrigger>
                                </SidebarMenuItem>
                                <CollapsibleContent>
                                    <SidebarMenuSub>
                                        <SidebarMenuSubItem v-for="item in group.items" :key="item.route">
                                            <SidebarMenuSubButton as-child :is-active="item.href === page.url || (item.exact ? item.href === page.url : false)">
                                                <Link :href="item.href">
                                                    <component :is="iconFor(item.icon)" class="h-4 w-4" />
                                                    <span>{{ item.title }}</span>
                                                </Link>
                                            </SidebarMenuSubButton>
                                        </SidebarMenuSubItem>
                                    </SidebarMenuSub>
                                </CollapsibleContent>
                            </Collapsible>
                        </template>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="[]" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
