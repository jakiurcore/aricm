/**
 * Thin fetch client for the module JSON APIs. Same-domain session auth: the XSRF cookie Laravel sets
 * is echoed back as a header on every write.
 */
export class ApiError extends Error {
    constructor(
        public status: number,
        message: string,
        public errors: Record<string, string[]> = {},
    ) {
        super(message);
    }
}

function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

async function request<T>(method: string, path: string, body?: unknown): Promise<T> {
    const isForm = body instanceof FormData;
    const response = await fetch(`/api${path}`, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
            ...(body !== undefined && !isForm ? { 'Content-Type': 'application/json' } : {}),
        },
        body: body === undefined ? undefined : isForm ? body : JSON.stringify(body),
    });

    if (response.status === 401) {
        window.location.href = '/login';
        throw new ApiError(401, 'Unauthenticated');
    }

    if (response.status === 204) {
        return undefined as T;
    }

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new ApiError(response.status, data.message ?? 'Request failed', data.errors ?? {});
    }

    return data as T;
}

export const api = {
    get: <T>(path: string, params?: Record<string, string | number | boolean | undefined | null>) => {
        const query = params
            ? '?' + new URLSearchParams(Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== '').map(([k, v]) => [k, String(v)])).toString()
            : '';

        return request<T>('GET', path + (query === '?' ? '' : query));
    },
    post: <T>(path: string, body?: unknown) => request<T>('POST', path, body ?? {}),
    put: <T>(path: string, body?: unknown) => request<T>('PUT', path, body ?? {}),
    delete: <T>(path: string) => request<T>('DELETE', path),
};
