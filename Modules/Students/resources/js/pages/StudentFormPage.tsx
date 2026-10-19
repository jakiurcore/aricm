import { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { Link, useNavigate, useParams } from 'react-router';
import { toast } from 'sonner';

import { useAcademicOptions } from '@academic/js/hooks/useAcademicOptions';
import { usePageTitle } from '@/admin-shell/pageTitle';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { api, ApiError } from '@core/js/api';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { PhotoSlot } from '@core/js/components/PhotoSlot';
import { ErrorState, LoadingState } from '@core/js/components/States';
import { Panel, PanelTitle, StickyBar } from '@core/js/components/Surface';
import { t } from '@core/js/i18n';

import { BLOOD_GROUPS, RESIDENCY, STATUSES, type Student } from '../types';

type Values = Record<string, any>;

const k = (key: string) => t(`students::admin.fields.${key}`);
const f = (key: string) => t(`students::admin.form.${key}`);

const READ_ONLY = ['id', 'student_id', 'class_name', 'section_name', 'session_name', 'photo_url', 'must_change_password', 'fee_badge', 'latest_gpa'];

function newStudentDefaults(sessionId: number | null): Values {
    return {
        session_id: sessionId ?? '', class_id: '', section_id: '', roll: '', name_bn: '', name_en: '', father_name: '', mother_name: '', guardian_name: '', guardian_phone: '', guardian_relation: '',
        date_of_birth: '', birth_registration_no: '', gender: 'male', blood_group: '', religion: 'ইসলাম', present_address: '', permanent_address: '', admission_date: new Date().toISOString().slice(0, 10), residency_type: 'non_residential',
    };
}

/** The design's "নতুন শিক্ষার্থী" and "তথ্য সম্পাদনা" screen: three cards and a sticky save bar. */
export function StudentFormPage() {
    const { id } = useParams();
    const editing = id !== undefined;
    const navigate = useNavigate();
    const academic = useAcademicOptions();
    const form = useForm<Values>({ defaultValues: newStudentDefaults(null) });
    const { register, watch, reset, setError, handleSubmit, formState } = form;
    const errors = formState.errors;
    const [student, setStudent] = useState<Student | null>(null);
    const [photo, setPhoto] = useState<File | null>(null);
    const [failed, setFailed] = useState(false);
    const classId = watch('class_id');
    const sections = academic.classes.find((schoolClass) => String(schoolClass.id) === String(classId))?.sections ?? [];

    usePageTitle(f(editing ? 'edit_title' : 'new_title'));

    useEffect(() => {
        if (!editing) {
            reset(newStudentDefaults(academic.current_session_id));

            return;
        }

        setFailed(false);
        api.get<{ data: Student }>(`/students/${id}`)
            .then((response) => {
                setStudent(response.data);
                reset({ ...response.data, birth_registration_no: response.data.birth_registration_no ?? '' });
            })
            .catch(() => setFailed(true));
    }, [editing, id, academic.current_session_id, reset]);

    const submit = handleSubmit(async (values) => {
        const body = new FormData();
        for (const [key, value] of Object.entries(values)) {
            if (!READ_ONLY.includes(key) && key !== 'photo') {
                body.append(key, value === null || value === undefined ? '' : String(value));
            }
        }

        if (photo) {
            body.append('photo', photo);
        }

        try {
            if (editing) {
                body.append('_method', 'PUT');
                await api.post(`/students/${id}`, body);
            } else {
                await api.post('/students', body);
            }
            toast.success(t('core::admin.common.saved'));
            navigate('/students');
        } catch (error) {
            if (error instanceof ApiError) {
                for (const [field, messages] of Object.entries(error.errors)) {
                    setError(field, { message: messages[0] });
                }
            }
            toast.error(t('core::admin.common.error'));
        }
    });

    if (editing && failed) {
        return <ErrorState onRetry={() => navigate(0)} />;
    }

    if (editing && !student) {
        return <LoadingState />;
    }

    const text = (name: string, options: { type?: string; label?: string; required?: boolean } = {}) => (
        <Field label={options.label ?? k(name)} htmlFor={`f-${name}`} error={errors[name]?.message as string | undefined}>
            <Input id={`f-${name}`} type={options.type ?? 'text'} {...register(name)} />
        </Field>
    );

    const select = (name: string, choices: { value: string | number; label: string }[], placeholder?: string, label?: string) => (
        <Field label={label ?? k(name)} htmlFor={`f-${name}`} error={errors[name]?.message as string | undefined}>
            <NativeSelect id={`f-${name}`} options={choices} placeholder={placeholder} {...register(name)} />
        </Field>
    );

    return (
        <form onSubmit={submit} className="grid gap-4" noValidate>
            <div className="grid items-start gap-4" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 360px), 1fr))' }}>
                <Panel>
                    <PanelTitle>{f('personal')}</PanelTitle>
                    <div className="flex items-center gap-3.5">
                        <PhotoSlot src={student?.photo_url} placeholder={f('photo_upload')} onChange={setPhoto} />
                        <span className="text-sm text-muted-foreground">{f('photo_hint')}</span>
                    </div>
                    {errors.photo && <p className="text-[13px] font-medium text-danger">{errors.photo.message as string}</p>}
                    {text('name_bn', { label: f('name_bn') })}
                    {text('name_en', { label: f('name_en') })}
                    {text('date_of_birth', { type: 'date' })}
                    {text('birth_registration_no', { label: f('birth_registration_no') })}
                    {select('blood_group', BLOOD_GROUPS.map((group) => ({ value: group, label: group })), '—')}
                    {select('gender', [{ value: 'male', label: t('students::admin.gender.male') }, { value: 'female', label: t('students::admin.gender.female') }])}
                    {text('religion')}
                </Panel>

                <div className="grid gap-4">
                    <Panel>
                        <PanelTitle>{f('guardian')}</PanelTitle>
                        {text('father_name')}
                        {text('mother_name')}
                        {text('guardian_phone', { label: f('guardian_phone') })}
                        {text('guardian_name')}
                        {text('guardian_relation')}
                        {text('present_address')}
                        {text('permanent_address')}
                    </Panel>

                    <Panel>
                        <PanelTitle>{f('admission_info')}</PanelTitle>
                        <div className="grid gap-2.5" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(140px, 1fr))' }}>
                            {select('session_id', academic.sessions.map((session) => ({ value: session.id, label: session.name })))}
                            {select('class_id', academic.classes.map((schoolClass) => ({ value: schoolClass.id, label: schoolClass.name })), '—')}
                            {select('section_id', sections.map((section) => ({ value: section.id, label: section.name })), '—')}
                            {text('roll', { type: 'number' })}
                            {text('admission_date', { type: 'date' })}
                            {select('residency_type', RESIDENCY.map((value) => ({ value, label: t(`students::admin.residency.${value}`) })))}
                            {editing && select('status', STATUSES.map((value) => ({ value, label: t(`students::admin.status.${value}`) })))}
                        </div>
                        {!editing && <span className="text-[13px] text-muted-foreground">{f('id_auto')}</span>}
                    </Panel>
                </div>
            </div>

            <StickyBar>
                <Button type="button" variant="outline" render={<Link to="/students" />}>
                    {t('core::admin.common.cancel')}
                </Button>
                <Button type="submit" className="px-[22px]" disabled={formState.isSubmitting}>
                    {formState.isSubmitting ? t('core::admin.common.saving') : t('core::admin.common.save')}
                </Button>
            </StickyBar>
        </form>
    );
}
