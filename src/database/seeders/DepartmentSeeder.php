<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Канцелярия', 'code' => 'KANZ', 'description' => 'Регистрация и движение документов.'],
            ['name' => 'Бухгалтерия', 'code' => 'BUH', 'description' => 'Финансовый и бухгалтерский учет.'],
            ['name' => 'IT отдел', 'code' => 'IT', 'description' => 'Информационные технологии и сопровождение.'],
            ['name' => 'Юридический отдел', 'code' => 'JUR', 'description' => 'Правовое сопровождение и экспертиза.'],
            ['name' => 'Отдел кадров', 'code' => 'HR', 'description' => 'Кадровое делопроизводство и персонал.'],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(['code' => $department['code']], $department);
        }
    }
}
