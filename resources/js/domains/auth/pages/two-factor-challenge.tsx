import AuthLayout from '@/layouts/auth-layout';
import { Head } from '@inertiajs/react';

/**
 * Two-Factor Authentication is disabled in this application.
 * This stub file exists to prevent build errors.
 * Users authenticate via Google OAuth or Magic Link only.
 */
export default function TwoFactorChallenge() {
    return (
        <AuthLayout
            title="Two-Factor Authentication Disabled"
            description="This application uses Google OAuth and Magic Link for authentication."
        >
            <Head title="Two-Factor Authentication" />
            <div className="text-center text-muted-foreground">
                <p>Two-factor authentication is not available.</p>
                <p className="mt-2">Please use Google OAuth or Magic Link to sign in.</p>
            </div>
        </AuthLayout>
    );
}

