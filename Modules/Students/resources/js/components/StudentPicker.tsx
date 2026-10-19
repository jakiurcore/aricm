import { useEffect, useRef, useState } from 'react';

import { Input } from '@/components/ui/input';
import { api } from '@core/js/api';
import { t } from '@core/js/i18n';

export interface PickedStudent {
    id: number;
    student_id: string;
    name: string;
    class: string | null;
    roll: number | null;
}

interface Props {
    value: PickedStudent | null;
    onChange: (student: PickedStudent | null) => void;
    placeholder?: string;
    autoFocus?: boolean;
}

/** Type-ahead student search (name, ID or guardian phone). Used wherever another module needs to point at a student. */
export function StudentPicker({ value, onChange, placeholder, autoFocus }: Props) {
    const [term, setTerm] = useState('');
    const [results, setResults] = useState<PickedStudent[]>([]);
    const timer = useRef<ReturnType<typeof setTimeout>>(undefined);

    useEffect(() => {
        clearTimeout(timer.current);

        if (term.trim().length < 2) {
            setResults([]);

            return;
        }

        timer.current = setTimeout(() => void api.get<{ data: PickedStudent[] }>('/students/lookup', { q: term }).then((response) => setResults(response.data)).catch(() => setResults([])), 250);

        return () => clearTimeout(timer.current);
    }, [term]);

    if (value) {
        return (
            <div className="flex items-center justify-between rounded-lg border bg-muted/40 px-3 py-2 text-sm">
                <span>
                    <strong>{value.name}</strong> · {value.student_id} {value.class && <span className="text-muted-foreground">· {value.class}</span>}
                </span>
                <button type="button" className="text-xs underline" onClick={() => onChange(null)}>
                    {t('core::admin.common.edit')}
                </button>
            </div>
        );
    }

    return (
        <div className="relative">
            <Input autoFocus={autoFocus} placeholder={placeholder ?? t('students::admin.search')} value={term} onChange={(event) => setTerm(event.target.value)} />
            {results.length > 0 && (
                <ul className="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-lg border bg-popover shadow-md">
                    {results.map((student) => (
                        <li key={student.id}>
                            <button type="button" className="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-muted" onClick={() => { onChange(student); setTerm(''); setResults([]); }}>
                                <span>{student.name}</span>
                                <span className="text-xs text-muted-foreground">{student.student_id} · {student.class}</span>
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
