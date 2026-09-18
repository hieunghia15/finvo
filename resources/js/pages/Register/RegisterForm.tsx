import { FormEvent } from 'react';
import { useForm, Link } from '@inertiajs/react';
import { registerSchema, RegisterFormValues } from '@/Schemas';

type RegisterField = keyof RegisterFormValues;

export default function RegisterForm() {
    const { data, setData, post, processing, errors, clearErrors, setError, reset } = useForm<RegisterFormValues>({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        clearErrors();

        const result = registerSchema.safeParse(data);
        if (!result.success) {
            const seen = new Set<RegisterField>();
            for (const issue of result.error.issues) {
                const field = issue.path[0] as RegisterField;
                if (!seen.has(field)) {
                    seen.add(field);
                    setError(field, issue.message);
                }
            }
            return;
        }

        post('/register', {
            onError: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <form onSubmit={handleSubmit}>
            <div className="form-group mb-3">
                <label htmlFor="name" className="label text-secondary">
                    Full Name
                </label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    className={`form-control h-55${errors.name ? ' is-invalid' : ''}`}
                    style={errors.name ? { backgroundImage: 'none' } : undefined}
                    placeholder="Enter your full name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    autoComplete="name"
                />
                {errors.name && <div className="invalid-feedback d-block">{errors.name}</div>}
            </div>

            <div className="form-group mb-3">
                <label htmlFor="email" className="label text-secondary">
                    Email Address
                </label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    className={`form-control h-55${errors.email ? ' is-invalid' : ''}`}
                    style={errors.email ? { backgroundImage: 'none' } : undefined}
                    placeholder="example@trezo.com"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    autoComplete="email"
                />
                {errors.email && <div className="invalid-feedback d-block">{errors.email}</div>}
            </div>

            <div className="form-group mb-3">
                <label htmlFor="password" className="label text-secondary">
                    Password
                </label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    className={`form-control h-55${errors.password ? ' is-invalid' : ''}`}
                    style={errors.password ? { backgroundImage: 'none' } : undefined}
                    placeholder="Type password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    autoComplete="new-password"
                />
                {errors.password && <div className="invalid-feedback d-block">{errors.password}</div>}
            </div>

            <div className="form-group mb-3">
                <label htmlFor="password_confirmation" className="label text-secondary">
                    Confirm Password
                </label>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    className={`form-control h-55${errors.password_confirmation ? ' is-invalid' : ''}`}
                    style={errors.password_confirmation ? { backgroundImage: 'none' } : undefined}
                    placeholder="Retype password"
                    value={data.password_confirmation}
                    onChange={(e) => setData('password_confirmation', e.target.value)}
                    autoComplete="new-password"
                />
                {errors.password_confirmation && <div className="invalid-feedback d-block">{errors.password_confirmation}</div>}
            </div>

            <div className="form-group mb-3">
                <button type="submit" disabled={processing} className="btn btn-primary fw-medium py-2 px-3 w-100">
                    <div className="d-flex align-items-center justify-content-center py-1">
                        <i className="material-symbols-outlined text-white fs-20 me-2">person_4</i>
                        <span>{processing ? 'Registering…' : 'Register'}</span>
                    </div>
                </button>
            </div>

            <div className="form-group">
                <p>
                    By registering, you agree to our{' '}
                    <a href="#" className="fw-medium text-decoration-none">
                        Terms of Service
                    </a>{' '}
                    and that you have read and understood our{' '}
                    <a href="#" className="fw-medium text-decoration-none">
                        Privacy Policy
                    </a>
                    .
                </p>
                <p>
                    Already have an account.{' '}
                    <Link href="/" className="fw-medium text-primary text-decoration-none">
                        Log In
                    </Link>
                </p>
            </div>
        </form>
    );
}
