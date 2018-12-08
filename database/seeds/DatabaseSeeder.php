<?php

use Illuminate\Database\Seeder;
use App\User;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UsersTableSeeder::class);
        factory(User::class)->create(
            [
                'name' => 'Nelson',
                'email' => 'nelsob44@yahoo.com'
            ]
        );

        factory(User::class)->create(
            [
                'name' => 'Nelly',
                'email' => 'nelly@yahoo.com'
            ]
        );

        factory(User::class)->create(
            [
                'name' => 'David',
                'email' => 'david@yahoo.com'
            ]
        );
    }
}
