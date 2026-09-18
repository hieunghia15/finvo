import { Head, usePage } from '@inertiajs/react';
import AuthLayout from '@/Components/Auth/AuthLayout';
import SocialButtons from '@/Components/Auth/SocialButtons';
import { PageProps } from '@/types';
import LoginForm from './LoginForm';

export default function Index() {
    const { flash } = usePage<PageProps>().props;

    return (
        <div className="boxed-size bg-white">
            <Head title="Sign In" />
            <AuthLayout>
                <h3 className="fs-28 mb-2">Welcome back to Trezo!</h3>
                <p className="fw-medium fs-16 mb-4">Sign In with social account or enter your details</p>

                {flash?.status && (
                    <div className="alert alert-success" role="alert">
                        {flash.status}
                    </div>
                )}

                <SocialButtons />

                <LoginForm />
            </AuthLayout>
        </div>
    );
}
