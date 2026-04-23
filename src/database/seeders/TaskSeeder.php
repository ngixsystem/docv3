<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('login', 'admin')->firstOrFail();
        $manager = User::where('login', 'manager')->firstOrFail();
        $clerk = User::where('login', 'clerk')->firstOrFail();
        $accountant = User::where('login', 'accountant')->firstOrFail();
        $itLead = User::where('login', 'it-lead')->firstOrFail();

        $incomingDoc = Document::where('number', 'ВХ-001/2026')->first();
        $memoDoc = Document::where('number', 'СЗ-001/2026')->first();
        $internalDoc = Document::where('number', 'ВН-001/2026')->first();

        $tasks = [
            [
                'title' => 'Подготовить ответ на входящий запрос',
                'description' => 'Составить и согласовать коммерческое предложение.',
                'assignee_id' => $accountant->id,
                'created_by' => $manager->id,
                'document_id' => $incomingDoc?->id,
                'status' => 'in_progress',
                'priority' => 'high',
                'deadline' => '2026-04-25',
            ],
            [
                'title' => 'Провести инвентаризацию оргтехники',
                'description' => 'Пересчитать оборудование и подготовить акт.',
                'assignee_id' => $itLead->id,
                'created_by' => $admin->id,
                'document_id' => $internalDoc?->id,
                'status' => 'new',
                'priority' => 'urgent',
                'deadline' => '2026-04-20',
            ],
            [
                'title' => 'Разработать регламент архивирования',
                'description' => 'Подготовить правила работы с архивными документами.',
                'assignee_id' => $clerk->id,
                'created_by' => $admin->id,
                'document_id' => null,
                'status' => 'new',
                'priority' => 'medium',
                'deadline' => '2026-05-10',
            ],
            [
                'title' => 'Оценить бюджет закупки',
                'description' => 'Подготовить финансовое обоснование закупки техники.',
                'assignee_id' => $accountant->id,
                'created_by' => $manager->id,
                'document_id' => $memoDoc?->id,
                'status' => 'in_progress',
                'priority' => 'medium',
                'deadline' => '2026-04-28',
            ],
        ];

        foreach ($tasks as $task) {
            Task::updateOrCreate(
                ['title' => $task['title']],
                $task
            );
        }
    }
}
