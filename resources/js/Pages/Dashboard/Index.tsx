import React from 'react';
import { Head } from '@inertiajs/react';
import MainLayout from '@/Layouts/MainLayout';
import Breadcrumb from '@/Components/Layout/Breadcrumb';
import { useTranslation } from '@/hooks/useTranslation';
import { PageProps } from '@/types';

interface IndexProps extends PageProps {
    pageTitle?: string;
}

/**
 * Placeholder until the Dashboard module is built; only its title and
 * breadcrumb are translated, the body is replaced with the real dashboard.
 */
export default function Index({ pageTitle = 'Dashboard' }: IndexProps) {
    const { t } = useTranslation();
    const title = t(pageTitle);

    return (
        <MainLayout>
            <Head title={title} />

            <Breadcrumb title={title} items={[{ label: title, active: true }]} />

            {/* Main Content Card / Workspace Area */}
            <div className="card bg-white border-0 rounded-3 mb-4">
                <div className="card-body p-4">
                    <h4 className="fs-18 fw-semibold mb-3">Content Area</h4>
                    <p className="text-secondary mb-0">This is a blank page layout ready for your custom Laravel + Inertia.js components and business logic.</p>
                </div>
            </div>
        </MainLayout>
    );
}
