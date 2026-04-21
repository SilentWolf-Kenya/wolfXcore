import React, { useEffect, useMemo, useRef, useState } from 'react';
import { ITerminalOptions, Terminal } from 'xterm';
import { FitAddon } from 'xterm-addon-fit';
import { SearchAddon } from 'xterm-addon-search';
import { SearchBarAddon } from 'xterm-addon-search-bar';
import { WebLinksAddon } from 'xterm-addon-web-links';
import { Unicode11Addon } from 'xterm-addon-unicode11';
import { ScrollDownHelperAddon } from '@/plugins/XtermScrollDownHelperAddon';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { ServerContext } from '@/state/server';
import { usePermissions } from '@/plugins/usePermissions';
import useEventListener from '@/plugins/useEventListener';
import { debounce } from 'debounce';
import { usePersistedState } from '@/plugins/usePersistedState';
import { SocketEvent, SocketRequest } from '@/components/server/events';
import classNames from 'classnames';
import { ChevronDoubleRightIcon } from '@heroicons/react/solid';
import { useStoreState } from 'easy-peasy';

import 'xterm/css/xterm.css';
import styles from './style.module.css';

const buildTerminalTheme = (cfg: Record<string, string | undefined>) => ({
    background:     cfg.consoleBg     || '#020702',
    cursor:         cfg.consoleCursor || '#00e676',
    cursorAccent:   cfg.consoleBg     || '#020702',
    black:          cfg.consoleBg     || '#020702',
    red:            cfg.consoleRed    || '#ff5370',
    green:          cfg.consoleGreen  || '#00e676',
    yellow:         cfg.consoleYellow || '#facc15',
    blue:           '#82aaff',
    magenta:        '#c792ea',
    cyan:           cfg.consoleCyan   || '#89ddff',
    white:          cfg.consoleWhite  || '#d0d0d0',
    brightBlack:    'rgba(255,255,255,0.2)',
    brightRed:      cfg.consoleRed    || '#ff6b6b',
    brightGreen:    cfg.consoleGreen  || '#b9f6ca',
    brightYellow:   cfg.consoleYellow || '#ffe082',
    brightBlue:     '#82aaff',
    brightMagenta:  '#c792ea',
    brightCyan:     cfg.consoleCyan   || '#89ddff',
    brightWhite:    '#ffffff',
    selection:      (cfg.consoleGreen || '#00e676') + '40',
});

const PRELUDE_GREEN  = '\u001b[1m\u001b[32mwolfXcore\u001b[0m\u001b[32m~ \u001b[0m';
const PRELUDE_RED    = '\u001b[1m\u001b[31mwolfXcore\u001b[0m\u001b[31m~ \u001b[0m';
const PRELUDE_YELLOW = '\u001b[1m\u001b[33mwolfXcore\u001b[0m\u001b[33m~ \u001b[0m';

const sanitizeLine = (line: string): string =>
    line
        .replace(/\[wolfXcore Daemon\]/gi, '\u001b[1m\u001b[32m[wolfXcore]\u001b[0m')
        .replace(/\[wolfXcore\]/gi,        '\u001b[1m\u001b[32m[wolfXcore]\u001b[0m')
        .replace(/container@wolfxcore~/gi, '\u001b[32mcontainer@wolfxcore~\u001b[0m')
        .replace(/wolfxcore/gi,            'wolfXcore');

export default () => {
    const ref = useRef<HTMLDivElement>(null);
    const cfg = useStoreState((state) => state.settings.data ?? {});

    const terminal = useMemo(
        () => new Terminal({
            disableStdin: true,
            cursorStyle: 'underline',
            allowTransparency: true,
            fontSize: 12,
            fontFamily: '"Courier New", Courier, monospace',
            rows: 30,
            theme: buildTerminalTheme(cfg as Record<string, string | undefined>),
        } as ITerminalOptions),
        []
    );

    const fitAddon            = new FitAddon();
    const searchAddon         = new SearchAddon();
    const searchBar           = new SearchBarAddon({ searchAddon });
    const webLinksAddon       = new WebLinksAddon();
    const unicode11Addon      = new Unicode11Addon();
    const scrollDownHelperAddon = new ScrollDownHelperAddon();

    const { connected, instance } = ServerContext.useStoreState((state) => state.socket);
    const [canSendCommands] = usePermissions(['control.console']);
    const serverId        = ServerContext.useStoreState((state) => state.server.data!.id);
    const isTransferring  = ServerContext.useStoreState((state) => state.server.data!.isTransferring);
    const [history, setHistory] = usePersistedState<string[]>(`${serverId}:command_history`, []);
    const [historyIndex, setHistoryIndex] = useState(-1);
    const zIndex = `.xterm-search-bar__addon { z-index: 10; }`;

    const handleConsoleOutput = (line: string, prelude = false) =>
        terminal.writeln(
            (prelude ? PRELUDE_GREEN : '') +
            sanitizeLine(line.replace(/(?:\r\n|\r|\n)$/im, '')) +
            '\u001b[0m'
        );

    const handleTransferStatus = (status: string) => {
        if (status === 'failure') terminal.writeln(PRELUDE_RED + 'Transfer has failed.\u001b[0m');
    };

    const handleDaemonErrorOutput = (line: string) =>
        terminal.writeln(PRELUDE_RED + '\u001b[31m' + sanitizeLine(line.replace(/(?:\r\n|\r|\n)$/im, '')) + '\u001b[0m');

    const handlePowerChangeEvent = (state: string) =>
        terminal.writeln(PRELUDE_YELLOW + '\u001b[33mServer marked as ' + state + '...\u001b[0m');

    const handleCommandKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'ArrowUp') {
            const newIndex = Math.min(historyIndex + 1, history!.length - 1);
            setHistoryIndex(newIndex);
            e.currentTarget.value = history![newIndex] || '';
            e.preventDefault();
        }
        if (e.key === 'ArrowDown') {
            const newIndex = Math.max(historyIndex - 1, -1);
            setHistoryIndex(newIndex);
            e.currentTarget.value = history![newIndex] || '';
        }
        const command = e.currentTarget.value;
        if (e.key === 'Enter' && command.length > 0) {
            setHistory((prevHistory) => [command, ...prevHistory!].slice(0, 32));
            setHistoryIndex(-1);
            instance && instance.send('send command', command);
            e.currentTarget.value = '';
        }
    };

    useEffect(() => {
        if (connected && ref.current && !terminal.element) {
            terminal.loadAddon(fitAddon);
            terminal.loadAddon(searchAddon);
            terminal.loadAddon(searchBar);
            terminal.loadAddon(webLinksAddon);
            terminal.loadAddon(unicode11Addon);
            terminal.loadAddon(scrollDownHelperAddon);
            terminal.open(ref.current);
            terminal.unicode.activeVersion = '11';
            fitAddon.fit();
            searchBar.addNewStyle(zIndex);
            terminal.attachCustomKeyEventHandler((e: KeyboardEvent) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'c') { document.execCommand('copy'); return false; }
                else if ((e.ctrlKey || e.metaKey) && e.key === 'f') { e.preventDefault(); searchBar.show(); return false; }
                else if (e.key === 'Escape') { searchBar.hidden(); }
                return true;
            });
        }
    }, [terminal, connected]);

    useEventListener('resize', debounce(() => {
        if (terminal.element) fitAddon.fit();
    }, 100));

    useEffect(() => {
        const listeners: Record<string, (s: string) => void> = {
            [SocketEvent.STATUS]:          handlePowerChangeEvent,
            [SocketEvent.CONSOLE_OUTPUT]:  handleConsoleOutput,
            [SocketEvent.INSTALL_OUTPUT]:  handleConsoleOutput,
            [SocketEvent.TRANSFER_LOGS]:   handleConsoleOutput,
            [SocketEvent.TRANSFER_STATUS]: handleTransferStatus,
            [SocketEvent.DAEMON_MESSAGE]:  (line) => handleConsoleOutput(line, true),
            [SocketEvent.DAEMON_ERROR]:    handleDaemonErrorOutput,
        };
        if (connected && instance) {
            if (!isTransferring) terminal.clear();
            Object.keys(listeners).forEach((key) => instance.addListener(key, listeners[key]));
            instance.send(SocketRequest.SEND_LOGS);
        }
        return () => {
            if (instance) Object.keys(listeners).forEach((key) => instance.removeListener(key, listeners[key]));
        };
    }, [connected, instance]);

    return (
        <div className={classNames(styles.terminal, 'relative')}>
            <SpinnerOverlay visible={!connected} size={'large'} />
            <div className={classNames(styles.container, styles.overflows_container, { 'rounded-b': !canSendCommands })}>
                <div className={'h-full'}>
                    <div id={styles.terminal} ref={ref} />
                </div>
            </div>
            {canSendCommands && (
                <div className={classNames('relative', styles.overflows_container)}>
                    <input
                        className={classNames('peer', styles.command_input)}
                        type={'text'}
                        placeholder={'Type a command...'}
                        aria-label={'Console command input.'}
                        disabled={!instance || !connected}
                        onKeyDown={handleCommandKeyDown}
                        autoCorrect={'off'}
                        autoCapitalize={'none'}
                    />
                    <div className={classNames('peer-focus:animate-pulse', styles.command_icon)}>
                        <ChevronDoubleRightIcon className={'w-4 h-4'} />
                    </div>
                </div>
            )}
        </div>
    );
};
