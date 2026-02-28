<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Role;
use App\Models\User;

class AssignAdmin extends Command
{
    protected $signature = 'roles:assign-admin {email : Email pengguna yang akan dijadikan admin}';
    protected $description = 'Assign role admin to a user by email (creates role if missing)';

    public function handle()
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        $role = Role::firstOrCreate(['name' => 'admin'], ['display_name' => 'Administrator']);

        $user->role_id = $role->id;
        $user->save();

        $this->info("Assigned role 'admin' to {$email} (role_id={$role->id}).");
        return 0;
    }
}