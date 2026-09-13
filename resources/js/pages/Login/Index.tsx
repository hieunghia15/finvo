import { Head } from '@inertiajs/react';
import AuthLayout from '@/Components/Auth/AuthLayout';
import SocialButtons from '@/Components/Auth/SocialButtons';
import LoginForm from './LoginForm';

export default function Index() {
    return (
        <>
            <Head title="Sign In" />
            <AuthLayout>
                <h3 className="fs-28 mb-2">Welcome back to Trezo!</h3>
                <p className="fw-medium fs-16 mb-4">
                    Sign In with social account or enter your details
                </p>

                <SocialButtons />

                <LoginForm />
            </AuthLayout>
        </>
    );
}
