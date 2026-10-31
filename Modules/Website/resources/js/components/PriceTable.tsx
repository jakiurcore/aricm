import { PlusIcon, Trash2Icon } from 'lucide-react';
import { forwardRef, useCallback, useEffect, useImperativeHandle, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { api, ApiError } from '@core/js/api';
import { ConfirmDialog } from '@core/js/components/ConfirmDialog';
import { DesignTable, Pill, Td, Th, toAsciiDigits } from '@core/js/components/design';
import { EmptyState, ErrorState, LoadingState } from '@core/js/components/States';
import { IconButton, ListCard, ListCardHead, StickyBar } from '@core/js/components/Surface';
import { locale, t } from '@core/js/i18n';

import { moved } from '../types';
import type { PriceRow, WebsiteItem } from '../types';
import { ReorderButtons } from './ReorderButtons';

const k = (key: string, replacements?: Record<string, string | number>) => t(`website::admin.ui.${key}`, replacements);

interface Row {
    /** The server id, or null for a row not saved yet. */
    id: number | null;
    tmp: number;
    label_bn: string;
    label_en: string;
    note_bn: string;
    note_en: string;
    prices: Record<string, string>;
    dirty: boolean;
}

export interface PriceTableHandle {
    reload: () => void;
}

let counter = 0;

const fromServer = (row: PriceRow): Row => ({
    id: row.id,
    tmp: ++counter,
    label_bn: row.label_bn,
    label_en: row.label_en ?? '',
    note_bn: row.note_bn ?? '',
    note_en: row.note_en ?? '',
    prices: Object.fromEntries(Object.entries(row.prices).map(([key, value]) => [key, String(value)])),
    dirty: false,
});

/** The fee table of the admission page: one row per fee item, one price column per division. */
export const PriceTable = forwardRef<PriceTableHandle, { editable?: boolean }>(function PriceTable({ editable = true }, ref) {
    const [rows, setRows] = useState<Row[] | null>(null);
    const [columns, setColumns] = useState<WebsiteItem[]>([]);
    const [failed, setFailed] = useState(false);
    const [saving, setSaving] = useState(false);
    const [deleting, setDeleting] = useState<Row | null>(null);
    const [rowErrors, setRowErrors] = useState<Record<number, string>>({});

    const load = useCallback(() => {
        setFailed(false);
        api.get<{ data: PriceRow[]; columns: WebsiteItem[] }>('/website/price-rows')
            .then((response) => {
                setRows(response.data.map(fromServer));
                setColumns(response.columns);
            })
            .catch(() => setFailed(true));
    }, []);

    useEffect(load, [load]);
    useImperativeHandle(ref, () => ({ reload: load }), [load]);

    if (failed) {
        return <ErrorState onRetry={load} />;
    }

    if (!rows) {
        return <LoadingState rows={2} height={64} />;
    }

    const patch = (tmp: number, changes: Partial<Row>) => setRows((all) => all?.map((row) => (row.tmp === tmp ? { ...row, ...changes, dirty: true } : row)) ?? null);
    const columnTitle = (column: WebsiteItem) => (locale() === 'en' && column.title_en ? column.title_en : column.title_bn);
    const columnKey = (column: WebsiteItem) => String(column.meta.key ?? '');
    const dirtyCount = rows.filter((row) => row.dirty).length;

    const payload = (row: Row) => ({
        label_bn: row.label_bn,
        label_en: row.label_en,
        note_bn: row.note_bn,
        note_en: row.note_en,
        prices: Object.fromEntries(columns.map((column) => [columnKey(column), row.prices[columnKey(column)] ?? '']).filter(([, price]) => price !== '').map(([key, price]) => [key, Number(price)])),
    });

    const save = async () => {
        setSaving(true);
        setRowErrors({});
        let failures = 0;
        const saved: Row[] = [];

        for (const row of rows) {
            if (!row.dirty) {
                saved.push(row);
                continue;
            }

            try {
                const response = row.id ? await api.put<{ data: PriceRow }>(`/website/price-rows/${row.id}`, payload(row)) : await api.post<{ data: PriceRow }>('/website/price-rows', payload(row));
                saved.push({ ...fromServer(response.data), tmp: row.tmp });
            } catch (error) {
                failures++;
                saved.push(row);
                setRowErrors((all) => ({ ...all, [row.tmp]: error instanceof ApiError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : t('core::admin.common.error') }));
            }
        }

        setRows(saved);
        setSaving(false);

        if (failures === 0) {
            toast.success(t('core::admin.common.saved'));
        } else {
            toast.error(t('core::admin.common.error'));
        }
    };

    const reorder = async (index: number, step: -1 | 1) => {
        const next = moved(rows, index, step);
        setRows(next);
        const ids = next.map((row) => row.id).filter((id): id is number => id !== null);

        try {
            await api.post('/website/price-rows/reorder', { ids });
        } catch {
            toast.error(t('core::admin.common.error'));
            load();
        }
    };

    const remove = async () => {
        if (!deleting) {
            return;
        }

        try {
            if (deleting.id) {
                await api.delete(`/website/price-rows/${deleting.id}`);
            }

            setRows((all) => all?.filter((row) => row.tmp !== deleting.tmp) ?? null);
            toast.success(k('deleted'));
        } catch {
            toast.error(t('core::admin.common.error'));
        } finally {
            setDeleting(null);
        }
    };

    const addRow = () => setRows((all) => [...(all ?? []), { id: null, tmp: ++counter, label_bn: '', label_en: '', note_bn: '', note_en: '', prices: {}, dirty: true }]);

    return (
        <fieldset disabled={!editable} className="m-0 grid min-w-0 gap-2 border-0 p-0">
            <p className="m-0 text-[13px] text-muted-foreground">{k('prices.hint')}</p>
            <ListCard>
                <ListCardHead action={editable && (
                    <Button type="button" size="sm" onClick={addRow}>
                        <PlusIcon className="size-4" />
                        {k('prices.add_row')}
                    </Button>
                )}>
                    {k('prices.title')}
                </ListCardHead>
                {columns.length === 0 && <p className="m-0 px-4 py-3 text-sm font-semibold text-danger">{k('prices.no_columns')}</p>}
                {rows.length === 0 ? (
                    <div className="p-3">
                        <EmptyState title={k('prices.empty')} glyph="৳" />
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                    <DesignTable minWidth={760 + columns.length * 120}>
                        <thead>
                            <tr className="border-b border-line bg-surface-2">
                                {editable && <Th className="w-12"><span className="sr-only">{k('order')}</span></Th>}
                                <Th>{k('prices.item')}</Th>
                                {columns.map((column) => (
                                    <Th key={column.id} className="w-32">{columnTitle(column)} (৳)</Th>
                                ))}
                                <Th>{k('prices.note')}</Th>
                                {editable && <Th className="w-14"><span className="sr-only">{t('core::admin.common.delete')}</span></Th>}
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((row, index) => (
                                <tr key={row.tmp} className="border-b border-line align-top last:border-b-0" data-testid="price-row">
                                    {editable && (
                                        <Td>
                                            <ReorderButtons index={index} count={rows.length} onMove={(step) => void reorder(index, step)} />
                                        </Td>
                                    )}
                                    <Td>
                                        <div className="grid gap-1.5">
                                            <Input aria-label={`${k('prices.item')} (${k('bangla')})`} lang="bn" placeholder={k('bangla')} value={row.label_bn} onChange={(event) => patch(row.tmp, { label_bn: event.target.value })} />
                                            <Input aria-label={`${k('prices.item')} (${k('english')})`} lang="en" placeholder={k('english')} value={row.label_en} onChange={(event) => patch(row.tmp, { label_en: event.target.value })} />
                                            {row.dirty && <Pill tone="warn" className="justify-self-start">{k('prices.row_unsaved')}</Pill>}
                                            {rowErrors[row.tmp] && <span className="text-[13px] font-medium text-danger">{rowErrors[row.tmp]}</span>}
                                        </div>
                                    </Td>
                                    {columns.map((column) => (
                                        <Td key={column.id}>
                                            <div className="relative">
                                                <span className="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-muted-foreground" aria-hidden="true">৳</span>
                                                <Input
                                                    inputMode="numeric"
                                                    className="pl-7"
                                                    aria-label={`${row.label_bn || k('prices.item')} - ${columnTitle(column)}`}
                                                    value={row.prices[columnKey(column)] ?? ''}
                                                    onChange={(event) => patch(row.tmp, { prices: { ...row.prices, [columnKey(column)]: toAsciiDigits(event.target.value).replace(/\D/g, '') } })}
                                                />
                                            </div>
                                        </Td>
                                    ))}
                                    <Td>
                                        <div className="grid gap-1.5">
                                            <Input aria-label={`${k('prices.note')} (${k('bangla')})`} lang="bn" placeholder={k('bangla')} value={row.note_bn} onChange={(event) => patch(row.tmp, { note_bn: event.target.value })} />
                                            <Input aria-label={`${k('prices.note')} (${k('english')})`} lang="en" placeholder={k('english')} value={row.note_en} onChange={(event) => patch(row.tmp, { note_en: event.target.value })} />
                                        </div>
                                    </Td>
                                    {editable && (
                                        <Td>
                                            <IconButton tone="danger" aria-label={t('core::admin.common.delete')} onClick={() => (row.id === null ? setRows((all) => all?.filter((item) => item.tmp !== row.tmp) ?? null) : setDeleting(row))}>
                                                <Trash2Icon className="size-4" />
                                            </IconButton>
                                        </Td>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </DesignTable>
                    </div>
                )}
            </ListCard>
            {editable && (
                <StickyBar className="items-center">
                    {dirtyCount > 0 && <Pill tone="warn" size="md">{k('unsaved', { count: dirtyCount })}</Pill>}
                    <Button size="lg" onClick={() => void save()} disabled={saving || dirtyCount === 0 || rows.some((row) => row.dirty && !row.label_bn.trim())}>
                        {saving ? t('core::admin.common.saving') : k('save_changes')}
                    </Button>
                </StickyBar>
            )}
            <ConfirmDialog open={deleting !== null} description={deleting?.label_bn} onConfirm={() => void remove()} onClose={() => setDeleting(null)} />
        </fieldset>
    );
});
