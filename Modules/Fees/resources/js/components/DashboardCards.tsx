import { useEffect, useState } from 'react';

import { Card, CardContent } from '@/components/ui/card';
import { api } from '@core/js/api';
import { t } from '@core/js/i18n';
import { formatMoney } from '@core/js/money';
import { bnDigits } from '@core/js/number';

interface Stats {
    billed_this_month: number;
    collected_this_month: number;
    collected_today: number;
    pending_verifications: number;
    outstanding: number;
}

/** The fee numbers on the admin dashboard. Mounted only while the dashboard is shown, so it loads only there. */
export function DashboardCards() {
    const [stats, setStats] = useState<Stats | null>(null);

    useEffect(() => {
        void api.get<{ data: Stats }>('/fees/dashboard').then((response) => setStats(response.data));
    }, []);

    if (!stats) {
        return null;
    }

    const percent = stats.billed_this_month > 0 ? Math.min(100, Math.round((stats.collected_this_month / stats.billed_this_month) * 100)) : 0;

    return (
        <>
            <Card>
                <CardContent className="space-y-2 pt-4">
                    <p className="text-sm text-muted-foreground">{t('fees::admin.dashboard.this_month')}</p>
                    <p className="text-2xl font-semibold">{formatMoney(stats.collected_this_month)}</p>
                    <div className="h-2 overflow-hidden rounded-full bg-muted">
                        <div className="h-full bg-primary" style={{ width: `${percent}%` }} />
                    </div>
                    <p className="text-xs text-muted-foreground">{t('fees::admin.dashboard.of_billed', { billed: formatMoney(stats.billed_this_month), percent: bnDigits(percent) })}</p>
                </CardContent>
            </Card>
            <Card>
                <CardContent className="pt-4">
                    <p className="text-sm text-muted-foreground">{t('fees::admin.dashboard.today')}</p>
                    <p className="text-2xl font-semibold">{formatMoney(stats.collected_today)}</p>
                    <p className="text-xs text-muted-foreground">{t('fees::admin.dashboard.outstanding', { amount: formatMoney(stats.outstanding) })}</p>
                </CardContent>
            </Card>
            <Card>
                <CardContent className="pt-4">
                    <p className="text-sm text-muted-foreground">{t('fees::admin.dashboard.pending')}</p>
                    <p className="text-2xl font-semibold">{bnDigits(stats.pending_verifications)}</p>
                    <a className="text-xs underline" href="/admin/fees/verification">{t('fees::admin.dashboard.review')}</a>
                </CardContent>
            </Card>
        </>
    );
}
