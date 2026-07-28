<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Invitation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PengantinUserSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::firstOrCreate([
            'name' => 'pengantin',
            'guard_name' => 'web',
        ]);

        $pengantin = User::firstOrCreate(
            ['email' => 'pengantin@undangan.test'],
            [
                'name' => 'Mempelai',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        if (!$pengantin->hasRole('pengantin')) {
            $pengantin->assignRole($role);
        }

        $this->command->info('✅ Pengantin user created / found');
        $this->command->info('✅ Role pengantin assigned');
        $this->command->info('Email: pengantin@undangan.test');

        $firstInvitation = Invitation::first();
        if ($firstInvitation && !$pengantin->invitations->contains($firstInvitation->id)) {
            $pengantin->invitations()->attach($firstInvitation->id);
            $this->command->info("✅ Connected to invitation: {$firstInvitation->title}");
        }
    }
}
