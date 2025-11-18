import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import HeadingSmall from '@/components/heading-small';
import { Head } from '@inertiajs/react';

/**
 * Password settings are disabled in this application.
 * This stub file exists to prevent build errors.
 * Users authenticate via Google OAuth or Magic Link only.
 */
export default function Password() {
    return (
        <AppLayout>
            <Head title="Password settings" />
            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Password settings"
                        description="Password management is not available in this application."
                    />
                    <div className="text-center text-muted-foreground">
                        <p>This application uses Google OAuth and Magic Link for authentication.</p>
                        <p className="mt-2">Password management is not available.</p>
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
