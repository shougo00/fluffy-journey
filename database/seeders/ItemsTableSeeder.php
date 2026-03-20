<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItemsTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('items')->insert([
            // 服
            [
                'name' => '赤いシャツ',
                'type' => 'clothes',
                'price' => 100,
                'image_path' => 'items/clothes_red_shirt.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '青いシャツ',
                'type' => 'clothes',
                'price' => 120,
                'image_path' => 'items/clothes_blue_shirt.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // アクセサリー
            [
                'name' => 'サングラス',
                'type' => 'accessory',
                'price' => 150,
                'image_path' => 'items/accessory_sunglasses.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '帽子',
                'type' => 'hat',
                'price' => 80,
                'image_path' => 'items/hat_basic.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
