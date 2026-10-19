import type { SelectHTMLAttributes } from 'react';

import { cn } from '@/lib/utils';

export interface Option {
    value: string | number;
    label: string;
}

interface NativeSelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
    options: Option[];
    placeholder?: string;
}

/** A styled native <select>: reliable on mobile and trivial to drive from react-hook-form. */
export function NativeSelect({ options, placeholder, className, ...props }: NativeSelectProps) {
    return (
        <select
            className={cn('h-11 w-full min-w-0 cursor-pointer rounded-xl border border-input bg-surface px-3 text-base text-foreground outline-none focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-yellow-400 disabled:cursor-not-allowed disabled:bg-surface-2 disabled:text-faint aria-invalid:border-destructive', className)}
            {...props}
        >
            {placeholder !== undefined && <option value="">{placeholder}</option>}
            {options.map((option) => (
                <option key={option.value} value={option.value}>
                    {option.label}
                </option>
            ))}
        </select>
    );
}
