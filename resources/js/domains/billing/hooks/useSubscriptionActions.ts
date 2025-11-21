import { useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import { getInertiaHeaders } from '../utils/billing-helpers';

interface UseSubscriptionActionsReturn {
    cancelSubscription: () => void;
    resumeSubscription: () => void;
    openBillingPortal: () => Promise<void>;
    cancelProcessing: boolean;
    resumeProcessing: boolean;
    portalProcessing: boolean;
    portalError: string | null;
}

export function useSubscriptionActions(): UseSubscriptionActionsReturn {
    const cancelForm = useForm({});
    const resumeForm = useForm({});
    const [portalProcessing, setPortalProcessing] = useState(false);
    const [portalError, setPortalError] = useState<string | null>(null);

    const cancelSubscription = () => {
        cancelForm.post('/billing/cancel', {
            preserveScroll: true,
            onSuccess: () => {
                router.reload();
            },
        });
    };

    const resumeSubscription = () => {
        resumeForm.post('/billing/resume', {
            preserveScroll: true,
            onSuccess: () => {
                router.reload();
            },
        });
    };

    const openBillingPortal = async () => {
        setPortalProcessing(true);
        setPortalError(null);

        try {
            const response = await fetch('/billing/portal', {
                method: 'POST',
                headers: getInertiaHeaders(),
                credentials: 'same-origin',
            });

            if (response.ok) {
                const data = await response.json();
                if (data.portal_url) {
                    window.location.href = data.portal_url;
                }
            } else {
                const data = await response.json();
                setPortalError(data.message || 'Failed to create billing portal session');
                setPortalProcessing(false);
            }
        } catch (err) {
            setPortalError('An error occurred while processing your request');
            setPortalProcessing(false);
        }
    };

    return {
        cancelSubscription,
        resumeSubscription,
        openBillingPortal,
        cancelProcessing: cancelForm.processing,
        resumeProcessing: resumeForm.processing,
        portalProcessing,
        portalError,
    };
}
