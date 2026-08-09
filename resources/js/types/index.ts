import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface NavigationGroupItem {
    title: string;
    route: string;
    href: string;
    icon: string;
    badge?: string | null;
    exact?: boolean;
}

export interface NavigationGroup {
    id: string;
    title: string;
    icon: string;
    description?: string;
    items: NavigationGroupItem[];
}

export interface Navigation {
    groups: NavigationGroup[];
    moduleBoundaries: Array<{ name: string; label: string; path: string; routeFiles: string[] }>;
}

export interface Flash {
    success?: string;
    error?: string;
}

export interface SharedData {
    name: string;
    auth: Auth;
    navigation: Navigation;
    flash: Flash;
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
}

export interface Tenant {
    id: number;
    name: string;
    slug: string;
    email?: string;
    phone?: string;
    address?: string;
    currency: string;
    timezone: string;
    status: 'active' | 'trial' | 'suspended';
    plan: 'free' | 'starter' | 'pro' | 'enterprise';
    trial_ends_at?: string | null;
    settings?: Record<string, unknown> | null;
    users_count?: number;
    created_at: string;
    updated_at: string;
}

export interface Role {
    id: number;
    name: string;
    guard_name: string;
    permissions_count?: number;
    users_count?: number;
    created_at: string;
    updated_at: string;
}

export interface Permission {
    id: number;
    name: string;
    guard_name: string;
    roles_count?: number;
    created_at: string;
    updated_at: string;
}

export interface User {
    id: number;
    name: string;
    email: string;
    phone?: string;
    is_active?: boolean;
    tenant?: { id: number; name: string } | null;
    roles?: Array<{ id: number; name: string }> | string[];
    permissions?: string[];
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface ActivityLog {
    id: number;
    log_name: string;
    description: string;
    subject_type?: string | null;
    subject_id?: string | number | null;
    causer?: { id: number; name: string } | null;
    event?: string | null;
    created_at: string;
    updated_at: string;
}

export interface PaginationLinks {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginationMeta {
    current_page: number;
    from: number | null;
    last_page: number;
    path: string;
    per_page: number;
    to: number | null;
    total: number;
}

export interface Paginated<T> {
    data: T[];
    links: PaginationLinks[];
    meta: PaginationMeta;
    [key: string]: unknown;
}

export type BreadcrumbItemType = BreadcrumbItem;
