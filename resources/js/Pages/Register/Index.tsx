import React from 'react';
import { Head } from '@inertiajs/react';
import AuthLayout from '@/Components/Auth/AuthLayout';
import SocialButtons from '@/Components/Auth/SocialButtons';
import RegisterForm from './RegisterForm';

export default function Index() {
    return (
        <div className="boxed-size bg-white">
            <Head title="Register" />
            <AuthLayout image="/assets/trezo/images/register.jpg">
                <h3 className="fs-28 mb-2">Register to Finvo Dashboard</h3>
                <p className="fw-medium fs-16 mb-4">Register with social account or enter your details</p>

                <SocialButtons />

                <RegisterForm />
            </AuthLayout>
        </div>
    );
}
