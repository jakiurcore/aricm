import { CopyIcon } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';

import { useAcademicOptions } from '@academic/js/hooks/useAcademicOptions';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { api, ApiError } from '@core/js/api';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { t } from '@core/js/i18n';
import { poishaToTaka } from '@core/js/money';

interface Head {
    id: number;
    name_bn: string;
    type: string;
    is_active: boolean;
}

interface Cell {
    class_id: number;
    fee_head_id: number;
    residency_type: string | null;
    amount: number;
}

const k = (key: string, replacements: Record<string, string | number> = {}) => t(`fees::admin.structure.${key}`, replacements);

/** Editable matrix: classes down, fee heads across, with a residential column per head when it differs. */
export function StructurePage() {
    const academic = useAcademicOptions();
    const [sessionId, setSessionId] = useState('');
    const [heads, setHeads] = useState<Head[]>([]);
    const [cells, setCells] = useState<Record<string, string>>({});
    const [dirty, setDirty] = useState<Set<string>>(new Set());
    const [copyFrom, setCopyFrom] = useState('');
    const [showResidential, setShowResidential] = useState(false);

    const keyOf = (classId: number, headId: number, residency: string | null) => `${classId}|${headId}|${residency ?? 'all'}`;

    useEffect(() => {
        void api.get<{ data: Head[] }>('/fees/heads').then((response) => setHeads(response.data.filter((head) => head.is_active && head.type !== 'exam')));
    }, []);

    useEffect(() => {
        if (!sessionId && academic.current_session_id) {
            setSessionId(String(academic.current_session_id));
        }
    }, [academic.current_session_id, sessionId]);

    const load = useCallback(async () => {
        if (!sessionId) {
            return;
        }

        const response = await api.get<{ data: Cell[] }>('/fees/structures', { session_id: sessionId });
        setCells(Object.fromEntries(response.data.map((cell) => [keyOf(cell.class_id, cell.fee_head_id, cell.residency_type), poishaToTaka(cell.amount)])));
        setShowResidential(response.data.some((cell) => cell.residency_type !== null));
        setDirty(new Set());
    }, [sessionId]);

    useEffect(() => {
        void load();
    }, [load]);

    const columns = useMemo(() => heads.flatMap((head) => [{ head, residency: null as string | null }, ...(showResidential ? [{ head, residency: 'residential' }] : [])]), [heads, showResidential]);

    const change = (key: string, value: string) => {
        setCells((current) => ({ ...current, [key]: value }));
        setDirty((current) => new Set(current).add(key));
    };

    const save = async () => {
        const rows = [...dirty].map((key) => {
            const [classId, headId, residency] = key.split('|');

            return { class_id: Number(classId), fee_head_id: Number(headId), residency_type: residency === 'all' ? null : residency, amount: cells[key] ?? '' };
        });

        try {
            await api.put('/fees/structures', { session_id: Number(sessionId), rows });
            toast.success(t('core::admin.common.saved'));
            await load();
        } catch (error) {
            toast.error(error instanceof ApiError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : t('core::admin.common.error'));
        }
    };

    const copy = async () => {
        const response = await api.post<{ data: { copied: number } }>('/fees/structures/copy', { from_session_id: Number(copyFrom), to_session_id: Number(sessionId) });
        toast.success(k('copied', { count: response.data.copied }));
        await load();
    };

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <h1 className="text-2xl font-semibold tracking-tight">{k('title')}</h1>
                <div className="flex flex-wrap items-center gap-2">
                    <NativeSelect className="w-auto" aria-label={t('students::admin.fields.session_id')} value={sessionId} onChange={(event) => setSessionId(event.target.value)} options={academic.sessions.map((session) => ({ value: session.id, label: session.name }))} />
                    <label className="flex items-center gap-2 text-sm">
                        <input type="checkbox" checked={showResidential} onChange={(event) => setShowResidential(event.target.checked)} />
                        {k('residential')}
                    </label>
                    <NativeSelect className="w-auto" placeholder={k('copy_from')} value={copyFrom} onChange={(event) => setCopyFrom(event.target.value)} options={academic.sessions.filter((session) => String(session.id) !== sessionId).map((session) => ({ value: session.id, label: session.name }))} />
                    <Button variant="outline" disabled={!copyFrom} onClick={() => void copy()}>
                        <CopyIcon /> {k('copy')}
                    </Button>
                    <Button disabled={dirty.size === 0} onClick={() => void save()}>
                        {t('core::admin.common.save')}
                    </Button>
                </div>
            </div>
            <p className="text-sm text-muted-foreground">{k('hint')}</p>

            <div className="overflow-auto rounded-xl border bg-card">
                <table className="min-w-full border-collapse text-sm">
                    <thead>
                        <tr className="border-b bg-muted/50">
                            <th className="sticky left-0 z-10 bg-muted p-2 text-left">{t('students::admin.fields.class_id')}</th>
                            {columns.map(({ head, residency }) => (
                                <th key={`${head.id}-${residency}`} className="border-l p-2 text-center">
                                    {head.name_bn}
                                    {residency && <div className="text-xs font-normal text-muted-foreground">{k('residential_only')}</div>}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {academic.classes.map((schoolClass) => (
                            <tr key={schoolClass.id} className="border-b last:border-0">
                                <td className="sticky left-0 bg-card p-2 font-medium">{schoolClass.name}</td>
                                {columns.map(({ head, residency }) => {
                                    const key = keyOf(schoolClass.id, head.id, residency);

                                    return (
                                        <td key={key} className="border-l p-0">
                                            <Input className={`h-10 w-28 rounded-none border-0 text-right ${dirty.has(key) ? 'bg-amber-50' : ''}`} inputMode="decimal" value={cells[key] ?? ''} onChange={(event) => change(key, event.target.value)} aria-label={`${schoolClass.name} ${head.name_bn}`} />
                                        </td>
                                    );
                                })}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
