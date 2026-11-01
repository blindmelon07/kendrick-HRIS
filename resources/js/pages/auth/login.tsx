import { Form, Head } from '@inertiajs/react';
import { Button, Checkbox, Field, Input } from '@/components/ui';
import { store } from '@/routes/login';

export default function Login() {
    return (
        <>
            <Head title="Log in" />
            <div className="flex min-h-screen items-center justify-center px-4">
                <div className="w-full max-w-sm">
                    <div className="mb-8 text-center">
                        <img
                            src="/images/tristellar-wordmark.png"
                            alt="TriStellar – Bigas ng Bayan"
                            className="mx-auto h-20 w-auto"
                        />
                        <h1 className="mt-4 text-xl font-semibold text-ink">
                            TriStellar HRIS
                        </h1>
                        <p className="mt-1 text-sm text-ink-muted">
                            Employee records, timekeeping and payroll
                        </p>
                    </div>

                    <Form
                        {...store.form()}
                        resetOnError={['password']}
                        className="flex flex-col gap-4 rounded-lg border border-line bg-surface p-6 shadow-xs"
                    >
                        {({ errors, processing }) => (
                            <>
                                <Field label="Email" error={errors.email}>
                                    <Input
                                        type="email"
                                        name="email"
                                        autoComplete="username"
                                        autoFocus
                                        required
                                    />
                                </Field>
                                <Field label="Password" error={errors.password}>
                                    <Input
                                        type="password"
                                        name="password"
                                        autoComplete="current-password"
                                        required
                                    />
                                </Field>
                                <Checkbox
                                    name="remember"
                                    value="1"
                                    label="Keep me signed in"
                                />
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Signing in…' : 'Sign in'}
                                </Button>
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </>
    );
}
