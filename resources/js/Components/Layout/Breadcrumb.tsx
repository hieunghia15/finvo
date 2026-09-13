import React from 'react';
import { Link } from '@inertiajs/react';
import { BreadcrumbItem } from '@/types';

export interface BreadcrumbProps {
    title: string;
    items?: BreadcrumbItem[];
}

export const Breadcrumb: React.FC<BreadcrumbProps> = ({
    title,
    items = [
        { label: 'Dashboard', url: '/' },
        { label: 'Extra Pages', active: true },
        { label: title, active: true },
    ],
}) => {
    return (
        <div className="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <h3 className="mb-0">{title}</h3>

            <nav style={{ '--bs-breadcrumb-divider': "'>'" } as React.CSSProperties} aria-label="breadcrumb">
                <ol className="breadcrumb align-items-center mb-0 lh-1">
                    {items.map((item, index) => {
                        const isFirst = index === 0;
                        const isActive = item.active || index === items.length - 1;

                        return (
                            <li key={index} className={`breadcrumb-item ${isActive ? 'active' : ''}`} aria-current={isActive ? 'page' : undefined}>
                                {!isActive && item.url ? (
                                    <Link href={item.url} className="d-flex align-items-center text-decoration-none">
                                        {isFirst && <i className="ri-home-4-line fs-18 text-primary me-1"></i>}
                                        <span className="text-secondary fw-medium hover">{item.label}</span>
                                    </Link>
                                ) : (
                                    <span className="fw-medium">{item.label}</span>
                                )}
                            </li>
                        );
                    })}
                </ol>
            </nav>
        </div>
    );
};

export default Breadcrumb;
