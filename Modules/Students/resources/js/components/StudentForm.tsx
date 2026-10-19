import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { toast } from 'sonner';

import { useAcademicOptions } from '@academic/js/hooks/useAcademicOptions';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { api, ApiError } from '@core/js/api';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { t } from '@core/js/i18n';

import { BLOOD_GROUPS, RESIDENCY, STATUSES, type Student } from '../types';

type Values = Record<string, any>;

const k = (key: string) => t(`students::admin.fields.${key}`);

interface Props {
    student: Student | null;
    open: boolean;
    onClose: () => void;
    onSaved: () => void;
}

export function StudentForm({ student, open, onClose, onSaved }: Props) {
    const academic = useAcademicOptions();
    const form = useForm<Values>();
    const { register, watch, reset, setError, handleSubmit, formState } = form;
    const errors = formState.errors;
    const classId = watch('class_id');
    const sections = academic.classes.find((schoolClass) => String(schoolClass.id) === String(classId))?.sections ?? [];

    useEffect(() => {
        if (!open) {
            return;
        }

        reset(
            student
                ? { ...student, photo: undefined }
                : { session_id: academic.current_session_id ?? '', class_id: '', section_id: '', roll: '', name_bn: '', name_en: '', father_name: '', mother_name: '', guardian_name: '', guardian_phone: '', guardian_relation: '', date_of_birth: '', gender: 'male', blood_group: '', religion: 'ইসলাম', present_address: '', permanent_address: '', admission_date: new Date().toISOString().slice(0, 10), residency_type: 'non_residential' },
        );
    }, [open, student, academic.current_session_id, reset]);

    const submit = handleSubmit(async (values) => {
        const body = new FormData();
        for (const [key, value] of Object.entries(values)) {
            if (key === 'photo') {
                continue;
            }
            if (['id', 'student_id', 'class_name', 'section_name', 'session_name', 'photo_url', 'must_change_password'].includes(key)) {
                continue;
            }
            body.append(key, value === null || value === undefined ? '' : String(value));
        }

        const photo = (document.getElementById('f-photo') as HTMLInputElement | null)?.files?.[0];
        if (photo) {
            body.append('photo', photo);
        }

        try {
            if (student) {
                body.append('_method', 'PUT');
                await api.post(`/students/${student.id}`, body);
            } else {
                await api.post('/students', body);
            }
            toast.success(t('core::admin.common.saved'));
            onSaved();
        } catch (error) {
            if (error instanceof ApiError) {
                for (const [field, messages] of Object.entries(error.errors)) {
                    setError(field, { message: messages[0] });
                }
            }
            toast.error(t('core::admin.common.error'));
        }
    });

    const text = (name: string, type = 'text') => (
        <Field label={k(name)} htmlFor={`f-${name}`} error={errors[name]?.message as string | undefined}>
            <Input id={`f-${name}`} type={type} {...register(name)} />
        </Field>
    );

    const select = (name: string, options: { value: string | number; label: string }[], placeholder?: string) => (
        <Field label={k(name)} htmlFor={`f-${name}`} error={errors[name]?.message as string | undefined}>
            <NativeSelect id={`f-${name}`} options={options} placeholder={placeholder} {...register(name)} />
        </Field>
    );

    return (
        <Dialog open={open} onOpenChange={(next) => !next && onClose()}>
            <DialogContent className="max-h-[92vh] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>{student ? t('students::admin.edit') : t('students::admin.new')}</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="grid gap-4 sm:grid-cols-3">
                    {select('session_id', academic.sessions.map((session) => ({ value: session.id, label: session.name })))}
                    {select('class_id', academic.classes.map((schoolClass) => ({ value: schoolClass.id, label: schoolClass.name })), '—')}
                    {select('section_id', sections.map((section) => ({ value: section.id, label: section.name })), '—')}
                    {text('roll', 'number')}
                    {text('name_bn')}
                    {text('name_en')}
                    {text('father_name')}
                    {text('mother_name')}
                    {text('guardian_name')}
                    {text('guardian_phone')}
                    {text('guardian_relation')}
                    {text('date_of_birth', 'date')}
                    {select('gender', [{ value: 'male', label: t('students::admin.gender.male') }, { value: 'female', label: t('students::admin.gender.female') }])}
                    {select('blood_group', BLOOD_GROUPS.map((group) => ({ value: group, label: group })), '—')}
                    {text('religion')}
                    {text('admission_date', 'date')}
                    {select('residency_type', RESIDENCY.map((value) => ({ value, label: t(`students::admin.residency.${value}`) })))}
                    {student && select('status', STATUSES.map((value) => ({ value, label: t(`students::admin.status.${value}`) })))}
                    <div className="sm:col-span-3 grid gap-4 sm:grid-cols-2">
                        <Field label={k('present_address')} htmlFor="f-present_address" error={errors.present_address?.message as string | undefined}>
                            <Textarea id="f-present_address" rows={2} {...register('present_address')} />
                        </Field>
                        <Field label={k('permanent_address')} htmlFor="f-permanent_address" error={errors.permanent_address?.message as string | undefined}>
                            <Textarea id="f-permanent_address" rows={2} {...register('permanent_address')} />
                        </Field>
                    </div>
                    <div className="sm:col-span-3">
                        <Field label={k('photo')} htmlFor="f-photo" error={errors.photo?.message as string | undefined}>
                            <Input id="f-photo" type="file" accept="image/png,image/jpeg,image/webp" />
                        </Field>
                    </div>
                    <DialogFooter className="sm:col-span-3">
                        <Button type="button" variant="outline" onClick={onClose}>
                            {t('core::admin.common.cancel')}
                        </Button>
                        <Button type="submit" disabled={formState.isSubmitting}>
                            {formState.isSubmitting ? t('core::admin.common.saving') : t('core::admin.common.save')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
