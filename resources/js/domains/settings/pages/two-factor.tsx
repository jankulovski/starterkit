import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import HeadingSmall from '@/components/heading-small';
import { Head } from '@inertiajs/react';

/**
 * Two-Factor Authentication is disabled in this application.
 * This stub file exists to prevent build errors.
 * Users authenticate via Google OAuth or Magic Link only.
 */
export default function TwoFactor() {
    return (
        <AppLayout>
            <Head title="Two-Factor Authentication" />
            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Two-Factor Authentication"
                        description="Two-factor authentication is not available in this application."
                    />
                    <div className="text-center text-muted-foreground">
                        <p>This application uses Google OAuth and Magic Link for authentication.</p>
                        <p className="mt-2">Two-factor authentication is not available.</p>
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
