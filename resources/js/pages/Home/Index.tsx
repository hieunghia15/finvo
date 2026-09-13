import React from 'react';
import { Head } from '@inertiajs/react';
import MainLayout from '@/Layouts/MainLayout';
import Breadcrumb from '@/Components/Layout/Breadcrumb';
import { PageProps } from '@/types';

interface IndexProps extends PageProps {
    pageTitle?: string;
}

export default function Index({ pageTitle = 'Blank Page' }: IndexProps) {
    return (
        <MainLayout>
            <Head title={pageTitle} />

            <Breadcrumb
                title={pageTitle}
                items={[
                    { label: 'Dashboard', url: '/' },
                    { label: 'Extra Pages', active: true },
                    { label: pageTitle, active: true },
                ]}
            />

            {/* Main Content Card / Workspace Area */}
            <div className="card bg-white border-0 rounded-3 mb-4">
                <div className="card-body p-4">
                    <h4 className="fs-18 fw-semibold mb-3">Content Area</h4>
                    <p className="text-secondary mb-0">
                        This is a blank page layout ready for your custom Laravel + Inertia.js components and business logic.
                    </p>
                </div>
            </div>
        </MainLayout>
    );
}

