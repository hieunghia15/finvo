import React from 'react';

export default function SocialButtons() {
    return (
        <div className="row justify-content-center">
            <div className="col-lg-4 col-sm-4">
                <a href="https://www.google.com/" target="_blank" rel="noreferrer" className="btn btn-outline-secondary bg-transparent w-100 py-2 hover-bg mb-4" style={{ borderColor: '#D6DAE1' }}>
                    <img src="/assets/trezo/images/google.svg" alt="Google" />
                </a>
            </div>
            <div className="col-lg-4 col-sm-4">
                <a href="https://www.facebook.com/" target="_blank" rel="noreferrer" className="btn btn-outline-secondary bg-transparent w-100 py-2 hover-bg mb-4" style={{ borderColor: '#D6DAE1' }}>
                    <img src="/assets/trezo/images/facebook2.svg" alt="Facebook" />
                </a>
            </div>
            <div className="col-lg-4 col-sm-4">
                <a href="https://www.apple.com/" target="_blank" rel="noreferrer" className="btn btn-outline-secondary bg-transparent w-100 py-2 hover-bg mb-4" style={{ borderColor: '#D6DAE1' }}>
                    <img src="/assets/trezo/images/apple.svg" alt="Apple" />
                </a>
            </div>
        </div>
    );
}
