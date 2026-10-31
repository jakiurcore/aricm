import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { api, ApiError } from '@core/js/api';
import { Pill, Toggle } from '@core/js/components/design';
import { ErrorState, LoadingState } from '@core/js/components/States';
import { ListCard, ListCardHead, ListRow, StickyBar } from '@core/js/components/Surface';
import { t } from '@core/js/i18n';

import { moved } from '../types';
import type { SectionState } from '../types';
import { ReorderButtons } from './ReorderButtons';

const k = (key: string, replacements?: Record<string, string | number>) => t(`website::admin.ui.${key}`, replacements);

const ordered = (sections: SectionState[]) => [...sections].sort((a, b) => (a.sort_order ?? a.default_order) - (b.sort_order ?? b.default_order));
const signature = (sections: SectionState[]) => sections.map((section) => `${section.key}:${section.is_enabled ? 1 : 0}`).join('|');

/** Which blocks the home page shows and in what order. */
export function SectionsEditor({ editable = true }: { editable?: boolean }) {
    const [sections, setSections] = useState<SectionState[] | null>(null);
    const [saved, setSaved] = useState('');
    const [failed, setFailed] = useState(false);
    const [saving, setSaving] = useState(false);

    const adopt = (data: SectionState[]) => {
        const list = ordered(data);
        setSections(list);
        setSaved(signature(list));
    };

    const load = useCallback(() => {
        setFailed(false);
        api.get<{ data: SectionState[] }>('/website/sections')
            .then((response) => adopt(response.data))
            .catch(() => setFailed(true));
    }, []);

    useEffect(load, [load]);

    if (failed) {
        return <ErrorState onRetry={load} />;
    }

    if (!sections) {
        return <LoadingState rows={3} height={56} />;
    }

    const dirty = signature(sections) !== saved;

    const save = async () => {
        setSaving(true);

        try {
            const response = await api.put<{ data: SectionState[] }>('/website/sections', { sections: sections.map((section, index) => ({ key: section.key, is_enabled: section.is_enabled, sort_order: (index + 1) * 10 })) });
            adopt(response.data);
            toast.success(t('core::admin.common.saved'));
        } catch (error) {
            toast.error(error instanceof ApiError ? error.message : t('core::admin.common.error'));
        } finally {
            setSaving(false);
        }
    };

    return (
        <fieldset disabled={!editable} className="m-0 grid min-w-0 gap-2 border-0 p-0">
            <p className="m-0 text-[13px] text-muted-foreground">{k('sections.hint')}</p>
            <ListCard>
                <ListCardHead>{k('sections.title')}</ListCardHead>
                {sections.map((section, index) => (
                    <ListRow key={section.key} data-testid="section-row">
                        {editable && <ReorderButtons index={index} count={sections.length} onMove={(step) => setSections((all) => (all ? moved(all, index, step) : all))} />}
                        <span className="min-w-0 flex-1 font-semibold">{section.label}</span>
                        {!section.is_enabled && <Pill tone="muted">{k('hidden')}</Pill>}
                        <Toggle checked={section.is_enabled} label={`${k('show_on_site')}: ${section.label}`} onChange={(checked) => setSections((all) => all?.map((row) => (row.key === section.key ? { ...row, is_enabled: checked } : row)) ?? null)} />
                    </ListRow>
                ))}
            </ListCard>
            {editable && (
                <StickyBar className="items-center">
                    {dirty && <Pill tone="warn" size="md">{k('unsaved_generic')}</Pill>}
                    <Button size="lg" onClick={() => void save()} disabled={saving || !dirty}>
                        {saving ? t('core::admin.common.saving') : k('save_changes')}
                    </Button>
                </StickyBar>
            )}
        </fieldset>
    );
}
