/**
 * Translations for the React admin. The server inlines them as window.__I18N__ before any script runs
 * (resources/views/vendor/admin/app.blade.php), so t() is synchronous everywhere, including in menuFilter.
 * Keys are the same as the PHP side, e.g. t('accounts::admin.users.title', { name }).
 */
interface I18nPayload {
    locale: string;
    messages: Record<string, string>;
}

declare global {
    interface Window {
        __I18N__?: I18nPayload;
    }
}

const payload: I18nPayload = window.__I18N__ ?? { locale: 'bn', messages: {} };

export function locale(): string {
    return payload.locale;
}

export function t(key: string, replacements: Record<string, string | number> = {}): string {
    let text = payload.messages[key] ?? key;

    for (const [name, value] of Object.entries(replacements)) {
        text = text.replaceAll(`:${name}`, String(value));
    }

    return text;
}
