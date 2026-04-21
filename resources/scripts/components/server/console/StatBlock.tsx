import React from 'react';
import Icon from '@/components/elements/Icon';
import { IconDefinition } from '@fortawesome/free-solid-svg-icons';
import classNames from 'classnames';
import styles from './style.module.css';
import useFitText from 'use-fit-text';
import CopyOnClick from '@/components/elements/CopyOnClick';

interface StatBlockProps {
    title: string;
    copyOnClick?: string;
    color?: string | undefined;
    icon: IconDefinition;
    children: React.ReactNode;
    className?: string;
}

export default ({ title, copyOnClick, icon, color, className, children }: StatBlockProps) => {
    const { fontSize, ref } = useFitText({ minFontSize: 8, maxFontSize: 500 });

    return (
        <CopyOnClick text={copyOnClick}>
            <div className={classNames(styles.stat_block, className)}>
                <div
                    className={classNames(styles.status_bar)}
                    style={color ? { background: undefined } : undefined}
                />
                <div
                    className={classNames(styles.icon)}
                    style={
                        color === 'bg-red-500'
                            ? { background: 'rgba(220,38,38,0.15)', borderColor: 'rgba(220,38,38,0.4)' }
                            : color === 'bg-yellow-500'
                            ? { background: 'rgba(234,179,8,0.15)', borderColor: 'rgba(234,179,8,0.4)' }
                            : undefined
                    }
                >
                    <Icon
                        icon={icon}
                        className={'text-gray-100'}
                        style={
                            color === 'bg-red-500'
                                ? { color: '#f87171' }
                                : color === 'bg-yellow-500'
                                ? { color: '#facc15' }
                                : { color: '#00e676' }
                        }
                    />
                </div>
                <div className={'flex flex-col justify-center overflow-hidden w-full'}>
                    <p
                        className={'font-header font-medium leading-tight text-xs md:text-sm'}
                        style={{ color: 'rgba(0, 230, 118, 0.7)', letterSpacing: '0.04em', textTransform: 'uppercase', fontSize: '0.68rem' }}
                    >
                        {title}
                    </p>
                    <div
                        ref={ref}
                        className={'h-[1.75rem] w-full font-semibold truncate'}
                        style={{ fontSize, color: '#ffffff', fontFamily: "'Courier New', monospace" }}
                    >
                        {children}
                    </div>
                </div>
            </div>
        </CopyOnClick>
    );
};
