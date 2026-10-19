import { useEffect, useState } from 'react';

import { api } from '@core/js/api';

export interface AcademicOptions {
    current_session_id: number | null;
    sessions: { id: number; name: string }[];
    classes: { id: number; name: string; division: string | null; sections: { id: number; name: string }[] }[];
}

const empty: AcademicOptions = { current_session_id: null, sessions: [], classes: [] };

/** Sessions plus classes with their sections, for filter and form dropdowns on any admin screen. */
export function useAcademicOptions(): AcademicOptions {
    const [options, setOptions] = useState<AcademicOptions>(empty);

    useEffect(() => {
        void api.get<{ data: AcademicOptions }>('/academic/options').then((response) => setOptions(response.data));
    }, []);

    return options;
}
