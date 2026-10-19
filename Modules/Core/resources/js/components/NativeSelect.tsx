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
            className={cn('h-8 w-full min-w-0 rounded-lg border border-input bg-transparent px-2.5 py-1 text-sm outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 disabled:opacity-50', className)}
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
