/**
 * The admin shell: our own Layout, Sidebar, Topbar and Dashboard, built to the Claude Design "Admin Panel" and wired to
 * laramod-admin's module system. vite.config.ts redirects the package's App and dashboard module here, so nothing under
 * vendor/ is edited. What stays the package's: registerModule (menu, routes, fills), HeaderActions, DashboardWidgets.
 *
 * Primitives every admin page builds on (restyled to the design, same import paths as before):
 *   @/components/ui/button       Button   default = green pill 44px, outline, secondary (navy tint), ghost, destructive,
 *                                         link; sizes xs sm default lg icon icon-xs icon-sm icon-lg
 *   @/components/ui/input        Input, Textarea, Select, Label, Checkbox, Switch   (44px, 12px radius, line-strong border)
 *   @/components/ui/badge        Badge    status pill: default (green), secondary (grey), destructive, success, warning,
 *                                         info, accent (red), highlight (yellow)
 *   @/components/ui/card         Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter (20px radius)
 *   @/components/ui/table        Table, TableHeader, TableRow, TableHead, TableCell (surface-2 header, hover rows)
 *   @/components/ui/dialog       Dialog*, alert-dialog (24px radius, header band with the close button)
 *   @/components/ui/tabs         Tabs, TabsList, TabsTrigger, TabsContent (the design's pill tabs)
 *   @/components/ui/dropdown-menu, sonner (bottom toast pill), skeleton (shimmer), tooltip, sheet
 *   @core/js/components          ResourcePage, DataTable (+ pagination), Field, NativeSelect, ConfirmDialog,
 *                                States (LoadingState, EmptyState, ErrorState)
 *   @core/js/menu                registerMenuBadge(path, Component): a count pill on a menu entry
 *   resources/css/admin.css      the token mapping (shadcn variables -> design tokens), .adm-skeleton, dark mode
 */
import { Fragment, useEffect, useMemo, useState } from 'react';
import { Outlet, useLocation } from 'react-router';

import { DashboardWidgets, HeaderActions } from '@admin/js';
import { viewer } from '@admin/js/lib/boot';
import { activeMenuPath, getMenuGroups, getModules } from '@admin/js/lib/modular';
import { appName } from '@admin/js/lib/boot';

import '../../css/admin.css';

import { buildShellMenu } from './menu';
import { usePageTitleOverride } from './pageTitle';
import { Sidebar } from './Sidebar';
import { useIsPhone, useSidebarExpanded } from './theme';
import { Topbar } from './Topbar';

export function Layout() {
    const { pathname } = useLocation();
    const phone = useIsPhone();
    const [expanded, setExpanded] = useSidebarExpanded();
    const [drawerOpen, setDrawerOpen] = useState(false);

    const menu = useMemo(() => getMenuGroups(viewer), []);
    const groups = useMemo(() => buildShellMenu(menu), [menu]);
    const active = activeMenuPath(pathname, menu);
    const pageTitle = usePageTitleOverride();
    const title = pageTitle ?? groups.flatMap((group) => group.items).find((item) => item.path === active)?.label ?? appName;

    // A drawer never outlives the page it was opened on, nor the breakpoint.
    useEffect(() => setDrawerOpen(false), [pathname, phone]);

    useEffect(() => {
        document.title = `${title} · ${appName}`;
    }, [title]);

    const sidebarShown = phone ? drawerOpen : true;
    const labels = phone ? true : expanded;
    const columns = phone ? 'minmax(0,1fr)' : `${expanded ? 248 : 72}px minmax(0,1fr)`;

    return (
        <div id="admin-shell" style={{ minHeight: '100vh', background: 'var(--bg)', color: 'var(--ink)' }}>
            <HeaderActions.Provider>
                <DashboardWidgets.Provider>
                    <div className="grid" style={{ gridTemplateColumns: columns, minHeight: '100vh' }}>
                        {sidebarShown && <Sidebar groups={groups} active={active} labels={labels} phone={phone} onNavigate={() => setDrawerOpen(false)} />}
                        {phone && drawerOpen && <div onClick={() => setDrawerOpen(false)} aria-hidden="true" className="fixed inset-0" style={{ zIndex: 45, background: 'rgba(0,0,0,.4)' }} />}

                        <div className="flex min-w-0 flex-col">
                            <Topbar title={title} menuOpen={phone ? drawerOpen : expanded} onToggleMenu={() => (phone ? setDrawerOpen((open) => !open) : setExpanded(!expanded))} />

                            <main className="mx-auto grid w-full flex-1 content-start" style={{ padding: '20px 16px 48px', maxWidth: 1280, gap: 16, gridTemplateColumns: 'minmax(0,1fr)' }}>
                                {/* Page-level actions (the primary "new" button of a page) sit at the top right of the content, as in the design. */}
                                <HeaderActions.Outlet className="flex flex-wrap items-center justify-end gap-2 empty:hidden" />
                                <Outlet />
                            </main>
                        </div>
                    </div>

                    {/* Inside the providers and always mounted, so a module can fill a slot of a page that is not its own. */}
                    {getModules().map((module) => (
                        <Fragment key={module.id}>{module.fills}</Fragment>
                    ))}
                </DashboardWidgets.Provider>
            </HeaderActions.Provider>
        </div>
    );
}
