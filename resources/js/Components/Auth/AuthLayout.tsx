import React from 'react';
import LanguageSwitcher from '@/Components/Common/LanguageSwitcher';

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
                            <img src={image} className="rounded-3" alt="" />
                        </div>
                        <div className="col-lg-6">
                            <div className="mw-480 ms-lg-auto">
                                <div className="d-flex justify-content-between align-items-center mb-4">
                                    <div className="d-inline-block">
                                        <img src="/assets/trezo/images/logo.svg" className="rounded-3 for-light-logo" alt="Finvo" />
                                        <img src="/assets/trezo/images/white-logo.svg" className="rounded-3 for-dark-logo" alt="Finvo" />
                                    </div>

                                    {/* Same wrapper as the header, so the template's dropdown styles apply. */}
                                    <div className="right-header-content">
                                        <ul className="list-unstyled ps-0 mb-0">
                                            <li className="header-right-item me-0">
                                                <LanguageSwitcher />
                                            </li>
                                        </ul>
                                    </div>
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
