<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Models\User;
use App\Support\PhoneNumberNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateAdminUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-admin
                            {role : Role admin (main_admin atau branch_admin)}
                            {--name= : Nama user admin}
                            {--email= : Email user admin (opsional)}
                            {--phone= : Nomor telepon user admin (opsional)}
                            {--password= : Password user admin}
                            {--store_id= : ID store (wajib untuk branch_admin)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create admin user with role main_admin or branch_admin';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $role = strtolower(trim((string) $this->argument('role')));

        if (! in_array($role, ['main_admin', 'branch_admin'], true)) {
            $this->error('Role tidak valid. Gunakan main_admin atau branch_admin.');

            return self::FAILURE;
        }

        $nameInput = $this->option('name');
        $emailInput = $this->option('email');
        $phoneInput = $this->option('phone');
        $passwordInput = $this->option('password');
        $storeIdInput = $this->option('store_id');

        $name = is_string($nameInput) && $nameInput !== ''
            ? $nameInput
            : (string) $this->ask('Nama admin');

        $email = is_string($emailInput) && $emailInput !== ''
            ? $emailInput
            : null;

        $phoneRaw = is_string($phoneInput) && $phoneInput !== ''
            ? $phoneInput
            : null;

        $password = is_string($passwordInput) && $passwordInput !== ''
            ? $passwordInput
            : (string) $this->secret('Password admin (minimal 8 karakter)');

        $storeId = is_string($storeIdInput) && $storeIdInput !== ''
            ? (int) $storeIdInput
            : null;

        $phone = $phoneRaw !== null ? PhoneNumberNormalizer::normalize($phoneRaw) : null;

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
            'store_id' => $storeId,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'min:10', 'max:20', Rule::unique('users', 'phone')],
            'password' => ['required', 'string', 'min:8'],
            'store_id' => $role === 'branch_admin'
                ? ['required', 'integer', Rule::exists('stores', 'id')]
                : ['nullable', 'integer', Rule::exists('stores', 'id')],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if ($storeId !== null) {
            $store = Store::query()->find($storeId);

            if (! $store) {
                $this->error('Store tidak ditemukan.');

                return self::FAILURE;
            }

            if ($role === 'main_admin' && $store->type !== 'main') {
                $this->error('main_admin harus menggunakan store bertipe main.');

                return self::FAILURE;
            }

            if ($role === 'branch_admin' && $store->type !== 'branch') {
                $this->error('branch_admin harus menggunakan store bertipe branch.');

                return self::FAILURE;
            }
        }

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'phone_verified_at' => $phone !== null ? now() : null,
            'password' => $password,
            'role' => $role,
            'store_id' => $storeId,
        ]);

        $this->info('Admin berhasil dibuat.');
        $this->table(['ID', 'Name', 'Role', 'Phone', 'Email', 'Store ID'], [[
            $user->id,
            $user->name,
            $user->role,
            $user->phone,
            $user->email,
            $user->store_id,
        ]]);

        return self::SUCCESS;
    }
}
