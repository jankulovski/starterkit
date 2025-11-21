import { useForm, router } from '@inertiajs/react';

interface UseCreditPurchaseReturn {
    purchaseCredits: (packKey: string) => void;
    processing: boolean;
}

export function useCreditPurchase(): UseCreditPurchaseReturn {
    const creditPurchaseForm = useForm({
        pack_key: '',
    });

    const purchaseCredits = (packKey: string) => {
        creditPurchaseForm.setData('pack_key', packKey);
        creditPurchaseForm.post('/billing/credits/purchase', {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({ only: ['auth'] });
            },
        });
    };

    return {
        purchaseCredits,
        processing: creditPurchaseForm.processing,
    };
}
