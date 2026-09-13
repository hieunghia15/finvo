import React from 'react';

interface AuthLayoutProps {
    children: React.ReactNode;
    image?: string;
}

export default function AuthLayout({ children, image = '/assets/trezo/images/login.jpg' }: AuthLayoutProps) {
    return (
        <div className="container">
            <div className="main-content d-flex flex-column p-0">
                <div className="m-auto m-1230">
                    <div className="row align-items-center">
                        <div className="col-lg-6 d-none d-lg-block">
                            <img
                                src={image}
                                className="rounded-3"
                                alt="auth"
                            />
                        </div>
                        <div className="col-lg-6">
                            <div className="mw-480 ms-lg-auto">
                                <div className="d-inline-block mb-4">
                                    <img
                                        src="/assets/trezo/images/logo.svg"
                                        className="rounded-3 for-light-logo"
                                        alt="logo"
                                    />
                                    <img
                                        src="/assets/trezo/images/white-logo.svg"
                                        className="rounded-3 for-dark-logo"
                                        alt="logo"
                                    />
                                </div>
                                {children}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
