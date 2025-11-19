import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Lock, ArrowRight } from 'lucide-react';
import { useSettingsDialog } from '@/hooks/use-settings-dialog';

interface PlanPaywallProps {
    requiredPlan?: string;
    featureName?: string;
}

export function PlanPaywall({ requiredPlan, featureName }: PlanPaywallProps) {
    const { openSettings } = useSettingsDialog();

    const handleUpgrade = () => {
        openSettings('billing');
    };

    return (
        <Card className="border-destructive">
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Lock className="h-4 w-4" />
                    Upgrade Required
                </CardTitle>
                <CardDescription>
                    {featureName
                        ? `This feature requires a ${requiredPlan || 'paid'} plan.`
                        : `This feature requires a ${requiredPlan || 'paid'} plan.`}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Button onClick={handleUpgrade}>
                    Upgrade Plan
                    <ArrowRight className="ml-2 h-4 w-4" />
                </Button>
            </CardContent>
        </Card>
    );
}

