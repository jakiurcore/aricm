import { useCallback, useEffect, useState } from 'react';

/** The design keeps these under arm_*, so the admin and the portals share one preference. */
const THEME_KEY = 'arm_theme';
const SIDE_KEY = 'arm_admin_side';

export type Theme = 'light' | 'dark';

function read(key: string): string | null {
    try {
        return localStorage.getItem(key);
    } catch {
        return null;
    }
}

function write(key: string, value: string): void {
    try {
        localStorage.setItem(key, value);
    } catch {
        // Private windows and blocked storage: the preference just does not outlive the page.
    }
}

export function currentTheme(): Theme {
    return document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light';
}

export function useTheme(): [Theme, () => void] {
    const [theme, setTheme] = useState<Theme>(currentTheme);

    const toggle = useCallback(() => {
        const next: Theme = currentTheme() === 'dark' ? 'light' : 'dark';
        document.documentElement.dataset.theme = next;
        write(THEME_KEY, next);
        setTheme(next);
    }, []);

    return [theme, toggle];
}

/** Whether the desktop sidebar shows labels (true) or only icons (false). */
export function useSidebarExpanded(): [boolean, (expanded: boolean) => void] {
    const [expanded, setExpanded] = useState(() => read(SIDE_KEY) !== 'collapsed');

    const update = useCallback((next: boolean) => {
        write(SIDE_KEY, next ? 'expanded' : 'collapsed');
        setExpanded(next);
    }, []);

    return [expanded, update];
}

/** True below the design's 768px breakpoint, where the sidebar becomes a drawer. Correct on the first render. */
export function useIsPhone(): boolean {
    const query = '(max-width: 767.98px)';
    const [phone, setPhone] = useState(() => window.matchMedia(query).matches);

    useEffect(() => {
        const media = window.matchMedia(query);
        const onChange = () => setPhone(media.matches);
        media.addEventListener('change', onChange);

        return () => media.removeEventListener('change', onChange);
    }, []);

    return phone;
}
