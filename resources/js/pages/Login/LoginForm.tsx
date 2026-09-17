import { FormEvent } from 'react';
import { useForm, Link } from '@inertiajs/react';
import { loginSchema } from '@/Schemas';

interface LoginFormValues {
    email: string;
    password: string;
    remember: boolean;
    [key: string]: string | boolean;
}

export default function LoginForm() {
    const { data, setData, post, processing, errors, clearErrors, setError } = useForm<LoginFormValues>({
        email: '',
        password: '',
        remember: false,
    });

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        clearErrors();

        const result = loginSchema.safeParse({ email: data.email, password: data.password });
        if (!result.success) {
            for (const issue of result.error.issues) {
                setError(issue.path[0] as 'email' | 'password', issue.message);
            }
            return;
        }

        post('/login');
    };

    const emailError = errors.email;
    const passwordError = errors.password;

    return (
        <form onSubmit={handleSubmit}>
            <div className="form-group mb-4">
                <label htmlFor="email" className="label text-secondary">
                    Email Address
                </label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    className={`form-control h-55${emailError ? ' is-invalid' : ''}`}
                    style={emailError ? { backgroundImage: 'none' } : undefined}
                    placeholder="example@trezo.com"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    autoComplete="username"
                />
                {emailError && <div className="invalid-feedback d-block">{emailError}</div>}
            </div>

            <div className="form-group mb-4">
                <label htmlFor="password" className="label text-secondary">
                    Password
                </label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    className={`form-control h-55${passwordError ? ' is-invalid' : ''}`}
                    style={passwordError ? { backgroundImage: 'none' } : undefined}
                    placeholder="Type password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    autoComplete="current-password"
                />
                {passwordError && <div className="invalid-feedback d-block">{passwordError}</div>}
            </div>

            <div className="form-group mb-4 d-flex align-items-center justify-content-between">
                <div className="form-check">
                    <input id="remember" type="checkbox" className="form-check-input" checked={data.remember} onChange={(e) => setData('remember', e.target.checked)} />
                    <label htmlFor="remember" className="form-check-label text-secondary">
                        Remember Me
                    </label>
                </div>
                <Link href="/forgot-password" className="text-decoration-none text-primary fw-semibold">
                    Forgot Password?
                </Link>
            </div>

            <div className="form-group mb-4">
                <button type="submit" disabled={processing} className="btn btn-primary fw-medium py-2 px-3 w-100">
                    <div className="d-flex align-items-center justify-content-center py-1">
                        <i className="material-symbols-outlined text-white fs-20 me-2">login</i>
                        <span>{processing ? 'Signing in…' : 'Login'}</span>
                    </div>
                </button>
            </div>

            <div className="form-group">
                <p>
                    Don't have an account?{' '}
                    <Link href="/register" className="fw-medium text-primary text-decoration-none">
                        Register
                    </Link>
                </p>
            </div>
        </form>
    );
}
