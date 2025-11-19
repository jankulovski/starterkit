import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Coins, ArrowRight } from 'lucide-react';
import { useSettingsDialog } from '@/hooks/use-settings-dialog';

interface CreditsPaywallProps {
    required: number;
    current: number;
}

export function CreditsPaywall({ required, current }: CreditsPaywallProps) {
    const { openSettings } = useSettingsDialog();

    const handleBuyCredits = () => {
        openSettings('billing');
    };

    return (
        <Card className="border-destructive">
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Coins className="h-4 w-4" />
                    Insufficient Credits
                </CardTitle>
                <CardDescription>
                    This action requires {required} credits, but you only have {current} credits
                    available.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Button onClick={handleBuyCredits}>
                    Buy Credits
                    <ArrowRight className="ml-2 h-4 w-4" />
                </Button>
            </CardContent>
        </Card>
    );
}

