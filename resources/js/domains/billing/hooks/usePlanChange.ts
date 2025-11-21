import { useState } from 'react';
import { router } from '@inertiajs/react';
import { type Plan } from '@/types/billing';
import { getInertiaHeaders, getPlanChangeConfirmation } from '../utils/billing-helpers';

interface UsePlanChangeReturn {
    processing: boolean;
    error: string | null;
    executePlanChange: (targetPlan: Plan) => Promise<void>;
    getConfirmation: (currentPlan: Plan | null, targetPlan: Plan) => {
        title: string;
        description: string;
    };
}

export function usePlanChange(onSuccess?: (message?: string) => void): UsePlanChangeReturn {
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const executePlanChange = async (targetPlan: Plan) => {
        setProcessing(true);
        setError(null);

        try {
            const response = await fetch('/billing/change-subscription', {
                method: 'POST',
                headers: getInertiaHeaders(),
                credentials: 'same-origin',
                body: JSON.stringify({ plan_key: targetPlan.key }),
            });

            if (response.ok) {
                const data = await response.json();

                // If upgrading from free, redirect to checkout
                if (data.checkout_url) {
                    window.location.href = data.checkout_url;
                } else {
                    // Show success message and reload
                    if (data.message && onSuccess) {
                        onSuccess(data.message);
                    }
                    // Reload the page to show updated plan
                    router.reload();
                    setProcessing(false);
                }
            } else {
                const data = await response.json().catch(() => ({
                    message: 'Failed to change plan'
                }));
                setError(data.message || 'Failed to change plan');
                setProcessing(false);
            }
        } catch (err) {
            setError('An error occurred while processing your request');
            setProcessing(false);
        }
    };

    const getConfirmation = (currentPlan: Plan | null, targetPlan: Plan) => {
        return getPlanChangeConfirmation(currentPlan, targetPlan);
    };

    return {
        processing,
        error,
        executePlanChange,
        getConfirmation,
    };
}
