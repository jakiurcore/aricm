import { flexRender, getCoreRowModel, useReactTable, type ColumnDef } from '@tanstack/react-table';

import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

import { bnDigits } from '../number';
import { t } from '../i18n';

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
}

/** Server-paginated shadcn table. Pagination state lives with the caller (see usePaginated). */
export function DataTable<T>({ columns, data, loading = false, meta, onPageChange }: DataTableProps<T>) {
    const table = useReactTable({ data, columns, getCoreRowModel: getCoreRowModel(), manualPagination: true });

    return (
        <div className="space-y-3">
            <div className="overflow-x-auto rounded-xl border bg-card">
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
                        {data.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={columns.length} className="h-24 text-center text-muted-foreground">
                                    {loading ? t('core::admin.common.loading') : t('core::admin.common.empty')}
                                </TableCell>
                            </TableRow>
                        ) : (
                            table.getRowModel().rows.map((row) => (
                                <TableRow key={row.id} className={loading ? 'opacity-60' : undefined}>
                                    {row.getVisibleCells().map((cell) => (
                                        <TableCell key={cell.id}>{flexRender(cell.column.columnDef.cell, cell.getContext())}</TableCell>
                                    ))}
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>

            {meta && meta.last_page > 1 && onPageChange && (
                <div className="flex items-center justify-between text-sm text-muted-foreground">
                    <span>{t('core::admin.common.total', { count: bnDigits(meta.total) })}</span>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" disabled={meta.current_page <= 1} onClick={() => onPageChange(meta.current_page - 1)}>
                            {t('core::admin.common.previous')}
                        </Button>
                        <span>{t('core::admin.common.page_of', { page: bnDigits(meta.current_page), pages: bnDigits(meta.last_page) })}</span>
                        <Button variant="outline" size="sm" disabled={meta.current_page >= meta.last_page} onClick={() => onPageChange(meta.current_page + 1)}>
                            {t('core::admin.common.next')}
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}
