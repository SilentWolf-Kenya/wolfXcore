import React, { useEffect, useState, useRef } from 'react';
import http from '@/api/http';

const neon = '#00ff00';
const mono = "'JetBrains Mono', monospace";
const orb  = "'Orbitron', monospace";


interface WalletBalance {
    balance: number;
    total_credited: number;
    total_debited: number;
    currency: string;
}

interface WalletTxn {
    id: number;
    type: 'credit' | 'debit';
    amount: number;
    balance_after: number;
    description: string;
    reference: string;
    gateway: string;
    status: 'pending' | 'completed' | 'failed';
    created_at: string;
}

const PRESET_AMOUNTS = [100, 200, 500, 1000, 2000, 5000];

const fmt = (n: number) => n.toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const fmtDate = (s: string) => new Date(s).toLocaleDateString('en-KE', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

const SectionTitle = ({ children }: { children: React.ReactNode }) => (
    <div style={{
        fontFamily: orb, fontSize: '0.68rem', letterSpacing: '3px',
        textTransform: 'uppercase', color: neon,
        borderBottom: '1px solid rgba(0,255,0,0.15)',
        paddingBottom: 8, marginBottom: 20, marginTop: 28,
    }}>{children}</div>
);

const Card = ({ children, style }: { children: React.ReactNode; style?: React.CSSProperties }) => (
    <div style={{
        background: 'rgba(0,0,0,0.3)',
        border: '1px solid rgba(0,255,0,0.12)',
        borderRadius: 10, padding: 20,
        ...style,
    }}>{children}</div>
);

const StatusBadge = ({ status }: { status: string }) => {
    const colors: Record<string, { bg: string; color: string }> = {
        completed: { bg: 'rgba(0,255,0,0.1)',   color: neon },
        success:   { bg: 'rgba(0,255,0,0.1)',   color: neon },   // blade deposits use 'success'
        pending:   { bg: 'rgba(255,200,0,0.1)', color: '#ffc800' },
        failed:    { bg: 'rgba(255,60,60,0.1)', color: '#ff7070' },
    };
    const c = colors[status] ?? colors.pending;
    return (
        <span style={{
            fontFamily: mono, fontSize: '0.62rem', fontWeight: 700, letterSpacing: '0.5px',
            padding: '2px 8px', borderRadius: 10,
            background: c.bg, color: c.color,
            border: `1px solid ${c.color}33`,
        }}>
            {status.toUpperCase()}
        </span>
    );
};

export default () => {
    const [wallet, setWallet]           = useState<WalletBalance | null>(null);
    const [txns, setTxns]               = useState<WalletTxn[]>([]);
    const [loading, setLoading]         = useState(true);
    const [amount, setAmount]           = useState<number | ''>('');
    const [method, setMethod]           = useState<'card' | 'mpesa' | 'airtel'>('card');
    const [phone, setPhone]             = useState('');
    const [paying, setPaying]           = useState(false);
    const [toast, setToast]             = useState<{ msg: string; ok: boolean } | null>(null);
    const [stkOpen, setStkOpen]         = useState(false);
    const [stkState, setStkState]       = useState<'waiting' | 'success' | 'failed'>('waiting');
    const [stkMsg, setStkMsg]           = useState('');
    const [stkRef, setStkRef]           = useState('');
    const pollRef                        = useRef<ReturnType<typeof setInterval> | null>(null);

    const showToast = (msg: string, ok: boolean) => {
        setToast({ msg, ok });
        setTimeout(() => setToast(null), 4500);
    };

    const fetchWallet = async () => {
        try {
            const { data } = await http.get('/api/client/wallet');
            setWallet(data);
        } catch {
            /* silently fail */
        }
    };

    const fetchTxns = async () => {
        try {
            const { data } = await http.get('/api/client/wallet/transactions');
            setTxns(data.data ?? []);
        } catch {
            /* silently fail */
        }
    };

    useEffect(() => {
        if (!document.getElementById('paystack-inline-js')) {
            const s = document.createElement('script');
            s.id  = 'paystack-inline-js';
            s.src = 'https://js.paystack.co/v1/inline.js';
            document.head.appendChild(s);
        }
        Promise.all([fetchWallet(), fetchTxns()]).finally(() => setLoading(false));
    }, []);

    const stopPolling = () => {
        if (pollRef.current) { clearInterval(pollRef.current); pollRef.current = null; }
    };

    const pollVerify = (reference: string) => {
        let count = 0;
        pollRef.current = setInterval(async () => {
            count++;
            try {
                const { data } = await http.post('/api/client/wallet/verify', { reference });
                if (data.status === 'success') {
                    stopPolling();
                    setStkState('success');
                    setStkMsg(data.message ?? 'Wallet topped up successfully!');
                    await fetchWallet();
                    await fetchTxns();
                    return;
                }
                if (data.status === 'failed') {
                    stopPolling();
                    setStkState('failed');
                    setStkMsg(data.message ?? 'Payment failed.');
                    return;
                }
            } catch { /* keep polling */ }
            if (count >= 40) {
                stopPolling();
                setStkState('failed');
                setStkMsg('Timed out waiting for confirmation. Check your transaction history.');
            }
        }, 3000);
    };

    const handleTopup = async () => {
        if (!amount || Number(amount) < 40) { showToast('Minimum top-up is KES 40', false); return; }
        if ((method === 'mpesa' || method === 'airtel') && !phone.trim()) {
            showToast('Enter your phone number', false); return;
        }
        setPaying(true);
        try {
            const { data } = await http.post('/api/client/wallet/topup', {
                amount: Number(amount),
                payment_method: method,
                phone: phone || undefined,
            });

            if (data.type === 'card') {
                const PaystackPop = (window as any).PaystackPop;
                const handler = PaystackPop.setup({
                    key:       data.public_key,
                    email:     data.email,
                    amount:    data.amount_kobo,
                    currency:  data.currency,
                    ref:       data.reference,
                    label:     'Wallet Top-up',
                    onSuccess: async (res: any) => {
                        try {
                            const v = await http.post('/api/client/wallet/verify', { reference: res.reference });
                            if (v.data.status === 'success') {
                                showToast(v.data.message ?? 'Wallet topped up!', true);
                                await fetchWallet();
                                await fetchTxns();
                            } else {
                                showToast('Payment received but verification pending. Refresh in a moment.', false);
                            }
                        } catch { showToast('Verify error — check history.', false); }
                    },
                    onCancel: () => showToast('Payment cancelled.', false),
                });
                handler.openIframe();
                return;
            }

            if (data.type === 'mobile') {
                setStkRef(data.reference);
                setStkMsg(data.message);
                setStkState('waiting');
                setStkOpen(true);
                pollVerify(data.reference);
                return;
            }

            showToast('Unexpected response. Try again.', false);
        } catch (e: any) {
            const msg = e?.response?.data?.error ?? e?.response?.data?.message ?? 'Top-up failed.';
            showToast(msg, false);
        } finally {
            setPaying(false);
        }
    };

    const closeStkOverlay = () => {
        stopPolling();
        setStkOpen(false);
        fetchWallet();
        fetchTxns();
    };

    const currency = wallet?.currency ?? 'KES';

    return (
        <div style={{ padding: '28px 16px 80px', maxWidth: 900, margin: '0 auto', fontFamily: mono }}>

            {/* Toast */}
            {toast && (
                <div style={{
                    position: 'fixed', top: 20, right: 20, zIndex: 9999,
                    background: toast.ok ? 'rgba(0,40,0,0.97)' : 'rgba(40,0,0,0.97)',
                    border: `1px solid ${toast.ok ? 'rgba(0,255,0,0.4)' : 'rgba(255,60,60,0.4)'}`,
                    color: toast.ok ? neon : '#ff7070',
                    fontFamily: mono, fontSize: '0.8rem',
                    padding: '12px 20px', borderRadius: 8,
                    boxShadow: '0 4px 24px rgba(0,0,0,0.6)',
                    maxWidth: 340,
                }}>
                    {toast.ok ? '✓ ' : '✗ '}{toast.msg}
                </div>
            )}

            {/* STK Push Overlay */}
            {stkOpen && (
                <div style={{
                    position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.88)',
                    zIndex: 9998, display: 'flex', alignItems: 'center', justifyContent: 'center',
                }}>
                    <div style={{
                        background: '#0a1a0a', border: '1.5px solid rgba(0,255,0,0.45)',
                        borderRadius: 16, padding: '40px 36px 32px',
                        maxWidth: 460, width: '92%', textAlign: 'center',
                        boxShadow: '0 0 60px rgba(0,255,0,0.12), 0 24px 64px rgba(0,0,0,0.7)',
                    }}>
                        <div style={{ fontSize: '3rem', marginBottom: 14 }}>
                            {stkState === 'success' ? '✅' : stkState === 'failed' ? '❌' : '📱'}
                        </div>
                        <div style={{ fontFamily: orb, fontSize: '0.9rem', color: neon, marginBottom: 12, letterSpacing: '2px' }}>
                            {stkState === 'success' ? 'PAYMENT CONFIRMED' : stkState === 'failed' ? 'PAYMENT FAILED' : 'CHECK YOUR PHONE'}
                        </div>
                        <div style={{ fontSize: '0.82rem', color: 'rgba(255,255,255,0.65)', lineHeight: 1.65, marginBottom: 16 }}>
                            {stkMsg}
                        </div>
                        {stkRef && (
                            <div style={{ fontFamily: mono, fontSize: '0.65rem', color: 'rgba(0,255,0,0.5)', background: 'rgba(0,0,0,0.45)', padding: '6px 12px', borderRadius: 4, marginBottom: 20, wordBreak: 'break-all' }}>
                                {stkRef}
                            </div>
                        )}
                        {stkState === 'waiting' && (
                            <div style={{ width: 36, height: 36, border: '3px solid rgba(0,255,0,0.15)', borderTopColor: neon, borderRadius: '50%', animation: 'spin 0.8s linear infinite', margin: '0 auto 16px' }} />
                        )}
                        <button
                            onClick={closeStkOverlay}
                            style={{
                                fontFamily: orb, fontSize: '0.65rem', letterSpacing: '1.5px',
                                padding: '10px 28px', borderRadius: 5, cursor: 'pointer',
                                background: stkState === 'waiting' ? 'none' : neon,
                                color: stkState === 'waiting' ? 'rgba(255,255,255,0.4)' : '#000',
                                border: stkState === 'waiting' ? '1px solid rgba(255,255,255,0.15)' : 'none',
                                fontWeight: 700,
                            }}
                        >
                            {stkState === 'waiting' ? 'CANCEL' : 'CLOSE'}
                        </button>
                    </div>
                </div>
            )}

            <style>{`@keyframes spin { to { transform: rotate(360deg); } }`}</style>

            {/* Page header */}
            <div style={{ marginBottom: 28 }}>
                <h1 style={{ fontFamily: orb, fontSize: '1.1rem', color: neon, letterSpacing: '3px', marginBottom: 4 }}>
                    WALLET
                </h1>
                <p style={{ fontSize: '0.73rem', color: 'rgba(255,255,255,0.35)' }}>
                    Top up your balance and track all transactions
                </p>
            </div>

            {/* Balance cards */}
            {loading ? (
                <div style={{ color: 'rgba(255,255,255,0.3)', fontSize: '0.78rem', padding: '24px 0' }}>Loading wallet...</div>
            ) : (
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(220px, 1fr))', gap: 16, marginBottom: 8 }}>
                    <Card style={{ borderColor: 'rgba(0,255,0,0.25)', background: 'rgba(0,255,0,0.04)' }}>
                        <div style={{ fontFamily: mono, fontSize: '0.65rem', color: 'rgba(0,255,0,0.5)', letterSpacing: '2px', marginBottom: 8 }}>AVAILABLE BALANCE</div>
                        <div style={{ fontFamily: orb, fontSize: '2rem', color: '#fff', lineHeight: 1 }}>
                            {currency} <span style={{ color: neon }}>{fmt(wallet?.balance ?? 0)}</span>
                        </div>
                        <div style={{ fontFamily: mono, fontSize: '0.63rem', color: 'rgba(255,255,255,0.3)', marginTop: 8 }}>Updated live</div>
                    </Card>
                    <Card>
                        <div style={{ fontFamily: mono, fontSize: '0.65rem', color: 'rgba(255,255,255,0.35)', letterSpacing: '2px', marginBottom: 8 }}>TOTAL CREDITED</div>
                        <div style={{ fontFamily: orb, fontSize: '2rem', color: 'rgba(255,255,255,0.6)', lineHeight: 1 }}>
                            {currency} <span>{fmt(wallet?.total_credited ?? 0)}</span>
                        </div>
                        <div style={{ fontFamily: mono, fontSize: '0.63rem', color: 'rgba(255,255,255,0.25)', marginTop: 8 }}>All-time top-ups</div>
                    </Card>
                    <Card>
                        <div style={{ fontFamily: mono, fontSize: '0.65rem', color: 'rgba(255,255,255,0.35)', letterSpacing: '2px', marginBottom: 8 }}>TOTAL SPENT</div>
                        <div style={{ fontFamily: orb, fontSize: '2rem', color: 'rgba(255,255,255,0.6)', lineHeight: 1 }}>
                            {currency} <span>{fmt(wallet?.total_debited ?? 0)}</span>
                        </div>
                        <div style={{ fontFamily: mono, fontSize: '0.63rem', color: 'rgba(255,255,255,0.25)', marginTop: 8 }}>All-time debits</div>
                    </Card>
                </div>
            )}

            {/* Top-up section */}
            <SectionTitle>Top Up Balance</SectionTitle>
            <Card>
                <div style={{ fontSize: '0.72rem', color: 'rgba(255,255,255,0.5)', marginBottom: 16 }}>
                    Add funds to your wallet. Minimum KES 40. Payments processed securely via Paystack.
                </div>

                {/* Preset amounts */}
                <div style={{ display: 'flex', flexWrap: 'wrap', gap: 10, marginBottom: 16 }}>
                    {PRESET_AMOUNTS.map(amt => (
                        <button
                            key={amt}
                            onClick={() => setAmount(amt)}
                            style={{
                                background: amount === amt ? 'rgba(0,255,0,0.18)' : 'rgba(0,255,0,0.05)',
                                border: `1px solid ${amount === amt ? neon : 'rgba(0,255,0,0.2)'}`,
                                color: neon, fontFamily: mono, fontSize: '0.8rem', fontWeight: 700,
                                padding: '8px 18px', borderRadius: 6, cursor: 'pointer',
                                boxShadow: amount === amt ? `0 0 12px rgba(0,255,0,0.2)` : 'none',
                                transition: 'all 0.15s',
                            }}
                        >
                            KES {amt.toLocaleString()}
                        </button>
                    ))}
                </div>

                {/* Custom amount */}
                <div style={{ display: 'flex', gap: 10, alignItems: 'center', maxWidth: 360, marginBottom: 18 }}>
                    <input
                        type="number"
                        min={40}
                        value={amount === '' ? '' : amount}
                        onChange={e => setAmount(e.target.value === '' ? '' : Number(e.target.value))}
                        placeholder="Custom amount (min. KES 40)"
                        style={{
                            flex: 1, background: 'rgba(0,0,0,0.4)', border: '1px solid rgba(0,255,0,0.2)',
                            color: '#fff', fontFamily: mono, fontSize: '0.78rem',
                            padding: '9px 14px', borderRadius: 6, outline: 'none',
                        }}
                    />
                </div>

                {/* Payment method */}
                <div style={{ fontSize: '0.65rem', color: 'rgba(255,255,255,0.4)', letterSpacing: '1.5px', textTransform: 'uppercase', marginBottom: 10 }}>
                    Payment Method
                </div>
                <div style={{ display: 'flex', gap: 10, marginBottom: 16 }}>
                    {(['card', 'mpesa', 'airtel'] as const).map(m => (
                        <button
                            key={m}
                            onClick={() => setMethod(m)}
                            style={{
                                flex: 1, padding: '10px 8px', borderRadius: 7, cursor: 'pointer',
                                border: `1.5px solid ${method === m ? neon : 'rgba(0,255,0,0.14)'}`,
                                background: method === m ? 'rgba(0,255,0,0.07)' : 'rgba(0,0,0,0.3)',
                                textAlign: 'center', transition: 'all 0.15s',
                            }}
                        >
                            <div style={{ fontSize: '1.2rem', marginBottom: 4 }}>
                                {m === 'card' ? '💳' : m === 'mpesa' ? '📱' : '📲'}
                            </div>
                            <div style={{ fontFamily: orb, fontSize: '0.58rem', color: 'rgba(255,255,255,0.75)', letterSpacing: '0.5px' }}>
                                {m === 'card' ? 'CARD' : m === 'mpesa' ? 'M-PESA' : 'AIRTEL'}
                            </div>
                            <div style={{ fontSize: '0.6rem', color: 'rgba(255,255,255,0.3)', marginTop: 1 }}>
                                {m === 'card' ? 'Visa / MC' : m === 'mpesa' ? 'STK Push' : 'Airtel Money'}
                            </div>
                        </button>
                    ))}
                </div>

                {/* Phone input */}
                {(method === 'mpesa' || method === 'airtel') && (
                    <div style={{ marginBottom: 16 }}>
                        <div style={{ fontSize: '0.65rem', color: 'rgba(255,255,255,0.4)', letterSpacing: '1.5px', textTransform: 'uppercase', marginBottom: 6 }}>
                            Phone Number
                        </div>
                        <input
                            type="tel"
                            value={phone}
                            onChange={e => setPhone(e.target.value)}
                            placeholder="e.g. 0712345678"
                            style={{
                                width: '100%', maxWidth: 360, background: 'rgba(0,0,0,0.4)',
                                border: '1px solid rgba(0,255,0,0.2)', color: '#fff',
                                fontFamily: mono, fontSize: '0.78rem',
                                padding: '9px 14px', borderRadius: 6, outline: 'none',
                            }}
                        />
                    </div>
                )}

                {/* Top-up button */}
                <button
                    onClick={handleTopup}
                    disabled={paying || !amount || Number(amount) < 40}
                    style={{
                        background: paying || !amount || Number(amount) < 40 ? 'rgba(0,255,0,0.08)' : neon,
                        border: `1px solid ${paying || !amount ? 'rgba(0,255,0,0.2)' : neon}`,
                        color: paying || !amount || Number(amount) < 40 ? 'rgba(0,255,0,0.4)' : '#000',
                        fontFamily: orb, fontSize: '0.7rem', fontWeight: 900, letterSpacing: '2px',
                        padding: '12px 32px', borderRadius: 6, cursor: paying || !amount || Number(amount) < 40 ? 'not-allowed' : 'pointer',
                        transition: 'all 0.15s',
                    }}
                >
                    {paying ? 'PROCESSING...' : `⚡ TOP UP${amount ? ' KES ' + Number(amount).toLocaleString() : ''}`}
                </button>

                <div style={{ fontSize: '0.63rem', color: 'rgba(255,255,255,0.25)', marginTop: 10 }}>
                    Funds added to wallet instantly upon payment confirmation
                </div>
            </Card>

            {/* Transaction history */}
            <SectionTitle>Transaction History</SectionTitle>
            <Card style={{ padding: 0, overflow: 'hidden' }}>
                {txns.length === 0 ? (
                    <div style={{ padding: 32, textAlign: 'center', fontSize: '0.75rem', color: 'rgba(255,255,255,0.25)' }}>
                        No transactions yet.
                    </div>
                ) : (
                    <div style={{ overflowX: 'auto', WebkitOverflowScrolling: 'touch' as any }}>
                    <table style={{ width: '100%', minWidth: 540, borderCollapse: 'collapse' }}>
                        <thead>
                            <tr>
                                {['DATE', 'DESCRIPTION', 'AMOUNT', 'BALANCE AFTER', 'STATUS'].map(h => (
                                    <th key={h} style={{
                                        textAlign: 'left', padding: '10px 16px',
                                        fontFamily: orb, fontSize: '0.6rem', letterSpacing: '1.5px',
                                        color: 'rgba(0,255,0,0.5)', borderBottom: '1px solid rgba(0,255,0,0.1)',
                                    }}>{h}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {txns.map(t => (
                                <tr key={t.id}>
                                    <td style={{ padding: '11px 16px', fontSize: '0.7rem', color: 'rgba(255,255,255,0.35)', borderBottom: '1px solid rgba(255,255,255,0.04)', whiteSpace: 'nowrap' }}>
                                        {fmtDate(t.created_at)}
                                    </td>
                                    <td style={{ padding: '11px 16px', fontSize: '0.72rem', color: 'rgba(255,255,255,0.65)', borderBottom: '1px solid rgba(255,255,255,0.04)' }}>
                                        {t.description || '—'}
                                    </td>
                                    <td style={{ padding: '11px 16px', fontSize: '0.75rem', fontWeight: 700, color: t.type === 'credit' ? neon : '#ff7070', borderBottom: '1px solid rgba(255,255,255,0.04)', whiteSpace: 'nowrap' }}>
                                        {t.type === 'credit' ? '+' : '-'}{currency} {fmt(t.amount)}
                                    </td>
                                    <td style={{ padding: '11px 16px', fontSize: '0.72rem', color: 'rgba(255,255,255,0.45)', borderBottom: '1px solid rgba(255,255,255,0.04)', whiteSpace: 'nowrap' }}>
                                        {currency} {fmt(t.balance_after)}
                                    </td>
                                    <td style={{ padding: '11px 16px', borderBottom: '1px solid rgba(255,255,255,0.04)' }}>
                                        <StatusBadge status={t.status} />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    </div>
                )}
            </Card>

        </div>
    );
};
