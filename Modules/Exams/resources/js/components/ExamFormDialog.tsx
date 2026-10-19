import { useEffect, useState } from 'react';
import { toast } from 'sonner';

import { useAcademicOptions } from '@academic/js/hooks/useAcademicOptions';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { api, ApiError } from '@core/js/api';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { t } from '@core/js/i18n';

export interface ExamForm {
    id?: number;
    name_bn: string;
    name_en: string;
    session_id: string | number;
    start_date: string;
    end_date: string;
    status: string;
    description: string;
    class_ids: number[];
}

const k = (key: string) => t(`exams::admin.${key}`);
const SUGGESTIONS = ['half_yearly', 'annual', 'monthly', 'model'];

/** The design's "new exam" dialog: name with suggestion chips, dates, session and status, class chips. Also edits. */
export function ExamFormDialog({ open, exam, onClose, onSaved }: { open: boolean; exam: ExamForm | null; onClose: () => void; onSaved: (id: number) => void }) {
    const academic = useAcademicOptions();
    const blank: ExamForm = { name_bn: '', name_en: '', session_id: '', start_date: '', end_date: '', status: 'draft', description: '', class_ids: [] };
    const [form, setForm] = useState<ExamForm>(blank);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [busy, setBusy] = useState(false);

    useEffect(() => {
        if (open) {
            setErrors({});
            setForm(exam ?? { ...blank, session_id: academic.current_session_id ?? '', class_ids: academic.classes.map((schoolClass) => schoolClass.id) });
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, academic.current_session_id, academic.classes.length]);

    const set = (patch: Partial<ExamForm>) => setForm((current) => ({ ...current, ...patch }));
    const year = new Date().getFullYear() + 1;

    const save = async () => {
        setBusy(true);
        setErrors({});

        try {
            const response = form.id ? await api.put<{ data: { id: number } }>(`/exams/${form.id}`, form) : await api.post<{ data: { id: number } }>('/exams', form);
            toast.success(form.id ? t('core::admin.common.saved') : k('created'));
            onSaved(response.data.id);
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
            }
            toast.error(t('core::admin.common.error'));
        } finally {
            setBusy(false);
        }
    };

    return (
        <Dialog open={open} onOpenChange={(next) => !next && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{form.id ? k('edit') : k('new')}</DialogTitle>
                </DialogHeader>
                <div className="grid gap-3">
                    <Field label={k('fields.name_bn')} htmlFor="exam-name" error={errors.name_bn?.[0]}>
                        <Input id="exam-name" value={form.name_bn} placeholder={k('name_placeholder')} onChange={(event) => set({ name_bn: event.target.value })} />
                    </Field>
                    <div className="flex flex-wrap gap-1.5">
                        {SUGGESTIONS.map((suggestion) => (
                            <button key={suggestion} type="button" onClick={() => set({ name_bn: `${k(`suggest.${suggestion}`)} ${k('suggest.suffix')} ${year}` })} className="h-[34px] cursor-pointer rounded-full border border-line-strong bg-surface-2 px-3 text-sm">
                                {k(`suggest.${suggestion}`)}
                            </button>
                        ))}
                    </div>
                    <Field label={k('fields.name_en')} htmlFor="exam-name-en" error={errors.name_en?.[0]}>
                        <Input id="exam-name-en" value={form.name_en} onChange={(event) => set({ name_en: event.target.value })} />
                    </Field>
                    <div className="grid grid-cols-2 gap-2.5">
                        <Field label={k('fields.start_date')} htmlFor="exam-start" error={errors.start_date?.[0]}>
                            <Input id="exam-start" type="date" value={form.start_date} onChange={(event) => set({ start_date: event.target.value })} />
                        </Field>
                        <Field label={k('fields.end_date')} htmlFor="exam-end" error={errors.end_date?.[0]}>
                            <Input id="exam-end" type="date" value={form.end_date} onChange={(event) => set({ end_date: event.target.value })} />
                        </Field>
                    </div>
                    <div className="grid grid-cols-2 gap-2.5">
                        <Field label={k('fields.session_id')} htmlFor="exam-session" error={errors.session_id?.[0]}>
                            <NativeSelect id="exam-session" value={form.session_id} onChange={(event) => set({ session_id: event.target.value })} options={academic.sessions.map((session) => ({ value: session.id, label: session.name }))} />
                        </Field>
                        <Field label={k('fields.status')} htmlFor="exam-status" error={errors.status?.[0]}>
                            <NativeSelect id="exam-status" value={form.status} onChange={(event) => set({ status: event.target.value })} options={['draft', 'ongoing', 'completed'].map((value) => ({ value, label: k(`status.${value}`) }))} />
                        </Field>
                    </div>
                    <span className="text-sm font-semibold">{k('fields.classes')}</span>
                    <div className="flex flex-wrap gap-1.5">
                        {academic.classes.map((schoolClass) => {
                            const on = form.class_ids.includes(schoolClass.id);

                            return (
                                <label key={schoolClass.id} className="flex h-9 cursor-pointer items-center gap-1.5 rounded-full border border-line-strong px-3 text-sm" style={on ? { background: 'var(--green-50)', borderColor: 'var(--green-700)' } : undefined}>
                                    <input type="checkbox" checked={on} className="size-4 accent-green-700" onChange={() => set({ class_ids: on ? form.class_ids.filter((id) => id !== schoolClass.id) : [...form.class_ids, schoolClass.id] })} />
                                    {schoolClass.name}
                                </label>
                            );
                        })}
                    </div>
                    {errors.class_ids && <p className="text-[13px] font-medium text-danger">{errors.class_ids[0]}</p>}
                    <Field label={k('fields.description')} htmlFor="exam-description">
                        <Textarea id="exam-description" rows={2} value={form.description} onChange={(event) => set({ description: event.target.value })} />
                    </Field>
                    <Button size="lg" disabled={busy} onClick={() => void save()}>
                        {form.id ? t('core::admin.common.save') : k('create')}
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}
