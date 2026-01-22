<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::firstOrCreate(['title_en' => 'English Alphabet'], ['title_ar' => 'الأبجدية الإنجليزية']);
        Category::firstOrCreate(['title_en' => 'The World of Numbers'], ['title_ar' => 'عالم الأرقام']);
        Category::firstOrCreate(['title_en' => 'Fun & Learn'], ['title_ar' => 'مرح وتعلّم']);
        Category::firstOrCreate(['title_en' => 'Did You Know'], ['title_ar' => 'هل تعلم؟']);
        Category::firstOrCreate(['title_en' => 'Oba & Zuri’s Adventures'], ['title_ar' => 'مغامرات سالم و ندى']);
        Category::firstOrCreate(['title_en' => 'Story Time'], ['title_ar' => 'وقت القصة']);
        Category::firstOrCreate(['title_en' => 'Crafts & DIY'], ['title_ar' => 'مهارات يدوية']);
        Category::firstOrCreate(['title_en' => 'Clay Play'], ['title_ar' => 'فنّ الصلصال']);
        Category::firstOrCreate(['title_en' => 'let\'s draw '], ['title_ar' => 'هيا نرسم']);
    }
}
