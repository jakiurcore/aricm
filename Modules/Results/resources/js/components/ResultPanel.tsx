import { PrinterIcon } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { useAcademicOptions } from '@academic/js/hooks/useAcademicOptions';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { api, ApiError } from '@core/js/api';
import { DashedEmpty, Notice } from '@core/js/components/design';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { LoadingState } from '@core/js/components/States';
import { t } from '@core/js/i18n';
import { bnDigits, formatNumber } from '@core/js/number';
import { can } from '@core/js/permissions';

interface Row {
    id: number;
    code: string;
    name: string;
    roll: number | null;
    gpa: number;
    grade: string;
    is_passed: boolean;
    is_incomplete: boolean;
    position_in_class: number | null;
    subjects: { name: string; grade: string; total: number; passed: boolean; absent: boolean }[];
}

const k = (key: string, replacements: Record<string, string | number> = {}) => t(`results::admin.result.${key}`, replacements);

/**
 * The result tab of the design: a status card with the big red publish pill, then the tabulation sheet and the merit list
 * side by side. Publishing asks first, with the design's amber warning and a red confirm pill.
 */
export function ResultPanel({ examId, classId, onChanged }: { examId: number; classId: number; onChanged?: () => void }) {
    const academic = useAcademicOptions();
    const [sectionId, setSectionId] = useState('');
    const [rows, setRows] = useState<Row[] | null>(null);
    const [published, setPublished] = useState(false);
    const [asking, setAsking] = useState(false);
    const [busy, setBusy] = useState(false);
    const sections = academic.classes.find((schoolClass) => schoolClass.id === classId)?.sections ?? [];
    const manage = can('results.publish');

    const load = useCallback(async () => {
        const response = await api.get<{ data: Row[]; published: boolean }>(`/results/exams/${examId}/classes/${classId}/preview`, { section_id: sectionId });
        setRows(response.data);
        setPublished(response.published);
    }, [examId, classId, sectionId]);

    useEffect(() => {
        setRows(null);
        void load();
    }, [load]);

    useEffect(() => setSectionId(''), [classId]);

    const run = async (path: string, success: string) => {
        setBusy(true);

        try {
            await api.post(path);
            toast.success(success);
            await load();
            onChanged?.();
        } catch (error) {
            toast.error(error instanceof ApiError ? error.message : t('core::admin.common.error'));
        } finally {
            setBusy(false);
            setAsking(false);
        }
    };

    if (rows === null) {
        return <LoadingState rows={3} height={96} />;
    }

    const incomplete = rows.filter((row) => row.is_incomplete).length;
    const complete = rows.length - incomplete;
    const subjectNames = [...new Set(rows.flatMap((row) => row.subjects.map((subject) => subject.name)))];
    const merit = rows.filter((row) => row.is_passed && row.position_in_class).sort((a, b) => (a.position_in_class ?? 0) - (b.position_in_class ?? 0));

    return (
        <div className="grid gap-3.5">
            <div className="flex flex-wrap items-center justify-between gap-3 rounded-[20px] border p-4" style={published ? { background: 'var(--success-bg)', borderColor: 'var(--success)' } : { background: 'var(--surface)', borderColor: 'var(--line)' }}>
                <span className="grid leading-[1.45]">
                    <strong className="text-[17px]" style={{ color: published ? 'var(--success)' : 'var(--ink)' }}>
                        {published ? `✓ ${k('published')}` : rows.length === 0 ? k('not_processed') : k('ready')}
                    </strong>
                    <span className="text-sm">{published ? k('published_body') : rows.length === 0 ? k('not_processed_body') : k('ready_body', { complete: bnDigits(complete), incomplete: bnDigits(incomplete) })}</span>
                </span>
                {(manage || can('results.process')) && (
                    <span className="flex flex-wrap items-center gap-2">
                        {!published && can('results.process') && (
                            <Button variant="outline" disabled={busy} onClick={() => void run(`/results/exams/${examId}/process`, k('processed'))}>
                                {k('process')}
                            </Button>
                        )}
                        {!manage ? null : published ? (
                            <Button variant="outline" disabled={busy} className="border-2 border-danger bg-transparent text-danger hover:bg-danger-bg" onClick={() => void run(`/results/exams/${examId}/unpublish`, k('unpublished_done'))}>
                                {k('unpublish')}
                            </Button>
                        ) : (
                            <Button disabled={busy || rows.length === 0} className="h-14 bg-red-600 px-7 text-lg text-white shadow-[0_8px_20px_rgba(214,32,42,.25)] hover:bg-red-600" onClick={() => setAsking(true)}>
                                📢 {k('publish')}
                            </Button>
                        )}
                    </span>
                )}
            </div>

            <div className="flex flex-wrap items-center gap-2">
                {sections.length > 1 && <NativeSelect className="w-auto" aria-label={t('students::admin.fields.section_id')} placeholder={t('exams::admin.admit.all_sections')} value={sectionId} onChange={(event) => setSectionId(event.target.value)} options={sections.map((section) => ({ value: section.id, label: section.name }))} />}
                {(['tabulation', 'merit', 'marksheets'] as const).map((document) => (
                    <Button key={document} variant="outline" size="sm" className="h-10" render={<a href={`/api/results/exams/${examId}/classes/${classId}/${document}?section_id=${sectionId}`} target="_blank" rel="noreferrer" />}>
                        <PrinterIcon /> {t(`results::admin.preview.print_${document}`)}
                    </Button>
                ))}
            </div>

            {rows.length === 0 ? (
                <DashedEmpty glyph="📊" title={t('results::admin.preview.empty')} />
            ) : (
                <div className="grid items-start gap-4" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 420px), 1fr))' }}>
                    <div className="overflow-x-auto rounded-2xl border border-line bg-surface">
                        <div className="bg-surface-2 px-3.5 py-3 font-bold">{k('tabulation')}</div>
                        <table className="w-full border-collapse text-sm" style={{ minWidth: 520 }}>
                            <thead>
                                <tr className="text-left text-muted-foreground">
                                    <th className="px-3 py-2 font-bold">{t('students::admin.fields.roll')}</th>
                                    <th className="px-2 py-2 font-bold">{k('student')}</th>
                                    {subjectNames.map((name) => (
                                        <th key={name} className="px-2 py-2 font-bold whitespace-nowrap">
                                            {name}
                                        </th>
                                    ))}
                                    <th className="px-3 py-2 font-bold">GPA</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((row) => (
                                    <tr key={row.id} className="border-t border-line">
                                        <td className="px-3 py-2">{row.roll ? bnDigits(row.roll) : '—'}</td>
                                        <td className="px-2 py-2 font-semibold whitespace-nowrap">{row.name}</td>
                                        {subjectNames.map((name) => {
                                            const subject = row.subjects.find((candidate) => candidate.name === name);

                                            return (
                                                <td key={name} className="px-2 py-2" style={subject && !subject.passed ? { color: 'var(--danger)', fontWeight: 700 } : undefined}>
                                                    {subject ? (subject.absent ? k('absent_short') : bnDigits(subject.total)) : '—'}
                                                </td>
                                            );
                                        })}
                                        <td className="px-3 py-2 font-bold text-green-700">{row.is_passed ? formatNumber(row.gpa, 2) : k('failed')}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="overflow-hidden rounded-2xl border border-line bg-surface">
                        <div className="bg-surface-2 px-3.5 py-3 font-bold">🏆 {k('merit')}</div>
                        {merit.length === 0 && <div className="border-t border-line px-3.5 py-4 text-sm text-muted-foreground">{k('no_merit')}</div>}
                        {merit.map((row, index) => (
                            <div key={row.id} className="flex items-center gap-3 border-t border-line px-3.5 py-2.5">
                                <span className="grid size-8 place-items-center rounded-full font-bold text-[#17212B]" style={{ background: index === 0 ? 'var(--yellow-400)' : index === 1 ? 'var(--line-strong)' : 'var(--surface-2)' }}>
                                    {bnDigits(row.position_in_class)}
                                </span>
                                <span className="flex-1">{row.name}</span>
                                <strong>{formatNumber(row.gpa, 2)}</strong>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {incomplete > 0 && !published && <Notice tone="warn">⚠ {k('incomplete_warn', { count: bnDigits(incomplete) })}</Notice>}

            <Dialog open={asking} onOpenChange={setAsking}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{k('publish')}</DialogTitle>
                    </DialogHeader>
                    <div className="grid gap-3.5">
                        <span>{k('confirm')}</span>
                        <div className="rounded-xl px-3.5 py-3 text-sm font-semibold" style={{ background: 'var(--warn-bg)', color: 'var(--warn)' }}>
                            {k('confirm_warn')}
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setAsking(false)}>
                            {t('core::admin.common.cancel')}
                        </Button>
                        <Button className="bg-red-600 px-5 text-white hover:bg-red-600" disabled={busy} onClick={() => void run(`/results/exams/${examId}/publish`, k('published_done'))}>
                            {k('yes_publish')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
