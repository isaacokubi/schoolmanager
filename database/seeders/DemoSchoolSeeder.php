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

        // Keep the existing test accounts usable while making the seed repeatable.
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@schoolmanager.test'],
            [
                'name' => 'School Administrator',
                'password' => Hash::make('Admin@12345'),
                'role' => 'admin',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
        DB::table('users')->updateOrInsert(
            ['email' => 'manager@schoolmanager.test'],
            [
                'name' => 'Operations Manager',
                'password' => Hash::make('Manager@12345'),
                'role' => 'manager',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $settings = [
            'school_name' => 'Makini Academy',
            'phone' => '0707476586',
            'email' => 'izobrack3@gmail.com',
            'address' => 'P.O. Box 10400-00100, Nairobi, Kenya',
            'currency' => 'KES',
            'academic_year' => '2026',
            'academic_term' => '3',
            'timezone' => 'Africa/Nairobi',
            'mission' => 'To provide a safe, inclusive and inspiring learning environment where every learner can develop academically, socially and creatively.',
            'vision' => 'To nurture responsible, confident and capable young people prepared to contribute positively to society.',
            'values' => 'Integrity, respect, excellence, responsibility, teamwork and lifelong learning.',
        ];
        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => $now, 'created_at' => $now]
            );
        }

        $classData = [
            ['name' => 'Grade 1', 'stream' => 'A'],
            ['name' => 'Grade 2', 'stream' => 'A'],
            ['name' => 'Grade 3', 'stream' => 'A'],
            ['name' => 'Grade 4', 'stream' => 'A'],
            ['name' => 'Grade 5', 'stream' => 'A'],
            ['name' => 'Grade 6', 'stream' => 'A'],
            ['name' => 'Grade 7', 'stream' => 'A'],
            ['name' => 'Grade 8', 'stream' => 'A'],
        ];
        $classes = [];
        foreach ($classData as $data) {
            $id = DB::table('school_classes')->updateOrInsert(
                ['name' => $data['name'], 'academic_year' => 2026],
                ['stream' => $data['stream'], 'updated_at' => $now, 'created_at' => $now]
            );
            $classes[$data['name']] = DB::table('school_classes')->where('name', $data['name'])->where('academic_year', 2026)->value('id');
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

        $studentRows = [
            ['admission_number' => 'MA2026-001', 'name' => 'Amani Mwangi', 'class' => 'Grade 1', 'phone' => '0712345601', 'balance' => 12500],
            ['admission_number' => 'MA2026-002', 'name' => 'Brian Otieno', 'class' => 'Grade 2', 'phone' => '0712345602', 'balance' => 0],
            ['admission_number' => 'MA2026-003', 'name' => 'Cynthia Akinyi', 'class' => 'Grade 3', 'phone' => '0712345603', 'balance' => 7800],
            ['admission_number' => 'MA2026-004', 'name' => 'David Kiptoo', 'class' => 'Grade 4', 'phone' => '0712345604', 'balance' => 15400],
            ['admission_number' => 'MA2026-005', 'name' => 'Eva Njeri', 'class' => 'Grade 5', 'phone' => '0712345605', 'balance' => 4200],
            ['admission_number' => 'MA2026-006', 'name' => 'Felix Kamau', 'class' => 'Grade 6', 'phone' => '0712345606', 'balance' => 0],
            ['admission_number' => 'MA2026-007', 'name' => 'Gloria Chebet', 'class' => 'Grade 7', 'phone' => '0712345607', 'balance' => 9600],
            ['admission_number' => 'MA2026-008', 'name' => 'Hassan Ochieng', 'class' => 'Grade 8', 'phone' => '0712345608', 'balance' => 2300],
            ['admission_number' => 'MA2026-009', 'name' => 'Ivy Wanjiku', 'class' => 'Grade 3', 'phone' => '0712345609', 'balance' => 6100],
            ['admission_number' => 'MA2026-010', 'name' => 'Joel Mwangi', 'class' => 'Grade 5', 'phone' => '0712345610', 'balance' => 11800],
            ['admission_number' => 'MA2026-011', 'name' => 'Kevin Otieno', 'class' => 'Grade 6', 'phone' => '0712345601', 'balance' => 3500],
            ['admission_number' => 'MA2026-012', 'name' => 'Linda Akinyi', 'class' => 'Grade 7', 'phone' => '0712345602', 'balance' => 0],
        ];
        $students = [];
        foreach ($studentRows as $row) {
            DB::table('students')->updateOrInsert(
                ['admission_number' => $row['admission_number']],
                [
                    'parent_id' => $parents[$row['phone']],
                    'class_id' => $classes[$row['class']],
                    'name' => $row['name'],
                    'class_name' => $row['class'],
                    'parent_name' => DB::table('parents')->where('id', $parents[$row['phone']])->value('name'),
                    'parent_phone' => $row['phone'],
                    'fee_balance' => $row['balance'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
            $students[$row['admission_number']] = DB::table('students')->where('admission_number', $row['admission_number'])->value('id');
        }

        $teacherRows = [
            ['name' => 'Alice Wambui', 'email' => 'alice.wambui@example.test', 'phone' => '0722001001', 'employee_number' => 'T001'],
            ['name' => 'George Otieno', 'email' => 'george.otieno@example.test', 'phone' => '0722001002', 'employee_number' => 'T002'],
            ['name' => 'Jane Chebet', 'email' => 'jane.chebet@example.test', 'phone' => '0722001003', 'employee_number' => 'T003'],
            ['name' => 'Mark Kiptoo', 'email' => 'mark.kiptoo@example.test', 'phone' => '0722001004', 'employee_number' => 'T004'],
            ['name' => 'Susan Njeri', 'email' => 'susan.njeri@example.test', 'phone' => '0722001005', 'employee_number' => 'T005'],
            ['name' => 'Paul Kamau', 'email' => 'paul.kamau@example.test', 'phone' => '0722001006', 'employee_number' => 'T006'],
        ];
        $teachers = [];
        foreach ($teacherRows as $row) {
            DB::table('teachers')->updateOrInsert(['employee_number' => $row['employee_number']], $row + ['updated_at' => $now, 'created_at' => $now]);
            $teachers[$row['employee_number']] = DB::table('teachers')->where('employee_number', $row['employee_number'])->value('id');
        }

        $subjectRows = [
            ['name' => 'English', 'code' => 'ENG', 'teacher' => 'T001'],
            ['name' => 'Mathematics', 'code' => 'MAT', 'teacher' => 'T002'],
            ['name' => 'Science & Technology', 'code' => 'SCI', 'teacher' => 'T003'],
            ['name' => 'Social Studies', 'code' => 'SST', 'teacher' => 'T004'],
            ['name' => 'Kiswahili', 'code' => 'KIS', 'teacher' => 'T005'],
            ['name' => 'Creative Arts', 'code' => 'ART', 'teacher' => 'T006'],
        ];
        $subjects = [];
        foreach ($subjectRows as $row) {
            DB::table('subjects')->updateOrInsert(
                ['code' => $row['code']],
                ['name' => $row['name'], 'teacher_id' => $teachers[$row['teacher']], 'updated_at' => $now, 'created_at' => $now]
            );
            $subjects[$row['code']] = DB::table('subjects')->where('code', $row['code'])->value('id');
        }

        $admissions = [
            ['student_name' => 'Naomi Wairimu', 'date_of_birth' => '2018-04-12', 'parent_name' => 'Michael Wairimu', 'parent_phone' => '0733002001', 'parent_email' => 'michael.wairimu@example.test', 'requested_class' => 'Grade 2', 'message' => 'Applying for the 2026 academic year.', 'status' => 'pending'],
            ['student_name' => 'Owen Maina', 'date_of_birth' => '2017-09-03', 'parent_name' => 'Faith Maina', 'parent_phone' => '0733002002', 'parent_email' => 'faith.maina@example.test', 'requested_class' => 'Grade 3', 'message' => 'Interested in the school music programme.', 'status' => 'pending'],
            ['student_name' => 'Purity Achieng', 'date_of_birth' => '2016-11-20', 'parent_name' => 'David Achieng', 'parent_phone' => '0733002003', 'parent_email' => 'david.achieng@example.test', 'requested_class' => 'Grade 4', 'message' => 'Transfer application.', 'status' => 'approved'],
            ['student_name' => 'Quincy Kiprotich', 'date_of_birth' => '2015-07-18', 'parent_name' => 'Lilian Kiprotich', 'parent_phone' => '0733002004', 'parent_email' => 'lilian.kiprotich@example.test', 'requested_class' => 'Grade 5', 'message' => 'Transfer from another school.', 'status' => 'rejected'],
            ['student_name' => 'Rita Wanjiru', 'date_of_birth' => '2014-02-09', 'parent_name' => 'John Wanjiru', 'parent_phone' => '0733002005', 'parent_email' => 'john.wanjiru@example.test', 'requested_class' => 'Grade 6', 'message' => 'Seeking a strong academic programme.', 'status' => 'approved'],
            ['student_name' => 'Sharon Atieno', 'date_of_birth' => '2013-06-22', 'parent_name' => 'Mercy Atieno', 'parent_phone' => '0733002006', 'parent_email' => 'mercy.atieno@example.test', 'requested_class' => 'Grade 7', 'message' => 'Application for junior secondary.', 'status' => 'pending'],
        ];
        foreach ($admissions as $index => $row) {
            DB::table('admission_applications')->updateOrInsert(
                ['student_name' => $row['student_name'], 'parent_phone' => $row['parent_phone']],
                $row + ['created_at' => $now->copy()->subDays(5 - $index), 'updated_at' => $now]
            );
        }

        $paymentRows = [
            ['student' => 'MA2026-001', 'amount' => 25000, 'type' => 'Tuition', 'receipt' => 'QAB001ABC', 'status' => 'paid', 'days' => 20],
            ['student' => 'MA2026-002', 'amount' => 35000, 'type' => 'Tuition', 'receipt' => 'QAB002ABC', 'status' => 'paid', 'days' => 18],
            ['student' => 'MA2026-003', 'amount' => 30000, 'type' => 'Tuition', 'receipt' => 'QAB003ABC', 'status' => 'paid', 'days' => 15],
            ['student' => 'MA2026-004', 'amount' => 18000, 'type' => 'Tuition', 'receipt' => 'QAB004ABC', 'status' => 'paid', 'days' => 12],
            ['student' => 'MA2026-005', 'amount' => 22000, 'type' => 'Tuition', 'receipt' => 'QAB005ABC', 'status' => 'paid', 'days' => 10],
            ['student' => 'MA2026-006', 'amount' => 40000, 'type' => 'Tuition', 'receipt' => 'QAB006ABC', 'status' => 'paid', 'days' => 8],
            ['student' => 'MA2026-007', 'amount' => 27000, 'type' => 'Tuition', 'receipt' => 'QAB007ABC', 'status' => 'paid', 'days' => 6],
            ['student' => 'MA2026-008', 'amount' => 31000, 'type' => 'Tuition', 'receipt' => 'QAB008ABC', 'status' => 'paid', 'days' => 4],
            ['student' => 'MA2026-009', 'amount' => 19500, 'type' => 'Transport', 'receipt' => 'QAB009ABC', 'status' => 'paid', 'days' => 3],
            ['student' => 'MA2026-010', 'amount' => 15000, 'type' => 'Meals', 'receipt' => 'QAB010ABC', 'status' => 'paid', 'days' => 2],
            ['student' => 'MA2026-011', 'amount' => 12500, 'type' => 'Tuition', 'receipt' => null, 'status' => 'pending', 'days' => 1],
            ['student' => 'MA2026-012', 'amount' => 28000, 'type' => 'Tuition', 'receipt' => null, 'status' => 'failed', 'days' => 1],
        ];
        foreach ($paymentRows as $row) {
            $studentId = $students[$row['student']];
            $data = [
                'student_id' => $studentId,
                'parent_phone' => DB::table('students')->where('id', $studentId)->value('parent_phone'),
                'payment_type' => $row['type'],
                'amount' => $row['amount'],
                'account_reference' => $row['student'],
                'checkout_request_id' => 'ws_CO_' . $row['student'],
                'merchant_request_id' => 'mr_' . $row['student'],
                'mpesa_receipt' => $row['receipt'],
                'status' => $row['status'],
                'paid_at' => $row['status'] === 'paid' ? $now->copy()->subDays($row['days']) : null,
                'updated_at' => $now,
                'created_at' => $now->copy()->subDays($row['days']),
            ];
            DB::table('payments')->updateOrInsert(['account_reference' => $row['student'], 'payment_type' => $row['type'], 'amount' => $row['amount']], $data);
        }

        // Attendance for the last seven school days, deliberately covering every status.
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
            ['name' => 'Term 2 Mid-Term Assessment', 'term' => 'Term 2', 'start_date' => '2026-05-11', 'end_date' => '2026-05-15'],
            ['name' => 'Term 3 End-Term Examination', 'term' => 'Term 3', 'start_date' => '2026-09-21', 'end_date' => '2026-09-25'],
        ];
        $exams = [];
        foreach ($examRows as $row) {
            DB::table('exams')->updateOrInsert(
                ['name' => $row['name'], 'academic_year' => 2026],
                $row + ['academic_year' => 2026, 'updated_at' => $now, 'created_at' => $now]
            );
            $exams[$row['name']] = DB::table('exams')->where('name', $row['name'])->where('academic_year', 2026)->value('id');
        }

        $subjectCodes = array_keys($subjects);
        $studentIds = array_values($students);
        foreach ($studentIds as $studentIndex => $studentId) {
            foreach ($exams as $examIndex => $examId) {
                foreach ($subjectCodes as $subjectIndex => $code) {
                    $marks = 55 + (($studentIndex * 7 + $subjectIndex * 5 + $examIndex * 8) % 43);
                    $grade = $marks >= 80 ? 'A' : ($marks >= 70 ? 'B' : ($marks >= 60 ? 'C' : ($marks >= 50 ? 'D' : 'E')));
                    DB::table('results')->updateOrInsert(
                        ['exam_id' => $examId, 'student_id' => $studentId, 'subject_id' => $subjects[$code]],
                        ['marks' => $marks, 'grade' => $grade, 'remarks' => $marks >= 80 ? 'Excellent performance.' : ($marks >= 60 ? 'Good progress.' : 'Needs additional support.'), 'updated_at' => $now, 'created_at' => $now]
                    );
                }
            }
        }

        $announcements = [
            ['title' => 'Welcome to Term 3', 'body' => 'We welcome all learners and parents back for Term 3. Please review the academic calendar and fee balances.', 'published' => true, 'days' => 2],
            ['title' => 'Parent-Teacher Consultation Day', 'body' => 'Parents are invited to meet class teachers on Friday from 9:00 AM to 2:00 PM.', 'published' => true, 'days' => 4],
            ['title' => 'Library Reading Challenge', 'body' => 'Learners are encouraged to complete at least two books this month and record their reading progress.', 'published' => true, 'days' => 7],
            ['title' => 'Staff Development Workshop', 'body' => 'A professional development workshop will be held for all teaching staff.', 'published' => false, 'days' => 1],
        ];
        foreach ($announcements as $row) {
            DB::table('announcements')->updateOrInsert(
                ['title' => $row['title']],
                ['body' => $row['body'], 'published' => $row['published'], 'published_at' => $row['published'] ? $now->copy()->subDays($row['days']) : null, 'updated_at' => $now, 'created_at' => $now->copy()->subDays($row['days'])]
            );
        }

        $events = [
            ['title' => 'Term 3 Opening Day', 'event_date' => '2026-09-07', 'location' => 'Main School Campus', 'description' => 'Official opening of Term 3 and learner orientation.'],
            ['title' => 'Parents Association Meeting', 'event_date' => '2026-09-12', 'location' => 'School Hall', 'description' => 'Parents meet school leadership to review term priorities.'],
            ['title' => 'Inter-Class Sports Day', 'event_date' => '2026-09-19', 'location' => 'School Sports Ground', 'description' => 'A full day of athletics, football and team activities.'],
            ['title' => 'Mid-Term Academic Clinic', 'event_date' => '2026-10-09', 'location' => 'Classrooms', 'description' => 'Learner support and parent consultation sessions.'],
            ['title' => 'Prize Giving Day', 'event_date' => '2026-11-27', 'location' => 'School Hall', 'description' => 'Celebrating academic, sporting and leadership achievements.'],
        ];
        foreach ($events as $row) {
            DB::table('events')->updateOrInsert(['title' => $row['title'], 'event_date' => $row['event_date']], $row + ['updated_at' => $now, 'created_at' => $now]);
        }
    }
}
