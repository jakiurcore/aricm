export interface ContentEntry {
    key: string;
    page: string;
    group: string;
    type: 'text' | 'textarea' | 'image';
    label: string;
    value_bn: string | null;
    value_en: string | null;
    image_url: string | null;
    has_image_override: boolean;
    default_bn: string | null;
    default_en: string | null;
}

export interface WebsiteItem {
    id: number;
    collection: string;
    title_bn: string;
    title_en: string | null;
    body_bn: string | null;
    body_en: string | null;
    icon: string | null;
    meta: Record<string, string | number | null>;
    image_url: string | null;
    sort_order: number;
    is_active: boolean;
}

export interface PriceRow {
    id: number;
    label_bn: string;
    label_en: string | null;
    note_bn: string | null;
    note_en: string | null;
    prices: Record<string, number>;
    sort_order: number;
}

export interface SectionState {
    key: string;
    label: string;
    default_order: number;
    is_enabled: boolean;
    sort_order: number | null;
}

export interface NavItem {
    label_bn: string;
    label_en: string;
    route: string | null;
    url: string | null;
    visible: boolean;
    sort_order: number;
    location: 'header' | 'footer';
}

/** Collapses an API error bag to the first message of each field. */
export function firstErrors(errors: Record<string, string[]>): Record<string, string> {
    return Object.fromEntries(Object.entries(errors).map(([field, messages]) => [field, messages[0]]));
}

/** Moves the item at `from` by `step` places, or returns the list untouched when it would leave the list. */
export function moved<T>(list: T[], from: number, step: -1 | 1): T[] {
    const to = from + step;

    if (to < 0 || to >= list.length) {
        return list;
    }

    const next = [...list];
    [next[from], next[to]] = [next[to], next[from]];

    return next;
}
