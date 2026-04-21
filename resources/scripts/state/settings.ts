import { action, Action } from 'easy-peasy';

export interface SiteSettings {
    name: string;
    locale: string;
    recaptcha: {
        enabled: boolean;
        siteKey: string;
    };
    accentColor?: string;
    customCss?: string;
    disabledTabs?: string[];
    consoleBg?: string;
    consoleCursor?: string;
    consoleGreen?: string;
    consoleRed?: string;
    consoleYellow?: string;
    consoleCyan?: string;
    consoleWhite?: string;
    btnStartBg?: string;
    btnStartText?: string;
    btnStopBg?: string;
    btnStopText?: string;
    btnRestartBg?: string;
    btnRestartText?: string;
    btnOrder?: string[];
    btnPosition?: string;
    gridEnable?: string;
    scanEnable?: string;
    repoClone?: {
        enabled: boolean;
        allowlist: string[];
    };
}

export interface SettingsStore {
    data?: SiteSettings;
    setSettings: Action<SettingsStore, SiteSettings>;
}

const settings: SettingsStore = {
    data: undefined,
    setSettings: action((state, payload) => {
        state.data = payload;
    }),
};

export default settings;
