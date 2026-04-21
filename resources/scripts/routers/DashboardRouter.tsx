import React from 'react';
import { NavLink, Redirect, Route, Switch } from 'react-router-dom';
import SideBar from '@/components/SideBar';
import DashboardContainer from '@/components/dashboard/DashboardContainer';
import ServersContainer from '@/components/dashboard/ServersContainer';
import WalletContainer from '@/components/dashboard/WalletContainer';
import { NotFound } from '@/components/elements/ScreenBlock';
import TransitionRouter from '@/TransitionRouter';
import SubNavigation from '@/components/elements/SubNavigation';
import { useLocation } from 'react-router';
import Spinner from '@/components/elements/Spinner';
import routes from '@/routers/routes';

export default () => {
    const location = useLocation();

    return (
        <div style={{ display: 'flex', height: '100vh', overflow: 'hidden' }}>
            {/* Left Sidebar */}
            <SideBar />

            {/* Main content area */}
            <div style={{ flex: 1, overflowY: 'auto', minWidth: 0, display: 'flex', flexDirection: 'column' }}>
                {/* Account sub-navigation (only shown under /account) */}
                {location.pathname.startsWith('/account') && (
                    <SubNavigation>
                        <div>
                            {routes.account
                                .filter((route) => !!route.name)
                                .map(({ path, name, exact = false }) => (
                                    <NavLink key={path} to={`/account/${path}`.replace('//', '/')} exact={exact}>
                                        {name}
                                    </NavLink>
                                ))}
                        </div>
                    </SubNavigation>
                )}

                <TransitionRouter>
                    <React.Suspense fallback={<Spinner centered />}>
                        <Switch location={location}>
                            <Redirect from={'/dashboard'} to={'/'} exact />
                            <Route path={'/'} exact>
                                <DashboardContainer />
                            </Route>
                            <Route path={'/servers'} exact>
                                <ServersContainer />
                            </Route>
                            <Route path={'/wallet'} exact>
                                <WalletContainer />
                            </Route>
                            {routes.account.map(({ path, component: Component }) => (
                                <Route key={path} path={`/account/${path}`.replace('//', '/')} exact>
                                    <Component />
                                </Route>
                            ))}
                            <Route path={'*'}>
                                <NotFound />
                            </Route>
                        </Switch>
                    </React.Suspense>
                </TransitionRouter>
            </div>
        </div>
    );
};
