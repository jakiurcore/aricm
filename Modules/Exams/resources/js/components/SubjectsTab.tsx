import { PlusIcon, Trash2Icon } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { api, ApiError } from '@core/js/api';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { t } from '@core/js/i18n';

interface Component {
    id?: number;
    name_bn: string;
    name_en?: string | null;
    full_marks: number | string;
    pass_marks: number | string;
}

interface Row {
    subject_id: number;
    name: string;
    full_marks: number | string;
    pass_marks: number | string;
    include_in_gpa: boolean;
    is_optional: boolean;
    components: Component[];
}

interface Setup {
    subjects: Row[];
    class_subjects: { id: number; name: string }[];
    other_exams: { id: number; name: string }[];
}

const k = (key: string) => t(`exams::admin.subjects.${key}`);
const TEMPLATES = ['লিখিত', 'MCQ', 'মৌখিক', 'ব্যবহারিক'];

export function SubjectsTab({ examId, classId }: { examId: number; classId: number }) {
    const [setup, setSetup] = useState<Setup | null>(null);
    const [rows, setRows] = useState<Row[]>([]);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [source, setSource] = useState('');

    const apply = (response: Setup) => {
        setSetup(response);
        setRows(response.subjects);
    };

    const load = useCallback(async () => apply(await api.get<Setup>(`/exams/${examId}/classes/${classId}`)), [examId, classId]);

    useEffect(() => {
        setErrors({});
        void load();
    }, [load]);

    if (!setup) {
        return <p className="text-muted-foreground">{t('core::admin.common.loading')}</p>;
    }

    const update = (index: number, patch: Partial<Row>) => setRows(rows.map((row, position) => (position === index ? { ...row, ...patch } : row)));
    const updateComponent = (index: number, position: number, patch: Partial<Component>) =>
        update(index, { components: rows[index].components.map((component, at) => (at === position ? { ...component, ...patch } : component)) });
    const unused = setup.class_subjects.filter((subject) => !rows.some((row) => row.subject_id === subject.id));

    const save = async () => {
        setErrors({});

        try {
            apply(await api.put<Setup>(`/exams/${examId}/classes/${classId}/subjects`, { subjects: rows }));
            toast.success(t('core::admin.common.saved'));
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
                toast.error(Object.keys(error.errors).length ? t('core::admin.common.error') : error.message);
            }
        }
    };

    const action = async (path: string) => {
        try {
            apply(await api.post<Setup>(`/exams/${examId}/classes/${classId}/subjects/${path}`));
        } catch (error) {
            toast.error(error instanceof ApiError ? error.message : t('core::admin.common.error'));
        }
    };

    const error = (key: string) => errors[key]?.[0];

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center gap-2">
                <Button variant="outline" onClick={() => void action('seed')}>
                    {k('seed')}
                </Button>
                <NativeSelect className="w-auto" placeholder={k('copy_from')} value={source} onChange={(event) => setSource(event.target.value)} options={setup.other_exams.map((exam) => ({ value: exam.id, label: exam.name }))} />
                <Button variant="outline" disabled={!source} onClick={() => void action(`copy/${source}`)}>
                    {k('copy')}
                </Button>
            </div>

            <div className="space-y-3">
                {rows.length === 0 && <p className="text-sm text-muted-foreground">{k('empty')}</p>}
                {rows.map((row, index) => (
                    <div key={row.subject_id} className="space-y-3 rounded-xl border bg-card p-4">
                        <div className="flex flex-wrap items-end gap-3">
                            <div className="min-w-40 flex-1 font-medium">{row.name}</div>
                            <label className="text-xs">
                                {k('full_marks')}
                                <Input className="w-24" type="number" disabled={row.components.length > 0} value={row.components.length > 0 ? row.components.reduce((sum, component) => sum + Number(component.full_marks || 0), 0) : row.full_marks} onChange={(event) => update(index, { full_marks: event.target.value })} />
                            </label>
                            <label className="text-xs">
                                {k('pass_marks')}
                                <Input className="w-24" type="number" value={row.pass_marks} onChange={(event) => update(index, { pass_marks: event.target.value })} />
                                {error(`subjects.${index}.pass_marks`) && <span className="text-destructive">{error(`subjects.${index}.pass_marks`)}</span>}
                            </label>
                            <label className="flex items-center gap-2 text-sm">
                                <Checkbox checked={row.include_in_gpa} onCheckedChange={(checked) => update(index, { include_in_gpa: Boolean(checked) })} />
                                {k('include_in_gpa')}
                            </label>
                            <label className="flex items-center gap-2 text-sm">
                                <Checkbox checked={row.is_optional} onCheckedChange={(checked) => update(index, { is_optional: Boolean(checked) })} />
                                {k('optional')}
                            </label>
                            <Button variant="ghost" size="icon" aria-label={t('core::admin.common.delete')} onClick={() => setRows(rows.filter((_, position) => position !== index))}>
                                <Trash2Icon />
                            </Button>
                        </div>

                        {row.components.map((component, position) => (
                            <div key={component.id ?? `new-${position}`} className="flex flex-wrap items-end gap-2 border-l-2 pl-3">
                                <Input className="w-40" list="component-names" placeholder={k('component')} value={component.name_bn} onChange={(event) => updateComponent(index, position, { name_bn: event.target.value })} />
                                <Input className="w-24" type="number" placeholder={k('full_marks')} value={component.full_marks} onChange={(event) => updateComponent(index, position, { full_marks: event.target.value })} />
                                <Input className="w-24" type="number" placeholder={k('pass_marks')} value={component.pass_marks} onChange={(event) => updateComponent(index, position, { pass_marks: event.target.value })} />
                                <Button variant="ghost" size="icon" aria-label={t('core::admin.common.delete')} onClick={() => update(index, { components: row.components.filter((_, at) => at !== position) })}>
                                    <Trash2Icon />
                                </Button>
                            </div>
                        ))}
                        <Button variant="ghost" size="sm" onClick={() => update(index, { components: [...row.components, { name_bn: '', full_marks: '', pass_marks: '' }] })}>
                            <PlusIcon /> {k('add_component')}
                        </Button>
                    </div>
                ))}
                <datalist id="component-names">
                    {TEMPLATES.map((name) => (
                        <option key={name} value={name} />
                    ))}
                </datalist>
            </div>

            {unused.length > 0 && (
                <div className="flex flex-wrap items-center gap-2">
                    <span className="text-sm text-muted-foreground">{k('add_subject')}</span>
                    {unused.map((subject) => (
                        <Button key={subject.id} variant="outline" size="sm" onClick={() => setRows([...rows, { subject_id: subject.id, name: subject.name, full_marks: 100, pass_marks: 33, include_in_gpa: true, is_optional: false, components: [] }])}>
                            <PlusIcon /> {subject.name}
                        </Button>
                    ))}
                </div>
            )}

            <Button onClick={() => void save()}>{t('core::admin.common.save')}</Button>
        </div>
    );
}
