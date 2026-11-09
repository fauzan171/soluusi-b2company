<?php
namespace Tests\Feature;
use App\Models\{Consultant,ConsultantPackage,Schedule,Role,User,Voucher};
use App\Models\Klinik\{Klinik,KlinikAdmin,MarkupRule,HapPervorma,SplitSettlement};
use App\Models\ConsultationSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
class Dbg2Test extends TestCase { use RefreshDatabase;
 private function u(string $r): User { $x=Role::firstOrCreate(['name'=>$r]); return User::factory()->create(['role_id'=>$x->id]); }
 public function test_sweep(): void {
  DB::table('provinces')->insert(['id'=>'31','name'=>'DKI']);
  DB::table('regencies')->insert(['id'=>'3171','province_id'=>'31','name'=>'JAKSEL']);
  $k=Klinik::create(['name'=>'K','brand'=>'K','mou_at'=>now()->subDay(),'is_active'=>true,'regency_id'=>'3171']);
  $admin=$this->u('klinik_admin'); KlinikAdmin::create(['klinik_id'=>$k->id,'user_id'=>$admin->id,'role'=>'admin']);
  $c=Consultant::factory()->create(['status'=>Consultant::ACCEPT,'is_active'=>true]);
  $p=ConsultantPackage::factory()->create(['consultant_id'=>$c->id,'name'=>'P','price'=>250000,'mode'=>'online']);
  $s=Schedule::factory()->create(['consultant_id'=>$c->id,'klinik_id'=>$k->id,'mode'=>'online','status'=>'confirmed']);
  MarkupRule::create(['klinik_id'=>$k->id,'consultant_package_id'=>$p->id,'markup_amount'=>25000]);
  $this->actingAs($admin);
  foreach ([
    ['klinik.dashboard',[]],
    ['klinik.consultants.index',[]],
    ['klinik.consultants.show',[$c]],
    ['klinik.schedules.index',[]],
    ['klinik.schedules.create',['consultant_id'=>$c->id]],
    ['klinik.links.index',[]],
    ['klinik.links.create',['consultant_id'=>$c->id]],
    ['checkout.klinik',[$k->uuid]],
  ] as [$n,$args]) { try { $r=$this->get(route($n,$args)); echo str_pad($n,32)." => ".$r->status()."\n"; } catch (\Throwable $e) { echo str_pad($n,32)." => EX ".get_class($e).': '.substr($e->getMessage(),0,120)."\n"; } }
  $this->assertTrue(true);
 }
 public function test_store_link(): void {
  DB::table('provinces')->insert(['id'=>'31','name'=>'DKI']);
  DB::table('regencies')->insert(['id'=>'3171','province_id'=>'31','name'=>'JAKSEL']);
  $k=Klinik::create(['name'=>'K','brand'=>'K','mou_at'=>now()->subDay(),'is_active'=>true,'regency_id'=>'3171']);
  $admin=$this->u('klinik_admin'); KlinikAdmin::create(['klinik_id'=>$k->id,'user_id'=>$admin->id,'role'=>'admin']);
  $c=Consultant::factory()->create(['status'=>Consultant::ACCEPT,'is_active'=>true]);
  $p=ConsultantPackage::factory()->create(['consultant_id'=>$c->id,'name'=>'P','price'=>250000,'mode'=>'online']);
  $s=Schedule::factory()->create(['consultant_id'=>$c->id,'klinik_id'=>$k->id,'mode'=>'online','status'=>'confirmed']);
  MarkupRule::create(['klinik_id'=>$k->id,'consultant_package_id'=>$p->id,'markup_amount'=>25000]);
  $r=$this->actingAs($admin)->post(route('klinik.links.store'),['schedule_id'=>$s->id,'consultant_package_id'=>$p->id]);
  echo "STORE => ".$r->status()." loc=".$r->headers->get('Location')."\n";
  echo "ERR=".json_encode(session('error'))." SUCC=".json_encode(substr((string)session('success'),0,80))."\n";
  $this->assertTrue(true);
 }
}
