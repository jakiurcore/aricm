import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laramod from 'laramod-vite-plugin';
import path from 'node:path';
import { defineConfig, type Plugin } from 'vite';

const shell = (file: string) => path.resolve(__dirname, 'resources/js/admin-shell', file);

/**
 * Forks laramod-admin's shell without touching vendor/: the package's own imports of its App (router, 404) and its
 * dashboard module are redirected to resources/js/admin-shell, which keeps the module/menu/slot contract
 * (registerModule, HeaderActions, DashboardWidgets) and replaces the look.
 */
function adminShell(): Plugin {
    const packageJs = `${path.sep}laramod-admin${path.sep}resources${path.sep}js${path.sep}`;
    const redirects: Record<string, string> = {
        './App': shell('App.tsx'),
        './modules/dashboard': shell('dashboard.tsx'),
    };

    return {
        name: 'madrasa:admin-shell',
        enforce: 'pre',
        resolveId(source, importer) {
            if (importer && importer.includes(packageJs) && source in redirects) {
                return redirects[source];
            }

            return null;
        },
    };
}

export default defineConfig({
    plugins: [
        laramod({
            input: ['resources/css/app.css'],
            refresh: true,
        }),
        adminShell(),
        react(),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/vendor/**', '**/storage/**', '**/design/**'],
        },
    },
});
