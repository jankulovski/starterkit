import { type Plan } from '@/types/billing';

/**
 * Check if a plan change is an upgrade based on monthly credits
 */
export function isUpgrade(currentPlan: Plan | null, targetPlan: Plan): boolean {
    const currentCredits = currentPlan?.monthly_credits || 0;
    const targetCredits = targetPlan.monthly_credits || 0;
    return targetCredits > currentCredits;
}

/**
 * Check if a plan change is a downgrade based on monthly credits
 */
export function isDowngrade(currentPlan: Plan | null, targetPlan: Plan): boolean {
    const currentCredits = currentPlan?.monthly_credits || 0;
    const targetCredits = targetPlan.monthly_credits || 0;
    return targetCredits < currentCredits;
}

/**
 * Get confirmation message for plan change
 */
export function getPlanChangeConfirmation(currentPlan: Plan | null, targetPlan: Plan): {
    title: string;
    description: string;
} {
    const upgrading = isUpgrade(currentPlan, targetPlan);

    const title = upgrading
        ? `Upgrade to ${targetPlan.name}?`
        : `Switch to ${targetPlan.name}?`;

    const description = upgrading
        ? `• You'll be charged a prorated amount now\n• Credits will be reset to ${targetPlan.monthly_credits}\n• New features activate immediately`
        : `• Change takes effect at your next billing date\n• Credits will adjust to ${targetPlan.monthly_credits} then\n• You keep current features until then`;

    return { title, description };
}

/**
 * Get CSRF token from meta tag
 */
export function getCsrfToken(): string {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

/**
 * Get Inertia version from meta tag
 */
export function getInertiaVersion(): string {
    return document.querySelector('meta[name="inertia-version"]')?.getAttribute('content') || '';
}

/**
 * Get headers for manual fetch requests
 */
export function getInertiaHeaders(): HeadersInit {
    return {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Inertia': 'true',
        'X-Inertia-Version': getInertiaVersion(),
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': getCsrfToken(),
    };
}
