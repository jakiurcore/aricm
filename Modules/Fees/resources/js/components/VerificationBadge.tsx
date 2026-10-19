import { useEffect, useState } from 'react';
import { useLocation } from 'react-router';

import { api } from '@core/js/api';
import { MenuPill } from '@core/js/components/MenuPill';

/** The count of payments waiting for a check, on the "verify payments" menu entry. Refreshed whenever the page changes. */
export function VerificationBadge() {
    const { pathname } = useLocation();
    const [count, setCount] = useState<number | null>(null);

    useEffect(() => {
        let current = true;

        api.get<{ data: { pending_verifications: number } }>('/fees/dashboard')
            .then((response) => current && setCount(response.data.pending_verifications))
            .catch(() => current && setCount(null));

        return () => {
            current = false;
        };
    }, [pathname]);

    return <MenuPill count={count} />;
}
