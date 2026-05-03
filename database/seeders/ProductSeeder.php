<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test user
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'role' => 'user',
            ]
        );

        // Create an admin user
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('!#%ADMIN456hg'),
                'role' => 'admin',
            ]
        );

        $products = [
            ['name' => 'Nordic Chair', 'price' => 120.00, 'stock' => 10],
            ['name' => 'Minimalist Table', 'price' => 250.00, 'stock' => 5],
            ['name' => 'Scandi Lamp', 'price' => 45.00, 'stock' => 20],
            ['name' => 'Wool Rug', 'price' => 85.00, 'stock' => 15],
            ['name' => 'Wooden Shelf', 'price' => 110.00, 'stock' => 8],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
