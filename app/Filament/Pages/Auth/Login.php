<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;

class Login extends BaseLogin
{
    protected string $view = 'filament.pages.auth.login';

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'email' => 'admin@test.com',
            'password' => 'password',
            'remember' => true,
        ]);
    }

    public function fillCredentials(string $email, string $password): void
    {
        $this->form->fill([
            'email' => $email,
            'password' => $password,
            'remember' => true,
        ]);
    }
}
