<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $departments = Department::pluck('id', 'code');

        $users = [
            [
                'name' => 'Администратор Системы',
                'login' => 'admin',
                'email' => 'admin@docv3.local',
                'password' => Hash::make('admin123'),
                'department_id' => $departments['KANZ'],
                'role' => 'admin',
                'position' => 'Администратор системы',
                'phone' => '+7 701 111 0001',
                'is_active' => true,
            ],
            [
                'name' => 'Петрова Марина Владимировна',
                'login' => 'manager',
                'email' => 'manager@docv3.local',
                'password' => Hash::make('manager123'),
                'department_id' => $departments['KANZ'],
                'role' => 'manager',
                'position' => 'Руководитель аппарата',
                'phone' => '+7 701 111 0002',
                'is_active' => true,
            ],
            [
                'name' => 'Сидорова Анна Николаевна',
                'login' => 'clerk',
                'email' => 'clerk@docv3.local',
                'password' => Hash::make('clerk123'),
                'department_id' => $departments['KANZ'],
                'role' => 'clerk',
                'position' => 'Делопроизводитель',
                'phone' => '+7 701 111 0003',
                'is_active' => true,
            ],
            [
                'name' => 'Козлов Дмитрий Александрович',
                'login' => 'accountant',
                'email' => 'accountant@docv3.local',
                'password' => Hash::make('employee123'),
                'department_id' => $departments['BUH'],
                'role' => 'employee',
                'position' => 'Главный бухгалтер',
                'phone' => '+7 701 111 0004',
                'is_active' => true,
            ],
            [
                'name' => 'Новикова Елена Павловна',
                'login' => 'it-lead',
                'email' => 'it.lead@docv3.local',
                'password' => Hash::make('employee123'),
                'department_id' => $departments['IT'],
                'role' => 'employee',
                'position' => 'Системный администратор',
                'phone' => '+7 701 111 0005',
                'is_active' => true,
            ],
            [
                'name' => 'Кузнецов Антон Романович',
                'login' => 'developer',
                'email' => 'developer@docv3.local',
                'password' => Hash::make('employee123'),
                'department_id' => $departments['IT'],
                'role' => 'employee',
                'position' => 'Разработчик ПО',
                'phone' => '+7 701 111 0006',
                'is_active' => true,
            ],
            [
                'name' => 'Морозов Игорь Викторович',
                'login' => 'legal',
                'email' => 'legal@docv3.local',
                'password' => Hash::make('employee123'),
                'department_id' => $departments['JUR'],
                'role' => 'employee',
                'position' => 'Юрисконсульт',
                'phone' => '+7 701 111 0007',
                'is_active' => true,
            ],
            [
                'name' => 'Васильева Ольга Михайловна',
                'login' => 'hr',
                'email' => 'hr@docv3.local',
                'password' => Hash::make('employee123'),
                'department_id' => $departments['HR'],
                'role' => 'employee',
                'position' => 'Специалист по кадрам',
                'phone' => '+7 701 111 0008',
                'is_active' => true,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(['login' => $user['login']], $user);
        }

        Department::where('code', 'KANZ')->update([
            'head_id' => User::where('login', 'manager')->value('id'),
        ]);
        Department::where('code', 'BUH')->update([
            'head_id' => User::where('login', 'accountant')->value('id'),
        ]);
        Department::where('code', 'IT')->update([
            'head_id' => User::where('login', 'it-lead')->value('id'),
        ]);
        Department::where('code', 'JUR')->update([
            'head_id' => User::where('login', 'legal')->value('id'),
        ]);
        Department::where('code', 'HR')->update([
            'head_id' => User::where('login', 'hr')->value('id'),
        ]);
    }
}
