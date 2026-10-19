/**
 * What the signed-in user may do. The server inlines the permission names as window.__CAN__; this only decides what
 * to show. Every action is still authorised by the server, so hiding a button is a courtesy, not security.
 */
declare global {
    interface Window {
        __CAN__?: string[];
    }
}

const granted = new Set<string>(window.__CAN__ ?? []);

export function can(permission: string): boolean {
    return granted.has(permission);
}

export function canAny(...permissions: string[]): boolean {
    return permissions.some(can);
}
