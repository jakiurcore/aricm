import type { LucideIcon } from 'lucide-react';
import type { ComponentType } from 'react';

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

const badges = new Map<string, ComponentType>();

/**
 * Put a count pill (the pending verifications, say) on a menu entry. The component renders the pill itself, or null
 * for nothing to show; the admin shell mounts it inside the entry for as long as the sidebar is shown.
 */
export function registerMenuBadge(path: string, badge: ComponentType): void {
    badges.set(path, badge);
}

export function menuBadge(path: string): ComponentType | undefined {
    return badges.get(path);
}
