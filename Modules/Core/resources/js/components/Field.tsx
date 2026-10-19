import type { ReactNode } from 'react';

import { Label } from '@/components/ui/label';

interface FieldProps {
    label: string;
    htmlFor?: string;
    error?: string;
    hint?: string;
    children: ReactNode;
}

export function Field({ label, htmlFor, error, hint, children }: FieldProps) {
    return (
        <div className="grid gap-1">
            <Label htmlFor={htmlFor}>{label}</Label>
            {children}
            {hint && !error && <p className="text-[13px] text-muted-foreground">{hint}</p>}
            {error && <p className="text-[13px] font-medium text-danger">{error}</p>}
        </div>
    );
}
