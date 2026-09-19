import { useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import MainLayout from '@/Layouts/MainLayout';
import Breadcrumb from '@/Components/Layout/Breadcrumb';
import { Account, PageProps } from '@/types';
import ProfileForm from './ProfileForm';
import ChangePasswordForm from './ChangePasswordForm';

type AccountTab = 'profile' | 'password';

const TABS: Record<AccountTab, { label: string; crumb: string }> = {
    profile: { label: 'Account', crumb: 'Account' },
    password: { label: 'Change Password', crumb: 'Change Password' },
};

type IndexProps = PageProps<{ account: Account }>;

export default function Index({ account }: IndexProps) {
    const { flash } = usePage<PageProps>().props;
    const [activeTab, setActiveTab] = useState<AccountTab>('profile');
    const { label, crumb } = TABS[activeTab];

    return (
        <MainLayout>
            <Head title={label} />

            <Breadcrumb
                title={label}
                items={[
                    { label: 'Dashboard', url: '/dashboard' },
                    { label: crumb, active: true },
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
                        {(Object.keys(TABS) as AccountTab[]).map((tab) => {
                            const isActive = tab === activeTab;

                            return (
                                <li key={tab}>
                                    <button
                                        type="button"
                                        className={`btn btn-primary border border-primary py-2 px-3 fw-semibold ${isActive ? 'bg-primary text-white' : 'bg-transparent text-primary'}`}
                                        aria-current={isActive ? 'page' : undefined}
                                        onClick={() => setActiveTab(tab)}
                                    >
                                        {TABS[tab].label}
                                    </button>
                                </li>
                            );
                        })}
                    </ul>

                    {activeTab === 'profile' ? <ProfileForm account={account} /> : <ChangePasswordForm />}
                </div>
            </div>
        </MainLayout>
    );
}
