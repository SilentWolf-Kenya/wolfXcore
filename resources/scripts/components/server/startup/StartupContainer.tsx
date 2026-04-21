import React, { useCallback, useEffect, useState } from 'react';
import tw from 'twin.macro';
import VariableBox from '@/components/server/startup/VariableBox';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import getServerStartup from '@/api/swr/getServerStartup';
import Spinner from '@/components/elements/Spinner';
import { ServerError } from '@/components/elements/ScreenBlock';
import { httpErrorToHuman } from '@/api/http';
import { ServerContext } from '@/state/server';
import { useDeepCompareEffect } from '@/plugins/useDeepCompareEffect';
import Select from '@/components/elements/Select';
import isEqual from 'react-fast-compare';
import Input from '@/components/elements/Input';
import setSelectedDockerImage from '@/api/server/setSelectedDockerImage';
import InputSpinner from '@/components/elements/InputSpinner';
import useFlash from '@/plugins/useFlash';

const boxStyle: React.CSSProperties = {
    background: 'rgba(0,10,0,0.85)',
    border: '1px solid rgba(0,255,0,0.25)',
    borderRadius: '6px',
    boxShadow: '0 0 18px rgba(0,255,0,0.05), inset 0 0 30px rgba(0,0,0,0.4)',
};

const boxHeaderStyle: React.CSSProperties = {
    background: 'rgba(0,255,0,0.04)',
    borderBottom: '1px solid rgba(0,255,0,0.15)',
    padding: '10px 14px',
    borderRadius: '6px 6px 0 0',
    fontFamily: "'Orbitron', monospace",
    fontSize: '0.72rem',
    letterSpacing: '2px',
    color: 'rgba(0,255,0,0.7)',
    textTransform: 'uppercase' as const,
};

const boxBodyStyle: React.CSSProperties = {
    padding: '14px',
};

const StartupContainer = () => {
    const [loading, setLoading] = useState(false);
    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const variables = ServerContext.useStoreState(
        ({ server }) => ({
            variables: server.data!.variables,
            invocation: server.data!.invocation,
            dockerImage: server.data!.dockerImage,
        }),
        isEqual
    );

    const { data, error, isValidating, mutate } = getServerStartup(uuid, {
        ...variables,
        dockerImages: { [variables.dockerImage]: variables.dockerImage },
    });

    const setServerFromState = ServerContext.useStoreActions((actions) => actions.server.setServerFromState);
    const isCustomImage =
        data &&
        !Object.values(data.dockerImages)
            .map((v) => v.toLowerCase())
            .includes(variables.dockerImage.toLowerCase());

    useEffect(() => {
        mutate();
    }, []);

    useDeepCompareEffect(() => {
        if (!data) return;
        setServerFromState((s) => ({
            ...s,
            invocation: data.invocation,
            variables: data.variables,
        }));
    }, [data]);

    const updateSelectedDockerImage = useCallback(
        (v: React.ChangeEvent<HTMLSelectElement>) => {
            setLoading(true);
            clearFlashes('startup:image');
            const image = v.currentTarget.value;
            setSelectedDockerImage(uuid, image)
                .then(() => setServerFromState((s) => ({ ...s, dockerImage: image })))
                .catch((error) => {
                    console.error(error);
                    clearAndAddHttpError({ key: 'startup:image', error });
                })
                .then(() => setLoading(false));
        },
        [uuid]
    );

    return !data ? (
        !error || (error && isValidating) ? (
            <Spinner centered size={Spinner.Size.LARGE} />
        ) : (
            <ServerError title={'Oops!'} message={httpErrorToHuman(error)} onRetry={() => mutate()} />
        )
    ) : (
        <ServerContentBlock title={'Startup Settings'} showFlashKey={'startup:image'}>
            <div css={tw`md:flex gap-6`}>
                {/* Startup Command */}
                <div css={tw`flex-1`} style={boxStyle}>
                    <div style={boxHeaderStyle}>Startup Command</div>
                    <div style={boxBodyStyle}>
                        <p style={{
                            fontFamily: "'JetBrains Mono', monospace",
                            fontSize: '0.82rem',
                            lineHeight: '1.7',
                            color: '#00ff00',
                            background: 'rgba(0,255,0,0.03)',
                            border: '1px solid rgba(0,255,0,0.12)',
                            borderRadius: '4px',
                            padding: '12px 14px',
                            wordBreak: 'break-all',
                            textShadow: '0 0 8px rgba(0,255,0,0.3)',
                        }}>
                            {data.invocation}
                        </p>
                    </div>
                </div>

                {/* Docker Image */}
                <div css={tw`lg:flex-none lg:w-1/3 mt-6 md:mt-0`} style={boxStyle}>
                    <div style={boxHeaderStyle}>Docker Image</div>
                    <div style={boxBodyStyle}>
                        {Object.keys(data.dockerImages).length > 1 && !isCustomImage ? (
                            <>
                                <InputSpinner visible={loading}>
                                    <Select
                                        disabled={Object.keys(data.dockerImages).length < 2}
                                        onChange={updateSelectedDockerImage}
                                        defaultValue={variables.dockerImage}
                                    >
                                        {Object.keys(data.dockerImages).map((key) => (
                                            <option key={data.dockerImages[key]} value={data.dockerImages[key]}>
                                                {key}
                                            </option>
                                        ))}
                                    </Select>
                                </InputSpinner>
                                <p style={{ fontFamily: "'JetBrains Mono', monospace", fontSize: '0.7rem', color: 'rgba(0,255,0,0.4)', marginTop: '8px', lineHeight: '1.5' }}>
                                    Advanced: select the Docker image for this server instance.
                                </p>
                            </>
                        ) : (
                            <>
                                <Input disabled readOnly value={variables.dockerImage} />
                                {isCustomImage && (
                                    <p style={{ fontFamily: "'JetBrains Mono', monospace", fontSize: '0.7rem', color: 'rgba(0,255,0,0.4)', marginTop: '8px', lineHeight: '1.5' }}>
                                        Docker image was set by an administrator and cannot be changed here.
                                    </p>
                                )}
                            </>
                        )}
                    </div>
                </div>
            </div>

            {/* Variables heading */}
            <div css={tw`mt-10 mb-4`} style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                <h3 style={{
                    fontFamily: "'Orbitron', monospace",
                    fontSize: '1rem',
                    letterSpacing: '3px',
                    color: '#00ff00',
                    textTransform: 'uppercase',
                    textShadow: '0 0 12px rgba(0,255,0,0.4)',
                    margin: 0,
                }}>Variables</h3>
                <div style={{ flex: 1, height: '1px', background: 'linear-gradient(to right, rgba(0,255,0,0.3), transparent)' }} />
            </div>

            <div css={tw`grid gap-6 md:grid-cols-2`}>
                {data.variables.map((variable) => (
                    <VariableBox key={variable.envVariable} variable={variable} />
                ))}
            </div>
        </ServerContentBlock>
    );
};

export default StartupContainer;
