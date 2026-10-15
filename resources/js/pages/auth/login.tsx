import { Form, Head } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';

export default function Login() {
    return (
        <>
            <Head title="Masuk" />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="email">Alamat email</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="email"
                                placeholder="nama@nikel.test"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">Kata sandi</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                tabIndex={2}
                                autoComplete="current-password"
                                placeholder="Kata sandi"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="flex items-center space-x-3">
                            <Checkbox
                                id="remember"
                                name="remember"
                                tabIndex={3}
                            />
                            <Label htmlFor="remember">Ingat saya</Label>
                        </div>

                        <Button
                            type="submit"
                            size="lg"
                            className="group mt-2 w-full justify-between"
                            tabIndex={4}
                            disabled={processing}
                            data-test="login-button"
                        >
                            Masuk
                            {processing ? (
                                <Spinner />
                            ) : (
                                <ArrowRight className="transition-transform duration-300 ease-[var(--ease-out-expo)] group-hover:translate-x-1" />
                            )}
                        </Button>
                    </div>
                )}
            </Form>
        </>
    );
}

Login.layout = {
    title: 'Masuk ke Sistem',
    description: 'Gunakan email dan kata sandi akun perusahaan Anda.',
};
