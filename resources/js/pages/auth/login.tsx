import AuthLayout from '@/layouts/auth-layout';
import GoogleLoginButton from '@/domains/auth/components/GoogleLoginButton';
import MagicLinkForm from '@/domains/auth/components/MagicLinkForm';
import { Head, usePage } from '@inertiajs/react';

interface LoginProps {
    status?: string;
    error?: string;
}

/**
 * Legacy login page - redirects to domain version.
 * This file exists to prevent build errors for any legacy routes.
 */
export default function Login() {
    const { props } = usePage();
    const status = (props as LoginProps).status;
    const error = (props as LoginProps).error;

    return (
        <AuthLayout
            title="Log in to your account"
            description="Sign in using Google or request a magic link sent to your email"
        >
            <Head title="Log in" />

            <div className="flex flex-col gap-6">
                <GoogleLoginButton />
                <MagicLinkForm status={status} error={error} />
            </div>
        </AuthLayout>
    );
}
