<?php

use Illuminate\Database\Seeder;
use App\User;
use App\Role;

class UserTableSeeder extends Seeder
{
    public function run()
    {

        $user = new User();
        $user->name = 'SuperAdministrador';
        $user->email = 'admin@smileweb.net';
        $user->password = bcrypt('123123');
        $user->save();

        $role_admin = Role::where('name', 'SuperAdministrador')->first();
        $user->roles()->attach($role_admin);

    }
}