/**
 * Format a feature key into a readable label
 * Example: "basic_usage" -> "Basic Usage"
 */
export function formatFeatureName(feature: string): string {
    return feature
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

/**
 * Format a date string into a localized date
 */
export function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString();
}

/**
 * Format currency amount
 */
export function formatCurrency(amount: number, currency: string = 'USD'): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
    }).format(amount / 100); // Assuming amount is in cents
}

/**
 * Format credit count with proper pluralization
 */
export function formatCredits(count: number): string {
    return `${count.toLocaleString()} ${count === 1 ? 'credit' : 'credits'}`;
}
