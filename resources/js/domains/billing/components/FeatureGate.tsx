import { type PropsWithChildren } from 'react';
import { PlanPaywall } from './PlanPaywall';
import { CreditsPaywall } from './CreditsPaywall';
import { usePage } from '@inertiajs/react';
import { type SharedData } from '@/types';

interface FeatureGateProps {
    feature: string;
    creditCost?: number;
    requiredPlan?: string;
    fallback?: React.ReactNode;
}

export function FeatureGate({
    feature,
    creditCost = 0,
    requiredPlan,
    fallback,
    children,
}: PropsWithChildren<FeatureGateProps>) {
    const { auth } = usePage<SharedData>().props;
    const user = auth.user;

    if (!user) {
        return fallback || <PlanPaywall requiredPlan={requiredPlan} featureName={feature} />;
    }

    const currentPlan = user.billing?.current_plan;
    const creditsBalance = user.billing?.credits_balance ?? 0;

    // Check plan requirement
    if (requiredPlan && currentPlan?.key !== requiredPlan) {
        const planHierarchy = ['free', 'pro', 'business'];
        const currentPlanIndex = planHierarchy.indexOf(currentPlan?.key ?? 'free');
        const requiredPlanIndex = planHierarchy.indexOf(requiredPlan);

        if (currentPlanIndex < requiredPlanIndex) {
            return fallback || <PlanPaywall requiredPlan={requiredPlan} featureName={feature} />;
        }
    }

    // Check credit requirement
    if (creditCost > 0 && creditsBalance < creditCost) {
        return (
            fallback || <CreditsPaywall required={creditCost} current={creditsBalance} />
        );
    }

    return <>{children}</>;
}

