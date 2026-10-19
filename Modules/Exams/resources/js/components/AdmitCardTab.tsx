import { useEffect, useState } from 'react';
import { toast } from 'sonner';

import { useAcademicOptions } from '@academic/js/hooks/useAcademicOptions';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';
import { api } from '@core/js/api';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { t } from '@core/js/i18n';
import { bnDigits } from '@core/js/number';

interface RosterRow {
    student_id: number;
    code: string;
    name: string;
    roll: number | null;
    eligibility: { allowed: boolean; reason: string | null };
}

const k = (key: string) => t(`exams::admin.admit.${key}`);

export function AdmitCardTab({ examId, classId, released, onReleased }: { examId: number; classId: number; released: boolean; onReleased: (value: boolean) => void }) {
    const academic = useAcademicOptions();
    const [sectionId, setSectionId] = useState('');
    const [roster, setRoster] = useState<RosterRow[]>([]);
    const sections = academic.classes.find((schoolClass) => schoolClass.id === classId)?.sections ?? [];

    useEffect(() => {
        void api.get<{ data: RosterRow[] }>(`/exams/${examId}/admit-cards/roster`, { class_id: classId, section_id: sectionId }).then((response) => setRoster(response.data));
    }, [examId, classId, sectionId]);

    const toggle = async (value: boolean) => {
        await api.post(`/exams/${examId}/admit-cards/release`, { released: value });
        onReleased(value);
        toast.success(t('core::admin.common.saved'));
    };

    return (
        <div className="space-y-4">
            <label className="flex items-center gap-3 rounded-xl border bg-card p-4 text-sm">
                <Switch checked={released} onCheckedChange={(checked) => void toggle(checked)} />
                <span>
                    <span className="font-medium">{k('release')}</span>
                    <span className="block text-muted-foreground">{k('release_help')}</span>
                </span>
            </label>

            <div className="max-w-xs">
                <Field label={t('students::admin.fields.section_id')} htmlFor="admit-section">
                    <NativeSelect id="admit-section" placeholder={k('all_sections')} value={sectionId} onChange={(event) => setSectionId(event.target.value)} options={sections.map((section) => ({ value: section.id, label: section.name }))} />
                </Field>
            </div>

            <div className="overflow-x-auto rounded-xl border bg-card">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left text-muted-foreground">
                            <th className="p-3">{t('students::admin.fields.roll')}</th>
                            <th className="p-3">{t('students::admin.fields.name_bn')}</th>
                            <th className="p-3">ID</th>
                            <th className="p-3">{k('eligibility')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {roster.length === 0 && (
                            <tr>
                                <td colSpan={4} className="p-4 text-center text-muted-foreground">
                                    {t('core::admin.common.empty')}
                                </td>
                            </tr>
                        )}
                        {roster.map((row) => (
                            <tr key={row.student_id} className="border-b last:border-0">
                                <td className="p-3">{row.roll ? bnDigits(row.roll) : '—'}</td>
                                <td className="p-3">{row.name}</td>
                                <td className="p-3 text-muted-foreground">{row.code}</td>
                                <td className="p-3">{row.eligibility.allowed ? <Badge>{k('eligible')}</Badge> : <Badge variant="outline">{row.eligibility.reason}</Badge>}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
