import React, { useEffect, useRef, useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faHdd, faMemory, faMicrochip, faServer, faEthernet } from '@fortawesome/free-solid-svg-icons';
import { Link } from 'react-router-dom';
import { Server } from '@/api/server/getServer';
import getServerResourceUsage, { ServerPowerState, ServerStats } from '@/api/server/getServerResourceUsage';
import { bytesToString, ip, mbToBytes } from '@/lib/formatters';

const isAlarmState = (current: number, limit: number): boolean => limit > 0 && current / (limit * 1024 * 1024) >= 0.9;

type Timer = ReturnType<typeof setInterval>;

const statusColor = (status: ServerPowerState | undefined): string => {
    if (!status || status === 'offline') return '#ef4444';
    if (status === 'running') return '#00ff00';
    return '#eab308';
};

const statusLabel = (status: ServerPowerState | undefined, isTransferring: boolean, serverStatus: string | null): string => {
    if (isTransferring) return 'TRANSFERRING';
    if (serverStatus === 'installing') return 'INSTALLING';
    if (serverStatus === 'restoring_backup') return 'RESTORING';
    if (!serverStatus && status === 'running') return 'ONLINE';
    if (!serverStatus && (!status || status === 'offline')) return 'OFFLINE';
    if (status === 'running') return 'ONLINE';
    if (status === 'offline') return 'OFFLINE';
    return 'STARTING';
};

export default ({ server, className }: { server: Server; className?: string }) => {
    const interval = useRef<Timer>(null) as React.MutableRefObject<Timer>;
    const [isSuspended, setIsSuspended] = useState(server.status === 'suspended');
    const [stats, setStats] = useState<ServerStats | null>(null);

    const getStats = () =>
        getServerResourceUsage(server.uuid)
            .then((data) => setStats(data))
            .catch((error) => console.error(error));

    useEffect(() => {
        setIsSuspended(stats?.isSuspended || server.status === 'suspended');
    }, [stats?.isSuspended, server.status]);

    useEffect(() => {
        if (isSuspended) return;
        getStats().then(() => {
            interval.current = setInterval(() => getStats(), 30000);
        });
        return () => {
            interval.current && clearInterval(interval.current);
        };
    }, [isSuspended]);

    const alarms = { cpu: false, memory: false, disk: false };
    if (stats) {
        alarms.cpu = server.limits.cpu === 0 ? false : stats.cpuUsagePercent >= server.limits.cpu * 0.9;
        alarms.memory = isAlarmState(stats.memoryUsageInBytes, server.limits.memory);
        alarms.disk = server.limits.disk === 0 ? false : isAlarmState(stats.diskUsageInBytes, server.limits.disk);
    }

    const diskLimit = server.limits.disk !== 0 ? bytesToString(mbToBytes(server.limits.disk)) : 'Unlimited';
    const memoryLimit = server.limits.memory !== 0 ? bytesToString(mbToBytes(server.limits.memory)) : 'Unlimited';
    const cpuLimit = server.limits.cpu !== 0 ? `${server.limits.cpu} %` : 'Unlimited';

    const currentStatus = stats?.status;
    const color = isSuspended ? '#ef4444' : statusColor(currentStatus);
    const label = isSuspended ? 'SUSPENDED' : statusLabel(currentStatus, server.isTransferring, server.status);

    const allocation = server.allocations.find((a) => a.isDefault);
    const address = allocation ? `${allocation.port}` : '—';

    return (
        <div
            className={`wxn-server-card ${className ?? ''}`}
            style={{
                background: 'rgba(0,0,0,0.55)',
                border: `1px solid rgba(0,255,0,0.18)`,
                borderLeft: `3px solid ${color}`,
                borderRadius: '6px',
                padding: '1rem 1.25rem',
                display: 'flex',
                flexDirection: 'column',
                gap: '0.75rem',
                boxShadow: '0 2px 16px rgba(0,0,0,0.4)',
                transition: 'border-color 0.2s, box-shadow 0.2s',
            }}
        >
            {/* Header row */}
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '0.75rem' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem', minWidth: 0 }}>
                    <div style={{
                        width: '2.25rem', height: '2.25rem',
                        background: 'rgba(0,255,0,0.08)',
                        border: '1px solid rgba(0,255,0,0.2)',
                        borderRadius: '4px',
                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                        flexShrink: 0,
                    }}>
                        <FontAwesomeIcon icon={faServer} style={{ color: '#00ff00', fontSize: '0.9rem' }} />
                    </div>
                    <div style={{ minWidth: 0 }}>
                        <p style={{
                            fontFamily: 'Orbitron, monospace',
                            fontSize: '0.95rem',
                            fontWeight: 700,
                            color: '#ffffff',
                            letterSpacing: '0.04em',
                            whiteSpace: 'nowrap',
                            overflow: 'hidden',
                            textOverflow: 'ellipsis',
                        }}>
                            {server.name}
                        </p>
                        {server.description && (
                            <p style={{
                                fontFamily: 'JetBrains Mono, monospace',
                                fontSize: '0.72rem',
                                color: 'rgba(255,255,255,0.4)',
                                marginTop: '0.1rem',
                                whiteSpace: 'nowrap',
                                overflow: 'hidden',
                                textOverflow: 'ellipsis',
                            }}>
                                {server.description}
                            </p>
                        )}
                    </div>
                </div>

                {/* Status badge */}
                <span style={{
                    fontFamily: 'JetBrains Mono, monospace',
                    fontSize: '0.65rem',
                    fontWeight: 700,
                    letterSpacing: '0.12em',
                    color: color,
                    background: `${color}18`,
                    border: `1px solid ${color}44`,
                    borderRadius: '3px',
                    padding: '3px 8px',
                    flexShrink: 0,
                    display: 'flex',
                    alignItems: 'center',
                    gap: '0.35rem',
                }}>
                    <span style={{
                        width: '6px', height: '6px',
                        borderRadius: '50%',
                        background: color,
                        boxShadow: `0 0 4px ${color}`,
                        display: 'inline-block',
                    }} />
                    {label}
                </span>
            </div>

            {/* IP + Stats row */}
            <div style={{
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fit, minmax(100px, 1fr))',
                gap: '0.5rem',
            }}>
                {/* IP */}
                <div style={statBox}>
                    <FontAwesomeIcon icon={faEthernet} style={{ color: 'rgba(0,255,0,0.5)', fontSize: '0.75rem' }} />
                    <div>
                        <p style={statValue}>{address}</p>
                        <p style={statLabel}>PORT</p>
                    </div>
                </div>

                {/* CPU */}
                <div style={{ ...statBox, ...(alarms.cpu ? alarmBox : {}) }}>
                    <FontAwesomeIcon icon={faMicrochip} style={{ color: alarms.cpu ? '#ef4444' : 'rgba(0,255,0,0.5)', fontSize: '0.75rem' }} />
                    <div>
                        {!stats ? (
                            <p style={statValue}>—</p>
                        ) : (
                            <p style={{ ...statValue, color: alarms.cpu ? '#ef4444' : '#fff' }}>
                                {stats.cpuUsagePercent.toFixed(1)}%
                            </p>
                        )}
                        <p style={statLabel}>CPU / {cpuLimit}</p>
                    </div>
                </div>

                {/* RAM */}
                <div style={{ ...statBox, ...(alarms.memory ? alarmBox : {}) }}>
                    <FontAwesomeIcon icon={faMemory} style={{ color: alarms.memory ? '#ef4444' : 'rgba(0,255,0,0.5)', fontSize: '0.75rem' }} />
                    <div>
                        {!stats ? (
                            <p style={statValue}>—</p>
                        ) : (
                            <p style={{ ...statValue, color: alarms.memory ? '#ef4444' : '#fff' }}>
                                {bytesToString(stats.memoryUsageInBytes)}
                            </p>
                        )}
                        <p style={statLabel}>RAM / {memoryLimit}</p>
                    </div>
                </div>

                {/* Disk */}
                <div style={{ ...statBox, ...(alarms.disk ? alarmBox : {}) }}>
                    <FontAwesomeIcon icon={faHdd} style={{ color: alarms.disk ? '#ef4444' : 'rgba(0,255,0,0.5)', fontSize: '0.75rem' }} />
                    <div>
                        {!stats ? (
                            <p style={statValue}>—</p>
                        ) : (
                            <p style={{ ...statValue, color: alarms.disk ? '#ef4444' : '#fff' }}>
                                {bytesToString(stats.diskUsageInBytes)}
                            </p>
                        )}
                        <p style={statLabel}>DISK / {diskLimit}</p>
                    </div>
                </div>
            </div>

            {/* Manage button */}
            <Link
                to={`/server/${server.id}`}
                style={{
                    display: 'block',
                    textAlign: 'center',
                    fontFamily: 'Orbitron, monospace',
                    fontSize: '0.7rem',
                    fontWeight: 700,
                    letterSpacing: '0.12em',
                    color: '#00ff00',
                    background: 'transparent',
                    border: '1.5px solid #00ff00',
                    borderRadius: '4px',
                    padding: '0.6rem 1rem',
                    textDecoration: 'none',
                    transition: 'background 0.2s, color 0.2s',
                }}
                onMouseEnter={(e) => {
                    e.currentTarget.style.background = '#00ff00';
                    e.currentTarget.style.color = '#000';
                }}
                onMouseLeave={(e) => {
                    e.currentTarget.style.background = 'transparent';
                    e.currentTarget.style.color = '#00ff00';
                }}
            >
                MANAGE SERVER
            </Link>
        </div>
    );
};

const statBox: React.CSSProperties = {
    background: 'rgba(0,0,0,0.3)',
    border: '1px solid rgba(0,255,0,0.08)',
    borderRadius: '4px',
    padding: '0.5rem 0.65rem',
    display: 'flex',
    alignItems: 'center',
    gap: '0.5rem',
};

const alarmBox: React.CSSProperties = {
    border: '1px solid rgba(239,68,68,0.25)',
    background: 'rgba(239,68,68,0.05)',
};

const statValue: React.CSSProperties = {
    fontFamily: 'JetBrains Mono, monospace',
    fontSize: '0.8rem',
    color: '#ffffff',
    fontWeight: 600,
    lineHeight: 1.2,
};

const statLabel: React.CSSProperties = {
    fontFamily: 'JetBrains Mono, monospace',
    fontSize: '0.6rem',
    color: 'rgba(255,255,255,0.3)',
    letterSpacing: '0.08em',
    marginTop: '0.15rem',
};
