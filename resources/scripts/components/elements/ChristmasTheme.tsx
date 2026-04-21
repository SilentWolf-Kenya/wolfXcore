import React, { useEffect, useState, useRef } from 'react';

/* ─── Season detection: Nov 25 – Dec 31 ─────────────────────────────── */
function isChristmasSeason(): boolean {
    const now = new Date();
    const m   = now.getMonth(); // 0-based
    const d   = now.getDate();
    return (m === 10 && d >= 25) || m === 11;
}

/* ─── Web Audio jingle bells ─────────────────────────────────────────── */
let _audioCtx: AudioContext | null = null;
function getAudioCtx(): AudioContext {
    if (!_audioCtx) _audioCtx = new (window.AudioContext || (window as any).webkitAudioContext)();
    return _audioCtx;
}

function playNote(ctx: AudioContext, freq: number, start: number, dur: number, vol = 0.18) {
    const osc  = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.type = 'triangle';
    osc.frequency.value = freq;
    gain.gain.setValueAtTime(vol, start);
    gain.gain.exponentialRampToValueAtTime(0.001, start + dur * 0.9);
    osc.start(start);
    osc.stop(start + dur);
}

const JINGLE_NOTES = [
    // "Jingle Bells" first bar
    [659,0.18],[659,0.18],[659,0.36],
    [659,0.18],[659,0.18],[659,0.36],
    [659,0.18],[784,0.18],[523,0.18],[587,0.18],[659,0.48],
    [698,0.18],[698,0.18],[698,0.18],[698,0.18],
    [698,0.18],[659,0.18],[659,0.18],[659,0.14],
    [659,0.14],[587,0.18],[587,0.18],[659,0.18],[587,0.36],[784,0.36],
];

function playJingleBells() {
    try {
        const ctx = getAudioCtx();
        let t = ctx.currentTime + 0.05;
        JINGLE_NOTES.forEach(([freq, dur]) => {
            playNote(ctx, freq as number, t, dur as number);
            t += (dur as number) + 0.03;
        });
    } catch (_) { /* audio blocked */ }
}

/* ─── Snowflake data ─────────────────────────────────────────────────── */
const CHARS = ['❄','❅','❆','*'];
interface Flake { id: number; ch: string; left: number; size: number; dur: number; delay: number; sway: number; }

function makeFlakes(n: number): Flake[] {
    return Array.from({ length: n }, (_, i) => ({
        id:    i,
        ch:    CHARS[Math.floor(Math.random() * CHARS.length)],
        left:  Math.random() * 100,
        size:  Math.random() * 14 + 7,
        dur:   Math.random() * 9 + 7,
        delay: Math.random() * 12,
        sway:  Math.random() * 40 + 20,
    }));
}

/* ─── Global keyframe injection ─────────────────────────────────────── */
function injectStyles() {
    if (document.getElementById('xmas-styles')) return;
    const s = document.createElement('style');
    s.id = 'xmas-styles';
    s.textContent = `
@keyframes xmas-fall {
    0%   { transform: translateY(-30px) translateX(0) rotate(0deg); opacity: 0; }
    5%   { opacity: 1; }
    92%  { opacity: 0.7; }
    100% { transform: translateY(110vh) translateX(var(--sway)) rotate(360deg); opacity: 0; }
}
@keyframes xmas-sway {
    0%,100% { margin-left: 0; }
    50%      { margin-left: var(--sway-px); }
}
@keyframes xmas-fly {
    0%   { transform: translateX(110vw) scaleX(-1); }
    100% { transform: translateX(-18vw) scaleX(-1); }
}
@keyframes xmas-fly2 {
    0%   { transform: translateX(110vw) scaleX(-1); }
    100% { transform: translateX(-18vw) scaleX(-1); }
}
@keyframes xmas-lights-blink {
    0%,100% { opacity: 1; box-shadow: 0 0 6px 2px currentColor; }
    50%     { opacity: 0.4; box-shadow: none; }
}
@keyframes xmas-modal-in {
    0%   { transform: scale(0.3) rotate(-10deg); opacity: 0; }
    60%  { transform: scale(1.08) rotate(2deg); opacity: 1; }
    80%  { transform: scale(0.97) rotate(-1deg); }
    100% { transform: scale(1) rotate(0deg); }
}
@keyframes xmas-star-spin {
    from { transform: rotate(0deg) scale(1); }
    to   { transform: rotate(360deg) scale(1.1); }
}
@keyframes xmas-confetti-fall {
    0%   { transform: translateY(0) rotate(0deg); opacity: 1; }
    100% { transform: translateY(120px) rotate(720deg); opacity: 0; }
}
@keyframes xmas-bell-ring {
    0%,100% { transform: rotate(0deg) }
    20% { transform: rotate(-20deg) }
    40% { transform: rotate(20deg) }
    60% { transform: rotate(-15deg) }
    80% { transform: rotate(15deg) }
}
@keyframes xmas-glow-pulse {
    0%,100% { text-shadow: 0 0 10px #ff0000, 0 0 20px #00aa00; }
    50%     { text-shadow: 0 0 20px #ff4444, 0 0 40px #00cc00, 0 0 60px #ffcc00; }
}
@keyframes xmas-ribbon {
    0%,100% { opacity: 0.6; }
    50%     { opacity: 1; }
}
`;
    document.head.appendChild(s);
}

/* ─── Confetti particle for Merry Christmas modal ────────────────────── */
const CONFETTI_COLORS = ['#ff0000','#00aa00','#ffcc00','#ffffff','#ff69b4','#00ccff'];
interface Confetti { id: number; x: number; color: string; size: number; delay: number; }
function makeConfetti(n: number): Confetti[] {
    return Array.from({ length: n }, (_, i) => ({
        id: i, x: Math.random() * 100, color: CONFETTI_COLORS[i % CONFETTI_COLORS.length],
        size: Math.random() * 8 + 4, delay: Math.random() * 0.8,
    }));
}

/* ─── Lights strip ──────────────────────────────────────────────────── */
const LIGHT_COLORS = ['#ff2222','#22ff22','#ffcc00','#2288ff','#ff66cc','#ffffff'];
function LightsStrip() {
    const count = Math.ceil(window.innerWidth / 30);
    return (
        <div style={{
            position: 'fixed', top: 0, left: 0, right: 0, height: 22,
            display: 'flex', alignItems: 'flex-end', zIndex: 9998,
            pointerEvents: 'none',
            background: 'linear-gradient(to bottom, rgba(0,0,0,0.55), transparent)',
        }}>
            {/* wire */}
            <div style={{ position: 'absolute', top: 8, left: 0, right: 0, height: 2, background: 'rgba(80,40,0,0.8)' }} />
            {Array.from({ length: count }, (_, i) => {
                const color = LIGHT_COLORS[i % LIGHT_COLORS.length];
                return (
                    <div key={i} style={{
                        width: 10, height: 16, borderRadius: '50% 50% 45% 45%',
                        background: color, color,
                        flex: '0 0 auto', marginLeft: 20,
                        animation: `xmas-lights-blink ${0.8 + (i % 5) * 0.3}s ease-in-out ${(i * 0.07) % 1.5}s infinite`,
                        position: 'relative', top: 4,
                    }} />
                );
            })}
        </div>
    );
}

/* ─── Reindeer + sleigh ─────────────────────────────────────────────── */
function Reindeer({ wave }: { wave: number }) {
    const top  = [10, 16, 12][wave % 3];
    const dur  = [22, 28, 18][wave % 3];
    const dly  = [0, 12, 6][wave % 3];
    return (
        <div style={{
            position: 'fixed',
            top: `${top}%`,
            right: 0,
            zIndex: 9997,
            pointerEvents: 'none',
            animation: `xmas-fly${wave === 1 ? '2' : ''} ${dur}s linear ${dly}s infinite`,
            fontSize: wave === 2 ? '1.4rem' : '1.8rem',
            filter: 'drop-shadow(0 2px 8px rgba(0,0,0,0.6))',
            whiteSpace: 'nowrap',
        }}>
            🦌🦌🦌🦌&nbsp;🛷🎅
        </div>
    );
}

/* ─── Merry Christmas Deployment Modal ──────────────────────────────── */
function MerryChristmasModal({ onClose }: { onClose: () => void }) {
    const confetti = makeConfetti(40);
    return (
        <div style={{
            position: 'fixed', inset: 0, zIndex: 99999,
            display: 'flex', alignItems: 'center', justifyContent: 'center',
            background: 'rgba(0,5,0,0.88)',
        }} onClick={onClose}>
            <div style={{
                background: 'linear-gradient(135deg, #0a1f0a 0%, #1a0a0a 50%, #0a0a1f 100%)',
                border: '2px solid rgba(0,255,80,0.4)',
                borderRadius: 20,
                padding: '40px 52px',
                textAlign: 'center',
                position: 'relative',
                maxWidth: 520,
                boxShadow: '0 0 60px rgba(0,200,0,0.3), 0 0 120px rgba(255,0,0,0.15)',
                animation: 'xmas-modal-in 0.6s cubic-bezier(0.22,1,0.36,1) forwards',
                overflow: 'hidden',
            }} onClick={e => e.stopPropagation()}>

                {/* Confetti */}
                {confetti.map(c => (
                    <div key={c.id} style={{
                        position: 'absolute',
                        left: `${c.x}%`,
                        top: -10,
                        width: c.size, height: c.size,
                        background: c.color,
                        borderRadius: c.id % 3 === 0 ? '50%' : 2,
                        animation: `xmas-confetti-fall 1.2s ease-out ${c.delay}s forwards`,
                        pointerEvents: 'none',
                    }} />
                ))}

                {/* Tree */}
                <div style={{ fontSize: '4rem', marginBottom: 4, animation: 'xmas-star-spin 4s linear infinite' }}>🎄</div>

                {/* Merry Christmas */}
                <div style={{
                    fontFamily: "'Orbitron', monospace",
                    fontSize: '1.8rem', fontWeight: 900,
                    background: 'linear-gradient(90deg, #ff2222, #00cc44, #ffcc00, #ff2222)',
                    backgroundSize: '200% auto',
                    WebkitBackgroundClip: 'text',
                    WebkitTextFillColor: 'transparent',
                    animation: 'xmas-glow-pulse 1.5s ease-in-out infinite',
                    letterSpacing: '0.06em',
                    marginBottom: 8,
                }}>
                    MERRY CHRISTMAS!
                </div>

                <div style={{
                    fontFamily: "'JetBrains Mono', monospace",
                    fontSize: '0.95rem', color: 'rgba(255,255,255,0.8)',
                    marginBottom: 6,
                }}>
                    🎁 Your server has been deployed!
                </div>

                <div style={{
                    fontFamily: "'JetBrains Mono', monospace",
                    fontSize: '0.72rem', color: 'rgba(0,255,80,0.7)',
                    marginBottom: 20,
                    letterSpacing: '0.05em',
                }}>
                    ✨ May your uptime be 99.9% and your packets never drop ✨
                </div>

                {/* Bells */}
                <div style={{ fontSize: '2rem', marginBottom: 16, animation: 'xmas-bell-ring 1s ease-in-out 3' }}>
                    🔔 🎅 🔔
                </div>

                <button onClick={() => { playJingleBells(); onClose(); }} style={{
                    background: 'linear-gradient(135deg, #cc0000, #006600)',
                    border: 'none', borderRadius: 8,
                    color: '#fff', fontFamily: "'Orbitron', monospace",
                    fontSize: '0.75rem', fontWeight: 700,
                    padding: '10px 28px', cursor: 'pointer',
                    letterSpacing: '0.1em',
                    boxShadow: '0 4px 20px rgba(255,0,0,0.3)',
                    transition: 'transform 0.1s',
                }}
                    onMouseEnter={e => (e.currentTarget.style.transform = 'scale(1.05)')}
                    onMouseLeave={e => (e.currentTarget.style.transform = 'scale(1)')}
                >
                    🎶 JINGLE & CLOSE
                </button>

                <div style={{
                    marginTop: 12,
                    fontFamily: "'JetBrains Mono', monospace",
                    fontSize: '0.6rem', color: 'rgba(255,255,255,0.2)',
                }}>click anywhere to close</div>
            </div>
        </div>
    );
}

/* ─── Christmas corner decorations ──────────────────────────────────── */
function CornerDecorations() {
    return (
        <>
            {/* Top-left holly */}
            <div style={{
                position: 'fixed', top: 24, left: 8, zIndex: 9996,
                pointerEvents: 'none', fontSize: '1.6rem',
                filter: 'drop-shadow(0 2px 6px rgba(0,0,0,0.7))',
                animation: 'xmas-ribbon 2s ease-in-out infinite',
            }}>🎋</div>
            {/* Bottom-left */}
            <div style={{
                position: 'fixed', bottom: 16, left: 8, zIndex: 9996,
                pointerEvents: 'none', fontSize: '1.4rem',
                filter: 'drop-shadow(0 2px 6px rgba(0,0,0,0.7))',
                animation: 'xmas-ribbon 2.4s ease-in-out 0.4s infinite',
            }}>🎁</div>
            {/* Bottom-right */}
            <div style={{
                position: 'fixed', bottom: 16, right: 16, zIndex: 9996,
                pointerEvents: 'none', fontSize: '1.4rem',
                filter: 'drop-shadow(0 2px 6px rgba(0,0,0,0.7))',
                animation: 'xmas-ribbon 2s ease-in-out 0.8s infinite',
            }}>⛄</div>
            {/* Jingle bell watermark right side */}
            <div style={{
                position: 'fixed', top: '45%', right: 8, zIndex: 9996,
                pointerEvents: 'none', fontSize: '1.3rem',
                animation: 'xmas-bell-ring 3s ease-in-out infinite',
                filter: 'drop-shadow(0 2px 4px rgba(0,0,0,0.6))',
            }}>🔔</div>
        </>
    );
}

/* ─── Jingle Bell Button ─────────────────────────────────────────────────── */
function JingleBellButton() {
    return (
        <div title="Jingle Bells!" onClick={() => playJingleBells()}
            style={{
                position: 'fixed', bottom: 60, right: 16, zIndex: 9999,
                cursor: 'pointer', fontSize: '1.8rem',
                filter: 'drop-shadow(0 2px 10px rgba(255,200,0,0.8))',
                animation: 'xmas-bell-ring 2s ease-in-out infinite',
                userSelect: 'none',
            }}>🔔</div>
    );
}

/* ─── Main ChristmasTheme Component ─────────────────────────────────── */
const ChristmasTheme = () => {
    const [active,    setActive]    = useState(false);
    const [showModal, setShowModal] = useState(false);
    const [flakes]                  = useState(() => makeFlakes(60));
    const checked = useRef(false);

    /* Determine active state from DB setting passed via SiteConfiguration */
    useEffect(() => {
        if (checked.current) return;
        checked.current = true;
        const cfg = (window as any).SiteConfiguration;
        const dbMode: string = cfg?.christmasTheme ?? 'auto';
        setActive(dbMode === 'on' || (dbMode !== 'off' && isChristmasSeason()));
    }, []);

    /* Listen for install completed event */
    useEffect(() => {
        const handler = () => { if (active) setShowModal(true); };
        window.addEventListener('wolfxcore:install_completed', handler);
        return () => window.removeEventListener('wolfxcore:install_completed', handler);
    }, [active]);

    /* Inject styles once active */
    useEffect(() => {
        if (active) injectStyles();
    }, [active]);

    if (!active) return null;

    return (
        <>
            <LightsStrip />

            <div style={{ position: 'fixed', inset: 0, zIndex: 9995, pointerEvents: 'none', overflow: 'hidden' }}>
                {flakes.map(f => (
                    <span key={f.id} style={{
                        position: 'absolute',
                        left: `${f.left}%`,
                        top: -30,
                        fontSize: f.size,
                        color: 'rgba(255,255,255,0.85)',
                        animation: `xmas-fall ${f.dur}s linear ${f.delay}s infinite`,
                        ['--sway' as any]: `${f.sway * (f.id % 2 === 0 ? 1 : -1)}px`,
                        userSelect: 'none',
                    }}>
                        {f.ch}
                    </span>
                ))}
            </div>

            <Reindeer wave={0} />
            <Reindeer wave={1} />
            <Reindeer wave={2} />

            <CornerDecorations />

            <JingleBellButton />

            {showModal && <MerryChristmasModal onClose={() => setShowModal(false)} />}
        </>
    );
};

export default ChristmasTheme;

/* ─── Export helper to trigger modal externally ──────────────────────── */
export function triggerChristmasDeployModal() {
    window.dispatchEvent(new CustomEvent('wolfxcore:install_completed'));
}
