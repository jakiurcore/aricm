import { flexRender, getCoreRowModel, useReactTable, type ColumnDef } from '@tanstack/react-table';

import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

import { bnDigits } from '../number';
import { t } from '../i18n';
import { ErrorState, LoadingState } from './States';

export interface PageMeta {
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
}

interface DataTableProps<T> {
    columns: ColumnDef<T, any>[];
    data: T[];
    loading?: boolean;
    meta?: PageMeta;
    onPageChange?: (page: number) => void;
    /** Shown in the dashed frame when nothing matches (defaults to the generic empty line). */
    emptyText?: string;
    /** The request failed: show the design's error panel with a retry in place of the table. */
    failed?: boolean;
    onRetry?: () => void;
}

/** Server-paginated shadcn table. Pagination state lives with the caller (see usePaginated). */
export function DataTable<T>({ columns, data, loading = false, meta, onPageChange, emptyText, failed = false, onRetry }: DataTableProps<T>) {
    const table = useReactTable({ data, columns, getCoreRowModel: getCoreRowModel(), manualPagination: true });

    return (
        <div className="grid gap-3">
            {failed && data.length === 0 && <ErrorState onRetry={onRetry} />}
            {data.length === 0 && loading && !failed && <LoadingState />}
            {data.length === 0 && !loading && !failed && (
                <div className="rounded-2xl border-2 border-dashed border-input p-9 text-center text-muted-foreground">{emptyText ?? t('core::admin.common.empty')}</div>
            )}
            {data.length > 0 && (
            <div className="overflow-x-auto rounded-2xl border border-line bg-card">
                <Table>
                    <TableHeader>
                        {table.getHeaderGroups().map((group) => (
                            <TableRow key={group.id}>
                                {group.headers.map((header) => (
                                    <TableHead key={header.id}>
                                        {header.isPlaceholder ? null : flexRender(header.column.columnDef.header, header.getContext())}
                                    </TableHead>
                                ))}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>
                        {table.getRowModel().rows.map((row) => (
                            <TableRow key={row.id} className={loading ? 'opacity-60' : undefined}>
                                {row.getVisibleCells().map((cell) => (
                                    <TableCell key={cell.id}>{flexRender(cell.column.columnDef.cell, cell.getContext())}</TableCell>
                                ))}
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
            )}

            {meta && onPageChange && <TablePagination meta={meta} onPageChange={onPageChange} />}
        </div>
    );
}

/** The footer of a server-paginated list: the total, previous / next and where you are. Nothing for a single page. */
export function TablePagination({ meta, onPageChange }: { meta: PageMeta; onPageChange: (page: number) => void }) {
    if (meta.last_page <= 1) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center justify-between gap-2 text-sm text-muted-foreground">
            <span>{t('core::admin.common.total', { count: bnDigits(meta.total) })}</span>
            <div className="flex flex-wrap items-center gap-2">
                <Button variant="outline" size="sm" disabled={meta.current_page <= 1} onClick={() => onPageChange(meta.current_page - 1)}>
                    {t('core::admin.common.previous')}
                </Button>
                <span>{t('core::admin.common.page_of', { page: bnDigits(meta.current_page), pages: bnDigits(meta.last_page) })}</span>
                <Button variant="outline" size="sm" disabled={meta.current_page >= meta.last_page} onClick={() => onPageChange(meta.current_page + 1)}>
                    {t('core::admin.common.next')}
                </Button>
            </div>
        </div>
    );
}
