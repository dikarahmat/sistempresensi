<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseOverhaulSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        
        DB::table('attendances')->truncate();
        DB::table('school_classes')->update(['teacher_id' => null]);
        DB::table('students')->truncate();
        DB::table('teachers')->truncate();
        
        DB::statement('ALTER TABLE students AUTO_INCREMENT = 1;');
        DB::statement('ALTER TABLE teachers AUTO_INCREMENT = 1;');
        
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
}
