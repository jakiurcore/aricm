import { KeyRoundIcon } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router';
import { toast } from 'sonner';

import { HeaderActions } from '@admin/js';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { api } from '@core/js/api';
import { ConfirmDialog } from '@core/js/components/ConfirmDialog';
import { t } from '@core/js/i18n';
import { bnDigits, formatDate } from '@core/js/number';

import type { Student } from '../types';

interface Profile {
    data: Student;
    enrollments: { session: string; class: string; section: string | null; roll: number | null; status: string }[];
    summary: { label: string; value: string; tone: string; route: string | null }[];
}

const toneClass: Record<string, string> = { neutral: '', success: 'text-green-700', warn: 'text-amber-700', danger: 'text-destructive' };

export function StudentProfilePage() {
    const { id } = useParams();
    const [profile, setProfile] = useState<Profile | null>(null);
    const [confirmReset, setConfirmReset] = useState(false);

    const load = useCallback(async () => setProfile(await api.get<Profile>(`/students/${id}`)), [id]);

    useEffect(() => {
        void load();
    }, [load]);

    if (!profile) {
        return <p className="text-muted-foreground">{t('core::admin.common.loading')}</p>;
    }

    const student = profile.data;
    const rows: [string, string][] = [
        [t('students::admin.fields.father_name'), student.father_name ?? '—'],
        [t('students::admin.fields.mother_name'), student.mother_name ?? '—'],
        [t('students::admin.fields.guardian_name'), `${student.guardian_name ?? '—'} (${student.guardian_relation ?? ''})`],
        [t('students::admin.fields.guardian_phone'), bnDigits(student.guardian_phone ?? '—')],
        [t('students::admin.fields.date_of_birth'), formatDate(student.date_of_birth)],
        [t('students::admin.fields.gender'), t(`students::admin.gender.${student.gender}`)],
        [t('students::admin.fields.blood_group'), student.blood_group ?? '—'],
        [t('students::admin.fields.religion'), student.religion ?? '—'],
        [t('students::admin.fields.admission_date'), formatDate(student.admission_date)],
        [t('students::admin.fields.residency_type'), t(`students::admin.residency.${student.residency_type}`)],
        [t('students::admin.fields.present_address'), student.present_address ?? '—'],
        [t('students::admin.fields.permanent_address'), student.permanent_address ?? '—'],
    ];

    const reset = async () => {
        await api.post(`/students/${student.id}/reset-password`);
        toast.success(t('students::admin.password_reset'));
        setConfirmReset(false);
    };

    return (
        <div className="max-w-4xl space-y-4">
            <HeaderActions.Fill>
                <Button variant="outline" onClick={() => setConfirmReset(true)}>
                    <KeyRoundIcon /> {t('students::admin.reset_password')}
                </Button>
            </HeaderActions.Fill>

            <Link to="/students" className="text-sm text-muted-foreground underline">
                ← {t('students::admin.title')}
            </Link>

            <div className="flex items-center gap-4">
                <Avatar className="size-20">
                    {student.photo_url && <AvatarImage src={student.photo_url} alt="" />}
                    <AvatarFallback className="text-2xl">{student.name_bn.slice(0, 1)}</AvatarFallback>
                </Avatar>
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">{student.name_bn}</h1>
                    <p className="text-muted-foreground">
                        {student.student_id} · {[student.class_name, student.section_name].filter(Boolean).join(' / ')} · {t('students::admin.fields.roll')} {student.roll ? bnDigits(student.roll) : '—'}
                    </p>
                    <Badge variant={student.status === 'active' ? 'default' : 'outline'}>{t(`students::admin.status.${student.status}`)}</Badge>
                </div>
            </div>

            {profile.summary.length > 0 && (
                <div className="grid gap-3 sm:grid-cols-3">
                    {profile.summary.map((item) => (
                        <Card key={item.label}>
                            <CardContent className="pt-4">
                                <p className="text-xs text-muted-foreground">{item.label}</p>
                                <p className={`text-xl font-semibold ${toneClass[item.tone] ?? ''}`}>{item.value}</p>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}

            <Card>
                <CardHeader>
                    <CardTitle>{t('students::admin.details')}</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl className="grid gap-x-8 gap-y-2 text-sm sm:grid-cols-2">
                        {rows.map(([label, value]) => (
                            <div key={label} className="flex justify-between gap-4 border-b py-1.5">
                                <dt className="text-muted-foreground">{label}</dt>
                                <dd className="text-right font-medium">{value}</dd>
                            </div>
                        ))}
                    </dl>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{t('students::admin.history')}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-1 text-sm">
                    {profile.enrollments.map((row) => (
                        <div key={row.session} className="flex justify-between border-b py-1.5">
                            <span>{row.session} — {row.class}{row.section ? ` / ${row.section}` : ''}</span>
                            <span className="text-muted-foreground">{t('students::admin.fields.roll')} {row.roll ? bnDigits(row.roll) : '—'} · {row.status}</span>
                        </div>
                    ))}
                </CardContent>
            </Card>

            <ConfirmDialog open={confirmReset} title={t('students::admin.reset_password')} description={t('students::admin.reset_confirm')} confirmLabel={t('students::admin.reset_password')} onConfirm={() => void reset()} onClose={() => setConfirmReset(false)} />
        </div>
    );
}
