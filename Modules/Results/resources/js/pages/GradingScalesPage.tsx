import { PlusIcon, Trash2Icon } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { api, ApiError } from '@core/js/api';
import { Field } from '@core/js/components/Field';
import { t } from '@core/js/i18n';

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

export function GradingScalesPage() {
    const [scales, setScales] = useState<Scale[]>([]);
    const [editing, setEditing] = useState<Scale | null>(null);
    const [errors, setErrors] = useState<Record<string, string[]>>({});

    const load = useCallback(async () => setScales((await api.get<{ data: Scale[] }>('/results/grading-scales')).data), []);

    useEffect(() => {
        void load();
    }, [load]);

    const save = async () => {
        if (!editing) {
            return;
        }

        setErrors({});

        try {
            editing.id ? await api.put(`/results/grading-scales/${editing.id}`, editing) : await api.post('/results/grading-scales', editing);
            toast.success(t('core::admin.common.saved'));
            setEditing(null);
            await load();
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
            }
            toast.error(t('core::admin.common.error'));
        }
    };

    const remove = async (scale: Scale) => {
        try {
            await api.delete(`/results/grading-scales/${scale.id}`);
            await load();
        } catch (error) {
            toast.error(error instanceof ApiError ? error.message : t('core::admin.common.error'));
        }
    };

    const setItem = (index: number, patch: Partial<Item>) => editing && setEditing({ ...editing, items: editing.items.map((item, position) => (position === index ? { ...item, ...patch } : item)) });

    return (
        <div className="max-w-4xl space-y-4">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-semibold tracking-tight">{k('title')}</h1>
                <Button onClick={() => setEditing({ name: '', is_default: false, items: [{ min_percent: 0, max_percent: 100, grade: '', grade_point: 0 }] })}>
                    <PlusIcon /> {k('new')}
                </Button>
            </div>

            {editing && (
                <Card>
                    <CardHeader>
                        <CardTitle>{editing.id ? k('edit') : k('new')}</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <Field label={k('name')} htmlFor="scale-name" error={errors.name?.[0]}>
                            <Input id="scale-name" value={editing.name} onChange={(event) => setEditing({ ...editing, name: event.target.value })} />
                        </Field>
                        <label className="flex items-center gap-3 text-sm">
                            <Switch checked={editing.is_default} onCheckedChange={(checked) => setEditing({ ...editing, is_default: checked })} />
                            {k('default')}
                        </label>
                        <div className="space-y-2">
                            <div className="grid grid-cols-[1fr_1fr_1fr_1fr_auto] gap-2 text-xs text-muted-foreground">
                                <span>{k('min')}</span>
                                <span>{k('max')}</span>
                                <span>{k('grade')}</span>
                                <span>{k('point')}</span>
                                <span />
                            </div>
                            {editing.items.map((item, index) => (
                                <div key={index} className="grid grid-cols-[1fr_1fr_1fr_1fr_auto] gap-2">
                                    <Input type="number" value={item.min_percent} onChange={(event) => setItem(index, { min_percent: event.target.value })} />
                                    <Input type="number" value={item.max_percent} onChange={(event) => setItem(index, { max_percent: event.target.value })} />
                                    <Input value={item.grade} onChange={(event) => setItem(index, { grade: event.target.value })} />
                                    <Input type="number" step="0.01" value={item.grade_point} onChange={(event) => setItem(index, { grade_point: event.target.value })} />
                                    <Button variant="ghost" size="icon" aria-label={t('core::admin.common.delete')} onClick={() => setEditing({ ...editing, items: editing.items.filter((_, position) => position !== index) })}>
                                        <Trash2Icon />
                                    </Button>
                                </div>
                            ))}
                            {errors.items && <p className="text-sm text-destructive">{errors.items[0]}</p>}
                            <Button variant="outline" size="sm" onClick={() => setEditing({ ...editing, items: [...editing.items, { min_percent: '', max_percent: '', grade: '', grade_point: '' }] })}>
                                <PlusIcon /> {k('add_band')}
                            </Button>
                        </div>
                        <div className="flex gap-2">
                            <Button onClick={() => void save()}>{t('core::admin.common.save')}</Button>
                            <Button variant="outline" onClick={() => setEditing(null)}>
                                {t('core::admin.common.cancel')}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            )}

            {scales.map((scale) => (
                <Card key={scale.id}>
                    <CardHeader className="flex-row items-center justify-between">
                        <CardTitle className="flex items-center gap-2">
                            {scale.name} {scale.is_default && <Badge>{k('default')}</Badge>}
                        </CardTitle>
                        <div className="flex gap-2">
                            <Button variant="outline" size="sm" onClick={() => setEditing(scale)}>
                                {t('core::admin.common.edit')}
                            </Button>
                            {!scale.is_default && (
                                <Button variant="ghost" size="icon" aria-label={t('core::admin.common.delete')} onClick={() => void remove(scale)}>
                                    <Trash2Icon />
                                </Button>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-wrap gap-2 text-sm">
                            {scale.items.map((item) => (
                                <span key={`${item.grade}-${item.min_percent}`} className="rounded-md border px-2 py-1">
                                    {Number(item.min_percent)}–{Number(item.max_percent)} → <strong>{item.grade}</strong> ({Number(item.grade_point).toFixed(2)})
                                </span>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}
