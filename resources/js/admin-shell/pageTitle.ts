import { useEffect, useSyncExternalStore } from 'react';

/**
 * A sub-page the menu does not list (a student's profile, the edit form, an exam's detail) names itself in the top bar,
 * as the design does: `usePageTitle(t('students::admin.form.edit_title'))`. The menu entry's label is the default.
 */
let override: string | null = null;
const listeners = new Set<() => void>();

function emit(): void {
    listeners.forEach((listener) => listener());
}

export function usePageTitle(title: string | null): void {
    useEffect(() => {
        override = title;
        emit();

        return () => {
            override = null;
            emit();
        };
    }, [title]);
}

export function usePageTitleOverride(): string | null {
    return useSyncExternalStore(
        (listener) => {
            listeners.add(listener);

            return () => listeners.delete(listener);
        },
        () => override,
    );
}
