import { XIcon } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { api, ApiError } from '@core/js/api';
import { Chip, DesignTable, PageTitle, Th, Toggle } from '@core/js/components/design';
import { ConfirmDialog } from '@core/js/components/ConfirmDialog';
import { Field } from '@core/js/components/Field';
import { EmptyState, ErrorState, LoadingState } from '@core/js/components/States';
import { t } from '@core/js/i18n';
import { can } from '@core/js/permissions';

interface Item {
    min_percent: number | string;
    max_percent: number | string;
    grade: string;
    grade_point: number | string;
}

interface Scale {
    id?: number;
    name: string;
    is_default: boolean;
    items: Item[];
}

const k = (key: string) => t(`results::admin.scales.${key}`);
const cell = 'h-11 min-h-0 rounded-[10px] border border-line-strong bg-surface px-2 text-base text-foreground outline-none focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-yellow-400 disabled:bg-surface-2';
const blank = (): Scale => ({ name: '', is_default: false, items: [{ min_percent: 0, max_percent: 100, grade: '', grade_point: 0 }] });

/** The grading scale of the design: one 20px card, a table of bands with their inputs, "+ add row" and save underneath. */
export function GradingScalesPage() {
    const [scales, setScales] = useState<Scale[] | null>(null);
    const [failed, setFailed] = useState(false);
    const [current, setCurrent] = useState<Scale | null>(null);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [deleting, setDeleting] = useState(false);
    const editable = can('results.grading');

    const load = useCallback(async (select?: number) => {
        setFailed(false);

        try {
            const response = (await api.get<{ data: Scale[] }>('/results/grading-scales')).data;
            setScales(response);
            setCurrent((previous) => {
                const wanted = select ?? previous?.id;

                return response.find((scale) => scale.id === wanted) ?? response.find((scale) => scale.is_default) ?? response[0] ?? null;
            });
        } catch {
            setFailed(true);
        }
    }, []);

    useEffect(() => {
        void load();
    }, [load]);

    if (failed) {
        return <ErrorState onRetry={() => void load()} />;
    }

    if (!scales) {
        return <LoadingState rows={3} height={64} />;
    }

    const choose = (scale: Scale) => {
        setErrors({});
        setCurrent(scale);
    };

    const save = async () => {
        if (!current) {
            return;
        }

        setErrors({});

        try {
            const response = current.id ? await api.put<{ data: Scale }>(`/results/grading-scales/${current.id}`, current) : await api.post<{ data: Scale }>('/results/grading-scales', current);
            toast.success(t('core::admin.common.saved'));
            await load(response?.data?.id);
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
            }
            toast.error(t('core::admin.common.error'));
        }
    };

    const remove = async () => {
        if (!current?.id) {
            return;
        }

        try {
            await api.delete(`/results/grading-scales/${current.id}`);
            toast.success(t('core::admin.common.deleted'));
            setCurrent(null);
            await load();
        } catch (error) {
            toast.error(error instanceof ApiError ? error.message : t('core::admin.common.error'));
        } finally {
            setDeleting(false);
        }
    };

    const setItem = (index: number, patch: Partial<Item>) => current && setCurrent({ ...current, items: current.items.map((item, position) => (position === index ? { ...item, ...patch } : item)) });

    return (
        <div className="grid gap-3.5">
            <PageTitle>{k('title')}</PageTitle>

            {(scales.length > 1 || editable) && (
                <div className="flex flex-wrap items-center gap-1.5">
                    {scales.map((scale) => (
                        <Chip key={scale.id} active={current?.id === scale.id} onClick={() => choose(scale)}>
                            {scale.name}
                            {scale.is_default ? ' ★' : ''}
                        </Chip>
                    ))}
                    {editable && (
                        <Chip active={current !== null && !current.id} onClick={() => choose(blank())} className="border-dashed border-green-500 bg-green-50 text-green-700">
                            + {k('new')}
                        </Chip>
                    )}
                </div>
            )}

            {current === null ? (
                <EmptyState glyph="🔢" title={t('core::admin.common.empty')} />
            ) : (
                <div className="max-w-[720px] overflow-x-auto rounded-[20px] border border-line bg-surface">
                    <div className="grid gap-3 border-b border-line p-3.5 sm:grid-cols-[1fr_auto] sm:items-end">
                        <Field label={k('name')} htmlFor="scale-name" error={errors.name?.[0]}>
                            <Input id="scale-name" disabled={!editable} value={current.name} onChange={(event) => setCurrent({ ...current, name: event.target.value })} />
                        </Field>
                        <label className="flex min-h-11 items-center gap-3 text-sm font-semibold">
                            <Toggle checked={current.is_default} disabled={!editable} label={k('default')} onChange={(checked) => setCurrent({ ...current, is_default: checked })} />
                            {k('default')}
                        </label>
                    </div>
                    <DesignTable minWidth={480} className="text-base">
                        <thead>
                            <tr className="bg-surface-2 text-left">
                                <Th className="px-3.5 py-3">{k('min')}</Th>
                                <Th className="px-2 py-3">{k('max')}</Th>
                                <Th className="px-2 py-3">{k('grade')}</Th>
                                <Th className="px-2 py-3">{k('point')}</Th>
                                <Th />
                            </tr>
                        </thead>
                        <tbody>
                            {current.items.map((item, index) => (
                                <tr key={index} className="border-t border-line">
                                    <td className="px-3.5 py-1.5">
                                        <input className={cell} style={{ width: 80 }} type="number" disabled={!editable} aria-label={k('min')} value={item.min_percent} onChange={(event) => setItem(index, { min_percent: event.target.value })} />
                                    </td>
                                    <td className="px-2 py-1.5">
                                        <input className={cell} style={{ width: 80 }} type="number" disabled={!editable} aria-label={k('max')} value={item.max_percent} onChange={(event) => setItem(index, { max_percent: event.target.value })} />
                                    </td>
                                    <td className="px-2 py-1.5">
                                        <input className={`${cell} font-semibold`} style={{ width: 70, fontFamily: 'Poppins, sans-serif' }} disabled={!editable} aria-label={k('grade')} value={item.grade} onChange={(event) => setItem(index, { grade: event.target.value })} />
                                    </td>
                                    <td className="px-2 py-1.5">
                                        <input className={cell} style={{ width: 80 }} type="number" step="0.01" disabled={!editable} aria-label={k('point')} value={item.grade_point} onChange={(event) => setItem(index, { grade_point: event.target.value })} />
                                    </td>
                                    <td className="px-3.5 py-1.5">
                                        {editable && (
                                            <button type="button" aria-label={t('core::admin.common.delete')} onClick={() => setCurrent({ ...current, items: current.items.filter((_, position) => position !== index) })} className="grid size-10 cursor-pointer place-items-center rounded-[10px] border border-line bg-surface text-danger">
                                                <XIcon className="size-4" />
                                            </button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </DesignTable>
                    {errors.items && <p className="px-3.5 pb-2 text-[13px] font-medium text-danger">{errors.items[0]}</p>}
                    {editable && (
                        <div className="flex flex-wrap justify-between gap-2 border-t border-line px-3.5 py-3">
                            <Button variant="outline" className="border-dashed border-green-500 bg-green-50 font-bold text-green-700 hover:bg-green-50" onClick={() => setCurrent({ ...current, items: [...current.items, { min_percent: '', max_percent: '', grade: '', grade_point: '' }] })}>
                                + {k('add_band')}
                            </Button>
                            <span className="flex gap-2">
                                {current.id && !current.is_default && (
                                    <Button variant="outline" className="border-red-600 bg-red-50 text-danger hover:bg-red-50" onClick={() => setDeleting(true)}>
                                        {t('core::admin.common.delete')}
                                    </Button>
                                )}
                                <Button onClick={() => void save()}>{t('core::admin.common.save')}</Button>
                            </span>
                        </div>
                    )}
                </div>
            )}

            <ConfirmDialog open={deleting} description={current?.name} onConfirm={() => void remove()} onClose={() => setDeleting(false)} />
        </div>
    );
}
