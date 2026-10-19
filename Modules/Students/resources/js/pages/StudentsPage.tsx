import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router';

import { useAcademicOptions } from '@academic/js/hooks/useAcademicOptions';
import { HeaderActions } from '@admin/js';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { TablePagination } from '@core/js/components/DataTable';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { FilterBar } from '@core/js/components/Surface';
import { ErrorState, LoadingState } from '@core/js/components/States';
import { usePaginated } from '@core/js/hooks/usePaginated';
import { t } from '@core/js/i18n';
import { bnDigits } from '@core/js/number';
import { can } from '@core/js/permissions';

import type { Student } from '../types';

const k = (key: string) => t(`students::admin.list.${key}`);

/** The class column: "প্রথম – ক". */
function classLabel(student: Student): string {
    return [student.class_name, student.section_name].filter(Boolean).join(' – ');
}

function FeePill({ student, small = false }: { student: Student; small?: boolean }) {
    if (!student.fee_badge) {
        return null;
    }

    return (
        <Badge variant={student.fee_badge === 'paid' ? 'success' : 'destructive'} className={small ? 'text-xs' : undefined}>
            {k(student.fee_badge)}
        </Badge>
    );
}

export function StudentsPage() {
    const navigate = useNavigate();
    const academic = useAcademicOptions();
    const [filters, setFilters] = useState<Record<string, string>>({ session_id: '', class_id: '', section_id: '', status: '' });
    const [search, setSearch] = useState('');
    const [debounced, setDebounced] = useState('');
    const sessionId = filters.session_id || (academic.current_session_id ? String(academic.current_session_id) : '');
    const { rows, meta, loading, failed, setPage, reload } = usePaginated<Student>('/students', { ...filters, session_id: sessionId, search: debounced });

    useEffect(() => {
        const timer = setTimeout(() => setDebounced(search), 300);

        return () => clearTimeout(timer);
    }, [search]);

    const sections = academic.classes.find((schoolClass) => String(schoolClass.id) === filters.class_id)?.sections ?? [];
    const setFilter = (name: string, value: string) => setFilters((current) => ({ ...current, [name]: value, ...(name === 'class_id' ? { section_id: '' } : {}) }));
    const exportQuery = new URLSearchParams(Object.entries({ ...filters, session_id: sessionId, search: debounced }).filter(([, value]) => value)).toString();

    return (
        <div className="grid gap-3.5">
            <HeaderActions.Fill>
                {can('students.import') && (
                    <Button variant="outline" render={<Link to="/students/import" />}>
                        ⬆ {t('students::admin.import_page.title')}
                    </Button>
                )}
                {can('students.promote') && (
                    <Button variant="outline" render={<Link to="/students/promote" />}>
                        ⇪ {t('students::admin.promote_page.title')}
                    </Button>
                )}
                <Button variant="outline" render={<a href={`/api/students/export?${exportQuery}`} />}>
                    ⬇ {t('students::admin.export')}
                </Button>
                {can('students.manage') && <Button render={<Link to="/students/new" />}>+ {t('students::admin.new')}</Button>}
            </HeaderActions.Fill>

            <FilterBar>
                <Input className="col-span-2" placeholder={`🔍 ${k('search')}`} value={search} onChange={(event) => setSearch(event.target.value)} aria-label={k('search')} />
                <NativeSelect
                    aria-label={t('students::admin.fields.session_id')}
                    value={sessionId}
                    onChange={(event) => setFilter('session_id', event.target.value)}
                    options={academic.sessions.map((session) => ({ value: session.id, label: String(session.id) === sessionId ? k('session_label').replace(':name', session.name) : session.name }))}
                />
                <NativeSelect aria-label={t('students::admin.fields.class_id')} placeholder={k('all_classes')} value={filters.class_id} onChange={(event) => setFilter('class_id', event.target.value)} options={academic.classes.map((schoolClass) => ({ value: schoolClass.id, label: schoolClass.name }))} />
                <NativeSelect aria-label={t('students::admin.fields.section_id')} placeholder={k('section_all')} value={filters.section_id} onChange={(event) => setFilter('section_id', event.target.value)} options={sections.map((section) => ({ value: section.id, label: section.name }))} />
                <NativeSelect aria-label={t('students::admin.fields.status')} placeholder={k('status_all')} value={filters.status} onChange={(event) => setFilter('status', event.target.value)} options={['active', 'inactive'].map((value) => ({ value, label: t(`students::admin.status.${value}`) }))} />
            </FilterBar>

            {failed && rows.length === 0 && <ErrorState onRetry={reload} />}
            {loading && rows.length === 0 && !failed && <LoadingState />}
            {!loading && !failed && rows.length === 0 && <div className="rounded-2xl border-2 border-dashed border-input p-9 text-center text-muted-foreground">{k('no_match')}</div>}

            {rows.length > 0 && (
                <>
                    <div className="hidden overflow-x-auto rounded-2xl border border-line bg-surface md:block">
                        <Table className="min-w-[720px]">
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{k('student')}</TableHead>
                                    <TableHead>ID</TableHead>
                                    <TableHead>{t('students::admin.fields.class_id')}</TableHead>
                                    <TableHead>{t('students::admin.fields.roll')}</TableHead>
                                    <TableHead>{k('fee')}</TableHead>
                                    <TableHead>{t('students::admin.fields.status')}</TableHead>
                                    <TableHead />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {rows.map((student) => (
                                    <TableRow key={student.id} className={loading ? 'opacity-60' : undefined}>
                                        <TableCell>
                                            <span className="flex items-center gap-2.5">
                                                <span className="grid size-9 flex-none place-items-center rounded-full bg-green-50 font-bold text-green-700">{student.name_bn.trim().slice(0, 1)}</span>
                                                <strong className="font-semibold">{student.name_bn}</strong>
                                            </span>
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">{bnDigits(student.student_id)}</TableCell>
                                        <TableCell>{classLabel(student)}</TableCell>
                                        <TableCell>{student.roll ? bnDigits(student.roll) : '—'}</TableCell>
                                        <TableCell>
                                            <FeePill student={student} />
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={student.status === 'active' ? 'default' : 'secondary'}>{t(`students::admin.status.${student.status}`)}</Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button variant="outline" size="sm" className="font-semibold" render={<Link to={`/students/${student.id}`} />}>
                                                {k('view')}
                                            </Button>{' '}
                                            <Button variant="secondary" size="sm" className="font-semibold" render={<Link to={`/students/${student.id}/edit`} />}>
                                                {t('core::admin.common.edit')}
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>

                    <div className="grid gap-2 md:hidden">
                        {rows.map((student) => (
                            <button key={student.id} type="button" onClick={() => navigate(`/students/${student.id}`)} className="flex cursor-pointer items-center gap-3 rounded-2xl border border-line bg-surface p-3 text-left text-foreground">
                                <span className="grid size-11 flex-none place-items-center rounded-full bg-green-50 font-bold text-green-700">{student.name_bn.trim().slice(0, 1)}</span>
                                <span className="grid min-w-0 flex-1 leading-[1.4]">
                                    <strong>{student.name_bn}</strong>
                                    <span className="text-[13px] text-muted-foreground">
                                        {classLabel(student)} · {k('roll_short')} {student.roll ? bnDigits(student.roll) : '—'} · {bnDigits(student.student_id)}
                                    </span>
                                </span>
                                <FeePill student={student} small />
                            </button>
                        ))}
                    </div>

                    {meta && <TablePagination meta={meta} onPageChange={setPage} />}
                </>
            )}
        </div>
    );
}
