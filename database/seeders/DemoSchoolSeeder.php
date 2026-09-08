<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSchoolSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        DB::table('users')->updateOrInsert(['email' => 'admin@schoolmanager.test'], [
            'name' => 'School Administrator',
            'password' => Hash::make('Admin@12345'),
            'role' => 'admin',
            'updated_at' => $now,
            'created_at' => $now,
        ]);
        DB::table('users')->updateOrInsert(['email' => 'manager@schoolmanager.test'], [
            'name' => 'Operations Manager',
            'password' => Hash::make('Manager@12345'),
            'role' => 'manager',
            'updated_at' => $now,
            'created_at' => $now,
        ]);

        $settings = [
            'school_name' => 'Makini Academy',
            'phone' => '0707476586',
            'email' => 'izobrack3@gmail.com',
            'address' => 'P.O. Box 10400-00100, Nairobi, Kenya',
            'school_phone' => '0707476586',
            'school_email' => 'izobrack3@gmail.com',
            'school_address' => 'P.O. Box 10400-00100, Nairobi, Kenya',
            'currency' => 'KES',
            'academic_year' => '2026',
            'academic_term' => '3',
            'timezone' => 'Africa/Nairobi',
            'mission' => 'To provide a safe, inclusive and inspiring learning environment where every learner can develop academically, socially and creatively.',
            'vision' => 'To nurture responsible, confident and capable young people prepared to contribute positively to society.',
            'values' => 'Integrity, respect, excellence, responsibility, teamwork and lifelong learning.',
        ];
        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(['key' => $key], [
                'value' => $value,
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }

        $classes = [];
        foreach (range(1, 8) as $grade) {
            $name = 'Grade ' . $grade;
            DB::table('school_classes')->updateOrInsert(
                ['name' => $name, 'academic_year' => 2026],
                ['stream' => 'A', 'updated_at' => $now, 'created_at' => $now]
            );
            $classes[$name] = DB::table('school_classes')->where('name', $name)->where('academic_year', 2026)->value('id');
        }

        $parentRows = [
            ['name' => 'James Mwangi', 'phone' => '0712345601', 'email' => 'james.mwangi@example.test', 'relationship' => 'Father'],
            ['name' => 'Mary Wanjiku', 'phone' => '0712345602', 'email' => 'mary.wanjiku@example.test', 'relationship' => 'Mother'],
            ['name' => 'Peter Otieno', 'phone' => '0712345603', 'email' => 'peter.otieno@example.test', 'relationship' => 'Father'],
            ['name' => 'Grace Akinyi', 'phone' => '0712345604', 'email' => 'grace.akinyi@example.test', 'relationship' => 'Mother'],
            ['name' => 'Daniel Kiptoo', 'phone' => '0712345605', 'email' => 'daniel.kiptoo@example.test', 'relationship' => 'Father'],
            ['name' => 'Esther Njeri', 'phone' => '0712345606', 'email' => 'esther.njeri@example.test', 'relationship' => 'Mother'],
            ['name' => 'Samuel Kamau', 'phone' => '0712345607', 'email' => 'samuel.kamau@example.test', 'relationship' => 'Guardian'],
            ['name' => 'Lucy Atieno', 'phone' => '0712345608', 'email' => 'lucy.atieno@example.test', 'relationship' => 'Mother'],
            ['name' => 'Brian Ochieng', 'phone' => '0712345609', 'email' => 'brian.ochieng@example.test', 'relationship' => 'Father'],
            ['name' => 'Ruth Chebet', 'phone' => '0712345610', 'email' => 'ruth.chebet@example.test', 'relationship' => 'Mother'],
        ];
        $parents = [];
        foreach ($parentRows as $row) {
            DB::table('parents')->updateOrInsert(['phone' => $row['phone']], $row + ['updated_at' => $now, 'created_at' => $now]);
            $parents[$row['phone']] = DB::table('parents')->where('phone', $row['phone'])->value('id');
        }

        $students = [];
        $studentRows = [
            ['Amani Mwangi', 'Grade 1', '0712345601', 12500], ['Brian Otieno', 'Grade 2', '0712345602', 0],
            ['Cynthia Akinyi', 'Grade 3', '0712345603', 7800], ['David Kiptoo', 'Grade 4', '0712345604', 15400],
            ['Eva Njeri', 'Grade 5', '0712345605', 4200], ['Felix Kamau', 'Grade 6', '0712345606', 0],
            ['Gloria Chebet', 'Grade 7', '0712345607', 9600], ['Hassan Ochieng', 'Grade 8', '0712345608', 2300],
            ['Ivy Wanjiku', 'Grade 3', '0712345609', 6100], ['Joel Mwangi', 'Grade 5', '0712345610', 11800],
            ['Kevin Otieno', 'Grade 6', '0712345601', 3500], ['Linda Akinyi', 'Grade 7', '0712345602', 0],
        ];
        foreach ($studentRows as $index => $row) {
            $admission = sprintf('MA2026-%03d', $index + 1);
            DB::table('students')->updateOrInsert(['admission_number' => $admission], [
                'parent_id' => $parents[$row[2]],
                'class_id' => $classes[$row[1]],
                'name' => $row[0],
                'class_name' => $row[1],
                'parent_name' => DB::table('parents')->where('id', $parents[$row[2]])->value('name'),
                'parent_phone' => $row[2],
                'fee_balance' => $row[3],
                'updated_at' => $now,
                'created_at' => $now,
            ]);
            $students[$admission] = DB::table('students')->where('admission_number', $admission)->value('id');
        }

        $teacherRows = [
            ['Alice Wambui', 'alice.wambui@example.test', '0722001001', 'T001'], ['George Otieno', 'george.otieno@example.test', '0722001002', 'T002'],
            ['Jane Chebet', 'jane.chebet@example.test', '0722001003', 'T003'], ['Mark Kiptoo', 'mark.kiptoo@example.test', '0722001004', 'T004'],
            ['Susan Njeri', 'susan.njeri@example.test', '0722001005', 'T005'], ['Paul Kamau', 'paul.kamau@example.test', '0722001006', 'T006'],
        ];
        $teachers = [];
        foreach ($teacherRows as $row) {
            DB::table('teachers')->updateOrInsert(['employee_number' => $row[3]], [
                'name' => $row[0], 'email' => $row[1], 'phone' => $row[2], 'updated_at' => $now, 'created_at' => $now,
            ]);
            $teachers[$row[3]] = DB::table('teachers')->where('employee_number', $row[3])->value('id');
        }

        $subjectRows = [
            ['English', 'ENG', 'T001'], ['Mathematics', 'MAT', 'T002'], ['Science & Technology', 'SCI', 'T003'],
            ['Social Studies', 'SST', 'T004'], ['Kiswahili', 'KIS', 'T005'], ['Creative Arts', 'ART', 'T006'],
        ];
        $subjects = [];
        foreach ($subjectRows as $row) {
            DB::table('subjects')->updateOrInsert(['code' => $row[1]], [
                'name' => $row[0], 'teacher_id' => $teachers[$row[2]], 'updated_at' => $now, 'created_at' => $now,
            ]);
            $subjects[$row[1]] = DB::table('subjects')->where('code', $row[1])->value('id');
        }

        $admissionRows = [
            ['Naomi Wairimu', '2018-04-12', 'Michael Wairimu', '0733002001', 'michael.wairimu@example.test', 'Grade 2', 'pending'],
            ['Owen Maina', '2017-09-03', 'Faith Maina', '0733002002', 'faith.maina@example.test', 'Grade 3', 'pending'],
            ['Purity Achieng', '2016-11-20', 'David Achieng', '0733002003', 'david.achieng@example.test', 'Grade 4', 'approved'],
            ['Quincy Kiprotich', '2015-07-18', 'Lilian Kiprotich', '0733002004', 'lilian.kiprotich@example.test', 'Grade 5', 'rejected'],
            ['Rita Wanjiru', '2014-02-09', 'John Wanjiru', '0733002005', 'john.wanjiru@example.test', 'Grade 6', 'approved'],
            ['Sharon Atieno', '2013-06-22', 'Mercy Atieno', '0733002006', 'mercy.atieno@example.test', 'Grade 7', 'pending'],
        ];
        foreach ($admissionRows as $i => $row) {
            DB::table('admission_applications')->updateOrInsert(
                ['student_name' => $row[0], 'parent_phone' => $row[3]],
                ['date_of_birth' => $row[1], 'parent_name' => $row[2], 'parent_email' => $row[4], 'requested_class' => $row[5], 'message' => 'Demo application for system testing.', 'status' => $row[6], 'created_at' => $now->copy()->subDays(5 - $i), 'updated_at' => $now]
            );
        }

        $paymentRows = [
            ['MA2026-001',25000,'Tuition','QAB001ABC','paid',20], ['MA2026-002',35000,'Tuition','QAB002ABC','paid',18],
            ['MA2026-003',30000,'Tuition','QAB003ABC','paid',15], ['MA2026-004',18000,'Tuition','QAB004ABC','paid',12],
            ['MA2026-005',22000,'Tuition','QAB005ABC','paid',10], ['MA2026-006',40000,'Tuition','QAB006ABC','paid',8],
            ['MA2026-007',27000,'Tuition','QAB007ABC','paid',6], ['MA2026-008',31000,'Tuition','QAB008ABC','paid',4],
            ['MA2026-009',19500,'Transport','QAB009ABC','paid',3], ['MA2026-010',15000,'Meals','QAB010ABC','paid',2],
            ['MA2026-011',12500,'Tuition',null,'pending',1], ['MA2026-012',28000,'Tuition',null,'failed',1],
        ];
        foreach ($paymentRows as $row) {
            $studentId = $students[$row[0]];
            DB::table('payments')->updateOrInsert(
                ['account_reference' => $row[0], 'payment_type' => $row[2], 'amount' => $row[1]],
                [
                    'student_id' => $studentId,
                    'parent_phone' => DB::table('students')->where('id', $studentId)->value('parent_phone'),
                    'payment_type' => $row[2], 'amount' => $row[1], 'account_reference' => $row[0],
                    'checkout_request_id' => 'ws_CO_' . $row[0], 'merchant_request_id' => 'mr_' . $row[0],
                    'mpesa_receipt' => $row[3], 'status' => $row[4],
                    'paid_at' => $row[4] === 'paid' ? $now->copy()->subDays((int) $row[5]) : null,
                    'updated_at' => $now, 'created_at' => $now->copy()->subDays((int) $row[5]),
                ]
            );
        }

        $statuses = ['present', 'present', 'present', 'late', 'excused', 'absent', 'present'];
        foreach (array_values($students) as $studentIndex => $studentId) {
            for ($day = 0; $day < 7; $day++) {
                $date = $now->copy()->subDays($day + 1)->toDateString();
                $status = $statuses[($studentIndex + $day) % count($statuses)];
                DB::table('attendance')->updateOrInsert(
                    ['student_id' => $studentId, 'attendance_date' => $date],
                    ['status' => $status, 'notes' => $status === 'late' ? 'Arrived after morning assembly.' : ($status === 'excused' ? 'Approved absence.' : null), 'updated_at' => $now, 'created_at' => $now]
                );
            }
        }

        $examRows = [
            ['Term 2 Mid-Term Assessment', 'Term 2', '2026-05-11', '2026-05-15'],
            ['Term 3 End-Term Examination', 'Term 3', '2026-09-21', '2026-09-25'],
        ];
        $exams = [];
        foreach ($examRows as $row) {
            DB::table('exams')->updateOrInsert(['name' => $row[0], 'academic_year' => 2026], [
                'term' => $row[1], 'start_date' => $row[2], 'end_date' => $row[3], 'updated_at' => $now, 'created_at' => $now,
            ]);
            $exams[] = DB::table('exams')->where('name', $row[0])->where('academic_year', 2026)->value('id');
        }
        $subjectIds = array_values($subjects);
        foreach (array_values($students) as $studentIndex => $studentId) {
            foreach (array_values($exams) as $examIndex => $examId) {
                foreach ($subjectIds as $subjectIndex => $subjectId) {
                    $marks = 55 + (($studentIndex * 7 + $subjectIndex * 5 + $examIndex * 8) % 43);
                    $grade = $marks >= 80 ? 'A' : ($marks >= 70 ? 'B' : ($marks >= 60 ? 'C' : ($marks >= 50 ? 'D' : 'E')));
                    DB::table('results')->updateOrInsert(
                        ['exam_id' => $examId, 'student_id' => $studentId, 'subject_id' => $subjectId],
                        ['marks' => $marks, 'grade' => $grade, 'remarks' => $marks >= 80 ? 'Excellent performance.' : ($marks >= 60 ? 'Good progress.' : 'Needs additional support.'), 'updated_at' => $now, 'created_at' => $now]
                    );
                }
            }
        }

        $announcements = [
            ['Welcome to Term 3', 'We welcome all learners and parents back for Term 3. Please review the academic calendar and fee balances.', true, 2],
            ['Parent-Teacher Consultation Day', 'Parents are invited to meet class teachers on Friday from 9:00 AM to 2:00 PM.', true, 4],
            ['Library Reading Challenge', 'Learners are encouraged to complete at least two books this month and record their reading progress.', true, 7],
            ['Staff Development Workshop', 'A professional development workshop will be held for all teaching staff.', false, 1],
        ];
        foreach ($announcements as $row) {
            DB::table('announcements')->updateOrInsert(['title' => $row[0]], [
                'body' => $row[1], 'published' => $row[2], 'published_at' => $row[2] ? $now->copy()->subDays($row[3]) : null,
                'updated_at' => $now, 'created_at' => $now->copy()->subDays($row[3]),
            ]);
        }

        $events = [
            ['Term 3 Opening Day', $now->copy()->addDays(3)->toDateString(), 'School Hall', 'Opening and orientation for Term 3.'],
            ['Parent-Teacher Consultation Day', $now->copy()->addDays(7)->toDateString(), 'Main Campus', 'Parents meet class teachers.'],
            ['Inter-House Sports Day', $now->copy()->addDays(14)->toDateString(), 'School Field', 'Annual athletics and team competitions.'],
            ['Science and Innovation Fair', $now->copy()->addDays(21)->toDateString(), 'Science Block', 'Learners showcase practical projects.'],
            ['Prize Giving Day', $now->copy()->addDays(35)->toDateString(), 'Main Hall', 'Celebration of learner achievement.'],
        ];
        foreach ($events as $row) {
            DB::table('events')->updateOrInsert(['title' => $row[0], 'event_date' => $row[1]], [
                'location' => $row[2], 'description' => $row[3], 'updated_at' => $now, 'created_at' => $now,
            ]);
        }
    }
}
