import * as React from 'react';
import { Paintbrush, User, PanelLeft } from 'lucide-react';
import { usePage } from '@inertiajs/react';

import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import { UserInfo } from '@/components/user-info';
import { type SharedData } from '@/types';
import ProfileSettings from '@/domains/settings/profile-settings';
import AppearanceSettings from '@/domains/settings/appearance-settings';

type SettingsSection = 'profile' | 'appearance';

const settingsNav = [
    { id: 'profile' as SettingsSection, name: 'Profile', icon: User },
    { id: 'appearance' as SettingsSection, name: 'Appearance', icon: Paintbrush },
];

// Helper function to get cookie value
function getCookie(name: string): string | null {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) {
        return parts.pop()?.split(';').shift() || null;
    }
    return null;
}

// Helper function to set cookie
function setCookie(name: string, value: string, maxAge: number): void {
    document.cookie = `${name}=${value}; path=/; max-age=${maxAge}`;
}

interface SettingsDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    defaultSection?: SettingsSection;
    mustVerifyEmail?: boolean;
    status?: string;
}

export function SettingsDialog({
    open,
    onOpenChange,
    defaultSection = 'profile',
    mustVerifyEmail = false,
    status,
}: SettingsDialogProps) {
    const { auth } = usePage<SharedData>().props;
    const [activeSection, setActiveSection] =
        React.useState<SettingsSection>(defaultSection);
    
    // Initialize sidebar state from cookie, with defaults: open on desktop, closed on mobile
    const [sidebarOpen, setSidebarOpen] = React.useState(() => {
        // Default: open on desktop (>= 768px), closed on mobile (< 768px)
        if (typeof window !== 'undefined') {
            return window.innerWidth >= 768;
        }
        return true; // SSR fallback: default to open
    });

    // Reinitialize sidebar state whenever the dialog opens
    React.useEffect(() => {
        if (open) {
            // Recheck window width and set sidebar state accordingly
            if (typeof window !== 'undefined') {
                setSidebarOpen(window.innerWidth >= 768);
            }
        }
    }, [open]);

    React.useEffect(() => {
        if (open && defaultSection) {
            setActiveSection(defaultSection);
        }
    }, [open, defaultSection]);

    const currentSectionName = settingsNav.find(
        (item) => item.id === activeSection,
    )?.name;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="overflow-hidden p-0 md:max-h-[600px] md:max-w-[700px] lg:max-w-[900px]">
                <DialogTitle className="sr-only">Settings</DialogTitle>
                <DialogDescription className="sr-only">
                    Customize your settings here.
                </DialogDescription>
                <div className="flex h-[580px] overflow-hidden">
                    {/* Custom inline sidebar for dialog */}
                    <aside
                        className={cn(
                            'flex-shrink-0 border-r border-sidebar-border/50 bg-sidebar transition-all duration-200 ease-in-out',
                            sidebarOpen ? 'w-48' : 'w-14',
                        )}
                    >
                        <div className="flex h-full w-full flex-col p-2">
                            {/* User Info at the top */}
                            {auth.user && (
                                <div className="mb-4 flex items-center gap-2 px-1 py-2">
                                    <UserInfo
                                        user={auth.user}
                                        showEmail={sidebarOpen}
                                    />
                                </div>
                            )}
                            <nav className="space-y-1">
                                {settingsNav.map((item) => (
                                    <button
                                        key={item.id}
                                        onClick={() => {
                                            setActiveSection(item.id);
                                            // Close sidebar on mobile after selection
                                            if (window.innerWidth < 768) {
                                                setSidebarOpen(false);
                                            }
                                        }}
                                        className={cn(
                                            'flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                            'hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                                            activeSection === item.id
                                                ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                                                : 'text-sidebar-foreground',
                                        )}
                                        title={item.name}
                                    >
                                        <item.icon className="h-4 w-4 flex-shrink-0" />
                                        <span
                                            className={cn(
                                                'whitespace-nowrap',
                                                !sidebarOpen && 'sr-only',
                                            )}
                                        >
                                            {item.name}
                                        </span>
                                    </button>
                                ))}
                            </nav>
                        </div>
                    </aside>

                    {/* Main content */}
                    <main className="flex flex-1 flex-col overflow-hidden">
                        <header className="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/50 px-4">
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-7 w-7"
                                    onClick={() =>
                                        setSidebarOpen(!sidebarOpen)
                                    }
                                >
                                    <PanelLeft className="h-4 w-4" />
                                    <span className="sr-only">
                                        Toggle Sidebar
                                    </span>
                                </Button>
                                <Breadcrumb>
                                    <BreadcrumbList>
                                        <BreadcrumbItem className="hidden md:block">
                                            <BreadcrumbLink href="#">
                                                Settings
                                            </BreadcrumbLink>
                                        </BreadcrumbItem>
                                        <BreadcrumbSeparator className="hidden md:block" />
                                        <BreadcrumbItem>
                                            <BreadcrumbPage>
                                                {currentSectionName}
                                            </BreadcrumbPage>
                                        </BreadcrumbItem>
                                    </BreadcrumbList>
                                </Breadcrumb>
                            </div>
                        </header>
                        <div className="flex flex-1 flex-col gap-4 overflow-y-auto p-6">
                            <div className="max-w-xl">
                                {activeSection === 'profile' && (
                                    <ProfileSettings
                                        mustVerifyEmail={mustVerifyEmail}
                                        status={status}
                                    />
                                )}
                                {activeSection === 'appearance' && (
                                    <AppearanceSettings />
                                )}
                            </div>
                        </div>
                    </main>
                </div>
            </DialogContent>
        </Dialog>
    );
}

