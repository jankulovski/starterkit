import { Button } from '@/components/ui/button';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { ExternalLink, Loader2, RotateCcw } from 'lucide-react';
import { useState } from 'react';
import { useSubscriptionActions } from '../hooks/useSubscriptionActions';

interface BillingActionsProps {
    hasPaidPlan: boolean;
    isActiveSubscription: boolean;
    isCanceled: boolean;
}

export function BillingActions({
    hasPaidPlan,
    isActiveSubscription,
    isCanceled,
}: BillingActionsProps) {
    const {
        cancelSubscription,
        resumeSubscription,
        openBillingPortal,
        cancelProcessing,
        resumeProcessing,
        portalProcessing,
    } = useSubscriptionActions();

    const [showCancelDialog, setShowCancelDialog] = useState(false);
    const [showResumeDialog, setShowResumeDialog] = useState(false);

    const handleCancelClick = () => {
        setShowCancelDialog(true);
    };

    const handleResumeClick = () => {
        setShowResumeDialog(true);
    };

    const confirmCancel = () => {
        cancelSubscription();
        setShowCancelDialog(false);
    };

    const confirmResume = () => {
        resumeSubscription();
        setShowResumeDialog(false);
    };

    if (!hasPaidPlan) {
        return null;
    }

    return (
        <>
            <div className="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    onClick={openBillingPortal}
                    disabled={portalProcessing}
                >
                    {portalProcessing ? (
                        <>
                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            Loading...
                        </>
                    ) : (
                        <>
                            <ExternalLink className="mr-2 h-4 w-4" />
                            Manage billing
                        </>
                    )}
                </Button>

                {isActiveSubscription && (
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={handleCancelClick}
                        disabled={cancelProcessing}
                        className="text-red-600 hover:text-red-700 hover:bg-red-50"
                    >
                        {cancelProcessing ? (
                            <>
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                Canceling...
                            </>
                        ) : (
                            'Cancel subscription'
                        )}
                    </Button>
                )}

                {isCanceled && (
                    <Button
                        variant="default"
                        size="sm"
                        onClick={handleResumeClick}
                        disabled={resumeProcessing}
                    >
                        {resumeProcessing ? (
                            <>
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                Resuming...
                            </>
                        ) : (
                            <>
                                <RotateCcw className="mr-2 h-4 w-4" />
                                Resume subscription
                            </>
                        )}
                    </Button>
                )}
            </div>

            {/* Cancel Confirmation Dialog */}
            <AlertDialog open={showCancelDialog} onOpenChange={setShowCancelDialog}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Cancel subscription?</AlertDialogTitle>
                        <AlertDialogDescription>
                            You will retain access until the end of your billing period.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={confirmCancel}>
                            Confirm
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            {/* Resume Confirmation Dialog */}
            <AlertDialog open={showResumeDialog} onOpenChange={setShowResumeDialog}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Resume subscription?</AlertDialogTitle>
                        <AlertDialogDescription>
                            You will continue to be billed at the end of your current billing
                            period.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={confirmResume}>
                            Confirm
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
