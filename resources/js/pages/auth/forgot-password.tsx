import AuthLayout from '@/layouts/auth-layout';
import { Head } from '@inertiajs/react';

/**
 * Password reset is disabled in this application.
 * This stub file exists to prevent build errors.
 * Users authenticate via Google OAuth or Magic Link only.
 */
export default function ForgotPassword() {
    return (
        <AuthLayout
            title="Password Reset Disabled"
            description="This application uses Google OAuth and Magic Link for authentication."
        >
            <Head title="Forgot Password" />
            <div className="text-center text-muted-foreground">
                <p>Password reset is not available.</p>
                <p className="mt-2">Please use Google OAuth or Magic Link to sign in.</p>
            </div>
        </AuthLayout>
    );
}
