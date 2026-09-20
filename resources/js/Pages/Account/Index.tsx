import { useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import MainLayout from '@/Layouts/MainLayout';
import Breadcrumb from '@/Components/Layout/Breadcrumb';
import { Account, PageProps } from '@/types';
import ProfileForm from './ProfileForm';
import ChangePasswordForm from './ChangePasswordForm';

type AccountTab = 'profile' | 'password';

type IndexProps = PageProps<{ account: Account }>;

function tabButtonClass(isActive: boolean): string {
    const colors = isActive ? 'bg-primary text-white' : 'bg-transparent text-primary';

    return `btn btn-primary border border-primary py-2 px-3 fw-semibold ${colors}`;
}

export default function Index({ account }: IndexProps) {
    const { flash } = usePage<PageProps>().props;

    // The backend has a single GET /account, so the two tabs are local state
    // only: switching tabs does not navigate and does not touch the URL.
    const [activeTab, setActiveTab] = useState<AccountTab>('profile');

    const pageTitle = activeTab === 'profile' ? 'Account' : 'Change Password';

    return (
        <MainLayout>
            <Head title={pageTitle} />

            <Breadcrumb
                title={pageTitle}
                items={[
                    { label: 'Dashboard', url: '/dashboard' },
                    { label: pageTitle, active: true },
                ]}
            />

            {flash?.status && (
                <div className="alert alert-success" role="alert">
                    {flash.status}
                </div>
            )}

            <div className="card bg-white border-0 rounded-3 mb-4">
                <div className="card-body p-4">
                    <ul className="ps-0 mb-4 list-unstyled d-flex flex-wrap gap-2 gap-lg-3">
                        <li>
                            <button
                                type="button"
                                className={tabButtonClass(activeTab === 'profile')}
                                aria-current={activeTab === 'profile' ? 'page' : undefined}
                                onClick={() => setActiveTab('profile')}
                            >
                                Account
                            </button>
                        </li>
                        <li>
                            <button
                                type="button"
                                className={tabButtonClass(activeTab === 'password')}
                                aria-current={activeTab === 'password' ? 'page' : undefined}
                                onClick={() => setActiveTab('password')}
                            >
                                Change Password
                            </button>
                        </li>
                    </ul>

                    {activeTab === 'profile' ? <ProfileForm account={account} /> : <ChangePasswordForm />}
                </div>
            </div>
        </MainLayout>
    );
}
