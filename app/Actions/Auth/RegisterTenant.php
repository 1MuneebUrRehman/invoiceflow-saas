<?php

namespace App\Actions\Auth;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;

class RegisterTenant
{
    /**
     * Create a tenant and its owner user atomically.
     */
    public function execute(string $businessName, string $name, string $email, string $password): User
    {
        $user = DB::transaction(function () use ($businessName, $name, $email, $password): User {
            $tenant = Tenant::create([
                'name' => $businessName,
            ]);

            $user = new User([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);

            $user->tenant_id = $tenant->id;
            $user->role = UserRole::Owner;
            $user->save();

            return $user;
        });

        event(new Registered($user));

        return $user;
    }
}
