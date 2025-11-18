import AuthLayout from '@/layouts/auth-layout';
import { Head } from '@inertiajs/react';

/**
 * Password confirmation is disabled in this application.
 * This stub file exists to prevent build errors.
 * Users authenticate via Google OAuth or Magic Link only.
 */
export default function ConfirmPassword() {
    return (
        <AuthLayout
            title="Password Confirmation Disabled"
            description="This application uses Google OAuth and Magic Link for authentication."
        >
            <Head title="Confirm Password" />
            <div className="text-center text-muted-foreground">
                <p>Password confirmation is not available.</p>
                <p className="mt-2">Please use Google OAuth or Magic Link to sign in.</p>
            </div>
        </AuthLayout>
    );
}
