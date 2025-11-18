import AppearanceTabs from '@/components/appearance-tabs';
import HeadingSmall from '@/components/heading-small';

export default function AppearanceSettings() {
    return (
        <div className="space-y-6">
            <HeadingSmall
                title="Appearance settings"
                description="Update your account's appearance settings"
            />
            <AppearanceTabs />
        </div>
    );
}

