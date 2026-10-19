import { Link, Navigate, useRoutes } from 'react-router';

import { viewer } from '@admin/js/lib/boot';
import { getRoutes, homePath } from '@admin/js/lib/modular';
import { t } from '@core/js/i18n';

import { Layout } from './Layout';

// No module owns "/": it lands on the first page the viewer is offered.
function Home() {
    const home = homePath(viewer);

    return home && home !== '/' ? <Navigate to={home} replace /> : null;
}

function NotFound() {
    return (
        <div className="grid justify-items-center gap-2 rounded-[20px] border-2 border-dashed border-input p-12 text-center">
            <span className="text-3xl">🧭</span>
            <h1>{t('core::admin.shell.not_found_title')}</h1>
            <p className="text-muted-foreground">{t('core::admin.shell.not_found_body')}</p>
            <Link to="/" className="font-bold text-primary">
                {t('core::admin.shell.back_home')}
            </Link>
        </div>
    );
}

export default function App() {
    return useRoutes([
        {
            element: <Layout />,
            children: [{ index: true, element: <Home /> }, ...getRoutes(), { path: '*', element: <NotFound /> }],
        },
    ]);
}
