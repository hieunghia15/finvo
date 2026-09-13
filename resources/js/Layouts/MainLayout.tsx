import React, { useState, useEffect, PropsWithChildren } from 'react';
import { usePage } from '@inertiajs/react';
import { PageProps } from '@/types';
import Sidebar from '@/Components/Layout/Sidebar';
import Header from '@/Components/Layout/Header';
import Footer from '@/Components/Layout/Footer';

export const MainLayout: React.FC<PropsWithChildren> = ({ children }) => {
    const { props } = usePage<PageProps>();
    const [isSidebarHidden, setIsSidebarHidden] = useState(false);

    const toggleSidebar = () => {
        setIsSidebarHidden((prev) => {
            const nextState = !prev;
            if (nextState) {
                document.body.setAttribute('sidebar-data-theme', 'sidebar-hide');
            } else {
                document.body.setAttribute('sidebar-data-theme', 'sidebar-show');
            }
            return nextState;
        });
    };

    useEffect(() => {
        if (isSidebarHidden) {
            document.body.setAttribute('sidebar-data-theme', 'sidebar-hide');
        } else {
            document.body.setAttribute('sidebar-data-theme', 'sidebar-show');
        }
    }, [isSidebarHidden]);

    return (
        <div className="layout-wrapper">
            {/* Sidebar Component */}
            <Sidebar isOpen={!isSidebarHidden} onToggleSidebar={toggleSidebar} />

            {/* Main Content Container */}
            <div className="container-fluid">
                <div className="main-content d-flex flex-column min-vh-100">
                    {/* Header Component */}
                    <Header user={props.auth?.user} onToggleSidebar={toggleSidebar} />

                    {/* Page Content */}
                    <main className="main-content-container overflow-hidden flex-grow-1">{children}</main>

                    {/* Footer Component */}
                    <Footer />
                </div>
            </div>
        </div>
    );
};

export default MainLayout;
