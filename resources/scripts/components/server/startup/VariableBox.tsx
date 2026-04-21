import React, { memo, useState } from 'react';
import { ServerEggVariable } from '@/api/server/types';
import { usePermissions } from '@/plugins/usePermissions';
import InputSpinner from '@/components/elements/InputSpinner';
import Input from '@/components/elements/Input';
import Switch from '@/components/elements/Switch';
import { debounce } from 'debounce';
import updateStartupVariable from '@/api/server/updateStartupVariable';
import useFlash from '@/plugins/useFlash';
import FlashMessageRender from '@/components/FlashMessageRender';
import getServerStartup from '@/api/swr/getServerStartup';
import Select from '@/components/elements/Select';
import isEqual from 'react-fast-compare';
import { ServerContext } from '@/state/server';

interface Props {
    variable: ServerEggVariable;
}

const VariableBox = ({ variable }: Props) => {
    const FLASH_KEY = `server:startup:${variable.envVariable}`;

    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const [loading, setLoading] = useState(false);
    const [canEdit] = usePermissions(['startup.update']);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { mutate } = getServerStartup(uuid);

    const setVariableValue = debounce((value: string) => {
        setLoading(true);
        clearFlashes(FLASH_KEY);

        updateStartupVariable(uuid, variable.envVariable, value)
            .then(([response, invocation]) =>
                mutate(
                    (data) => ({
                        ...data,
                        invocation,
                        variables: (data.variables || []).map((v) =>
                            v.envVariable === response.envVariable ? response : v
                        ),
                    }),
                    false
                )
            )
            .catch((error) => {
                console.error(error);
                clearAndAddHttpError({ error, key: FLASH_KEY });
            })
            .then(() => setLoading(false));
    }, 500);

    const useSwitch = variable.rules.some(
        (v) => v === 'boolean' || v === 'in:0,1' || v === 'in:1,0' || v === 'in:true,false' || v === 'in:false,true'
    );
    const isStringSwitch = variable.rules.some((v) => v === 'string');
    const selectValues = variable.rules.find((v) => v.startsWith('in:'))?.split(',') || [];

    return (
        <div style={{
            background: 'rgba(0,10,0,0.85)',
            border: '1px solid rgba(0,255,0,0.2)',
            borderRadius: '6px',
            boxShadow: '0 0 14px rgba(0,255,0,0.04), inset 0 0 20px rgba(0,0,0,0.3)',
            overflow: 'hidden',
        }}>
            {/* Header */}
            <div style={{
                background: 'rgba(0,255,0,0.04)',
                borderBottom: '1px solid rgba(0,255,0,0.12)',
                padding: '9px 14px',
                display: 'flex',
                alignItems: 'center',
                gap: '8px',
            }}>
                {!variable.isEditable && (
                    <span style={{
                        background: 'rgba(0,255,0,0.08)',
                        border: '1px solid rgba(0,255,0,0.2)',
                        color: 'rgba(0,255,0,0.5)',
                        fontFamily: "'JetBrains Mono', monospace",
                        fontSize: '0.6rem',
                        padding: '2px 7px',
                        borderRadius: '20px',
                        letterSpacing: '1px',
                        textTransform: 'uppercase' as const,
                    }}>
                        Read Only
                    </span>
                )}
                <p style={{
                    fontFamily: "'Orbitron', monospace",
                    fontSize: '0.68rem',
                    letterSpacing: '1.5px',
                    color: 'rgba(0,255,0,0.75)',
                    textTransform: 'uppercase' as const,
                    margin: 0,
                }}>
                    {variable.name}
                </p>
            </div>

            {/* Body */}
            <div style={{ padding: '12px 14px' }}>
                <FlashMessageRender byKey={FLASH_KEY} className='mb-2 md:mb-4' />
                <InputSpinner visible={loading}>
                    {useSwitch ? (
                        <Switch
                            readOnly={!canEdit || !variable.isEditable}
                            name={variable.envVariable}
                            defaultChecked={
                                isStringSwitch ? variable.serverValue === 'true' : variable.serverValue === '1'
                            }
                            onChange={() => {
                                if (canEdit && variable.isEditable) {
                                    if (isStringSwitch) {
                                        setVariableValue(variable.serverValue === 'true' ? 'false' : 'true');
                                    } else {
                                        setVariableValue(variable.serverValue === '1' ? '0' : '1');
                                    }
                                }
                            }}
                        />
                    ) : selectValues.length > 0 ? (
                        <Select
                            onChange={(e) => setVariableValue(e.target.value)}
                            name={variable.envVariable}
                            defaultValue={variable.serverValue ?? variable.defaultValue}
                            disabled={!canEdit || !variable.isEditable}
                        >
                            {selectValues.map((selectValue) => (
                                <option
                                    key={selectValue.replace('in:', '')}
                                    value={selectValue.replace('in:', '')}
                                >
                                    {selectValue.replace('in:', '')}
                                </option>
                            ))}
                        </Select>
                    ) : (
                        <Input
                            onKeyUp={(e) => {
                                if (canEdit && variable.isEditable) {
                                    setVariableValue(e.currentTarget.value);
                                }
                            }}
                            readOnly={!canEdit || !variable.isEditable}
                            name={variable.envVariable}
                            defaultValue={variable.serverValue ?? ''}
                            placeholder={variable.defaultValue}
                        />
                    )}
                </InputSpinner>

                <p style={{
                    marginTop: '8px',
                    fontFamily: "'JetBrains Mono', monospace",
                    fontSize: '0.7rem',
                    color: 'rgba(0,255,0,0.38)',
                    lineHeight: '1.5',
                }}>
                    {variable.description}
                </p>
            </div>
        </div>
    );
};

export default memo(VariableBox, isEqual);
