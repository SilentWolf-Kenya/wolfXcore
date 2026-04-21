import React, { forwardRef } from 'react';
import { Form } from 'formik';
import FlashMessageRender from '@/components/FlashMessageRender';

type Props = React.DetailedHTMLProps<React.FormHTMLAttributes<HTMLFormElement>, HTMLFormElement> & {
    title?: string;
};

const neon = '#00ff00';
const fontDisplay = "'Orbitron', sans-serif";
const fontMono = "'JetBrains Mono', 'Courier New', monospace";

export default forwardRef<HTMLFormElement, Props>(({ title, ...props }, ref) => (
    <>
        <style>{`
            .wxn-auth-wrapper {
                position: relative;
                z-index: 2;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 16px;
                font-family: ${fontMono};
                width: 100%;
            }
            .wxn-brand {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 28px;
                user-select: none;
                text-decoration: none !important;
            }
            .wxn-brand-icon {
                width: 44px;
                height: 44px;
                border: 1px solid rgba(0,255,0,0.3);
                border-radius: 10px;
                background: rgba(0,255,0,0.04);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
                flex-shrink: 0;
                animation: wxnIconGlow 3s ease-in-out infinite;
            }
            @keyframes wxnIconGlow {
                0%,100% { box-shadow: 0 0 10px rgba(0,255,0,0.2); }
                50%      { box-shadow: 0 0 22px rgba(0,255,0,0.4); }
            }
            .wxn-brand-name {
                font-size: 1.25rem;
                font-weight: 700;
                letter-spacing: 0.06em;
                color: #ffffff;
                font-family: ${fontDisplay};
            }
            .wxn-brand-w  { color: ${neon}; }
            .wxn-brand-x  { color: #9ca3af; }
            .wxn-brand-c  { color: #ffffff; }
            .wxn-brand-sub {
                font-size: 0.62rem;
                color: #4b5563;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                margin-top: 2px;
                font-family: ${fontMono};
            }
            .wxn-card {
                width: 100%;
                max-width: 420px;
                background: rgba(0,0,0,0.5);
                border: 1px solid rgba(0,255,0,0.18);
                border-radius: 0.75rem;
                padding: 32px 28px 24px;
                backdrop-filter: blur(14px);
                -webkit-backdrop-filter: blur(14px);
                box-shadow: 0 8px 40px rgba(0,0,0,0.7), 0 0 30px rgba(0,255,0,0.04);
                position: relative;
                transition: border-color 0.25s;
            }
            .wxn-card:hover {
                border-color: rgba(0,255,0,0.32);
            }
            .wxn-card::before {
                content: '';
                position: absolute;
                top: 0; left: 12%; right: 12%;
                height: 2px;
                background: linear-gradient(90deg, transparent, ${neon}, transparent);
                opacity: 0.5;
                border-radius: 1px;
            }
            .wxn-card-title {
                font-size: 0.85rem;
                font-weight: 700;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                color: #ffffff;
                text-align: center;
                margin-bottom: 24px;
                font-family: ${fontDisplay};
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .wxn-footer {
                margin-top: 22px;
                font-size: 0.62rem;
                letter-spacing: 0.08em;
                color: #4b5563;
                text-align: center;
                font-family: ${fontMono};
            }
            .wxn-footer a {
                color: #4b5563 !important;
                transition: color 0.2s;
            }
            .wxn-footer a:hover { color: rgba(255,255,255,0.6) !important; }
            .wxn-form {
                display: flex;
                flex-direction: column;
            }
            /* Make auth nav links white and visible */
            .wxn-auth-wrapper a:not(.wxn-footer a) {
                color: rgba(255,255,255,0.75) !important;
                font-family: ${fontMono};
                font-size: 0.75rem;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                text-decoration: none !important;
                transition: color 0.2s;
            }
            .wxn-auth-wrapper a:not(.wxn-footer a):hover {
                color: ${neon} !important;
            }
            @media (max-width: 480px) {
                .wxn-card { padding: 24px 18px 20px; border-radius: 10px; }
                .wxn-brand-name { font-size: 1.1rem; }
            }
        `}</style>

        <div className={'wxn-auth-wrapper'}>
            <a href={'/'} className={'wxn-brand'}>
                <div className={'wxn-brand-icon'}>⚡</div>
                <div>
                    <div className={'wxn-brand-name'}>
                        <span className={'wxn-brand-w'}>wolf</span>
                        <span className={'wxn-brand-x'}>X</span>
                        <span className={'wxn-brand-c'}>core</span>
                    </div>
                    <div className={'wxn-brand-sub'}>Game Server Panel</div>
                </div>
            </a>

            <div className={'wxn-card'}>
                {title && (
                    <div className={'wxn-card-title'}>
                        <span className={'wxn-pulse'} />
                        {title}
                    </div>
                )}
                <FlashMessageRender css={{ marginBottom: '16px' }} />
                <Form {...props} ref={ref} className={'wxn-form'}>
                    {props.children}
                </Form>
            </div>

            <p className={'wxn-footer'}>
                &copy; 2025 – {new Date().getFullYear()} wolfXcore &nbsp;·&nbsp; Powered by WOLF TECH
            </p>
        </div>
    </>
));
