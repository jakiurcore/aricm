import { useCallback, useEffect, useState, type ReactNode } from 'react';
import { Link, useNavigate, useParams } from 'react-router';
import { toast } from 'sonner';

import { usePageTitle } from '@/admin-shell/pageTitle';
import { Button } from '@/components/ui/button';
import { api, ApiError } from '@core/js/api';
import { ConfirmDialog } from '@core/js/components/ConfirmDialog';
import { ErrorState, LoadingState } from '@core/js/components/States';
import { ListCard, Panel } from '@core/js/components/Surface';
import { t } from '@core/js/i18n';
import { bnDigits, formatDate } from '@core/js/number';
import { can } from '@core/js/permissions';

import type { Student } from '../types';

interface Profile {
    data: Student;
    enrollments: { session: string; class: string; section: string | null; roll: number | null; status: string }[];
    summary: { label: string; value: string; tone: string; route: string | null }[];
}

const toneClass: Record<string, string> = { neutral: '', success: 'text-success', warn: 'text-warn', danger: 'text-danger' };

const k = (key: string) => t(`students::admin.fields.${key}`);
const dash = (value: ReactNode) => (value === null || value === undefined || value === '' ? '—' : value);

function Group({ title, rows }: { title: string; rows: [string, ReactNode, string?][] }) {
    return (
        <ListCard>
            <h3 className="m-0 bg-surface-2 px-4 py-3 text-base leading-[1.65] font-bold">{title}</h3>
            {rows.map(([label, value, tone]) => (
                <div key={label} className="grid gap-2.5 border-t border-line px-4 py-[9px] text-[15px]" style={{ gridTemplateColumns: '40% 1fr' }}>
                    <span className="text-muted-foreground">{label}</span>
                    <strong className={`font-semibold ${tone ?? ''}`}>{dash(value)}</strong>
                </div>
            ))}
        </ListCard>
    );
}

/** The design's "শিক্ষার্থীর প্রোফাইল": the identity card on top, then the detail groups. */
export function StudentProfilePage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const [profile, setProfile] = useState<Profile | null>(null);
    const [failed, setFailed] = useState(false);
    const [confirm, setConfirm] = useState<'reset' | 'delete' | null>(null);

    usePageTitle(t('students::admin.profile_page.title'));

    const load = useCallback(() => {
        setFailed(false);
        api.get<Profile>(`/students/${id}`).then(setProfile).catch(() => setFailed(true));
    }, [id]);

    useEffect(load, [load]);

    if (failed) {
        return <ErrorState onRetry={load} />;
    }

    if (!profile) {
        return <LoadingState />;
    }

    const student = profile.data;
    const classLine = [student.class_name, student.section_name].filter(Boolean).join(' – ');

    const act = async () => {
        try {
            if (confirm === 'reset') {
                await api.post(`/students/${student.id}/reset-password`);
                toast.success(t('students::admin.password_reset'));
            } else {
                await api.delete(`/students/${student.id}`);
                toast.success(t('core::admin.common.deleted'));
                navigate('/students');
            }
        } catch (error) {
            toast.error(error instanceof ApiError ? error.message : t('core::admin.common.error'));
        } finally {
            setConfirm(null);
        }
    };

    return (
        <div className="grid gap-4">
            <Panel className="flex flex-wrap items-center gap-4">
                <div className="h-[116px] w-24 flex-none overflow-hidden rounded-2xl bg-surface-2">
                    {student.photo_url ? (
                        <img src={student.photo_url} alt="" className="size-full object-cover" />
                    ) : (
                        <span className="grid size-full place-items-center text-[13px] font-medium text-muted-foreground">{t('students::admin.fields.photo')}</span>
                    )}
                </div>
                <div className="grid min-w-[200px] flex-1 leading-normal">
                    <strong className="font-head text-[22px] text-heading">{student.name_bn}</strong>
                    <span className="text-muted-foreground">
                        ID {bnDigits(student.student_id)} · {classLine} · {t('students::admin.list.roll_short')} {student.roll ? bnDigits(student.roll) : '—'}
                    </span>
                </div>
                <div className="flex flex-wrap gap-2">
                    {can('students.manage') && (
                        <Button variant="secondary" className="font-bold" render={<Link to={`/students/${student.id}/edit`} />}>
                            {t('core::admin.common.edit')}
                        </Button>
                    )}
                    {can('students.id_card') && (
                        <Button variant="outline" render={<a href={`/api/students/${student.id}/id-card`} target="_blank" rel="noreferrer" />}>
                            🪪 {t('students::admin.profile_page.id_card')}
                        </Button>
                    )}
                    {can('students.reset_password') && (
                        <Button variant="outline" onClick={() => setConfirm('reset')}>
                            🔑 {t('students::admin.reset_password')}
                        </Button>
                    )}
                    {can('students.manage') && (
                        <Button variant="outline" className="border-danger text-danger" onClick={() => setConfirm('delete')}>
                            🗑 {t('core::admin.common.delete')}
                        </Button>
                    )}
                </div>
            </Panel>

            <div className="grid gap-4" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 320px), 1fr))' }}>
                <Group
                    title={t('students::admin.form.personal')}
                    rows={[
                        [k('name_en'), student.name_en],
                        [k('date_of_birth'), formatDate(student.date_of_birth)],
                        [t('students::admin.form.birth_registration_no'), student.birth_registration_no ? bnDigits(student.birth_registration_no) : null],
                        [k('blood_group'), student.blood_group],
                        [k('gender'), t(`students::admin.gender.${student.gender}`)],
                        [k('religion'), student.religion],
                        [k('section_id'), student.section_name],
                        [k('session_id'), student.session_name ? bnDigits(student.session_name) : null],
                        [k('admission_date'), formatDate(student.admission_date)],
                        [k('residency_type'), t(`students::admin.residency.${student.residency_type}`)],
                        [k('status'), t(`students::admin.status.${student.status}`)],
                    ]}
                />
                <Group
                    title={t('students::admin.form.guardian')}
                    rows={[
                        [k('father_name'), student.father_name],
                        [k('mother_name'), student.mother_name],
                        [t('students::admin.form.guardian_phone'), student.guardian_phone ? bnDigits(student.guardian_phone) : null],
                        [k('guardian_name'), [student.guardian_name, student.guardian_relation && `(${student.guardian_relation})`].filter(Boolean).join(' ')],
                        [k('present_address'), student.present_address],
                        [k('permanent_address'), student.permanent_address],
                    ]}
                />
                {profile.summary.length > 0 && <Group title={t('students::admin.profile_page.summary')} rows={profile.summary.map((item): [string, ReactNode, string] => [item.label, item.value, toneClass[item.tone] ?? ''])} />}
                {profile.enrollments.length > 0 && (
                    <Group
                        title={t('students::admin.history')}
                        rows={profile.enrollments.map((row): [string, ReactNode] => [bnDigits(row.session), `${row.class}${row.section ? ` – ${row.section}` : ''} · ${t('students::admin.list.roll_short')} ${row.roll ? bnDigits(row.roll) : '—'}`])}
                    />
                )}
            </div>

            <ConfirmDialog
                open={confirm !== null}
                title={confirm === 'reset' ? t('students::admin.reset_password') : undefined}
                description={confirm === 'reset' ? t('students::admin.reset_confirm') : student.name_bn}
                confirmLabel={confirm === 'reset' ? t('students::admin.reset_password') : undefined}
                onConfirm={() => void act()}
                onClose={() => setConfirm(null)}
            />
        </div>
    );
}
