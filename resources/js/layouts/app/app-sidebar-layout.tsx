import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { SettingsDialog } from '@/components/settings-dialog';
import {
    SettingsDialogProvider,
    useSettingsDialog,
} from '@/hooks/use-settings-dialog';
import { type BreadcrumbItem } from '@/types';
import { type PropsWithChildren } from 'react';

function AppSidebarLayoutContent({
    children,
    breadcrumbs = [],
}: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] }>) {
    const { open, setOpen, activeSection } = useSettingsDialog();

    return (
        <>
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
        </AppShell>
            <SettingsDialog
                open={open}
                onOpenChange={setOpen}
                defaultSection={activeSection}
            />
        </>
    );
}

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] }>) {
    return (
        <SettingsDialogProvider>
            <AppSidebarLayoutContent breadcrumbs={breadcrumbs}>
                {children}
            </AppSidebarLayoutContent>
        </SettingsDialogProvider>
    );
}
