import type { ReactNode } from 'react';

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

import { t } from '../i18n';

interface ConfirmDialogProps {
    open: boolean;
    title?: string;
    description?: ReactNode;
    confirmLabel?: string;
    onConfirm: () => void;
    onClose: () => void;
}

export function ConfirmDialog({ open, title, description, confirmLabel, onConfirm, onClose }: ConfirmDialogProps) {
    return (
        <AlertDialog open={open} onOpenChange={(next) => !next && onClose()}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{title ?? t('core::admin.common.confirm_delete')}</AlertDialogTitle>
                    {description && <AlertDialogDescription>{description}</AlertDialogDescription>}
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>{t('core::admin.common.cancel')}</AlertDialogCancel>
                    <AlertDialogAction onClick={onConfirm}>{confirmLabel ?? t('core::admin.common.delete')}</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
