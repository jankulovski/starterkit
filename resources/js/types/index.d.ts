import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
    onClick?: (e: React.MouseEvent) => void;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    is_admin?: boolean;
    created_at: string;
    updated_at: string;
    billing?: {
        credits_balance: number;
        current_plan: {
            key: string;
            name: string;
            type: 'free' | 'paid';
            monthly_credits: number;
            features: string[];
        } | null;
        subscription_status: 'none' | 'active' | 'canceled' | 'expired';
    };
    [key: string]: unknown; // This allows for additional properties...
}
