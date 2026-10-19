import { useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { api } from '@core/js/api';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { t } from '@core/js/i18n';

interface Subject {
    id: number;
    name_bn: string;
}

interface SchoolClass {
    id: number;
    name_bn: string;
}

interface Mapped {
    subject_id: number;
    is_optional: boolean;
}

const k = (key: string) => t(`academic::admin.mapping.${key}`);

export function MappingPage() {
    const [classes, setClasses] = useState<SchoolClass[]>([]);
    const [subjects, setSubjects] = useState<Subject[]>([]);
    const [classId, setClassId] = useState('');
    const [selected, setSelected] = useState<Record<number, boolean>>({});
    const [optional, setOptional] = useState<Record<number, boolean>>({});

    useEffect(() => {
        void Promise.all([api.get<{ data: SchoolClass[] }>('/academic/classes'), api.get<{ data: Subject[] }>('/academic/subjects')]).then(([classResponse, subjectResponse]) => {
            setClasses(classResponse.data);
            setSubjects(subjectResponse.data);
            setClassId(String(classResponse.data[0]?.id ?? ''));
        });
    }, []);

    useEffect(() => {
        if (!classId) {
            return;
        }

        void api.get<{ data: Mapped[] }>(`/academic/classes/${classId}/subjects`).then((response) => {
            setSelected(Object.fromEntries(response.data.map((row) => [row.subject_id, true])));
            setOptional(Object.fromEntries(response.data.map((row) => [row.subject_id, row.is_optional])));
        });
    }, [classId]);

    const save = async () => {
        const rows = subjects.filter((subject) => selected[subject.id]).map((subject) => ({ subject_id: subject.id, is_optional: Boolean(optional[subject.id]) }));
        await api.put(`/academic/classes/${classId}/subjects`, { subjects: rows });
        toast.success(k('saved'));
    };

    return (
        <div className="max-w-2xl space-y-4">
            <h1 className="text-2xl font-semibold tracking-tight">{k('title')}</h1>
            <div className="max-w-xs">
                <Field label={k('class')} htmlFor="class">
                    <NativeSelect id="class" value={classId} onChange={(event) => setClassId(event.target.value)} options={classes.map((schoolClass) => ({ value: schoolClass.id, label: schoolClass.name_bn }))} />
                </Field>
            </div>
            <div className="rounded-xl border bg-card">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>{t('academic::admin.subjects.title')}</TableHead>
                            <TableHead>{k('taught')}</TableHead>
                            <TableHead>{k('optional')}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {subjects.map((subject) => (
                            <TableRow key={subject.id}>
                                <TableCell>{subject.name_bn}</TableCell>
                                <TableCell>
                                    <Checkbox checked={Boolean(selected[subject.id])} onCheckedChange={(checked) => setSelected({ ...selected, [subject.id]: Boolean(checked) })} />
                                </TableCell>
                                <TableCell>
                                    <Checkbox disabled={!selected[subject.id]} checked={Boolean(optional[subject.id])} onCheckedChange={(checked) => setOptional({ ...optional, [subject.id]: Boolean(checked) })} />
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
            <Button onClick={() => void save()}>{t('core::admin.common.save')}</Button>
        </div>
    );
}
