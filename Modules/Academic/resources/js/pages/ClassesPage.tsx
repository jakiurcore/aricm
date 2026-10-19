import { LayersIcon, Trash2Icon } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { api, ApiError } from '@core/js/api';
import { ResourcePage } from '@core/js/components/ResourcePage';
import { t } from '@core/js/i18n';
import { bnDigits } from '@core/js/number';

interface SchoolClass {
    id: number;
    name_bn: string;
    name_en: string | null;
    numeric_order: number;
    division: string | null;
    is_active: boolean;
    sections_count: number;
    subjects_count: number;
}

interface Section {
    id: number;
    name_bn: string;
    name_en: string | null;
}

const k = (key: string) => t(`academic::admin.classes.${key}`);
const s = (key: string) => t(`academic::admin.sections.${key}`);

function SectionsDialog({ schoolClass, onClose }: { schoolClass: SchoolClass | null; onClose: () => void }) {
    const [sections, setSections] = useState<Section[]>([]);
    const [nameBn, setNameBn] = useState('');
    const [nameEn, setNameEn] = useState('');

    const load = useCallback(async () => {
        if (schoolClass) {
            const response = await api.get<{ data: Section[] }>('/academic/sections', { class_id: schoolClass.id });
            setSections(response.data);
        }
    }, [schoolClass]);

    useEffect(() => {
        void load();
    }, [load]);

    const add = async () => {
        try {
            await api.post('/academic/sections', { class_id: schoolClass?.id, name_bn: nameBn, name_en: nameEn });
            setNameBn('');
            setNameEn('');
            await load();
        } catch (error) {
            toast.error(error instanceof ApiError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : t('core::admin.common.error'));
        }
    };

    const remove = async (section: Section) => {
        try {
            await api.delete(`/academic/sections/${section.id}`);
            await load();
        } catch (error) {
            toast.error(error instanceof ApiError ? error.message : t('core::admin.common.error'));
        }
    };

    return (
        <Dialog open={schoolClass !== null} onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {s('title')} — {schoolClass?.name_bn}
                    </DialogTitle>
                </DialogHeader>
                <ul className="divide-y rounded-lg border">
                    {sections.length === 0 && <li className="p-3 text-sm text-muted-foreground">{s('empty')}</li>}
                    {sections.map((section) => (
                        <li key={section.id} className="flex items-center justify-between p-3 text-sm">
                            <span>
                                {section.name_bn} {section.name_en && <span className="text-muted-foreground">({section.name_en})</span>}
                            </span>
                            <Button variant="ghost" size="icon" aria-label={t('core::admin.common.delete')} onClick={() => void remove(section)}>
                                <Trash2Icon />
                            </Button>
                        </li>
                    ))}
                </ul>
                <div className="grid gap-2 sm:grid-cols-[1fr_1fr_auto]">
                    <Input placeholder={s('name_bn')} value={nameBn} onChange={(event) => setNameBn(event.target.value)} />
                    <Input placeholder={s('name_en')} value={nameEn} onChange={(event) => setNameEn(event.target.value)} />
                    <Button onClick={() => void add()} disabled={!nameBn}>
                        {s('new')}
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}

export function ClassesPage() {
    const [managing, setManaging] = useState<SchoolClass | null>(null);

    return (
        <>
            <ResourcePage<SchoolClass>
                title={k('title')}
                newLabel={k('new')}
                editLabel={k('edit')}
                endpoint="/academic/classes"
                searchable={false}
                defaults={{ name_bn: '', name_en: '', numeric_order: 1, division: '', is_active: true }}
                fields={[
                    { name: 'name_bn', label: k('name_bn'), type: 'text' },
                    { name: 'name_en', label: k('name_en'), type: 'text' },
                    { name: 'numeric_order', label: k('numeric_order'), type: 'number' },
                    {
                        name: 'division',
                        label: k('division'),
                        type: 'select',
                        options: [
                            { value: 'kids', label: t('academic::admin.divisions.kids') },
                            { value: 'junior', label: t('academic::admin.divisions.junior') },
                        ],
                    },
                    { name: 'is_active', label: k('is_active'), type: 'switch' },
                ]}
                toForm={(row) => ({ name_bn: row.name_bn, name_en: row.name_en ?? '', numeric_order: row.numeric_order, division: row.division ?? '', is_active: row.is_active })}
                columns={[
                    { header: k('numeric_order'), cell: ({ row }) => bnDigits(row.original.numeric_order) },
                    { header: k('name_bn'), accessorKey: 'name_bn' },
                    { header: k('name_en'), accessorKey: 'name_en' },
                    { header: k('division'), cell: ({ row }) => (row.original.division ? t(`academic::admin.divisions.${row.original.division}`) : k('no_division')) },
                    { header: k('sections'), cell: ({ row }) => bnDigits(row.original.sections_count) },
                    { header: k('subjects'), cell: ({ row }) => bnDigits(row.original.subjects_count) },
                    { header: '', id: 'state', cell: ({ row }) => (row.original.is_active ? null : <Badge variant="outline">{t('academic::admin.classes.inactive')}</Badge>) },
                ]}
                rowActions={(row) => (
                    <Button variant="ghost" size="icon" aria-label={k('manage_sections')} title={k('manage_sections')} onClick={() => setManaging(row)}>
                        <LayersIcon />
                    </Button>
                )}
            />
            <SectionsDialog schoolClass={managing} onClose={() => setManaging(null)} />
        </>
    );
}
