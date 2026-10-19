import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { useAcademicOptions } from '@academic/js/hooks/useAcademicOptions';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { api, ApiError } from '@core/js/api';
import { PageTitle, Segmented, DesignTable, TableCard, Th, toAsciiDigits } from '@core/js/components/design';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { t } from '@core/js/i18n';
import { formatMoney, poishaToTaka } from '@core/js/money';
import { bnDigits } from '@core/js/number';
import { can } from '@core/js/permissions';

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
    const [residential, setResidential] = useState(false);

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
        setDirty(new Set());
    }, [sessionId]);

    useEffect(() => {
        void load();
    }, [load]);

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

    const residencyKey = residential ? 'residential' : null;
    const monthlyTotal = (classId: number) => heads.filter((head) => head.type === 'monthly').reduce((sum, head) => sum + (Number(cells[keyOf(classId, head.id, residencyKey)]) || 0), 0);
    const editable = can('fees.manage_structure');

    return (
        <div className="grid gap-3.5">
            <PageTitle>{k('title')}</PageTitle>
            <div className="flex flex-wrap items-end gap-2.5">
                <div className="grid flex-[0_1_150px] gap-1">
                    <Label htmlFor="structure-session">{t('students::admin.fields.session_id')}</Label>
                    <NativeSelect id="structure-session" value={sessionId} onChange={(event) => setSessionId(event.target.value)} options={academic.sessions.map((session) => ({ value: session.id, label: session.name }))} />
                </div>
                <div className="grid gap-1 text-sm font-semibold">
                    {k('residency')}
                    <Segmented label={k('residency')} value={residential ? 'residential' : 'default'} onChange={(value) => setResidential(value === 'residential')} options={[{ value: 'default', label: k('residency_default') }, { value: 'residential', label: k('residency_residential') }]} />
                </div>
                <span className="flex-1" />
                {editable && (
                    <>
                        <NativeSelect className="w-auto" aria-label={k('copy_from')} placeholder={k('copy_from')} value={copyFrom} onChange={(event) => setCopyFrom(event.target.value)} options={academic.sessions.filter((session) => String(session.id) !== sessionId).map((session) => ({ value: session.id, label: session.name }))} />
                        <Button variant="outline" disabled={!copyFrom} onClick={() => void copy()}>
                            ⟲ {k('copy')}
                        </Button>
                        <Button disabled={dirty.size === 0} onClick={() => void save()}>
                            {t('core::admin.common.save')}
                        </Button>
                    </>
                )}
            </div>

            <TableCard>
                <DesignTable minWidth={1040} className="text-sm">
                    <thead>
                        <tr className="bg-surface-2">
                            <Th className="sticky left-0 bg-surface-2 pl-3.5">{t('students::admin.fields.class_id')}</Th>
                            {heads.map((head) => (
                                <Th key={head.id} align="right">
                                    {head.name_bn}
                                    <br />
                                    <span className="text-[11px] font-semibold text-muted-foreground">{t(`fees::admin.types.${head.type}`)}</span>
                                </Th>
                            ))}
                            <Th align="right" className="pr-3.5 text-green-700">
                                {k('monthly_total')}
                            </Th>
                        </tr>
                    </thead>
                    <tbody>
                        {academic.classes.map((schoolClass) => (
                            <tr key={schoolClass.id} className="border-t border-line">
                                <td className="sticky left-0 bg-surface px-2.5 py-[9px] pl-3.5 font-bold whitespace-nowrap">{schoolClass.name}</td>
                                {heads.map((head) => {
                                    const key = keyOf(schoolClass.id, head.id, residencyKey);
                                    const changed = dirty.has(key);
                                    const filled = Number(cells[key]) > 0;

                                    return (
                                        <td key={key} className="p-1">
                                            <input
                                                inputMode="decimal"
                                                disabled={!editable}
                                                value={bnDigits(cells[key] ?? '')}
                                                onChange={(event) => change(key, toAsciiDigits(event.target.value))}
                                                aria-label={`${schoolClass.name} ${head.name_bn}`}
                                                className="h-10 w-full min-w-[84px] rounded-[10px] border px-2 text-right text-sm text-foreground outline-none focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-yellow-400"
                                                style={{ borderColor: changed ? 'var(--warn)' : 'var(--line)', background: changed ? 'var(--yellow-50)' : filled ? 'var(--surface)' : 'var(--surface-2)' }}
                                            />
                                        </td>
                                    );
                                })}
                                <td className="px-2.5 py-[9px] pr-3.5 text-right font-bold whitespace-nowrap text-green-700">{formatMoney(Math.round(monthlyTotal(schoolClass.id) * 100))}</td>
                            </tr>
                        ))}
                    </tbody>
                </DesignTable>
            </TableCard>
            <span className="text-[13px] text-muted-foreground">{k('hint')}</span>
        </div>
    );
}
