import { useRef, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { api, ApiError } from '@core/js/api';
import { Dropzone, ListCard } from '@core/js/components/Surface';
import { t } from '@core/js/i18n';
import { bnDigits } from '@core/js/number';

interface Result {
    valid: number;
    created: number;
    dry_run: boolean;
    errors: Record<string, string[]>;
}

const k = (key: string, replacements?: Record<string, string | number>) => t(`students::admin.import_page.${key}`, replacements);

/** The design's "CSV ইমপোর্ট": a drop area, then the check report with the import button. */
export function ImportPage() {
    const input = useRef<HTMLInputElement>(null);
    const [file, setFile] = useState<File | null>(null);
    const [result, setResult] = useState<Result | null>(null);
    const [busy, setBusy] = useState(false);
    const [dragging, setDragging] = useState(false);

    const run = async (chosen: File, dryRun: boolean) => {
        setBusy(true);
        const body = new FormData();
        body.append('file', chosen);
        body.append('dry_run', dryRun ? '1' : '0');

        try {
            const response = await api.post<{ data: Result }>('/students/import', body);
            setResult(response.data);

            if (!dryRun) {
                toast.success(t('students::admin.import.done', { count: bnDigits(response.data.created) }));
                setFile(null);
                setResult(null);
            }
        } catch (error) {
            if (error instanceof ApiError && error.body?.data) {
                // A 422 from the importer carries the per-row report in the body, not in the validation errors.
                setResult(error.body.data as Result);
            } else {
                toast.error(error instanceof ApiError ? error.message : t('core::admin.common.error'));
            }
        } finally {
            setBusy(false);
        }
    };

    const choose = (chosen: File | null) => {
        setFile(chosen);
        setResult(null);

        if (chosen) {
            void run(chosen, true);
        }
    };

    const errorLines = result ? Object.entries(result.errors) : [];

    return (
        <div className="grid max-w-[820px] gap-4">
            <Dropzone
                className="px-5 py-8"
                onDragOver={(event) => {
                    event.preventDefault();
                    setDragging(true);
                }}
                onDragLeave={() => setDragging(false)}
                onDrop={(event) => {
                    event.preventDefault();
                    setDragging(false);
                    choose(event.dataTransfer.files?.[0] ?? null);
                }}
                style={dragging ? { borderColor: 'var(--green-700)' } : undefined}
            >
                <span className="text-4xl" aria-hidden="true">
                    📄
                </span>
                <strong className="text-lg">{k('drop')}</strong>
                <span className="text-sm text-muted-foreground">{k('columns')}</span>
                <div className="flex flex-wrap justify-center gap-2">
                    <Button onClick={() => input.current?.click()}>{k('choose')}</Button>
                    <Button variant="outline" render={<a href="/api/students/template" />}>
                        ⬇ {k('template')}
                    </Button>
                </div>
                <input ref={input} type="file" accept=".csv,text/csv" hidden onChange={(event) => choose(event.target.files?.[0] ?? null)} />
            </Dropzone>

            {file && result && (
                <ListCard>
                    <div className="flex flex-wrap items-center justify-between gap-2 bg-surface-2 px-4 py-3">
                        <strong>{file.name}</strong>
                        <span className="flex gap-1.5 text-[13px] font-bold">
                            <Badge variant="success">
                                ✓ {bnDigits(result.valid)} {k('valid')}
                            </Badge>
                            {errorLines.length > 0 && (
                                <Badge variant="destructive">
                                    ✕ {bnDigits(errorLines.length)} {k('errors')}
                                </Badge>
                            )}
                        </span>
                    </div>
                    {errorLines.length > 0 && (
                        <div className="grid gap-1.5 px-4 py-3 text-sm">
                            {errorLines.map(([line, messages]) => (
                                <span key={line} className="text-danger">
                                    {t('students::admin.import.line', { line: bnDigits(line) })}: {messages.join('; ')}
                                </span>
                            ))}
                        </div>
                    )}
                    <div className="flex justify-end border-t border-line px-4 py-3">
                        <Button disabled={busy || errorLines.length > 0 || result.valid === 0} onClick={() => file && void run(file, false)}>
                            {k('import_valid', { count: bnDigits(result.valid) })}
                        </Button>
                    </div>
                </ListCard>
            )}
        </div>
    );
}
