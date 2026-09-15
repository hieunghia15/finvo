import React, { useState, useRef, useEffect } from 'react';
import { Link, router } from '@inertiajs/react';
import { User, NotificationItem } from '@/types';
import { useAuth } from '@/features/auth/hooks';

export interface HeaderProps {
    user?: User | null;
    onToggleSidebar?: () => void;
    notifications?: NotificationItem[];
}

export const Header: React.FC<HeaderProps> = ({ user, onToggleSidebar, notifications = [] }) => {
    const [isNotificationOpen, setIsNotificationOpen] = useState(false);

    const { logout } = useAuth();
    const handleLogout = async () => {
        try {
            await logout();
        } finally {
            router.visit('/');
        }
    };
    const [isProfileOpen, setIsProfileOpen] = useState(false);

    const notificationRef = useRef<HTMLDivElement>(null);
    const profileRef = useRef<HTMLDivElement>(null);

    // Close dropdowns when clicking outside
    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (notificationRef.current && !notificationRef.current.contains(event.target as Node)) {
                setIsNotificationOpen(false);
            }
            if (profileRef.current && !profileRef.current.contains(event.target as Node)) {
                setIsProfileOpen(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, []);

    const defaultNotifications: NotificationItem[] = [
        {
            id: 1,
            title: 'You have requested to withdrawal',
            time: '2 hrs ago',
            type: 'sms',
            read: false,
        },
        {
            id: 2,
            title: 'A new user added in Trezo',
            time: '3 hrs ago',
            type: 'person',
            read: false,
        },
        {
            id: 3,
            title: 'You have requested to withdrawal',
            time: '1 day ago',
            type: 'email',
            read: true,
        },
    ];

    const displayNotifications = notifications.length > 0 ? notifications : defaultNotifications;

    const renderNotificationIcon = (type?: string) => {
        switch (type) {
            case 'person':
                return <i className="material-symbols-outlined text-info">person</i>;
            case 'email':
                return <i className="material-symbols-outlined text-success">mark_email_unread</i>;
            default:
                return <i className="material-symbols-outlined text-primary">sms</i>;
        }
    };

    return (
        <header className="header-area bg-white mb-4 rounded-bottom-15" id="header-area">
            <div className="row align-items-center">
                <div className="col-lg-4 col-sm-6">
                    <div className="left-header-content">
                        <ul className="d-flex align-items-center ps-0 mb-0 list-unstyled justify-content-center justify-content-sm-start">
                            <li>
                                <button type="button" className="header-burger-menu bg-transparent p-0 border-0" id="header-burger-menu" onClick={onToggleSidebar} aria-label="Toggle Navigation">
                                    <span className="material-symbols-outlined">menu</span>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <div className="col-lg-8 col-sm-6">
                    <div className="right-header-content mt-2 mt-sm-0">
                        <ul className="d-flex align-items-center justify-content-center justify-content-sm-end ps-0 mb-0 list-unstyled">
                            {/* Notification Dropdown */}
                            <li className="header-right-item">
                                <div ref={notificationRef} className="dropdown notifications noti position-relative">
                                    <button
                                        className="btn btn-secondary border-0 p-0 position-relative badge"
                                        type="button"
                                        onClick={() => {
                                            setIsNotificationOpen((prev) => !prev);
                                            setIsProfileOpen(false);
                                        }}
                                        aria-expanded={isNotificationOpen}
                                    >
                                        <span className="material-symbols-outlined">notifications</span>
                                    </button>

                                    {isNotificationOpen && (
                                        <div
                                            className="dropdown-menu dropdown-lg p-0 border-0 dropdown-menu-end show d-block position-absolute end-0 mt-2"
                                            style={{ inset: '0px 0px auto auto', margin: '0px' }}
                                        >
                                            <div className="d-flex justify-content-between align-items-center title p-3">
                                                <span className="fw-semibold fs-15 text-secondary">
                                                    Notifications <span className="fw-normal text-body fs-14">({displayNotifications.length.toString().padStart(2, '0')})</span>
                                                </span>
                                                <button type="button" className="p-0 m-0 bg-transparent border-0 fs-14 text-primary">
                                                    Clear All
                                                </button>
                                            </div>

                                            <div className="max-h-217 overflow-auto">
                                                {displayNotifications.map((item) => (
                                                    <div key={item.id} className={`notification-menu ${!item.read ? 'unseen' : ''}`}>
                                                        <Link href={item.url || '/notification'} className="dropdown-item p-3 border-bottom d-block text-decoration-none">
                                                            <div className="d-flex align-items-center">
                                                                <div className="flex-shrink-0">{renderNotificationIcon(item.type)}</div>
                                                                <div className="flex-grow-1 ms-3">
                                                                    <p className="mb-0 text-dark">{item.title}</p>
                                                                    <span className="fs-13 text-muted">{item.time}</span>
                                                                </div>
                                                            </div>
                                                        </Link>
                                                    </div>
                                                ))}
                                            </div>

                                            <Link href="/notification" className="dropdown-item text-center text-primary d-block view-all fw-medium rounded-bottom-3 p-3 text-decoration-none">
                                                <span>See All Notifications</span>
                                            </Link>
                                        </div>
                                    )}
                                </div>
                            </li>

                            {/* Admin Profile Dropdown */}
                            <li className="header-right-item ms-3">
                                <div ref={profileRef} className="dropdown admin-profile position-relative">
                                    <div
                                        className="d-xxl-flex align-items-center bg-transparent border-0 text-start p-0 cursor dropdown-toggle"
                                        onClick={() => {
                                            setIsProfileOpen((prev) => !prev);
                                            setIsNotificationOpen(false);
                                        }}
                                        style={{ cursor: 'pointer' }}
                                    >
                                        <div className="flex-shrink-0">
                                            <img
                                                className="rounded-circle wh-40 administrator"
                                                src={user?.avatar || '/assets/trezo/images/administrator.jpg'}
                                                alt="admin"
                                                style={{ width: '40px', height: '40px', objectFit: 'cover' }}
                                            />
                                        </div>
                                        <div className="flex-grow-1 ms-2">
                                            <div className="d-flex align-items-center justify-content-between">
                                                <div className="d-none d-xxl-block">
                                                    <div className="d-flex align-content-center">
                                                        <h3 className="fs-14 mb-0 fw-semibold">{user?.name || 'Olivia'}</h3>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {isProfileOpen && (
                                        <div
                                            className="dropdown-menu border-0 bg-white dropdown-menu-end show d-block position-absolute end-0 mt-2 p-3 shadow-sm rounded-3"
                                            style={{ inset: '0px 0px auto auto', margin: '0px' }}
                                        >
                                            <div className="d-flex align-items-center info mb-3 pb-2 border-bottom">
                                                <div className="flex-shrink-0">
                                                    <img
                                                        className="rounded-circle wh-30 administrator"
                                                        src={user?.avatar || '/assets/trezo/images/administrator.jpg'}
                                                        alt="admin"
                                                        style={{ width: '30px', height: '30px', objectFit: 'cover' }}
                                                    />
                                                </div>
                                                <div className="flex-grow-1 ms-2">
                                                    <h3 className="fw-medium fs-14 mb-0">{user?.name || 'Olivia John'}</h3>
                                                    <span className="fs-12 text-muted">{user?.role || 'Marketing Manager'}</span>
                                                </div>
                                            </div>

                                            <ul className="admin-link ps-0 mb-0 list-unstyled">
                                                <li>
                                                    <Link className="dropdown-item admin-item-link d-flex align-items-center text-body py-2" href="/my-profile">
                                                        <i className="material-symbols-outlined me-2 fs-18">account_circle</i>
                                                        <span>My Profile</span>
                                                    </Link>
                                                </li>
                                                <li>
                                                    <Link className="dropdown-item admin-item-link d-flex align-items-center text-body py-2" href="/chat">
                                                        <i className="material-symbols-outlined me-2 fs-18">chat</i>
                                                        <span>Messages</span>
                                                    </Link>
                                                </li>
                                                <li>
                                                    <Link className="dropdown-item admin-item-link d-flex align-items-center text-body py-2" href="/to-do-list">
                                                        <i className="material-symbols-outlined me-2 fs-18">format_list_bulleted</i>
                                                        <span>My Task</span>
                                                    </Link>
                                                </li>
                                                <li>
                                                    <Link className="dropdown-item admin-item-link d-flex align-items-center text-body py-2" href="/checkout">
                                                        <i className="material-symbols-outlined me-2 fs-18">credit_card</i>
                                                        <span>Billing</span>
                                                    </Link>
                                                </li>
                                            </ul>

                                            <hr className="my-2" />

                                            <ul className="admin-link ps-0 mb-0 list-unstyled">
                                                <li>
                                                    <Link className="dropdown-item admin-item-link d-flex align-items-center text-body py-2" href="/settings">
                                                        <i className="material-symbols-outlined me-2 fs-18">settings</i>
                                                        <span>Settings</span>
                                                    </Link>
                                                </li>
                                                <li>
                                                    <Link className="dropdown-item admin-item-link d-flex align-items-center text-body py-2" href="/tickets">
                                                        <i className="material-symbols-outlined me-2 fs-18">support</i>
                                                        <span>Support</span>
                                                    </Link>
                                                </li>
                                                <li>
                                                    <Link className="dropdown-item admin-item-link d-flex align-items-center text-body py-2" href="/lock-screen">
                                                        <i className="material-symbols-outlined me-2 fs-18">lock</i>
                                                        <span>Lock Screen</span>
                                                    </Link>
                                                </li>
                                                <li>
                                                    <button
                                                        type="button"
                                                        className="dropdown-item admin-item-link d-flex align-items-center text-body py-2 w-100 border-0 bg-transparent text-start"
                                                        onClick={handleLogout}
                                                    >
                                                        <i className="material-symbols-outlined me-2 fs-18">logout</i>
                                                        <span>Logout</span>
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    )}
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>
    );
};

export default Header;
