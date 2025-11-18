import * as React from 'react';

type SettingsSection = 'profile' | 'appearance';

interface SettingsDialogContextType {
    open: boolean;
    setOpen: (open: boolean) => void;
    activeSection: SettingsSection;
    setActiveSection: (section: SettingsSection) => void;
    openSettings: (section?: SettingsSection) => void;
}

const SettingsDialogContext = React.createContext<
    SettingsDialogContextType | undefined
>(undefined);

export function SettingsDialogProvider({
    children,
}: {
    children: React.ReactNode;
}) {
    const [open, setOpen] = React.useState(false);
    const [activeSection, setActiveSection] =
        React.useState<SettingsSection>('profile');

    const openSettings = React.useCallback(
        (section: SettingsSection = 'profile') => {
            setActiveSection(section);
            setOpen(true);
        },
        [],
    );

    const value = React.useMemo(
        () => ({
            open,
            setOpen,
            activeSection,
            setActiveSection,
            openSettings,
        }),
        [open, activeSection, openSettings],
    );

    return (
        <SettingsDialogContext.Provider value={value}>
            {children}
        </SettingsDialogContext.Provider>
    );
}

export function useSettingsDialog() {
    const context = React.useContext(SettingsDialogContext);
    if (context === undefined) {
        throw new Error(
            'useSettingsDialog must be used within a SettingsDialogProvider',
        );
    }
    return context;
}

