import React from 'react';

export const Footer: React.FC = () => {
    const currentYear = new Date().getFullYear();

    return (
        <footer className="footer-area bg-white text-center rounded-top-7 py-3">
            <p className="fs-14 mb-0">
                © {currentYear} <span className="text-primary-div">Trezo</span> is Proudly Owned by{' '}
                <a href="https://envytheme.com/" target="_blank" rel="noopener noreferrer" className="text-decoration-none text-primary">
                    EnvyTheme
                </a>
            </p>
        </footer>
    );
};

export default Footer;
