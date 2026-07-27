<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Invitation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ReceptionistUserSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::firstOrCreate([
            'name' => 'receptionist',
            'guard_name' => 'web',
        ]);

        $receptionist = User::firstOrCreate(
            ['email' => 'resepsionis@undangan.test'],
            [
                'name' => 'Resepsionis',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        if (!$receptionist->hasRole('receptionist')) {
            $receptionist->assignRole($role);
        }

        $this->command->info('✅ Receptionist user created / found');
        $this->command->info('✅ Role receptionist assigned');
        $this->command->info('Email: resepsionis@undangan.test');

        $firstInvitation = Invitation::first();
        if ($firstInvitation && !$receptionist->invitations->contains($firstInvitation->id)) {
            $receptionist->invitations()->attach($firstInvitation->id);
            $this->command->info("✅ Connected to invitation: {$firstInvitation->title}");
        }
    }
}
