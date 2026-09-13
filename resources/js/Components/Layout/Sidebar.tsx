import React, { useState } from 'react';
import { Link } from '@inertiajs/react';
import { sidebarMenuConfig, MenuItem, MenuGroup } from '@/Config/sidebarMenu';

export interface SidebarProps {
    isOpen?: boolean;
    onToggleSidebar?: () => void;
    menuConfig?: MenuGroup[];
}

export const Sidebar: React.FC<SidebarProps> = ({ isOpen = true, onToggleSidebar, menuConfig = sidebarMenuConfig }) => {
    // Track open state for sub-menus (closed by default)
    const [openMenus, setOpenMenus] = useState<Record<string, boolean>>({});

    const toggleMenu = (id: string) => {
        setOpenMenus((prev) => ({
            ...prev,
            [id]: !prev[id],
        }));
    };

    const renderMenuItem = (item: MenuItem, isNested = false) => {
        const hasSubItems = Boolean(item.subItems && item.subItems.length > 0);
        const isOpenMenu = Boolean(openMenus[item.id]);

        if (hasSubItems) {
            return (
                <li key={item.id} className={`menu-item ${isOpenMenu ? 'open' : ''} ${isNested ? 'after-sub-menu' : ''}`}>
                    <button
                        type="button"
                        className={`menu-link menu-toggle w-100 text-start border-0 ${isOpenMenu ? 'active' : ''}`}
                        onClick={() => toggleMenu(item.id)}
                    >
                        {item.icon && <span className="material-symbols-outlined menu-icon">{item.icon}</span>}
                        <span className="title">{item.title}</span>
                    </button>

                    {isOpenMenu && item.subItems && (
                        <ul className="menu-sub">
                            {item.subItems.map((subItem) => renderMenuItem(subItem, true))}
                        </ul>
                    )}
                </li>
            );
        }

        return (
            <li key={item.id} className="menu-item">
                {item.method ? (
                    <Link
                        href={item.url || '#'}
                        method={item.method}
                        as="button"
                        className="menu-link border-0 w-100 text-start"
                    >
                        {item.icon && <span className="material-symbols-outlined menu-icon">{item.icon}</span>}
                        <span className="title">{item.title}</span>
                    </Link>
                ) : (
                    <Link href={item.url || '#'} className="menu-link">
                        {item.icon && <span className="material-symbols-outlined menu-icon">{item.icon}</span>}
                        <span className="title">{item.title}</span>
                    </Link>
                )}
            </li>
        );
    };

    return (
        <div className="sidebar-area" id="sidebar-area">
            <div className="logo position-relative">
                <Link href="/" className="d-block text-decoration-none position-relative">
                    <img src="/assets/trezo/images/logo-icon.png" alt="logo-icon" />
                    <span className="logo-text fw-bold text-dark">Trezo</span>
                </Link>
                <button
                    type="button"
                    className="sidebar-burger-menu bg-transparent p-0 border-0 opacity-0 z-n1 position-absolute top-50 end-0 translate-middle-y"
                    id="sidebar-burger-menu"
                    onClick={onToggleSidebar}
                    aria-label="Toggle Sidebar"
                >
                    <span className="material-symbols-outlined">close</span>
                </button>
            </div>

            <aside id="layout-menu" className="layout-menu menu-vertical menu active">
                <ul className="menu-inner">
                    {menuConfig.map((group) => (
                        <React.Fragment key={group.id}>
                            {group.title && (
                                <li className="menu-title small text-uppercase">
                                    <span className="menu-title-text">{group.title}</span>
                                </li>
                            )}
                            {group.items.map((item) => renderMenuItem(item))}
                        </React.Fragment>
                    ))}
                </ul>
            </aside>
        </div>
    );
};

export default Sidebar;
