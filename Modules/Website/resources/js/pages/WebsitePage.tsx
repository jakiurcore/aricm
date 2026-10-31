import { SettingsIcon } from 'lucide-react';
import { useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { Link } from 'react-router';

import { Chip } from '@core/js/components/design';
import { Panel, PanelTitle } from '@core/js/components/Surface';
import { t } from '@core/js/i18n';
import { can } from '@core/js/permissions';

import { ContentForm } from '../components/ContentForm';
import { ItemListEditor } from '../components/ItemListEditor';
import { MenuEditor } from '../components/MenuEditor';
import { PriceTable } from '../components/PriceTable';
import type { PriceTableHandle } from '../components/PriceTable';
import { SectionsEditor } from '../components/SectionsEditor';

const k = (key: string) => t(`website::admin.ui.${key}`);

const TABS = ['home', 'about', 'academics', 'admission', 'contact', 'menu'] as const;
type Tab = (typeof TABS)[number];

/** Everything the client can change on the public site, one tab per area. */
export function WebsitePage() {
    const editable = can('website.manage');
    const [tab, setTab] = useState<Tab>('home');
    const [visited, setVisited] = useState<Tab[]>(['home']);
    const prices = useRef<PriceTableHandle>(null);

    const open = (next: Tab) => {
        setTab(next);
        setVisited((all) => (all.includes(next) ? all : [...all, next]));
    };

    const items = (collection: string, title: string, fields = {}, hint?: string) => <ItemListEditor key={collection} collection={collection} title={title} hint={hint} fields={fields} editable={editable} onChanged={collection === 'divisions' ? () => prices.current?.reload() : undefined} />;

    const panels: Record<Tab, ReactNode> = {
        home: (
            <>
                <ContentForm page="home" editable={editable} />
                {items('programs', k('lists.programs'), {}, k('lists.programs_hint'))}
                {items('phonetics', k('lists.phonetics'), {}, k('lists.phonetics_hint'))}
                {items('divisions', k('lists.divisions'), { body: true, meta: [{ name: 'key', label: 'key', lockedWhenEditing: true }, { name: 'age_bn', label: 'age_bn' }, { name: 'age_en', label: 'age_en' }, { name: 'min', label: 'min', type: 'number' }, { name: 'max', label: 'max', type: 'number' }] }, k('lists.divisions_hint'))}
                {items('why', k('lists.why'), { body: true, icon: true })}
            </>
        ),
        about: (
            <>
                <ContentForm page="about" editable={editable} />
                {items('about_values', k('lists.about_values'), { body: true })}
            </>
        ),
        academics: (
            <>
                <ContentForm page="academics" editable={editable} />
                {items('academics_programs', k('lists.academics_programs'), { body: true, icon: true })}
                {items('schedule', k('lists.schedule'), { meta: [{ name: 'time_bn', label: 'time_bn' }, { name: 'time_en', label: 'time_en' }] }, k('lists.schedule_hint'))}
            </>
        ),
        admission: (
            <>
                <ContentForm page="admission" editable={editable} />
                <PriceTable ref={prices} editable={editable} />
                {items('admission_docs', k('lists.admission_docs'))}
            </>
        ),
        contact: (
            <>
                <ContentForm page="contact" editable={editable} />
                <Panel className="gap-2">
                    <PanelTitle>{k('contact.settings_title')}</PanelTitle>
                    <p className="m-0 text-sm text-muted-foreground">{k('contact.settings_body')}</p>
                    {can('settings.manage') && (
                        <Link to="/settings" className="inline-flex min-h-11 w-fit items-center gap-2 rounded-xl border border-line px-3.5 font-semibold text-foreground no-underline hover:bg-surface-2">
                            <SettingsIcon className="size-4" />
                            {t('core::admin.settings.menu')}
                        </Link>
                    )}
                </Panel>
            </>
        ),
        menu: (
            <>
                <SectionsEditor editable={editable} />
                <MenuEditor editable={editable && can('settings.manage')} />
            </>
        ),
    };

    return (
        <div className="grid gap-4">
            <div role="tablist" aria-label={k('tabs.label')} className="flex flex-wrap gap-2">
                {TABS.map((id) => (
                    <Chip key={id} role="tab" aria-selected={tab === id} active={tab === id} onClick={() => open(id)}>
                        {k(`tabs.${id}`)}
                    </Chip>
                ))}
            </div>
            {TABS.filter((id) => visited.includes(id)).map((id) => (
                <div key={id} role="tabpanel" hidden={tab !== id} className="grid gap-4">
                    {panels[id]}
                </div>
            ))}
        </div>
    );
}
