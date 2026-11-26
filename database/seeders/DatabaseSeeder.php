<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (blank(config('services.operator.password'))) {
            return;
        }

        User::updateOrCreate(['email' => config('services.operator.email')], [
            'name' => 'Operator',
            'password' => config('services.operator.password'),
            'is_operator' => true,
        ]);
    }
}
