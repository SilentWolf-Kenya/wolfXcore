import React, { useEffect, useState } from 'react';
import { Server } from '@/api/server/getServer';
import getServers from '@/api/getServers';
import ServerRow from '@/components/dashboard/ServerRow';
import Spinner from '@/components/elements/Spinner';
import PageContentBlock from '@/components/elements/PageContentBlock';
import useFlash from '@/plugins/useFlash';
import { useStoreState } from 'easy-peasy';
import { usePersistedState } from '@/plugins/usePersistedState';
import Switch from '@/components/elements/Switch';
import tw from 'twin.macro';
import useSWR from 'swr';
import { PaginatedResult } from '@/api/http';
import Pagination from '@/components/elements/Pagination';
import { useLocation } from 'react-router-dom';

const neon = '#00ff00';
const fontMono = "'JetBrains Mono', monospace";
const fontDisplay = "'Orbitron', monospace";

export default () => {
    const { search } = useLocation();
    const defaultPage = Number(new URLSearchParams(search).get('page') || '1');

    const [page, setPage] = useState(!isNaN(defaultPage) && defaultPage > 0 ? defaultPage : 1);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const uuid = useStoreState((state) => state.user.data!.uuid);
    const rootAdmin = useStoreState((state) => state.user.data!.rootAdmin);
    const [showOnlyAdmin, setShowOnlyAdmin] = usePersistedState(`${uuid}:show_all_servers`, false);

    const { data: servers, error } = useSWR<PaginatedResult<Server>>(
        ['/api/client/servers', showOnlyAdmin && rootAdmin, page],
        () => getServers({ page, type: showOnlyAdmin && rootAdmin ? 'admin' : undefined })
    );

    useEffect(() => { setPage(1); }, [showOnlyAdmin]);

    useEffect(() => {
        if (!servers) return;
        if (servers.pagination.currentPage > 1 && !servers.items.length) setPage(1);
    }, [servers?.pagination.currentPage]);

    useEffect(() => {
        window.history.replaceState(null, document.title, `/servers${page <= 1 ? '' : `?page=${page}`}`);
    }, [page]);

    return (
        <PageContentBlock title={'Servers'} showFlashKey={'servers'}>
            <style>{`
                /* ── Mobile-first responsive card grid ── */

                .wxn-server-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(min(280px, 100%), 1fr));
                    gap: 1rem;
                    width: 100%;
                    box-sizing: border-box;
                }

                /* Phones ≤ 600px: single column, full-width cards */
                @media (max-width: 600px) {
                    .wxn-server-grid {
                        grid-template-columns: 1fr !important;
                        gap: 0.75rem !important;
                    }
                    .wxn-server-card {
                        width: 100% !important;
                        box-sizing: border-box !important;
                        padding: 0.85rem 0.9rem !important;
                        min-width: 0 !important;
                    }
                    /* Allow name + badge to stack if too tight */
                    .wxn-card-header {
                        flex-wrap: wrap !important;
                        gap: 0.5rem !important;
                    }
                    /* Stat boxes: 2 per row on phones */
                    .wxn-stat-grid {
                        grid-template-columns: repeat(2, 1fr) !important;
                    }
                    /* Shrink the header titles slightly */
                    .wxn-servers-header h1 {
                        font-size: 1rem !important;
                    }
                    .wxn-servers-header {
                        flex-direction: column !important;
                        align-items: flex-start !important;
                        gap: 0.6rem !important;
                    }
                }

                /* Very small phones ≤ 360px */
                @media (max-width: 360px) {
                    .wxn-server-card {
                        padding: 0.7rem 0.75rem !important;
                    }
                    .wxn-stat-grid {
                        grid-template-columns: repeat(2, 1fr) !important;
                    }
                }
            `}</style>

            {/* Header */}
            <div
                className="wxn-servers-header"
                style={{
                    marginBottom: '1.5rem',
                    borderBottom: '1px solid rgba(0,255,0,0.12)',
                    paddingBottom: '1rem',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                }}
            >
                <div>
                    <p style={{ fontFamily: fontDisplay, color: neon, fontSize: '0.62rem', letterSpacing: '0.22em', textTransform: 'uppercase', marginBottom: '0.25rem' }}>
                        ● WOLFXCORE PANEL
                    </p>
                    <h1 style={{ fontFamily: fontDisplay, color: '#fff', fontSize: '1.2rem', fontWeight: 900, letterSpacing: '0.06em', margin: 0 }}>
                        YOUR SERVERS
                    </h1>
                </div>
                {rootAdmin && (
                    <div css={tw`flex items-center gap-2`}>
                        <p css={tw`uppercase text-xs text-neutral-400`}>
                            {showOnlyAdmin ? "Others' servers" : 'Your servers'}
                        </p>
                        <Switch
                            name={'show_all_servers'}
                            defaultChecked={showOnlyAdmin}
                            onChange={() => setShowOnlyAdmin((s) => !s)}
                        />
                    </div>
                )}
            </div>

            {!servers ? (
                <Spinner centered size={'large'} />
            ) : (
                <Pagination data={servers} onPageSelect={setPage}>
                    {({ items }) =>
                        items.length > 0 ? (
                            <div className="wxn-server-grid">
                                {items.map((server) => (
                                    <ServerRow key={server.uuid} server={server} />
                                ))}
                            </div>
                        ) : showOnlyAdmin ? (
                            <p css={tw`text-center text-sm text-neutral-400`}>
                                There are no other servers to display.
                            </p>
                        ) : (
                            <div style={{ textAlign: 'center', padding: '4rem 1rem' }}>
                                <p style={{ fontFamily: fontDisplay, color: neon, fontSize: '0.65rem', letterSpacing: '0.2em', marginBottom: '0.75rem' }}>
                                    ● NO SERVERS DETECTED
                                </p>
                                <p style={{ fontFamily: fontMono, color: 'rgba(255,255,255,0.4)', fontSize: '0.85rem' }}>
                                    You have no servers yet. Contact an admin or visit the{' '}
                                    <a href='/' style={{ color: neon, textDecoration: 'none' }}>Overview</a>
                                    {' '}page to get started.
                                </p>
                            </div>
                        )
                    }
                </Pagination>
            )}
        </PageContentBlock>
    );
};
