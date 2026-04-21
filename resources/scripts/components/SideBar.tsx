import * as React from 'react';
import { useState, useEffect, useRef } from 'react';
import { NavLink, Link, useLocation } from 'react-router-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faServer, faCreditCard, faWallet, faBell, faUser,
    faCog, faCogs, faSignOutAlt, faChevronLeft, faBars, faHome, faRobot,
} from '@fortawesome/free-solid-svg-icons';
import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import Avatar from '@/components/Avatar';
import http from '@/api/http';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { getNotifications, markAllNotificationsRead, markNotificationRead, WxnNotification } from '@/api/account/getNotifications';

const neon = '#00ff00';

/* ─── type helpers ─────────────────────────────────────────────── */
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

/* ─── Notification panel ───────────────────────────────────────── */
const NotifPanel = ({ onClose }: { onClose: () => void }) => {
    const [notifications, setNotifications] = useState<WxnNotification[]>([]);
    const [unread, setUnread]               = useState(0);

    useEffect(() => {
        getNotifications()
            .then(r => { setNotifications(r.notifications); setUnread(r.unread_count); })
            .catch(() => {});
    }, []);

    const handleMarkAll = async () => {
        await markAllNotificationsRead();
        setNotifications(p => p.map(n => ({ ...n, is_read: true })));
        setUnread(0);
    };

    const handleMarkOne = async (id: number) => {
        await markNotificationRead(id);
        setNotifications(p => p.map(n => n.id === id ? { ...n, is_read: true } : n));
        setUnread(prev => Math.max(0, prev - 1));
    };

    return (
        <div style={{
            position: 'fixed', top: 0, left: 220, bottom: 0, width: 320,
            background: '#060f06', borderRight: '1px solid rgba(0,255,0,0.2)',
            display: 'flex', flexDirection: 'column', zIndex: 1100,
            boxShadow: '4px 0 24px rgba(0,0,0,0.6)',
        }}>
            <div style={{
                display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                padding: '14px 16px', borderBottom: '1px solid rgba(0,255,0,0.12)', flexShrink: 0,
            }}>
                <span style={{ fontFamily: "'Orbitron',monospace", fontSize: '0.72rem', color: neon, letterSpacing: '2px' }}>
                    NOTIFICATIONS
                </span>
                <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                    {unread > 0 && (
                        <button onClick={handleMarkAll} style={{
                            background: 'transparent', border: '1px solid rgba(0,255,0,0.25)',
                            color: 'rgba(0,255,0,0.7)', fontSize: '0.62rem',
                            fontFamily: "'JetBrains Mono',monospace", padding: '2px 8px',
                            borderRadius: '3px', cursor: 'pointer',
                        }}>Mark all read</button>
                    )}
                    <button onClick={onClose} style={{
                        background: 'transparent', border: 'none', color: 'rgba(255,255,255,0.4)',
                        cursor: 'pointer', padding: '2px 4px', fontSize: '0.85rem',
                    }}>✕</button>
                </div>
            </div>
            <div style={{ overflowY: 'auto', flex: 1 }}>
                {notifications.length === 0 ? (
                    <div style={{ padding: 24, textAlign: 'center', color: 'rgba(255,255,255,0.3)', fontFamily: "'JetBrains Mono',monospace", fontSize: '0.73rem' }}>
                        No notifications
                    </div>
                ) : notifications.map(n => {
                    const c = typeColors[n.type] ?? typeColors.info;
                    return (
                        <div key={n.id} onClick={() => !n.is_read && handleMarkOne(n.id)} style={{
                            padding: '11px 14px', borderBottom: '1px solid rgba(255,255,255,0.04)',
                            background: n.is_read ? 'transparent' : 'rgba(0,255,0,0.02)',
                            cursor: n.is_read ? 'default' : 'pointer',
                            borderLeft: `3px solid ${n.is_read ? 'transparent' : c.border}`,
                        }}>
                            <div style={{ display: 'flex', alignItems: 'center', gap: 7, marginBottom: 3 }}>
                                <span style={{ width: 7, height: 7, borderRadius: '50%', background: c.dot, flexShrink: 0 }} />
                                <span style={{
                                    fontFamily: "'JetBrains Mono',monospace", fontSize: '0.76rem',
                                    color: n.is_read ? 'rgba(255,255,255,0.45)' : '#fff',
                                    fontWeight: n.is_read ? 400 : 700,
                                    flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap',
                                }}>{n.title}</span>
                                {!n.is_read && <span style={{ width: 6, height: 6, borderRadius: '50%', background: '#ff3333', flexShrink: 0 }} />}
                            </div>
                            <div style={{ fontFamily: "'JetBrains Mono',monospace", fontSize: '0.68rem', color: 'rgba(255,255,255,0.4)', lineHeight: 1.5, paddingLeft: 14 }}>
                                {n.body}
                            </div>
                            <div style={{ fontFamily: "'JetBrains Mono',monospace", fontSize: '0.6rem', color: 'rgba(255,255,255,0.2)', paddingLeft: 14, marginTop: 3 }}>
                                {timeAgo(n.created_at)}
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
};

/* ─── Main SideBar ─────────────────────────────────────────────── */
const SideBar = () => {
    const rootAdmin        = useStoreState((s: ApplicationStore) => s.user.data!.rootAdmin);
    const username         = useStoreState((s: ApplicationStore) => s.user.data!.username);
    const email            = useStoreState((s: ApplicationStore) => s.user.data!.email);
    const [loggingOut, setLoggingOut]   = useState(false);
    const [notifOpen, setNotifOpen]     = useState(false);
    const [unreadCount, setUnreadCount] = useState(0);
    const [collapsed, setCollapsed]     = useState(false);
    const location = useLocation();

    /* poll notification count */
    useEffect(() => {
        const fetch = () => getNotifications()
            .then(r => setUnreadCount(r.unread_count))
            .catch(() => {});
        fetch();
        const t = setInterval(fetch, 60000);
        return () => clearInterval(t);
    }, []);

    /* close notif panel if route changes */
    useEffect(() => { setNotifOpen(false); }, [location.pathname]);

    const logout = () => {
        setLoggingOut(true);
        http.post('/auth/logout').finally(() => { (window as any).location = '/'; });
    };

    const sideW = collapsed ? 60 : 220;

    const navItem = (
        icon: any,
        label: string,
        to: string,
        exact = false,
        external = false,
        badge?: number,
    ) => {
        const inner = (
            <>
                <span style={{
                    width: 20, display: 'flex', alignItems: 'center', justifyContent: 'center',
                    flexShrink: 0, position: 'relative',
                }}>
                    <FontAwesomeIcon icon={icon} style={{ fontSize: '0.9rem' }} />
                    {badge && badge > 0 ? (
                        <span style={{
                            position: 'absolute', top: -5, right: -8,
                            background: '#ff3333', color: '#fff',
                            borderRadius: '50%', fontSize: '0.55rem', fontWeight: 700,
                            minWidth: 14, height: 14,
                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                            padding: '0 2px', lineHeight: 1,
                            boxShadow: '0 0 5px rgba(255,0,0,0.5)',
                        }}>{badge > 99 ? '99+' : badge}</span>
                    ) : null}
                </span>
                {!collapsed && (
                    <span style={{
                        fontFamily: "'JetBrains Mono',monospace",
                        fontSize: '0.78rem', letterSpacing: '0.03em',
                        overflow: 'hidden', whiteSpace: 'nowrap',
                    }}>{label}</span>
                )}
            </>
        );

        const itemStyle: React.CSSProperties = {
            display: 'flex', alignItems: 'center',
            gap: collapsed ? 0 : 12,
            padding: collapsed ? '11px 0' : '10px 18px',
            justifyContent: collapsed ? 'center' : 'flex-start',
            color: 'rgba(255,255,255,0.5)',
            textDecoration: 'none',
            borderRadius: 6, margin: '1px 8px',
            transition: 'background 0.15s, color 0.15s',
            cursor: 'pointer',
        };

        if (external) {
            return (
                <a key={to} href={to} style={itemStyle}
                   onMouseEnter={e => { (e.currentTarget as HTMLElement).style.background = 'rgba(0,255,0,0.07)'; (e.currentTarget as HTMLElement).style.color = '#fff'; }}
                   onMouseLeave={e => { (e.currentTarget as HTMLElement).style.background = 'transparent'; (e.currentTarget as HTMLElement).style.color = 'rgba(255,255,255,0.5)'; }}>
                    {inner}
                </a>
            );
        }

        return (
            <NavLink key={to} to={to} exact={exact}
                style={itemStyle}
                activeStyle={{ background: 'rgba(0,255,0,0.1)', color: neon, boxShadow: `inset 3px 0 0 ${neon}` }}
                onMouseEnter={e => { (e.currentTarget as HTMLElement).style.background = 'rgba(0,255,0,0.07)'; (e.currentTarget as HTMLElement).style.color = '#fff'; }}
                onMouseLeave={e => { (e.currentTarget as HTMLElement).style.background = ''; (e.currentTarget as HTMLElement).style.color = ''; }}>
                {inner}
            </NavLink>
        );
    };

    return (
        <>
            <SpinnerOverlay visible={loggingOut} />

            {/* Notification slide-out panel */}
            {notifOpen && <NotifPanel onClose={() => setNotifOpen(false)} />}
            {/* Overlay to close notif panel */}
            {notifOpen && (
                <div onClick={() => setNotifOpen(false)} style={{
                    position: 'fixed', inset: 0, zIndex: 1099,
                }} />
            )}

            {/* Sidebar */}
            <aside style={{
                width: sideW, minWidth: sideW, height: '100vh',
                background: 'rgba(0,8,0,0.97)',
                borderRight: '1px solid rgba(0,255,0,0.15)',
                display: 'flex', flexDirection: 'column',
                position: 'sticky', top: 0,
                transition: 'width 0.2s',
                zIndex: 100, flexShrink: 0,
                overflowY: 'auto', overflowX: 'hidden',
            }}>

                {/* Logo + collapse toggle */}
                <div style={{
                    display: 'flex', alignItems: 'center',
                    justifyContent: collapsed ? 'center' : 'space-between',
                    padding: collapsed ? '16px 0' : '16px 14px 16px 18px',
                    borderBottom: '1px solid rgba(0,255,0,0.1)',
                    flexShrink: 0,
                }}>
                    {!collapsed && (
                        <Link to='/' style={{ textDecoration: 'none' }}>
                            <span style={{ fontFamily: "'Orbitron',monospace", fontWeight: 900, fontSize: '1rem', letterSpacing: '0.06em' }}>
                                <span style={{ color: '#fff' }}>WOLF</span>
                                <span style={{ color: neon, textShadow: `0 0 8px ${neon}` }}>X</span>
                                <span style={{ color: '#fff' }}>CORE</span>
                            </span>
                        </Link>
                    )}
                    <button onClick={() => setCollapsed(v => !v)} style={{
                        background: 'transparent', border: 'none',
                        color: 'rgba(255,255,255,0.3)', cursor: 'pointer',
                        padding: '4px', display: 'flex', alignItems: 'center',
                        transition: 'color 0.15s',
                    }}
                        onMouseEnter={e => (e.currentTarget.style.color = neon)}
                        onMouseLeave={e => (e.currentTarget.style.color = 'rgba(255,255,255,0.3)')}
                    >
                        <FontAwesomeIcon icon={collapsed ? faBars : faChevronLeft} style={{ fontSize: '0.8rem' }} />
                    </button>
                </div>

                {/* User info */}
                {!collapsed && (
                    <div style={{
                        display: 'flex', alignItems: 'center', gap: 10,
                        padding: '12px 16px', borderBottom: '1px solid rgba(0,255,0,0.08)',
                        flexShrink: 0,
                    }}>
                        <span style={{ width: 32, height: 32, borderRadius: '50%', overflow: 'hidden', flexShrink: 0, border: `1px solid rgba(0,255,0,0.3)` }}>
                            <Avatar.User />
                        </span>
                        <div style={{ overflow: 'hidden' }}>
                            <div style={{ fontFamily: "'JetBrains Mono',monospace", fontSize: '0.75rem', color: '#fff', fontWeight: 700, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                                {username}
                            </div>
                            <div style={{ fontFamily: "'JetBrains Mono',monospace", fontSize: '0.62rem', color: 'rgba(255,255,255,0.35)', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                                {email}
                            </div>
                        </div>
                    </div>
                )}
                {collapsed && (
                    <div style={{ display: 'flex', justifyContent: 'center', padding: '10px 0', borderBottom: '1px solid rgba(0,255,0,0.08)', flexShrink: 0 }}>
                        <span style={{ width: 30, height: 30, borderRadius: '50%', overflow: 'hidden', border: `1px solid rgba(0,255,0,0.3)` }}>
                            <Avatar.User />
                        </span>
                    </div>
                )}

                {/* Section label */}
                {!collapsed && (
                    <div style={{ padding: '14px 18px 6px', fontFamily: "'Orbitron',monospace", fontSize: '0.58rem', color: 'rgba(0,255,0,0.4)', letterSpacing: '2px' }}>
                        NAVIGATION
                    </div>
                )}

                {/* Nav links */}
                <nav style={{ flex: 1, paddingTop: collapsed ? 10 : 4 }}>
                    {navItem(faHome,       'Overview',       '/',        true)}
                    {navItem(faServer,     'Servers',        '/servers', false)}
                    {navItem(faRobot,      'Bot Marketplace','/bots',    false, true)}
                    {navItem(faCreditCard, 'Billing',        '/billing', false, true)}
                    {navItem(faWallet,     'Wallet',         '/wallet')}

                    {/* Notifications button */}
                    <div
                        onClick={() => setNotifOpen(v => !v)}
                        style={{
                            display: 'flex', alignItems: 'center',
                            gap: collapsed ? 0 : 12,
                            padding: collapsed ? '11px 0' : '10px 18px',
                            justifyContent: collapsed ? 'center' : 'flex-start',
                            color: notifOpen ? neon : 'rgba(255,255,255,0.5)',
                            background: notifOpen ? 'rgba(0,255,0,0.07)' : 'transparent',
                            boxShadow: notifOpen ? `inset 3px 0 0 ${neon}` : 'none',
                            borderRadius: 6, margin: '1px 8px',
                            cursor: 'pointer',
                            transition: 'background 0.15s, color 0.15s',
                        }}
                        onMouseEnter={e => { if (!notifOpen) { (e.currentTarget as HTMLElement).style.background = 'rgba(0,255,0,0.07)'; (e.currentTarget as HTMLElement).style.color = '#fff'; } }}
                        onMouseLeave={e => { if (!notifOpen) { (e.currentTarget as HTMLElement).style.background = 'transparent'; (e.currentTarget as HTMLElement).style.color = 'rgba(255,255,255,0.5)'; } }}
                    >
                        <span style={{ width: 20, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0, position: 'relative' }}>
                            <FontAwesomeIcon icon={faBell} style={{ fontSize: '0.9rem' }} />
                            {unreadCount > 0 && (
                                <span style={{
                                    position: 'absolute', top: -5, right: -8,
                                    background: '#ff3333', color: '#fff',
                                    borderRadius: '50%', fontSize: '0.55rem', fontWeight: 700,
                                    minWidth: 14, height: 14,
                                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                                    padding: '0 2px', lineHeight: 1,
                                    boxShadow: '0 0 5px rgba(255,0,0,0.5)',
                                }}>{unreadCount > 99 ? '99+' : unreadCount}</span>
                            )}
                        </span>
                        {!collapsed && (
                            <span style={{ fontFamily: "'JetBrains Mono',monospace", fontSize: '0.78rem', letterSpacing: '0.03em', overflow: 'hidden', whiteSpace: 'nowrap' }}>
                                Notifications
                            </span>
                        )}
                        {!collapsed && unreadCount > 0 && (
                            <span style={{
                                marginLeft: 'auto', background: 'rgba(255,51,51,0.15)',
                                color: '#ff6060', border: '1px solid rgba(255,51,51,0.3)',
                                borderRadius: 10, fontSize: '0.6rem', padding: '1px 7px',
                                fontFamily: "'JetBrains Mono',monospace",
                            }}>{unreadCount}</span>
                        )}
                    </div>
                </nav>

                {/* Divider */}
                <div style={{ borderTop: '1px solid rgba(0,255,0,0.08)', margin: '8px 0', flexShrink: 0 }} />

                {/* Bottom links */}
                <div style={{ paddingBottom: 12, flexShrink: 0 }}>
                    {navItem(faCog, 'Account', '/account')}
                    {rootAdmin && navItem(faCogs, 'Admin Panel', '/admin', false, true)}

                    {/* Sign out */}
                    <div
                        onClick={logout}
                        style={{
                            display: 'flex', alignItems: 'center',
                            gap: collapsed ? 0 : 12,
                            padding: collapsed ? '11px 0' : '10px 18px',
                            justifyContent: collapsed ? 'center' : 'flex-start',
                            color: 'rgba(255,80,80,0.6)',
                            borderRadius: 6, margin: '1px 8px',
                            cursor: 'pointer', transition: 'background 0.15s, color 0.15s',
                        }}
                        onMouseEnter={e => { (e.currentTarget as HTMLElement).style.background = 'rgba(255,60,60,0.07)'; (e.currentTarget as HTMLElement).style.color = '#ff8080'; }}
                        onMouseLeave={e => { (e.currentTarget as HTMLElement).style.background = 'transparent'; (e.currentTarget as HTMLElement).style.color = 'rgba(255,80,80,0.6)'; }}
                    >
                        <span style={{ width: 20, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                            <FontAwesomeIcon icon={faSignOutAlt} style={{ fontSize: '0.9rem' }} />
                        </span>
                        {!collapsed && (
                            <span style={{ fontFamily: "'JetBrains Mono',monospace", fontSize: '0.78rem' }}>Sign Out</span>
                        )}
                    </div>
                </div>

            </aside>
        </>
    );
};

export default SideBar;
