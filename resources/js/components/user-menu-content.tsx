import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { useSettingsDialog } from '@/hooks/use-settings-dialog';
import { logout } from '@/routes';
import { type User } from '@/types';
import { Link, router } from '@inertiajs/react';
import { CreditCard, LogOut, Settings, Shield } from 'lucide-react';

interface UserMenuContentProps {
    user: User;
}

export function UserMenuContent({ user }: UserMenuContentProps) {
    const cleanup = useMobileNavigation();
    const { openSettings } = useSettingsDialog();

    const handleLogout = () => {
        cleanup();
        router.flushAll();
    };

    const handleOpenSettings = () => {
        cleanup();
        openSettings('profile');
    };

    const handleOpenBilling = () => {
        cleanup();
        openSettings('billing');
    };

    return (
        <>
            <DropdownMenuLabel className="p-0 font-normal">
                <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                    <UserInfo user={user} showEmail={true} />
                </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
                <DropdownMenuItem onClick={handleOpenSettings}>
                        <Settings className="mr-2" />
                        Settings
                </DropdownMenuItem>
                <DropdownMenuItem onClick={handleOpenBilling}>
                        <CreditCard className="mr-2" />
                        Billing
                </DropdownMenuItem>
                {user.is_admin && (
                    <DropdownMenuItem asChild>
                        <Link
                            href="/admin"
                            className="block w-full"
                            onClick={cleanup}
                        >
                            <Shield className="mr-2" />
                            Admin
                        </Link>
                    </DropdownMenuItem>
                )}
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuItem asChild>
                <Link
                    className="block w-full"
                    href={logout()}
                    as="button"
                    onClick={handleLogout}
                    data-test="logout-button"
                >
                    <LogOut className="mr-2" />
                    Log out
                </Link>
            </DropdownMenuItem>
        </>
    );
}
