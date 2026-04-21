import React, { useState, useEffect } from 'react';
import PageContentBlock from '@/components/elements/PageContentBlock';

interface Plan {
    id: number;
    name: string;
    price: number;
    ram: string;
    cpu: string;
    disk: string;
    dbs: number;
    badge: string | null;
}

const neon = '#00ff00';
const dim = 'rgba(255,255,255,0.55)';
const card = 'rgba(0,0,0,0.50)';
const border = 'rgba(0,255,0,0.18)';
const fontMono = "'JetBrains Mono', monospace";
const fontDisplay = "'Orbitron', monospace";

const useCases = [
    { icon: '🎮', title: 'Game Servers', desc: 'Host Minecraft, Rust, ARK, CS2, Valheim, and 50+ other games with full mod support and instant startup.' },
    { icon: '🤖', title: 'Discord Bots', desc: 'Run your Discord bots 24/7 with Node.js, Python, or any runtime. Never go offline again.' },
    { icon: '🌐', title: 'Web & APIs', desc: 'Deploy web apps, REST APIs, dashboards, and backend services with full port and domain control.' },
    { icon: '📦', title: 'Custom Apps', desc: 'Run any Docker-compatible application — databases, schedulers, scrapers, trading bots, and more.' },
];

const steps = [
    { num: '01', title: 'Pick a Plan', desc: 'Choose a resource tier below. All plans include port allocations, panel access, and Paystack / M-Pesa payment.' },
    { num: '02', title: 'Subscribe via Billing', desc: 'Head to the Billing page, select your plan, and pay securely by card or M-Pesa STK push.' },
    { num: '03', title: 'Deploy & Play', desc: "Your server slot is activated instantly. Go to Servers, create your server, and you're live." },
];

export default () => {
    const [plans, setPlans] = useState<Plan[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetch('/api/client/wxn/plans', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache',
            },
            credentials: 'same-origin',
            cache: 'no-store',
        })
            .then((r) => r.json())
            .then((json) => {
                if (json.data) setPlans(json.data);
            })
            .catch(() => null)
            .finally(() => setLoading(false));
    }, []);

    const lowestPrice = plans.length > 0 ? plans[0].price : null;

    return (
        <PageContentBlock title={'Overview'}>
            <style>{`
                @media (max-width: 600px) {
                    .wxn-overview-hero h2 { font-size: 1.25rem !important; }
                    .wxn-overview-hero p  { font-size: 0.8rem !important; }
                    .wxn-plan-grid { grid-template-columns: 1fr !important; }
                    .wxn-steps-grid { grid-template-columns: 1fr !important; }
                    .wxn-use-grid  { grid-template-columns: 1fr !important; }
                }
                @media (max-width: 820px) {
                    .wxn-plan-grid { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)) !important; }
                }
                .wxn-btn-solid { color: #000 !important; }
                .wxn-btn-solid:hover { color: #000 !important; opacity: 0.88; }
            `}</style>

            <div style={{ maxWidth: '1100px', margin: '0 auto', padding: '0 0.5rem' }}>

                {/* ── Hero ── */}
                <div className="wxn-overview-hero" style={{ textAlign: 'center', marginBottom: '3rem' }}>
                    <p style={{ fontFamily: fontDisplay, color: neon, fontSize: '0.7rem', letterSpacing: '0.25em', marginBottom: '0.75rem', textTransform: 'uppercase' }}>
                        ● WOLFXCORE GAME PANEL
                    </p>
                    <h2 style={{ fontFamily: fontDisplay, color: '#fff', fontSize: '1.8rem', fontWeight: 900, letterSpacing: '0.05em', marginBottom: '0.75rem', lineHeight: 1.2 }}>
                        DEPLOY YOUR SERVER
                    </h2>
                    <p style={{ fontFamily: fontMono, color: dim, fontSize: '0.9rem', maxWidth: '520px', margin: '0 auto 1.25rem', lineHeight: 1.7 }}>
                        wolfXcore gives you full control over your game servers and applications.
                        {lowestPrice !== null && (
                            <> Plans start at <span style={{ color: neon, fontWeight: 700 }}>KES {lowestPrice}/month</span> — pay by card or M-Pesa.</>
                        )}
                    </p>
                    <a href="/billing" className="wxn-btn-solid" style={{
                        display: 'inline-block',
                        fontFamily: fontDisplay, fontSize: '0.72rem', fontWeight: 900, letterSpacing: '2px',
                        background: neon, color: '#000', border: 'none', borderRadius: '5px',
                        padding: '0.7rem 2rem', textDecoration: 'none', transition: 'opacity .18s',
                    }}>
                        {lowestPrice !== null ? `⚡ GET STARTED — KES ${lowestPrice}/mo` : '⚡ GET STARTED'}
                    </a>
                </div>

                {/* ── How to get a server ── */}
                <div style={{ marginBottom: '3rem' }}>
                    <p style={{ fontFamily: fontDisplay, color: neon, fontSize: '0.62rem', letterSpacing: '0.2em', textTransform: 'uppercase', marginBottom: '1.25rem', textAlign: 'center' }}>
                        ── HOW TO GET STARTED ──
                    </p>
                    <div className="wxn-steps-grid" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(260px, 1fr))', gap: '1rem' }}>
                        {steps.map((s) => (
                            <div key={s.num} style={{ background: card, border: `1px solid ${border}`, borderTop: `2px solid rgba(0,255,0,0.4)`, borderRadius: '6px', padding: '1.5rem' }}>
                                <div style={{ fontFamily: fontDisplay, color: neon, fontSize: '1.6rem', fontWeight: 900, lineHeight: 1, marginBottom: '0.5rem', opacity: 0.35 }}>
                                    {s.num}
                                </div>
                                <div style={{ fontFamily: fontDisplay, color: '#fff', fontSize: '0.75rem', fontWeight: 700, letterSpacing: '0.08em', textTransform: 'uppercase', marginBottom: '0.5rem' }}>
                                    {s.title}
                                </div>
                                <div style={{ fontFamily: fontMono, color: dim, fontSize: '0.82rem', lineHeight: 1.65 }}>
                                    {s.desc}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* ── Use cases ── */}
                <div style={{ marginBottom: '3rem' }}>
                    <p style={{ fontFamily: fontDisplay, color: neon, fontSize: '0.62rem', letterSpacing: '0.2em', textTransform: 'uppercase', marginBottom: '1.25rem', textAlign: 'center' }}>
                        ── WHAT CAN YOU RUN? ──
                    </p>
                    <div className="wxn-use-grid" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(230px, 1fr))', gap: '1rem' }}>
                        {useCases.map((u) => (
                            <div key={u.title} style={{ background: card, border: `1px solid ${border}`, borderRadius: '6px', padding: '1.25rem', display: 'flex', gap: '1rem', alignItems: 'flex-start' }}>
                                <span style={{ fontSize: '1.6rem', lineHeight: 1, flexShrink: 0 }}>{u.icon}</span>
                                <div>
                                    <div style={{ fontFamily: fontDisplay, color: '#fff', fontSize: '0.72rem', fontWeight: 700, letterSpacing: '0.06em', textTransform: 'uppercase', marginBottom: '0.4rem' }}>
                                        {u.title}
                                    </div>
                                    <div style={{ fontFamily: fontMono, color: dim, fontSize: '0.78rem', lineHeight: 1.65 }}>
                                        {u.desc}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* ── Plans ── */}
                <div style={{ marginBottom: '2rem' }}>
                    <p style={{ fontFamily: fontDisplay, color: neon, fontSize: '0.62rem', letterSpacing: '0.2em', textTransform: 'uppercase', marginBottom: '1.25rem', textAlign: 'center' }}>
                        ── CHOOSE YOUR PLAN ──
                    </p>

                    {loading ? (
                        <p style={{ fontFamily: fontMono, color: dim, fontSize: '0.82rem', textAlign: 'center', padding: '2rem 0' }}>
                            Loading plans...
                        </p>
                    ) : plans.length === 0 ? (
                        <p style={{ fontFamily: fontMono, color: dim, fontSize: '0.82rem', textAlign: 'center', padding: '2rem 0' }}>
                            No plans available yet. Check back soon.
                        </p>
                    ) : (
                        <div className="wxn-plan-grid" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: '1.25rem', maxWidth: '980px', margin: '0 auto' }}>
                            {plans.map((plan) => (
                                <div key={plan.id} style={{
                                    background: card,
                                    border: plan.badge === 'MOST POPULAR' ? '1.5px solid #00ff00' : `1px solid ${border}`,
                                    borderRadius: '6px',
                                    padding: '1.75rem 1.5rem',
                                    display: 'flex',
                                    flexDirection: 'column',
                                    alignItems: 'center',
                                    position: 'relative',
                                    boxShadow: plan.badge === 'MOST POPULAR' ? '0 0 28px rgba(0,255,0,0.14)' : 'none',
                                }}>
                                    {plan.badge && (
                                        <span style={{
                                            position: 'absolute', top: '-1px', left: '50%', transform: 'translateX(-50%)',
                                            background: neon, color: '#000', fontFamily: fontDisplay, fontSize: '0.56rem',
                                            fontWeight: 700, letterSpacing: '0.12em', padding: '2px 12px',
                                            borderRadius: '0 0 4px 4px', whiteSpace: 'nowrap',
                                        }}>
                                            {plan.badge}
                                        </span>
                                    )}
                                    <p style={{ fontFamily: fontDisplay, color: neon, fontSize: '0.68rem', letterSpacing: '0.2em', marginBottom: '0.6rem', marginTop: plan.badge ? '0.75rem' : '0' }}>
                                        {plan.name}
                                    </p>
                                    <div style={{ marginBottom: '1.25rem', lineHeight: 1, textAlign: 'center' }}>
                                        <span style={{ fontFamily: fontDisplay, color: '#fff', fontSize: '2rem', fontWeight: 700 }}>
                                            KES {plan.price}
                                        </span>
                                        <span style={{ fontFamily: fontMono, color: 'rgba(255,255,255,0.4)', fontSize: '0.72rem', display: 'block', marginTop: '0.25rem' }}>
                                            / month
                                        </span>
                                    </div>
                                    <ul style={{ listStyle: 'none', padding: 0, margin: '0 0 1.5rem 0', width: '100%' }}>
                                        {[
                                            `${plan.ram} RAM`,
                                            `${plan.cpu} CPU`,
                                            `${plan.disk} Disk`,
                                            `${plan.dbs} Database${plan.dbs !== 1 ? 's' : ''}`,
                                            'Port allocations included',
                                        ].map((f) => (
                                            <li key={f} style={{ fontFamily: fontMono, fontSize: '0.78rem', color: 'rgba(255,255,255,0.78)', padding: '0.35rem 0', borderBottom: '1px solid rgba(0,255,0,0.07)', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                                                <span style={{ color: neon, fontSize: '0.55rem', flexShrink: 0 }}>▶</span>
                                                {f}
                                            </li>
                                        ))}
                                    </ul>
                                    <a
                                        href="/billing"
                                        style={{
                                            width: '100%', textAlign: 'center',
                                            background: 'transparent',
                                            color: neon,
                                            border: `1.5px solid ${neon}`, borderRadius: '4px',
                                            fontFamily: fontDisplay, fontSize: '0.66rem', fontWeight: 700,
                                            letterSpacing: '0.1em', padding: '0.65rem 1rem',
                                            textDecoration: 'none', display: 'block', transition: 'all 0.18s',
                                        }}
                                        onMouseEnter={(e) => {
                                            const el = e.currentTarget as HTMLAnchorElement;
                                            el.style.background = neon;
                                            el.style.color = '#000';
                                            el.classList.add('wxn-btn-solid');
                                        }}
                                        onMouseLeave={(e) => {
                                            const el = e.currentTarget as HTMLAnchorElement;
                                            el.style.background = 'transparent';
                                            el.style.color = neon;
                                            el.classList.remove('wxn-btn-solid');
                                        }}
                                    >
                                        Subscribe — KES {plan.price}
                                    </a>
                                </div>
                            ))}
                        </div>
                    )}

                    <p style={{ fontFamily: fontMono, color: 'rgba(255,255,255,0.3)', fontSize: '0.73rem', textAlign: 'center', marginTop: '1.25rem', lineHeight: 1.7 }}>
                        Buy 3+ months on any plan and save KES 5–15 per month.
                        Payment by card or M-Pesa STK push via Paystack.
                    </p>
                </div>

            </div>
        </PageContentBlock>
    );
};
