<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\DocumentStatusHistory;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('login', 'admin')->firstOrFail();
        $manager = User::where('login', 'manager')->firstOrFail();
        $clerk = User::where('login', 'clerk')->firstOrFail();
        $accountant = User::where('login', 'accountant')->firstOrFail();
        $itLead = User::where('login', 'it-lead')->firstOrFail();
        $developer = User::where('login', 'developer')->firstOrFail();

        $procurementGroup = Group::where('name', 'Комиссия по закупкам')->first();

        $documents = [
            [
                'data' => [
                    'number' => 'ВХ-001/2026',
                    'type' => 'incoming',
                    'subject' => 'Запрос на предоставление коммерческого предложения',
                    'description' => 'Компания ТОО "АльфаТрейд" запрашивает коммерческое предложение на поставку офисного оборудования.',
                    'sender_org' => 'ТОО "АльфаТрейд"',
                    'executor_id' => $manager->id,
                    'created_by' => $clerk->id,
                    'status' => 'review',
                    'doc_date' => '2026-04-15',
                    'deadline' => '2026-04-30',
                ],
                'recipient_ids' => [$admin->id],
            ],
            [
                'data' => [
                    'number' => 'ВХ-002/2026',
                    'type' => 'incoming',
                    'subject' => 'Уведомление о налоговой проверке',
                    'description' => 'Налоговый департамент уведомляет о плановой проверке в мае.',
                    'sender_org' => 'Налоговый департамент',
                    'executor_id' => $accountant->id,
                    'created_by' => $clerk->id,
                    'status' => 'registered',
                    'doc_date' => '2026-04-18',
                    'deadline' => '2026-05-05',
                ],
                'recipient_ids' => [$admin->id],
            ],
            [
                'data' => [
                    'number' => 'ИСХ-001/2026',
                    'type' => 'outgoing',
                    'subject' => 'Ответ на запрос о коммерческом предложении',
                    'description' => 'Направляем коммерческое предложение согласно вашему запросу.',
                    'sender_id' => $manager->id,
                    'recipient_orgs' => ['ТОО "АльфаТрейд"'],
                    'created_by' => $clerk->id,
                    'status' => 'approved',
                    'doc_date' => '2026-04-20',
                ],
                'recipient_ids' => [],
            ],
            [
                'data' => [
                    'number' => 'СЗ-001/2026',
                    'type' => 'memo',
                    'subject' => 'Служебная записка о закупке компьютерного оборудования',
                    'description' => 'Просим согласовать закупку пяти рабочих станций для IT отдела.',
                    'sender_id' => $developer->id,
                    'recipient_group_id' => $procurementGroup?->id,
                    'executor_id' => $itLead->id,
                    'created_by' => $developer->id,
                    'status' => 'review',
                    'doc_date' => '2026-04-19',
                    'deadline' => '2026-05-01',
                ],
                'recipient_ids' => [$manager->id],
            ],
            [
                'data' => [
                    'number' => 'ВН-001/2026',
                    'type' => 'internal',
                    'subject' => 'Приказ о введении новых стандартов документооборота',
                    'description' => 'Ввести новый регламент работы с документами с 01.05.2026.',
                    'sender_id' => $admin->id,
                    'created_by' => $admin->id,
                    'status' => 'approved',
                    'doc_date' => '2026-04-10',
                ],
                'recipient_ids' => [],
            ],
        ];

        foreach ($documents as $entry) {
            $doc = Document::updateOrCreate(['number' => $entry['data']['number']], $entry['data']);
            $doc->recipients()->sync($entry['recipient_ids']);
            DocumentStatusHistory::updateOrCreate(
                [
                    'document_id' => $doc->id,
                    'to_status' => $doc->status,
                ],
                [
                    'user_id' => $doc->created_by,
                    'from_status' => null,
                    'comment' => 'Документ создан',
                ]
            );
        }
    }
}
