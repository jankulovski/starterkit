import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Coins } from 'lucide-react';

interface CreditBalanceProps {
    balance: number;
    monthlyAllocation: number;
}

export function CreditBalance({ balance, monthlyAllocation }: CreditBalanceProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Coins className="h-4 w-4" />
                    Credit Balance
                </CardTitle>
                <CardDescription>Your available credits for AI features</CardDescription>
            </CardHeader>
            <CardContent>
                <div className="space-y-2">
                    <div className="flex items-baseline gap-2">
                        <span className="text-3xl font-bold">{balance}</span>
                        <span className="text-muted-foreground text-sm">credits</span>
                    </div>
                    {monthlyAllocation > 0 && (
                        <p className="text-muted-foreground text-sm">
                            {monthlyAllocation} credits/month included with your plan
                        </p>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

