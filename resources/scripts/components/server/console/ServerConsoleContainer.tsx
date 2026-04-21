import React, { memo } from 'react';
import { ServerContext } from '@/state/server';
import Can from '@/components/elements/Can';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import isEqual from 'react-fast-compare';
import Spinner from '@/components/elements/Spinner';
import Features from '@feature/Features';
import Console from '@/components/server/console/Console';
import StatGraphs from '@/components/server/console/StatGraphs';
import PowerButtons from '@/components/server/console/PowerButtons';
import ServerDetailsBlock from '@/components/server/console/ServerDetailsBlock';
import { Alert } from '@/components/elements/alert';
import { useStoreState } from 'easy-peasy';

export type PowerAction = 'start' | 'stop' | 'restart' | 'kill';

/** Wraps PowerButtons with the right flex direction / spacing for each position */
const PowerButtonsWrapped = ({ position }: { position: string }) => {
    const isLeft   = position === 'left';
    const isRight  = position === 'right';
    const isBottom = position === 'bottom';

    const cls = isLeft
        ? 'flex flex-col space-y-2 justify-start'
        : isRight
        ? 'flex space-x-2 sm:justify-end'
        : isBottom
        ? 'flex space-x-2 mt-4'
        : 'flex space-x-2 mb-4'; // top

    return (
        <Can action={['control.start', 'control.stop', 'control.restart']} matchAny>
            <PowerButtons className={cls} />
        </Can>
    );
};

const ServerConsoleContainer = () => {
    const name                   = ServerContext.useStoreState((state) => state.server.data!.name);
    const description            = ServerContext.useStoreState((state) => state.server.data!.description);
    const isInstalling           = ServerContext.useStoreState((state) => state.server.isInstalling);
    const isTransferring         = ServerContext.useStoreState((state) => state.server.data!.isTransferring);
    const eggFeatures            = ServerContext.useStoreState((state) => state.server.data!.eggFeatures, isEqual);
    const isNodeUnderMaintenance = ServerContext.useStoreState((state) => state.server.data!.isNodeUnderMaintenance);
    const wxnHealth              = ServerContext.useStoreState((state) => state.server.data!.wxnHealth, isEqual);
    const btnPosition            = useStoreState((state: any) => state.settings.data?.btnPosition as string | undefined) ?? 'right';

    const isLeft  = btnPosition === 'left';
    const isRight = btnPosition === 'right';

    return (
        <ServerContentBlock title={'Console'}>
            {(isNodeUnderMaintenance || isInstalling || isTransferring) && (
                <Alert type={'warning'} className={'mb-4'}>
                    {isNodeUnderMaintenance
                        ? 'The node of this server is currently under maintenance and all actions are unavailable.'
                        : isInstalling
                        ? 'This server is currently running its installation process and most actions are unavailable.'
                        : 'This server is currently being transferred to another node and all actions are unavailable.'}
                </Alert>
            )}

            {/* wolfXcore: surface the bot-stability circuit breaker state to the server owner. */}
            {wxnHealth?.paused && (
                <Alert type={'danger'} className={'mb-4'}>
                    <strong>This bot is paused for stability.</strong> It crashed {wxnHealth.crash_count}{' '}
                    times in a short window, so we stopped it to protect your session files.
                    {' '}It will stay paused until you press <strong>Start</strong> below
                    (or an admin clears it). No automatic resume.
                    {wxnHealth.last_crash_reason && (
                        <div className={'mt-1 text-xs opacity-75'}>Last reason: {wxnHealth.last_crash_reason}</div>
                    )}
                </Alert>
            )}
            {!wxnHealth?.paused && wxnHealth?.needs_attention && (
                <Alert type={'warning'} className={'mb-4'}>
                    <strong>Heads up:</strong> this bot has crashed {wxnHealth.crash_count} times recently.
                    Check your environment variables and session pairing.
                </Alert>
            )}

            {/* ── TOP: full-width strip above the header ── */}
            {btnPosition === 'top' && <PowerButtonsWrapped position='top' />}

            {/* ── Header row: always server name, right buttons only when position=right ── */}
            <div className={'grid grid-cols-4 gap-4 mb-4'}>
                <div className={`hidden sm:block pr-4 ${isRight ? 'sm:col-span-2 lg:col-span-3' : 'col-span-4'}`}>
                    <h1 className={'font-header font-medium text-2xl text-gray-50 leading-relaxed line-clamp-1'}>
                        {name}
                    </h1>
                    <p className={'text-sm line-clamp-2'}>{description}</p>
                </div>
                {isRight && (
                    <div className={'col-span-4 sm:col-span-2 lg:col-span-1 self-end'}>
                        <PowerButtonsWrapped position='right' />
                    </div>
                )}
            </div>

            {/* ── Console + details row ── */}
            <div className={'grid grid-cols-4 gap-2 sm:gap-4 mb-4'}>
                {/* LEFT: vertical button column beside the console */}
                {isLeft && (
                    <div className={'hidden lg:flex flex-col col-span-1 pt-1'}>
                        <PowerButtonsWrapped position='left' />
                    </div>
                )}

                {/* Console terminal */}
                <div className={`flex ${isLeft ? 'col-span-4 lg:col-span-2' : 'col-span-4 lg:col-span-3'}`}>
                    <Spinner.Suspense>
                        <Console />
                    </Spinner.Suspense>
                </div>

                {/* Mobile: show buttons below console when left-positioned */}
                {isLeft && (
                    <div className={'flex lg:hidden col-span-4 mt-2'}>
                        <PowerButtonsWrapped position='top' />
                    </div>
                )}

                <ServerDetailsBlock className={'col-span-4 lg:col-span-1 order-last lg:order-none'} />
            </div>

            {/* ── Stats ── */}
            <div className={'grid grid-cols-1 md:grid-cols-3 gap-2 sm:gap-4'}>
                <Spinner.Suspense>
                    <StatGraphs />
                </Spinner.Suspense>
            </div>

            {/* ── BOTTOM: full-width strip below stats ── */}
            {btnPosition === 'bottom' && <PowerButtonsWrapped position='bottom' />}

            <Features enabled={eggFeatures} />
        </ServerContentBlock>
    );
};

export default memo(ServerConsoleContainer, isEqual);
