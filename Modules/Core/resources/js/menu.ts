import type { LucideIcon } from 'lucide-react';

export interface MenuEntry {
    label: string;
    icon: LucideIcon;
    path: string;
}

interface Group {
    label: string;
    items: MenuEntry[];
}

/**
 * Put entries into a menu group, creating the group if it is not there yet. Modules that share a group (the
 * "Content" group of Notices, Gallery and Admission) call this so none of them depends on another.
 */
export function addToGroup<T extends Group>(menu: T[], label: string, entries: MenuEntry[]): T[] {
    const existing = menu.find((group) => group.label === label);

    if (existing) {
        return menu.map((group) => (group === existing ? { ...group, items: [...group.items, ...entries] } : group));
    }

    return [...menu, { label, items: entries } as unknown as T];
}
