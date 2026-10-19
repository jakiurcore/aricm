import { useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';

import { useAcademicOptions } from '@academic/js/hooks/useAcademicOptions';
import { Checkbox } from '@/components/ui/checkbox';
import { api } from '@core/js/api';
import { DashedEmpty, Panel, Pill, TableCard, Toggle } from '@core/js/components/design';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { LoadingState } from '@core/js/components/States';
import { t } from '@core/js/i18n';
import { bnDigits } from '@core/js/number';
import { can } from '@core/js/permissions';

interface RosterRow {
    student_id: number;
    code: string;
    name: string;
    roll: number | null;
    eligibility: { allowed: boolean; reason: string | null };
}

const k = (key: string) => t(`exams::admin.admit.${key}`);

/**
 * The admit-card tab: the release switch panel of the design, then the generator block (section filter and the navy
 * "preview & print" pill) over the class roster with a tick and a ready / blocked pill for each student.
 */
export function AdmitCardTab({ examId, classId, released, onReleased }: { examId: number; classId: number; released: boolean; onReleased: (value: boolean) => void }) {
    const academic = useAcademicOptions();
    const [sectionId, setSectionId] = useState('');
    const [roster, setRoster] = useState<RosterRow[] | null>(null);
    const [skipped, setSkipped] = useState<number[]>([]);
    const sections = academic.classes.find((schoolClass) => schoolClass.id === classId)?.sections ?? [];

    useEffect(() => {
        setRoster(null);
        setSkipped([]);
        void api.get<{ data: RosterRow[] }>(`/exams/${examId}/admit-cards/roster`, { class_id: classId, section_id: sectionId }).then((response) => setRoster(response.data));
    }, [examId, classId, sectionId]);

    useEffect(() => setSectionId(''), [classId]);

    const toggle = async (value: boolean) => {
        await api.post(`/exams/${examId}/admit-cards/release`, { released: value });
        onReleased(value);
        toast.success(t('core::admin.common.saved'));
    };

    const chosen = useMemo(() => (roster ?? []).filter((row) => row.eligibility.allowed && !skipped.includes(row.student_id)), [roster, skipped]);
    const printUrl = useMemo(() => {
        const query = new URLSearchParams({ class_id: String(classId), section_id: sectionId });
        chosen.forEach((row) => query.append('student_ids[]', String(row.student_id)));

        return `/api/exams/${examId}/admit-cards/pdf?${query}`;
    }, [examId, classId, sectionId, chosen]);

    return (
        <div className="grid gap-4">
            <Panel className="max-w-[640px] gap-3">
                <div className="flex min-h-11 items-center justify-between gap-3">
                    <span className="grid leading-[1.4]">
                        <strong>{k('release')}</strong>
                        <span className="text-sm text-muted-foreground">{k('release_help')}</span>
                    </span>
                    <Toggle checked={released} label={k('release')} disabled={!can('exams.admit_card')} onChange={(value) => void toggle(value)} />
                </div>
                <div className="flex min-h-11 items-center justify-between gap-3 border-t border-line pt-2.5">
                    <span className="grid leading-[1.4]">
                        <strong>{k('block_unpaid')}</strong>
                        <span className="text-sm text-muted-foreground">{k('block_unpaid_help')}</span>
                    </span>
                </div>
            </Panel>

            <div className="grid items-end gap-2.5 rounded-[20px] border border-line bg-surface p-4" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 200px), 1fr))' }}>
                <Field label={t('students::admin.fields.section_id')} htmlFor="admit-section">
                    <NativeSelect id="admit-section" placeholder={k('all_sections')} value={sectionId} onChange={(event) => setSectionId(event.target.value)} options={sections.map((section) => ({ value: section.id, label: section.name }))} />
                </Field>
                {can('exams.admit_card') && (
                    <a
                        href={chosen.length > 0 ? printUrl : undefined}
                        target="_blank"
                        rel="noreferrer"
                        aria-disabled={chosen.length === 0}
                        className="grid h-11 place-items-center rounded-full bg-navy-800 px-4 font-bold text-white no-underline aria-disabled:pointer-events-none aria-disabled:opacity-50"
                    >
                        🖨 {k('print')}
                    </a>
                )}
            </div>

            {roster === null && <LoadingState rows={4} height={52} />}
            {roster?.length === 0 && <DashedEmpty glyph="🎫" title={t('core::admin.common.empty')} />}
            {roster && roster.length > 0 && (
                <TableCard className="overflow-hidden">
                    {roster.map((row) => {
                        const blocked = !row.eligibility.allowed;
                        const on = !blocked && !skipped.includes(row.student_id);

                        return (
                            <label key={row.student_id} className="flex cursor-pointer items-center gap-3 border-b border-line px-4 py-2.5 last:border-b-0 aria-disabled:cursor-default" aria-disabled={blocked}>
                                <Checkbox checked={on} disabled={blocked} className="size-[22px]" onCheckedChange={(checked) => setSkipped(checked ? skipped.filter((id) => id !== row.student_id) : [...skipped, row.student_id])} />
                                <span className="flex-1">
                                    {row.name} <span className="text-[13px] text-muted-foreground">· {row.roll ? `${t('students::admin.fields.roll')} ${bnDigits(row.roll)} · ` : ''}{row.code}</span>
                                </span>
                                <Pill tone={blocked ? 'danger' : 'success'}>{blocked ? (row.eligibility.reason ?? k('blocked')) : k('ready')}</Pill>
                            </label>
                        );
                    })}
                </TableCard>
            )}
        </div>
    );
}
