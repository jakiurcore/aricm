import { useState } from 'react';
import { toast } from 'sonner';

import { useAcademicOptions } from '@academic/js/hooks/useAcademicOptions';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { api, ApiError } from '@core/js/api';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { t } from '@core/js/i18n';
import { bnDigits } from '@core/js/number';

interface Props {
    studentIds: number[];
    open: boolean;
    onClose: () => void;
    onPromoted: () => void;
}

export function PromoteDialog({ studentIds, open, onClose, onPromoted }: Props) {
    const academic = useAcademicOptions();
    const [sessionId, setSessionId] = useState('');
    const [classId, setClassId] = useState('');
    const [sectionId, setSectionId] = useState('');
    const [rollMode, setRollMode] = useState('sequential');
    const sections = academic.classes.find((schoolClass) => String(schoolClass.id) === classId)?.sections ?? [];

    const submit = async () => {
        try {
            const response = await api.post<{ data: { promoted: number } }>('/students/promote', {
                student_ids: studentIds,
                session_id: sessionId || academic.current_session_id,
                class_id: classId,
                section_id: sectionId || null,
                roll_mode: rollMode,
            });
            toast.success(t('students::admin.promote.done', { count: bnDigits(response.data.promoted) }));
            onPromoted();
        } catch (error) {
            toast.error(error instanceof ApiError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : t('core::admin.common.error'));
        }
    };

    return (
        <Dialog open={open} onOpenChange={(next) => !next && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('students::admin.promote.title', { count: bnDigits(studentIds.length) })}</DialogTitle>
                </DialogHeader>
                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label={t('students::admin.fields.session_id')} htmlFor="p-session">
                        <NativeSelect id="p-session" value={sessionId || String(academic.current_session_id ?? '')} onChange={(event) => setSessionId(event.target.value)} options={academic.sessions.map((session) => ({ value: session.id, label: session.name }))} />
                    </Field>
                    <Field label={t('students::admin.fields.class_id')} htmlFor="p-class">
                        <NativeSelect id="p-class" placeholder="—" value={classId} onChange={(event) => { setClassId(event.target.value); setSectionId(''); }} options={academic.classes.map((schoolClass) => ({ value: schoolClass.id, label: schoolClass.name }))} />
                    </Field>
                    <Field label={t('students::admin.fields.section_id')} htmlFor="p-section">
                        <NativeSelect id="p-section" placeholder="—" value={sectionId} onChange={(event) => setSectionId(event.target.value)} options={sections.map((section) => ({ value: section.id, label: section.name }))} />
                    </Field>
                    <Field label={t('students::admin.promote.roll_mode')} htmlFor="p-roll">
                        <NativeSelect id="p-roll" value={rollMode} onChange={(event) => setRollMode(event.target.value)} options={['sequential', 'keep', 'clear'].map((value) => ({ value, label: t(`students::admin.promote.roll.${value}`) }))} />
                    </Field>
                </div>
                <DialogFooter>
                    <Button variant="outline" onClick={onClose}>
                        {t('core::admin.common.cancel')}
                    </Button>
                    <Button disabled={!classId || studentIds.length === 0} onClick={() => void submit()}>
                        {t('students::admin.promote.submit')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
