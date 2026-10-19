import { useCallback, useEffect, useState } from 'react';

import { api } from '../api';
import type { PageMeta } from '../components/DataTable';

interface PageResponse<T> {
    data: T[];
    meta: PageMeta;
}

/** Fetches a Laravel resource collection page by page, re-fetching whenever the page or filters change. */
export function usePaginated<T>(path: string, filters: Record<string, string | undefined> = {}) {
    const [page, setPage] = useState(1);
    const [rows, setRows] = useState<T[]>([]);
    const [meta, setMeta] = useState<PageMeta>();
    const [loading, setLoading] = useState(true);
    const filterKey = JSON.stringify(filters);

    const load = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get<PageResponse<T>>(path, { ...JSON.parse(filterKey), page });
            setRows(response.data);
            setMeta(response.meta);
        } finally {
            setLoading(false);
        }
    }, [path, filterKey, page]);

    useEffect(() => {
        void load();
    }, [load]);

    // A new search starts from page 1.
    useEffect(() => {
        setPage(1);
    }, [filterKey]);

    return { rows, meta, loading, page, setPage, reload: load };
}
