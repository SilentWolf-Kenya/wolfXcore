import styled from 'styled-components/macro';

const SubNavigation = styled.div`
    width: 100%;
    background: #000000;
    border-bottom: 1px solid rgba(0,255,0,0.15);
    overflow-x: auto;
    box-shadow: 0 2px 12px rgba(0,0,0,0.6);

    & > div {
        display: flex;
        align-items: center;
        font-size: 0.8rem;
        margin: 0 auto;
        padding: 0 0.5rem;
        max-width: 1200px;
        font-family: 'JetBrains Mono', 'Courier New', monospace;
        letter-spacing: 0.05em;

        & > a,
        & > div {
            display: inline-block;
            padding: 0.75rem 1rem;
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            white-space: nowrap;
            transition: color 0.15s, box-shadow 0.15s;
            text-transform: uppercase;

            &:not(:first-of-type) {
                margin-left: 0.25rem;
            }

            &:hover {
                color: #ffffff;
            }

            &:active,
            &.active {
                color: #00ff00;
                box-shadow: inset 0 -2px #00ff00;
                text-shadow: 0 0 6px rgba(0,255,0,0.4);
            }
        }
    }
`;

export default SubNavigation;
