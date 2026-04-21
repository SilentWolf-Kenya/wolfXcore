import * as React from 'react';
import { useState, useEffect, useRef } from 'react';
import { Link, NavLink } from 'react-router-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCogs, faLayerGroup, faSignOutAlt, faBell } from '@fortawesome/free-solid-svg-icons';
import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import SearchContainer from '@/components/dashboard/search/SearchContainer';
import http from '@/api/http';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import Avatar from '@/components/Avatar';
import styled from 'styled-components/macro';
import { getNotifications, markAllNotificationsRead, markNotificationRead, WxnNotification } from '@/api/account/getNotifications';

const neon = '#00ff00';
const font = "'Orbitron', 'Courier New', monospace";

const NavBar = styled.div`
    width: 100%;
    background: rgba(0, 8, 0, 0.97);
    border-bottom: 1px solid rgba(0,255,0,0.18);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    overflow-x: auto;
    position: relative;
    z-index: 10;
`;

const NavInner = styled.div`
    margin: 0 auto;
    width: 100%;
    max-width: 1200px;
    display: flex;
    align-items: center;
    height: 3.25rem;
    padding: 0 0.5rem;
`;

const Logo = styled(Link)`
    font-family: ${font};
    font-size: 1.1rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    color: #ffffff !important;
    text-decoration: none;
    padding: 0 0.75rem;
    flex: 1;
    white-space: nowrap;
    min-width: 0;
    text-transform: uppercase;
    &:hover { color: #ffffff !important; }
    .logo-wolf { color: #ffffff; }
    .logo-x { color: ${neon}; text-shadow: 0 0 8px ${neon}; }
    .logo-core { color: #ffffff; }
`;

const RightNav = styled.div`
    display: flex;
    height: 100%;
    align-items: center;
    flex-shrink: 0;

    & > a,
    & > button,
    & > .nav-link {
        display: flex;
        align-items: center;
        height: 100%;
        padding: 0 0.9rem;
        color: rgba(255,255,255,0.55) !important;
        text-decoration: none;
        cursor: pointer;
        background: transparent;
        border: none;
        font-size: 0.875rem;
        transition: color 0.15s, box-shadow 0.15s;

        &:hover, &:active, &.active {
            color: #ffffff !important;
            box-shadow: inset 0 -2px ${neon};
        }
    }

    @media (max-width: 480px) {
        & > a, & > button { padding: 0 0.6rem; font-size: 0.8rem; }
    }
`;

const typeColors: Record<string, { bg: string; border: string; dot: string }> = {
    info:    { bg: 'rgba(0,150,255,0.08)',  border: 'rgba(0,150,255,0.3)',  dot: '#4da6ff' },
    success: { bg: 'rgba(0,255,100,0.07)',  border: 'rgba(0,255,100,0.3)',  dot: '#00ff64' },
    warning: { bg: 'rgba(255,200,0,0.08)',  border: 'rgba(255,200,0,0.3)',  dot: '#ffc800' },
    danger:  { bg: 'rgba(255,60,60,0.08)', border: 'rgba(255,60,60,0.3)',  dot: '#ff4444' },
};

function timeAgo(iso: string): string {
    const diff = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
    if (diff < 60)   return `${diff}s ago`;
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)}d ago`;
}

const NotificationBell = () => {
    const [open, setOpen]                = useState(false);
    const [notifications, setNotifications] = useState<WxnNotification[]>([]);
    const [unread, setUnread]            = useState(0);
    const dropRef                        = useRef<HTMLDivElement>(null);

    const fetchNotifs = () => {
        getNotifications()
            .then((r) => { setNotifications(r.notifications); setUnread(r.unread_count); })
            .catch(() => {/* silently fail */});
    };

    useEffect(() => {
        fetchNotifs();
        const interval = setInterval(fetchNotifs, 60000);
        return () => clearInterval(interval);
    }, []);

    useEffect(() => {
        const handler = (e: MouseEvent) => {
            if (dropRef.current && !dropRef.current.contains(e.target as Node)) {
                setOpen(false);
            }
        };
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, []);

    const handleMarkAll = async () => {
        await markAllNotificationsRead();
        setNotifications((prev) => prev.map((n) => ({ ...n, is_read: true })));
        setUnread(0);
    };

    const handleMarkOne = async (id: number) => {
        await markNotificationRead(id);
        setNotifications((prev) => prev.map((n) => n.id === id ? { ...n, is_read: true } : n));
        setUnread((prev) => Math.max(0, prev - 1));
    };

    return (
        <div ref={dropRef} style={{ position: 'relative', height: '100%', display: 'flex', alignItems: 'center' }}>
            <button
                onClick={() => setOpen((v) => !v)}
                style={{
                    display: 'flex', alignItems: 'center', height: '100%',
                    padding: '0 0.9rem', background: 'transparent', border: 'none',
                    color: open ? '#ffffff' : 'rgba(255,255,255,0.55)',
                    cursor: 'pointer', position: 'relative',
                    boxShadow: open ? `inset 0 -2px ${neon}` : 'none',
                    transition: 'color 0.15s, box-shadow 0.15s',
                }}
            >
                <FontAwesomeIcon icon={faBell} />
                {unread > 0 && (
                    <span style={{
                        position: 'absolute', top: '6px', right: '6px',
                        background: '#ff3333', color: '#fff',
                        borderRadius: '50%', fontSize: '0.6rem', fontWeight: 700,
                        minWidth: '16px', height: '16px',
                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                        padding: '0 3px', lineHeight: 1,
                        fontFamily: "'JetBrains Mono', monospace",
                        boxShadow: '0 0 6px rgba(255,0,0,0.6)',
                    }}>
                        {unread > 99 ? '99+' : unread}
                    </span>
                )}
            </button>

            {open && (
                <div style={{
                    position: 'absolute', top: '100%', right: 0,
                    width: '340px', maxHeight: '480px',
                    background: '#050f05', border: '1px solid rgba(0,255,0,0.25)',
                    borderRadius: '6px', boxShadow: '0 8px 32px rgba(0,0,0,0.7)',
                    display: 'flex', flexDirection: 'column',
                    zIndex: 9999, overflow: 'hidden',
                }}>
                    {/* Header */}
                    <div style={{
                        display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                        padding: '10px 14px', borderBottom: '1px solid rgba(0,255,0,0.12)',
                        flexShrink: 0,
                    }}>
                        <span style={{ fontFamily: "'Orbitron',monospace", fontSize: '0.75rem', color: neon, letterSpacing: '2px' }}>
                            NOTIFICATIONS
                        </span>
                        {unread > 0 && (
                            <button onClick={handleMarkAll} style={{
                                background: 'transparent', border: '1px solid rgba(0,255,0,0.25)',
                                color: 'rgba(0,255,0,0.7)', fontSize: '0.65rem',
                                fontFamily: "'JetBrains Mono',monospace", padding: '3px 8px',
                                borderRadius: '3px', cursor: 'pointer', letterSpacing: '0.5px',
                            }}>
                                Mark all read
                            </button>
                        )}
                    </div>

                    {/* List */}
                    <div style={{ overflowY: 'auto', flex: 1 }}>
                        {notifications.length === 0 ? (
                            <div style={{ padding: '24px', textAlign: 'center', color: 'rgba(255,255,255,0.3)', fontFamily: "'JetBrains Mono',monospace", fontSize: '0.75rem' }}>
                                No notifications
                            </div>
                        ) : notifications.map((n) => {
                            const c = typeColors[n.type] ?? typeColors.info;
                            return (
                                <div
                                    key={n.id}
                                    onClick={() => !n.is_read && handleMarkOne(n.id)}
                                    style={{
                                        padding: '12px 14px',
                                        borderBottom: '1px solid rgba(255,255,255,0.04)',
                                        background: n.is_read ? 'transparent' : 'rgba(0,255,0,0.03)',
                                        cursor: n.is_read ? 'default' : 'pointer',
                                        transition: 'background 0.15s',
                                        borderLeft: `3px solid ${n.is_read ? 'transparent' : c.border}`,
                                    }}
                                >
                                    <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '4px' }}>
                                        <span style={{ width: '8px', height: '8px', borderRadius: '50%', background: c.dot, flexShrink: 0 }} />
                                        <span style={{ fontFamily: "'JetBrains Mono',monospace", fontSize: '0.78rem', color: n.is_read ? 'rgba(255,255,255,0.5)' : '#ffffff', fontWeight: n.is_read ? 400 : 700, flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                                            {n.title}
                                        </span>
                                        {!n.is_read && (
                                            <span style={{ width: '6px', height: '6px', borderRadius: '50%', background: '#ff3333', flexShrink: 0 }} />
                                        )}
                                    </div>
                                    <div style={{ fontFamily: "'JetBrains Mono',monospace", fontSize: '0.7rem', color: 'rgba(255,255,255,0.45)', lineHeight: 1.5, paddingLeft: '16px' }}>
                                        {n.body}
                                    </div>
                                    <div style={{ fontFamily: "'JetBrains Mono',monospace", fontSize: '0.62rem', color: 'rgba(255,255,255,0.25)', paddingLeft: '16px', marginTop: '4px' }}>
                                        {timeAgo(n.created_at)}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}
        </div>
    );
};

export default () => {
    const rootAdmin = useStoreState((state: ApplicationStore) => state.user.data!.rootAdmin);
    const [isLoggingOut, setIsLoggingOut] = useState(false);

    const onTriggerLogout = () => {
        setIsLoggingOut(true);
        http.post('/auth/logout').finally(() => {
            // @ts-expect-error this is valid
            window.location = '/';
        });
    };

    return (
        <NavBar>
            <SpinnerOverlay visible={isLoggingOut} />
            <NavInner>
                <Logo to={'/'}>
                    <span className={'logo-wolf'}>WOLF</span>
                    <span className={'logo-x'}>X</span>
                    <span className={'logo-core'}>CORE</span>
                </Logo>
                <RightNav>
                    <SearchContainer />
                    <NotificationBell />
                    <Tooltip placement={'bottom'} content={'Dashboard'}>
                        <NavLink to={'/'} exact>
                            <FontAwesomeIcon icon={faLayerGroup} />
                        </NavLink>
                    </Tooltip>
                    {rootAdmin && (
                        <Tooltip placement={'bottom'} content={'Admin'}>
                            <a href={'/admin'} rel={'noreferrer'}>
                                <FontAwesomeIcon icon={faCogs} />
                            </a>
                        </Tooltip>
                    )}
                    <Tooltip placement={'bottom'} content={'Account Settings'}>
                        <NavLink to={'/account'}>
                            <span style={{ display:'flex', alignItems:'center', width:'1.1rem', height:'1.1rem' }}>
                                <Avatar.User />
                            </span>
                        </NavLink>
                    </Tooltip>
                    <Tooltip placement={'bottom'} content={'Sign Out'}>
                        <button onClick={onTriggerLogout}>
                            <FontAwesomeIcon icon={faSignOutAlt} />
                        </button>
                    </Tooltip>
                </RightNav>
            </NavInner>
        </NavBar>
    );
};
