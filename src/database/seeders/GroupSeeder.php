<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            [
                'name' => 'Комиссия по закупкам',
                'description' => 'Рабочая группа по согласованию закупок.',
                'users' => ['admin', 'manager', 'it-lead'],
            ],
            [
                'name' => 'Юридическая комиссия',
                'description' => 'Группа для юридического согласования документов.',
                'users' => ['admin', 'manager', 'legal'],
            ],
            [
                'name' => 'Проект X',
                'description' => 'Рабочая группа по проекту X.',
                'users' => ['manager', 'it-lead', 'developer', 'hr'],
            ],
        ];

        foreach ($groups as $item) {
            $group = Group::updateOrCreate(
                ['name' => $item['name']],
                ['description' => $item['description']]
            );

            $userIds = User::whereIn('login', $item['users'])->pluck('id');
            $group->users()->sync($userIds);
        }
    }
}
