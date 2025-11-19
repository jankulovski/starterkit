export interface Plan {
    key: string;
    name: string;
    type: 'free' | 'paid';
    interval?: 'monthly' | 'yearly';
    stripe_price_id?: string | null;
    monthly_credits: number;
    features: string[];
}

export interface CreditPack {
    key: string;
    name: string;
    credits: number;
    stripe_price_id?: string | null;
}

export interface Subscription {
    status: 'none' | 'active' | 'canceled' | 'expired';
    stripe_id?: string | null;
    stripe_status?: string | null;
    next_billing_date?: string | null;
}

export interface CreditTransaction {
    id: number;
    amount: number;
    type: 'subscription' | 'purchase' | 'usage' | 'admin_adjustment';
    description?: string | null;
    metadata?: Record<string, any> | null;
    created_at: string;
}

export interface BillingData {
    currentPlan: Plan | null;
    availablePlans: Plan[];
    creditPacks: CreditPack[];
    subscription: Subscription;
    credits: {
        balance: number;
        monthly_allocation: number;
    };
    stripe_customer_id?: string | null;
}

