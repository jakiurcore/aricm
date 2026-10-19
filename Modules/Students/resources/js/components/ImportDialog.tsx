import { useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { api, ApiError } from '@core/js/api';
import { t } from '@core/js/i18n';
import { bnDigits } from '@core/js/number';

interface Result {
    valid: number;
    created: number;
    dry_run: boolean;
    errors: Record<string, string[]>;
}

export function ImportDialog({ open, onClose, onImported }: { open: boolean; onClose: () => void; onImported: () => void }) {
    const [file, setFile] = useState<File | null>(null);
    const [result, setResult] = useState<Result | null>(null);
    const [busy, setBusy] = useState(false);

    const run = async (dryRun: boolean) => {
        if (!file) {
            return;
        }

        setBusy(true);
        const body = new FormData();
        body.append('file', file);
        body.append('dry_run', dryRun ? '1' : '0');

        try {
            const response = await api.post<{ data: Result }>('/students/import', body);
            setResult(response.data);

            if (!dryRun) {
                toast.success(t('students::admin.import.done', { count: bnDigits(response.data.created) }));
                onImported();
            }
        } catch (error) {
            if (error instanceof ApiError && error.body?.data) {
                // A 422 from the importer carries the per-row report in the body, not in the validation errors.
                setResult(error.body.data as Result);
            }
            toast.error(error instanceof ApiError && !error.body?.data ? error.message : t('students::admin.import.has_errors'));
        } finally {
            setBusy(false);
        }
    };

    return (
        <Dialog open={open} onOpenChange={(next) => !next && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{t('students::admin.import.title')}</DialogTitle>
                </DialogHeader>
                <p className="text-sm text-muted-foreground">{t('students::admin.import.help')}</p>
                <a className="text-sm underline" href="/api/students/template">
                    {t('students::admin.import.template')}
                </a>
                <Input type="file" accept=".csv,text/csv" onChange={(event) => { setFile(event.target.files?.[0] ?? null); setResult(null); }} />
                {result && (
                    <div className="space-y-1 rounded-lg border p-3 text-sm">
                        <p>{t('students::admin.import.valid_rows', { count: bnDigits(result.valid) })}</p>
                        {Object.entries(result.errors).map(([line, messages]) => (
                            <p key={line} className="text-destructive">
                                {t('students::admin.import.line', { line: bnDigits(line) })}: {messages.join('; ')}
                            </p>
                        ))}
                    </div>
                )}
                <DialogFooter>
                    <Button variant="outline" disabled={!file || busy} onClick={() => void run(true)}>
                        {t('students::admin.import.check')}
                    </Button>
                    <Button disabled={!file || busy} onClick={() => void run(false)}>
                        {t('students::admin.import.run')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
