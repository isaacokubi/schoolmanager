<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OperationsFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name'=>'Operations Admin','email'=>'operations-admin@example.test','password'=>Hash::make('Password123!'),'role'=>'admin']);
    }

    private function manager(): User
    {
        return User::create(['name'=>'Operations Manager','email'=>'operations-manager@example.test','password'=>Hash::make('Password123!'),'role'=>'manager']);
    }

    private function student(string $admission='S-001'): int
    {
        return DB::table('students')->insertGetId(['admission_number'=>$admission,'name'=>'Test Learner','parent_name'=>'Test Parent','parent_phone'=>'+254712345678','fee_balance'=>1000,'created_at'=>now(),'updated_at'=>now()]);
    }

    public function test_admin_can_open_every_operations_section(): void
    {
        $admin=$this->admin();
        foreach(['parents','classes','teachers','subjects','attendance','exams','results','announcements','events'] as $section) $this->actingAs($admin)->get(route('admin.operations',['section'=>$section]))->assertOk();
    }

    public function test_manager_can_open_every_operations_section(): void
    {
        $manager=$this->manager();
        foreach(['parents','classes','teachers','subjects','attendance','exams','results','announcements','events'] as $section) $this->actingAs($manager)->get(route('admin.operations',['section'=>$section]))->assertOk();
    }

    public function test_manager_can_manage_school_operations_but_not_admin_only_areas(): void
    {
        $manager=$this->manager();
        $this->actingAs($manager)->post(route('admin.operations.store'),[
            'section'=>'teachers',
            'name'=>'Manager Created Teacher',
            'email'=>'manager.teacher@example.test',
            'phone'=>'0712345678',
            'employee_number'=>'MGR-TCH-001',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('teachers',[
            'name'=>'Manager Created Teacher',
            'employee_number'=>'MGR-TCH-001',
        ]);

        $this->actingAs($manager)->get(route('admin.settings'))->assertForbidden();
        $this->actingAs($manager)->get(route('admin.payments.index'))->assertForbidden();
    }

    public function test_non_admin_cannot_use_operations(): void
    {
        $user=User::create(['name'=>'Portal User','email'=>'portal@example.test','password'=>Hash::make('Password123!'),'role'=>'parent']);
        $this->actingAs($user)->get(route('admin.operations'))->assertForbidden();
    }

    public function test_parent_can_be_created_linked_and_archived(): void
    {
        $admin=$this->admin(); $student=$this->student();
        $this->actingAs($admin)->post(route('admin.operations.store'),['section'=>'parents','name'=>'Grace Wanjiku','phone'=>'0712345678','email'=>'grace@example.test','relationship'=>'Mother','student_id'=>$student])->assertSessionHas('success');
        $parent=DB::table('parents')->where('phone','0712345678')->first();
        $this->assertNotNull($parent); $this->assertDatabaseHas('students',['id'=>$student,'parent_id'=>$parent->id]);
        $this->actingAs($admin)->delete(route('admin.operations.destroy',$parent->id),['section'=>'parents'])->assertSessionHas('success');
        $this->assertNotNull(DB::table('parents')->where('id',$parent->id)->value('archived_at'));
        $this->assertSame(0,DB::table('parents')->where('id',$parent->id)->whereNull('archived_at')->count());
    }

    public function test_teacher_and_subject_enforce_active_unique_identifiers(): void
    {
        $admin=$this->admin();
        DB::table('teachers')->insert(['name'=>'Existing Teacher','employee_number'=>'TCH-001','created_at'=>now(),'updated_at'=>now()]);
        DB::table('subjects')->insert(['name'=>'Mathematics','code'=>'MAT','created_at'=>now(),'updated_at'=>now()]);
        $this->actingAs($admin)->post(route('admin.operations.store'),['section'=>'teachers','name'=>'Duplicate Teacher','employee_number'=>'TCH-001'])->assertSessionHasErrors('employee_number');
        $this->actingAs($admin)->post(route('admin.operations.store'),['section'=>'subjects','name'=>'Duplicate Mathematics','code'=>'MAT'])->assertSessionHasErrors('code');
    }

    public function test_teacher_and_subject_can_be_created_updated_and_archived(): void
    {
        $admin=$this->admin();
        $this->actingAs($admin)->post(route('admin.operations.store'),['section'=>'teachers','name'=>'Jane Wanjiku','email'=>'jane@example.test','phone'=>'0712345678','employee_number'=>'TCH-010'])->assertSessionHas('success');
        $teacher=DB::table('teachers')->where('employee_number','TCH-010')->first();
        $this->actingAs($admin)->put(route('admin.operations.update',$teacher->id),['section'=>'teachers','name'=>'Jane Wanjiku Updated','email'=>'jane.updated@example.test','phone'=>'0712345678','employee_number'=>'TCH-010'])->assertSessionHas('success');
        $this->assertDatabaseHas('teachers',['id'=>$teacher->id,'name'=>'Jane Wanjiku Updated']);
        $this->actingAs($admin)->post(route('admin.operations.store'),['section'=>'subjects','name'=>'English','code'=>'ENG','teacher_id'=>$teacher->id])->assertSessionHas('success');
        $subject=DB::table('subjects')->where('code','ENG')->first();
        $this->actingAs($admin)->put(route('admin.operations.update',$subject->id),['section'=>'subjects','name'=>'English Language','code'=>'ENG','teacher_id'=>$teacher->id])->assertSessionHas('success');
        $this->actingAs($admin)->delete(route('admin.operations.destroy',$subject->id),['section'=>'subjects'])->assertSessionHas('success');
        $this->assertNotNull(DB::table('subjects')->where('id',$subject->id)->value('archived_at'));
    }

    public function test_classes_validate_teacher_and_can_be_archived(): void
    {
        $admin=$this->admin();
        $teacher=DB::table('teachers')->insertGetId(['name'=>'Class Teacher','employee_number'=>'TCH-020','created_at'=>now(),'updated_at'=>now()]);
        $this->actingAs($admin)->post(route('admin.operations.store'),['section'=>'classes','name'=>'Grade 6','stream'=>'East','academic_year'=>2026,'class_teacher_id'=>$teacher])->assertSessionHas('success');
        $class=DB::table('school_classes')->where('name','Grade 6')->first();
        $this->assertEquals($teacher,$class->class_teacher_id);
        $this->actingAs($admin)->delete(route('admin.operations.destroy',$class->id),['section'=>'classes'])->assertSessionHas('success');
        $this->assertNotNull(DB::table('school_classes')->where('id',$class->id)->value('archived_at'));
    }

    public function test_assessment_and_announcement_workflows_preserve_status(): void
    {
        $admin=$this->admin();
        $this->actingAs($admin)->post(route('admin.operations.store'),['section'=>'exams','name'=>'Term 3 Assessment','term'=>'Term 3','academic_year'=>2026,'start_date'=>'2026-09-20','end_date'=>'2026-09-25'])->assertSessionHas('success');
        $exam=DB::table('exams')->where('name','Term 3 Assessment')->first();
        $this->actingAs($admin)->post(route('admin.operations.store'),['section'=>'announcements','title'=>'Parent Meeting','body'=>'The parent meeting will be held on Friday.','published'=>1])->assertSessionHas('success');
        $announcement=DB::table('announcements')->where('title','Parent Meeting')->first();
        $this->assertTrue((bool)$announcement->published); $this->assertNotNull($announcement->published_at);
        $this->actingAs($admin)->put(route('admin.operations.update',$announcement->id),['section'=>'announcements','title'=>'Parent Meeting Updated','body'=>'Updated meeting notice.'])->assertSessionHas('success');
        $announcement=DB::table('announcements')->find($announcement->id);
        $this->assertFalse((bool)$announcement->published); $this->assertNull($announcement->published_at);
        $this->actingAs($admin)->delete(route('admin.operations.destroy',$exam->id),['section'=>'exams'])->assertSessionHas('success');
        $this->assertNotNull(DB::table('exams')->where('id',$exam->id)->value('archived_at'));
    }

    public function test_event_can_be_created_updated_and_archived(): void
    {
        $admin=$this->admin();
        $this->actingAs($admin)->post(route('admin.operations.store'),['section'=>'events','title'=>'Sports Day','event_date'=>'2026-10-10','location'=>'Main Field','description'=>'Annual school sports day.'])->assertSessionHas('success');
        $event=DB::table('events')->where('title','Sports Day')->first();
        $this->actingAs($admin)->put(route('admin.operations.update',$event->id),['section'=>'events','title'=>'Annual Sports Day','event_date'=>'2026-10-11','location'=>'Main Field','description'=>'Updated sports day notice.'])->assertSessionHas('success');
        $this->assertDatabaseHas('events',['id'=>$event->id,'title'=>'Annual Sports Day']);
        $this->actingAs($admin)->delete(route('admin.operations.destroy',$event->id),['section'=>'events'])->assertSessionHas('success');
        $this->assertNotNull(DB::table('events')->where('id',$event->id)->value('archived_at'));
    }

    public function test_archived_related_records_are_not_displayed_as_active_context(): void
    {
        $admin=$this->admin();
        $teacher=DB::table('teachers')->insertGetId(['name'=>'Archived Teacher','employee_number'=>'TCH-ARCH','created_at'=>now(),'updated_at'=>now(),'archived_at'=>now()]);
        $subject=DB::table('subjects')->insertGetId(['name'=>'Archived Subject','code'=>'ARCH','teacher_id'=>$teacher,'created_at'=>now(),'updated_at'=>now(),'archived_at'=>now()]);
        $this->actingAs($admin)->get(route('admin.operations',['section'=>'subjects']))->assertOk()->assertDontSee('Archived Subject');
        $this->actingAs($admin)->get(route('admin.operations',['section'=>'teachers']))->assertOk()->assertDontSee('Archived Teacher');
        $this->actingAs($admin)->post(route('admin.operations.store'),['section'=>'subjects','name'=>'New Subject','code'=>'ARCH','teacher_id'=>null])->assertSessionHas('success');
        $this->assertDatabaseHas('subjects',['code'=>'ARCH','name'=>'New Subject','archived_at'=>null]);
        $this->assertNotNull(DB::table('subjects')->where('id',$subject)->value('archived_at'));
    }

    public function test_invalid_operations_section_is_rejected(): void
    {
        $admin=$this->admin();
        $this->actingAs($admin)->get(route('admin.operations',['section'=>'invalid']))->assertNotFound();
        $this->actingAs($admin)->post(route('admin.operations.store'),['section'=>'invalid'])->assertStatus(422);
    }
}
