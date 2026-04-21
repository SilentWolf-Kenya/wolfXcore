import styled, { css } from 'styled-components/macro';
import tw from 'twin.macro';

interface Props {
    hideDropdownArrow?: boolean;
}

const Select = styled.select<Props>`
    ${tw`shadow-none block p-3 pr-8 rounded w-full text-sm transition-colors duration-150 ease-linear`};

    &,
    &:hover:not(:disabled),
    &:focus {
        ${tw`outline-none`};
    }

    -webkit-appearance: none;
    -moz-appearance: none;
    background-size: 1rem;
    background-repeat: no-repeat;
    background-position-x: calc(100% - 0.75rem);
    background-position-y: center;
    color-scheme: dark;

    &::-ms-expand {
        display: none;
    }

    ${(props) =>
        !props.hideDropdownArrow &&
        css`
            background-color: rgba(0, 10, 0, 0.9) !important;
            border: 1px solid rgba(0, 255, 0, 0.3) !important;
            color: rgba(180, 255, 180, 0.9) !important;
            font-family: 'JetBrains Mono', monospace !important;
            font-size: 0.82rem !important;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='%2300ff00' d='M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z'/%3e%3c/svg%3e");

            &:hover:not(:disabled),
            &:focus {
                border-color: rgba(0, 255, 0, 0.6) !important;
                box-shadow: 0 0 0 2px rgba(0, 255, 0, 0.1);
            }

            &:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }

            option {
                background-color: #020d02 !important;
                color: rgba(180, 255, 180, 0.9) !important;
                font-family: 'JetBrains Mono', monospace !important;
                padding: 8px 12px !important;
            }

            option:hover,
            option:focus,
            option:checked {
                background-color: rgba(0, 255, 0, 0.15) !important;
                color: #00ff00 !important;
            }
        `};
`;

export default Select;
