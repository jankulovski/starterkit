import AuthLayout from '@/layouts/auth-layout';
import { Head } from '@inertiajs/react';

/**
 * Registration is disabled in this application.
 * This stub file exists to prevent build errors.
 * Users are auto-created via Google OAuth or Magic Link.
 */
export default function Register() {
    return (
        <AuthLayout
            title="Registration Disabled"
            description="This application uses Google OAuth and Magic Link for authentication."
        >
            <Head title="Register" />
            <div className="text-center text-muted-foreground">
                <p>Registration is not available.</p>
                <p className="mt-2">Please use Google OAuth or Magic Link to sign in.</p>
            </div>
        </AuthLayout>
    );
}
