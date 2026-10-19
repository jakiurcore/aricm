import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { api, ApiError } from '@core/js/api';
import { ErrorState, LoadingState } from '@core/js/components/States';
import { IconButton, ListCard, ListCardHead, ListRow } from '@core/js/components/Surface';
import { t } from '@core/js/i18n';
import { bnDigits } from '@core/js/number';
import { can } from '@core/js/permissions';

import { AcademicTabs } from '../components/AcademicTabs';

interface Subject {
    id: number;
    name_bn: string;
}

interface SchoolClass {
    id: number;
    name_bn: string;
    subjects_count: number;
}

interface Mapped {
    subject_id: number;
    is_optional: boolean;
}

const k = (key: string) => t(`academic::admin.mapping.${key}`);

/** One class's subject list: which are taught, which of those are optional. */
function MappingDialog({ schoolClass, subjects, onClose, onSaved }: { schoolClass: SchoolClass | null; subjects: Subject[]; onClose: () => void; onSaved: () => void }) {
    const [selected, setSelected] = useState<Record<number, boolean>>({});
    const [optional, setOptional] = useState<Record<number, boolean>>({});
    const [busy, setBusy] = useState(false);

    useEffect(() => {
        if (!schoolClass) {
            return;
        }

        setSelected({});
        setOptional({});
        void api.get<{ data: Mapped[] }>(`/academic/classes/${schoolClass.id}/subjects`).then((response) => {
            setSelected(Object.fromEntries(response.data.map((row) => [row.subject_id, true])));
            setOptional(Object.fromEntries(response.data.map((row) => [row.subject_id, row.is_optional])));
        });
    }, [schoolClass]);

    const save = async () => {
        if (!schoolClass) {
            return;
        }

        setBusy(true);

        try {
            const rows = subjects.filter((subject) => selected[subject.id]).map((subject) => ({ subject_id: subject.id, is_optional: Boolean(optional[subject.id]) }));
            await api.put(`/academic/classes/${schoolClass.id}/subjects`, { subjects: rows });
            toast.success(k('saved'));
            onSaved();
        } catch (error) {
            toast.error(error instanceof ApiError ? error.message : t('core::admin.common.error'));
        } finally {
            setBusy(false);
        }
    };

    return (
        <Dialog open={schoolClass !== null} onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {k('title')} — {schoolClass?.name_bn}
                    </DialogTitle>
                </DialogHeader>
                <div className="grid max-h-[50vh] gap-1.5 overflow-y-auto">
                    <div className="grid gap-2.5 px-3 text-[13px] font-bold text-muted-foreground" style={{ gridTemplateColumns: '1fr 80px 80px' }}>
                        <span>{t('academic::admin.subjects.title')}</span>
                        <span>{k('taught')}</span>
                        <span>{k('optional')}</span>
                    </div>
                    {subjects.map((subject) => (
                        <div key={subject.id} className="grid items-center gap-2.5 rounded-xl border border-line bg-surface-2 px-3 py-2" style={{ gridTemplateColumns: '1fr 80px 80px' }}>
                            <span className="font-semibold">{subject.name_bn}</span>
                            <input type="checkbox" className="m-[3px_3px_3px_4px] size-[22px] min-h-0 accent-green-700" aria-label={`${subject.name_bn} — ${k('taught')}`} checked={Boolean(selected[subject.id])} onChange={(event) => setSelected({ ...selected, [subject.id]: event.target.checked })} />
                            <input type="checkbox" className="m-[3px_3px_3px_4px] size-[22px] min-h-0 accent-green-700" aria-label={`${subject.name_bn} — ${k('optional')}`} disabled={!selected[subject.id]} checked={Boolean(optional[subject.id])} onChange={(event) => setOptional({ ...optional, [subject.id]: event.target.checked })} />
                        </div>
                    ))}
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" onClick={onClose}>
                        {t('core::admin.common.cancel')}
                    </Button>
                    <Button disabled={busy} onClick={() => void save()}>
                        {busy ? t('core::admin.common.saving') : t('core::admin.common.save')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

/** The design's "শ্রেণি-বিষয়" tab: a row per class with how many subjects it has; ✎ edits the list. */
export function MappingPage() {
    const [classes, setClasses] = useState<SchoolClass[] | null>(null);
    const [subjects, setSubjects] = useState<Subject[]>([]);
    const [failed, setFailed] = useState(false);
    const [editing, setEditing] = useState<SchoolClass | null>(null);
    const manage = can('academic.manage');

    const load = useCallback(() => {
        setFailed(false);
        Promise.all([api.get<{ data: SchoolClass[] }>('/academic/classes'), api.get<{ data: Subject[] }>('/academic/subjects')])
            .then(([classResponse, subjectResponse]) => {
                setClasses(classResponse.data);
                setSubjects(subjectResponse.data);
            })
            .catch(() => setFailed(true));
    }, []);

    useEffect(load, [load]);

    return (
        <div className="grid gap-3.5">
            <AcademicTabs />
            {failed && <ErrorState onRetry={load} />}
            {!failed && classes === null && <LoadingState rows={3} />}
            {classes !== null && (
                <ListCard>
                    <ListCardHead>{t('academic::admin.tabs.mapping')}</ListCardHead>
                    {classes.map((schoolClass) => (
                        <ListRow key={schoolClass.id}>
                            <span className="flex-1 font-semibold">{schoolClass.name_bn}</span>
                            <span className="text-sm text-muted-foreground">{t('academic::admin.rows.subjects_n', { count: bnDigits(schoolClass.subjects_count) })}</span>
                            {manage && (
                                <IconButton aria-label={t('core::admin.common.edit')} title={t('core::admin.common.edit')} onClick={() => setEditing(schoolClass)}>
                                    ✎
                                </IconButton>
                            )}
                        </ListRow>
                    ))}
                </ListCard>
            )}
            <MappingDialog schoolClass={editing} subjects={subjects} onClose={() => setEditing(null)} onSaved={() => { setEditing(null); load(); }} />
        </div>
    );
}
