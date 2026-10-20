<?php
namespace Tests\Feature;
use App\Models\Consultant;use App\Models\ConsultantPackage;use App\Models\Klinik\Klinik;use App\Models\Schedule;use App\Models\Klinik\MarkupRule;use Illuminate\Foundation\Testing\RefreshDatabase;use Illuminate\Support\Facades\DB;use Tests\TestCase;
class DbgTest extends TestCase { use RefreshDatabase;
 public function test_x(): void {
  $this->app['env']='local'; $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class); config(['midtrans.server_key'=>null]);
  DB::table('provinces')->insert(['id'=>'31','name'=>'DKI']); DB::table('regencies')->insert(['id'=>'3171','province_id'=>'31','name'=>'JAKSEL']);
  $k=Klinik::create(['name'=>'K','brand'=>'K','mou_at'=>now()->subDay(),'is_active'=>true]);
  $c=Consultant::factory()->create(['status'=>Consultant::ACCEPT,'is_active'=>true]);
  $p=ConsultantPackage::factory()->create(['consultant_id'=>$c->id,'name'=>'P','price'=>250000,'mode'=>ConsultantPackage::MODE_ONLINE]);
  $s=Schedule::factory()->create(['consultant_id'=>$c->id,'klinik_id'=>$k->id,'mode'=>Schedule::MODE_ONLINE,'status'=>Schedule::STATUS_CONFIRMED]);
  MarkupRule::create(['klinik_id'=>$k->id,'consultant_package_id'=>$p->id,'markup_amount'=>25000]);
  $r=$this->post(route('checkout.klinik.pay',$k->uuid),['consultant_id'=>$c->id,'schedule_id'=>$s->id,'consultant_package_id'=>$p->id,'customer_name'=>'C','customer_email'=>'c@e.test','customer_phone'=>'081','customer_dob'=>'1995-01-01','customer_regency_id'=>'3171','customer_gender'=>'male','complaint'=>'x','consent'=>'1']);
  echo "\nSTATUS ".$r->status()." LOC ".$r->headers->get('Location')."\n";
  echo "ERRORS: ".json_encode(session('errors')?->all())."\n";
  echo "SESS ERR: ".session('error')."\n";
  echo "LINKS: ".DB::table('checkout_links')->count()." SESS: ".DB::table('consultation_sessions')->count()."\n";
  $this->assertTrue(true);
 }}
