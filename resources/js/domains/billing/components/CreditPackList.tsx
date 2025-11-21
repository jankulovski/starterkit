import { Button } from '@/components/ui/button';
import { CreditCard, Coins, Loader2 } from 'lucide-react';
import { type CreditPack } from '@/types/billing';
import { useCreditPurchase } from '../hooks/useCreditPurchase';
import { formatCredits } from '../utils/formatters';

interface CreditPackListProps {
    creditPacks: CreditPack[];
}

export function CreditPackList({ creditPacks }: CreditPackListProps) {
    const { purchaseCredits, processing } = useCreditPurchase();

    if (creditPacks.length === 0) {
        return null;
    }

    return (
        <div className="space-y-3">
            {creditPacks.map((pack) => {
                const canPurchase = !!pack.stripe_price_id;

                return (
                    <div
                        key={pack.key}
                        className={`group relative rounded-lg border transition-all ${
                            canPurchase
                                ? 'hover:border-primary/50 hover:bg-accent/50'
                                : 'opacity-60'
                        }`}
                    >
                        <div className="p-6">
                            <div className="flex items-center justify-between gap-4">
                                <div className="flex items-center gap-4">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                                        <Coins className="h-5 w-5 text-primary" />
                                    </div>
                                    <div className="space-y-1">
                                        <p className="text-base font-semibold">{pack.name}</p>
                                        <p className="text-sm text-muted-foreground">
                                            {formatCredits(pack.credits)}
                                        </p>
                                    </div>
                                </div>

                                <Button
                                    onClick={() => purchaseCredits(pack.key)}
                                    disabled={processing || !canPurchase}
                                    size="sm"
                                >
                                    {processing ? (
                                        <>
                                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                            Processing
                                        </>
                                    ) : (
                                        <>
                                            <CreditCard className="mr-2 h-4 w-4" />
                                            Purchase
                                        </>
                                    )}
                                </Button>
                            </div>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
