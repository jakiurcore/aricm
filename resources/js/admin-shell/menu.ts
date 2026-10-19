import type { LucideIcon } from 'lucide-react';

import type { MenuGroup } from '@admin/js';
import { t } from '@core/js/i18n';

export interface ShellItem {
    label: string;
    path: string;
    icon: LucideIcon;
    /** The design's glyph for the entry; the module's own icon is the fallback for entries the design does not know. */
    glyph: string | null;
}

export interface ShellGroup {
    key: string;
    label: string;
    items: ShellItem[];
}

type Key = 'main' | 'students' | 'exams' | 'fees' | 'teachers' | 'site' | 'system';

/** Where each known page sits in the design's menu, and the glyph it carries. Order here is the order shown. */
const PLACEMENT: Array<[path: string, group: Key, glyph: string]> = [
    ['/dashboard', 'main', '▦'],
    ['/students', 'students', '👥'],
    ['/students/new', 'students', '＋'],
    ['/students/import', 'students', '⬆'],
    ['/students/promote', 'students', '⇪'],
    ['/students/id-cards', 'students', '🪪'],
    ['/academic', 'students', '🏫'],
    ['/exams', 'exams', '📝'],
    ['/results', 'exams', '🏅'],
    ['/results-grading', 'exams', '🔢'],
    ['/fees/collection', 'fees', '৳'],
    ['/fees/verification', 'fees', '✓'],
    ['/fees/invoices', 'fees', '🧾'],
    ['/fees/structure', 'fees', '▦'],
    ['/fees/heads', 'fees', '≡'],
    ['/fees/concessions', 'fees', '%'],
    ['/fees/reports', 'fees', '📊'],
    ['/fees/settings', 'fees', '⚙'],
    ['/teachers', 'teachers', '🧑‍🏫'],
    ['/teachers/assignments', 'teachers', '▤'],
    ['/diary', 'teachers', '📓'],
    ['/diary/phrases', 'teachers', '💬'],
    ['/diary/settings', 'teachers', '⚙'],
    ['/notices', 'site', '📢'],
    ['/gallery', 'site', '🖼'],
    ['/admission', 'site', '📥'],
    ['/admission-settings', 'site', '⚙'],
    ['/settings', 'site', '⚙'],
    ['/users', 'system', '🔐'],
    ['/roles', 'system', '🔐'],
];

const GROUP_ORDER: Key[] = ['main', 'students', 'exams', 'fees', 'teachers', 'site', 'system'];

/**
 * Re-sorts what the modules registered into the design's groups. Every module keeps registering its own entries and
 * group label; a page the design does not place keeps its module's group and is listed after the design's own.
 */
export function buildShellMenu(menu: MenuGroup[]): ShellGroup[] {
    const placed = new Map<Key, Array<{ rank: number; item: ShellItem }>>();
    const extra: ShellGroup[] = [];

    for (const group of menu) {
        for (const entry of group.items) {
            const rank = PLACEMENT.findIndex(([path]) => path === entry.path);

            if (rank === -1) {
                const target = extra.find((candidate) => candidate.key === group.label) ?? { key: group.label, label: group.label, items: [] };
                if (!extra.includes(target)) {
                    extra.push(target);
                }
                target.items.push({ label: entry.label, path: entry.path, icon: entry.icon, glyph: null });
                continue;
            }

            const [, key, glyph] = PLACEMENT[rank];
            placed.set(key, [...(placed.get(key) ?? []), { rank, item: { label: entry.label, path: entry.path, icon: entry.icon, glyph } }]);
        }
    }

    const known = GROUP_ORDER.filter((key) => placed.has(key)).map((key) => ({
        key,
        label: t(`core::admin.shell.groups.${key}`),
        items: placed.get(key)!.sort((a, b) => a.rank - b.rank).map(({ item }) => item),
    }));

    return [...known, ...extra];
}
