<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\ConsultantPackage;
use App\Models\ConsultationSession;
use App\Models\Klinik\Klinik;
use App\Models\Klinik\KlinikAdmin;
use App\Models\Klinik\HapPervorma;
use App\Models\Klinik\MarkupRule;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HpaKlinikPortalTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        $roleModel = Role::firstOrCreate(['name' => $role]);

        return User::factory()->create(['role_id' => $roleModel->id]);
    }

    private function activeKlinik(?User $associate = null): Klinik
    {
        return Klinik::create([
            'name' => 'PT Klinik Regression',
            'brand' => 'Klinik Regression',
            'associate_id' => $associate?->id,
            'mou_at' => now()->subDay(),
            'is_active' => true,
        ]);
    }

    public function test_hpa_and_klinik_dashboards_render_and_roles_are_isolated(): void
    {
        $associate = $this->userWithRole('associate');
        $klinik = $this->activeKlinik($associate);
        $admin = $this->userWithRole('klinik_admin');
        KlinikAdmin::create([
            'klinik_id' => $klinik->id,
            'user_id' => $admin->id,
            'role' => 'admin',
        ]);

        $this->actingAs($associate)
            ->get(route('associate.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard HPA')
            ->assertSee('Status pencairan dana')
            ->assertSee('Klinik Regression');

        // HPA hanya mendapat data agregat dari dashboard associate, bukan hak
        // operasional untuk membuat jadwal atau checkout link klinik.
        $this->actingAs($associate)
            ->get(route('klinik.dashboard'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('klinik.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Mitra')
            ->assertSee('Total penjualan')
            ->assertSee('Klinik Regression');
    }

    public function test_checkout_link_requires_active_future_slot_and_valid_markup(): void
    {
        $klinik = $this->activeKlinik();
        $admin = $this->userWithRole('klinik_admin');
        KlinikAdmin::create(['klinik_id' => $klinik->id, 'user_id' => $admin->id, 'role' => 'admin']);

        $consultant = Consultant::factory()->create(['status' => Consultant::ACCEPT, 'is_active' => true]);
        $package = ConsultantPackage::factory()->create([
            'consultant_id' => $consultant->id,
            'price' => 250_000,
            'mode' => ConsultantPackage::MODE_ONLINE,
        ]);
        $schedule = Schedule::factory()->create([
            'consultant_id' => $consultant->id,
            'klinik_id' => $klinik->id,
            'mode' => Schedule::MODE_ONLINE,
            'status' => Schedule::STATUS_CONFIRMED,
        ]);

        // Final 300k punya cap 15% (=45k), sehingga markup 50k ditolak.
        MarkupRule::create([
            'klinik_id' => $klinik->id,
            'consultant_package_id' => $package->id,
            'markup_amount' => 50_000,
        ]);

        $payload = ['schedule_id' => $schedule->id, 'consultant_package_id' => $package->id];
        $this->actingAs($admin)->post(route('klinik.links.store'), $payload)->assertStatus(422);
        $this->assertDatabaseCount('checkout_links', 0);

        MarkupRule::where('klinik_id', $klinik->id)
            ->where('consultant_package_id', $package->id)
            ->update(['markup_amount' => 25_000]);

        $this->actingAs($admin)
            ->post(route('klinik.links.store'), $payload)
            ->assertRedirect(route('klinik.links.index'));
        $this->assertDatabaseCount('checkout_links', 1);

        // Satu slot tidak boleh punya dua link aktif yang membingungkan customer.
        $this->actingAs($admin)
            ->from(route('klinik.links.create', ['consultant_id' => $consultant->id]))
            ->post(route('klinik.links.store'), $payload)
            ->assertRedirect(route('klinik.links.index'))
            ->assertSessionHas('info')
            ->assertSessionHas('created_link_id');
        $this->assertDatabaseCount('checkout_links', 1);
    }

    public function test_klinik_consultant_picker_renders_catalog_cards(): void
    {
        $klinik = $this->activeKlinik();
        $admin = $this->userWithRole('klinik_admin');
        KlinikAdmin::create(['klinik_id' => $klinik->id, 'user_id' => $admin->id, 'role' => 'admin']);

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Psikolog Klinis',
            'code_category' => 'psikolog-klinis',
            'vertical' => 'klinik',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $consultant = Consultant::factory()->create([
            'about' => 'Konsultan untuk anxiety, burnout, dan stress management.',
            'status' => Consultant::ACCEPT,
            'is_active' => true,
        ]);

        DB::table('consultant_categories')->insert([
            'consultant_id' => $consultant->id,
            'category_id' => $categoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $package = ConsultantPackage::factory()->create([
            'consultant_id' => $consultant->id,
            'price' => 250_000,
            'mode' => ConsultantPackage::MODE_ONLINE,
        ]);

        MarkupRule::create([
            'klinik_id' => $klinik->id,
            'consultant_package_id' => $package->id,
            'markup_amount' => 35_000,
        ]);

        Schedule::factory()->create([
            'consultant_id' => $consultant->id,
            'klinik_id' => $klinik->id,
            'mode' => Schedule::MODE_ONLINE,
            'status' => Schedule::STATUS_CONFIRMED,
            'started_at' => now()->addDay(),
            'ended_at' => now()->addDay()->addHour(),
        ]);

        $this->actingAs($admin)
            ->get(route('klinik.consultants.index', ['q' => 'burnout']))
            ->assertOk()
            ->assertSee('Katalog layanan klinik')
            ->assertSee('Cari kebutuhan')
            ->assertSee('Mulai')
            ->assertSee('Rp285.000')
            ->assertSee('Pilih Paket');

        $this->actingAs($admin)
            ->get(route('klinik.consultants.show', $consultant))
            ->assertOk()
            ->assertSee('Generate Link Customer')
            ->assertDontSee('Lanjut Buat Checkout Link');
    }

    public function test_checkout_link_succeeds_for_package_below_cap_range_floor(): void
    {
        // Regression (bug): paket net < Rp250k + markup kecil dulu selalu 422
        // karena capFor() return 0.0 (tidak ada range match) → margin apapun
        // ditolak. Setelah range termurah mulai dari 0, link harus jadi.
        $klinik = $this->activeKlinik();
        $admin = $this->userWithRole('klinik_admin');
        KlinikAdmin::create(['klinik_id' => $klinik->id, 'user_id' => $admin->id, 'role' => 'admin']);

        $consultant = Consultant::factory()->create(['status' => Consultant::ACCEPT, 'is_active' => true]);
        $package = ConsultantPackage::factory()->create([
            'consultant_id' => $consultant->id,
            'price' => 200_000,                 // di bawah floor range lama (250k)
            'mode' => ConsultantPackage::MODE_ONLINE,
        ]);
        $schedule = Schedule::factory()->create([
            'consultant_id' => $consultant->id,
            'klinik_id' => $klinik->id,
            'mode' => Schedule::MODE_ONLINE,
            'status' => Schedule::STATUS_CONFIRMED,
        ]);

        // Markup 20k → final 220k, margin 20k ≤ cap 10%×220k=22k. Boleh.
        MarkupRule::create([
            'klinik_id' => $klinik->id,
            'consultant_package_id' => $package->id,
            'markup_amount' => 20_000,
        ]);

        $this->actingAs($admin)
            ->post(route('klinik.links.store'), [
                'schedule_id' => $schedule->id,
                'consultant_package_id' => $package->id,
            ])
            ->assertRedirect(route('klinik.links.index'));
        $this->assertDatabaseCount('checkout_links', 1);
    }

    public function test_checkout_link_rejects_excessive_markup_below_cap_range_floor(): void
    {
        // Kasus gagal: paket < 250k + markup besar yang tetap melanggar cap floor.
        $klinik = $this->activeKlinik();
        $admin = $this->userWithRole('klinik_admin');
        KlinikAdmin::create(['klinik_id' => $klinik->id, 'user_id' => $admin->id, 'role' => 'admin']);

        $consultant = Consultant::factory()->create(['status' => Consultant::ACCEPT, 'is_active' => true]);
        $package = ConsultantPackage::factory()->create([
            'consultant_id' => $consultant->id,
            'price' => 200_000,
            'mode' => ConsultantPackage::MODE_ONLINE,
        ]);
        $schedule = Schedule::factory()->create([
            'consultant_id' => $consultant->id,
            'klinik_id' => $klinik->id,
            'mode' => Schedule::MODE_ONLINE,
            'status' => Schedule::STATUS_CONFIRMED,
        ]);

        // Markup 30k → final 230k, margin 30k > cap 10%×230k=23k. Tolak.
        MarkupRule::create([
            'klinik_id' => $klinik->id,
            'consultant_package_id' => $package->id,
            'markup_amount' => 30_000,
        ]);

        $this->actingAs($admin)
            ->post(route('klinik.links.store'), [
                'schedule_id' => $schedule->id,
                'consultant_package_id' => $package->id,
            ])
            ->assertStatus(422);
        $this->assertDatabaseCount('checkout_links', 0);
    }

    public function test_public_clinic_booking_link_lists_available_consultants(): void
    {
        $klinik = $this->activeKlinik();
        $consultant = Consultant::factory()->create([
            'status' => Consultant::ACCEPT,
            'is_active' => true,
        ]);
        $package = ConsultantPackage::factory()->create([
            'consultant_id' => $consultant->id,
            'name' => 'Konsultasi Online 60 Menit',
            'price' => 250_000,
            'mode' => ConsultantPackage::MODE_ONLINE,
        ]);
        Schedule::factory()->create([
            'consultant_id' => $consultant->id,
            'klinik_id' => $klinik->id,
            'mode' => Schedule::MODE_ONLINE,
            'status' => Schedule::STATUS_CONFIRMED,
        ]);
        MarkupRule::create([
            'klinik_id' => $klinik->id,
            'consultant_package_id' => $package->id,
            'markup_amount' => 25_000,
        ]);

        $this->get(route('checkout.klinik', $klinik->uuid))
            ->assertOk()
            ->assertSee('Booking Konsultasi')
            ->assertSee('slot tersedia');

        $this->get(route('checkout.klinik', [
            'klinik' => $klinik->uuid,
            'consultant_id' => $consultant->id,
        ]))
            ->assertOk()
            ->assertSee('Konsultasi Online 60 Menit')
            ->assertSee('Rp275.000');
    }

    public function test_public_clinic_booking_rejects_mismatched_schedule_and_package_modes_gracefully(): void
    {
        DB::table('provinces')->insert(['id' => '31', 'name' => 'DKI JAKARTA']);
        DB::table('regencies')->insert(['id' => '3171', 'province_id' => '31', 'name' => 'KOTA JAKARTA SELATAN']);

        $klinik = $this->activeKlinik();
        $consultant = Consultant::factory()->create([
            'status' => Consultant::ACCEPT,
            'is_active' => true,
        ]);
        ConsultantPackage::factory()->create([
            'consultant_id' => $consultant->id,
            'name' => 'Konsultasi Online 60 Menit',
            'price' => 250_000,
            'mode' => ConsultantPackage::MODE_ONLINE,
        ]);
        $offlinePackage = ConsultantPackage::factory()->create([
            'consultant_id' => $consultant->id,
            'name' => 'Konsultasi Tatap Muka 60 Menit',
            'price' => 300_000,
            'mode' => ConsultantPackage::MODE_OFFLINE,
        ]);
        $onlineSchedule = Schedule::factory()->create([
            'consultant_id' => $consultant->id,
            'klinik_id' => $klinik->id,
            'mode' => Schedule::MODE_ONLINE,
            'status' => Schedule::STATUS_CONFIRMED,
        ]);

        $this->from(route('checkout.klinik', [
            'klinik' => $klinik->uuid,
            'consultant_id' => $consultant->id,
        ]))->post(route('checkout.klinik.pay', $klinik->uuid), [
            'consultant_id' => $consultant->id,
            'schedule_id' => $onlineSchedule->id,
            'consultant_package_id' => $offlinePackage->id,
            'customer_name' => 'Customer Demo',
            'customer_email' => 'customer.demo@example.test',
            'customer_phone' => '081234567890',
            'customer_dob' => '1995-01-01',
            'customer_regency_id' => '3171',
            'customer_gender' => 'male',
            'complaint' => 'Demo presentasi',
            'consent' => '1',
        ])
            ->assertRedirect(route('checkout.klinik', [
                'klinik' => $klinik->uuid,
                'consultant_id' => $consultant->id,
            ]))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('checkout_links', 0);
    }

    public function test_public_clinic_booking_rejects_invalid_markup_gracefully(): void
    {
        DB::table('provinces')->insert(['id' => '31', 'name' => 'DKI JAKARTA']);
        DB::table('regencies')->insert(['id' => '3171', 'province_id' => '31', 'name' => 'KOTA JAKARTA SELATAN']);

        $klinik = $this->activeKlinik();
        $consultant = Consultant::factory()->create([
            'status' => Consultant::ACCEPT,
            'is_active' => true,
        ]);
        $package = ConsultantPackage::factory()->create([
            'consultant_id' => $consultant->id,
            'name' => 'Konsultasi Online 60 Menit',
            'price' => 220_000,
            'mode' => ConsultantPackage::MODE_ONLINE,
        ]);
        $schedule = Schedule::factory()->create([
            'consultant_id' => $consultant->id,
            'klinik_id' => $klinik->id,
            'mode' => Schedule::MODE_ONLINE,
            'status' => Schedule::STATUS_CONFIRMED,
        ]);
        MarkupRule::create([
            'klinik_id' => $klinik->id,
            'consultant_package_id' => $package->id,
            'markup_amount' => 35_000,
        ]);

        $this->from(route('checkout.klinik', [
            'klinik' => $klinik->uuid,
            'consultant_id' => $consultant->id,
        ]))->post(route('checkout.klinik.pay', $klinik->uuid), [
            'consultant_id' => $consultant->id,
            'schedule_id' => $schedule->id,
            'consultant_package_id' => $package->id,
            'customer_name' => 'Customer Demo',
            'customer_email' => 'customer.demo@example.test',
            'customer_phone' => '081234567890',
            'customer_dob' => '1995-01-01',
            'customer_regency_id' => '3171',
            'customer_gender' => 'male',
            'complaint' => 'Demo presentasi',
            'consent' => '1',
        ])
            ->assertRedirect(route('checkout.klinik', [
                'klinik' => $klinik->uuid,
                'consultant_id' => $consultant->id,
            ]))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('checkout_links', 0);
    }

    public function test_local_booking_uses_dummy_payment_when_midtrans_key_is_empty(): void
    {
        $this->app['env'] = 'local';
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        config(['midtrans.server_key' => null]);

        DB::table('provinces')->insert(['id' => '31', 'name' => 'DKI JAKARTA']);
        DB::table('regencies')->insert(['id' => '3171', 'province_id' => '31', 'name' => 'KOTA JAKARTA SELATAN']);

        $klinik = $this->activeKlinik();
        $consultant = Consultant::factory()->create([
            'status' => Consultant::ACCEPT,
            'is_active' => true,
        ]);
        $package = ConsultantPackage::factory()->create([
            'consultant_id' => $consultant->id,
            'name' => 'Konsultasi Online 60 Menit',
            'price' => 250_000,
            'mode' => ConsultantPackage::MODE_ONLINE,
        ]);
        $schedule = Schedule::factory()->create([
            'consultant_id' => $consultant->id,
            'klinik_id' => $klinik->id,
            'mode' => Schedule::MODE_ONLINE,
            'status' => Schedule::STATUS_CONFIRMED,
        ]);
        MarkupRule::create([
            'klinik_id' => $klinik->id,
            'consultant_package_id' => $package->id,
            'markup_amount' => 25_000,
        ]);

        $this->post(route('checkout.klinik.pay', $klinik->uuid), [
            'consultant_id' => $consultant->id,
            'schedule_id' => $schedule->id,
            'consultant_package_id' => $package->id,
            'customer_name' => 'Customer Demo',
            'customer_email' => 'customer.demo@example.test',
            'customer_phone' => '081234567890',
            'customer_dob' => '1995-01-01',
            'customer_regency_id' => '3171',
            'customer_gender' => 'male',
            'complaint' => 'Demo presentasi',
            'consent' => '1',
        ])->assertRedirect();

        $link = \App\Models\Klinik\CheckoutLink::latest('id')->firstOrFail();
        $this->assertDatabaseHas('consultation_sessions', [
            'checkout_link_id' => $link->id,
            'payment_status' => ConsultationSession::PAYMENT_STATUS_WAITING,
            'url_snap' => route('checkout.dummyPayment', $link->token),
        ]);

        $this->get(route('checkout.show', $link->token))
            ->assertRedirect(route('checkout.status', $link->token));

        $this->get(route('checkout.status', $link->token))
            ->assertOk()
            ->assertSee('Dashboard customer')
            ->assertSee('Menunggu pembayaran')
            ->assertSee('Jadwal konsultasi')
            ->assertSee('Lanjutkan Pembayaran')
            ->assertSee('Arti status di dashboard ini');

        $this->get(route('checkout.dummyPayment', $link->token))
            ->assertOk()
            ->assertSee('Pembayaran Dummy')
            ->assertSee('Simulasikan Pembayaran Berhasil');

        $this->post(route('checkout.dummyPayment.success', $link->token))
            ->assertRedirect(route('checkout.status', $link->token));

        $this->assertDatabaseHas('consultation_sessions', [
            'checkout_link_id' => $link->id,
            'payment_status' => ConsultationSession::PAYMENT_STATUS_VERIFIED,
            'status' => ConsultationSession::STATUS_SCHEDULED,
        ]);

        $this->get(route('checkout.status', $link->token))
            ->assertOk()
            ->assertSee('Sesi terjadwal')
            ->assertSee('Terverifikasi')
            ->assertSee('Konsultasi Online 60 Menit')
            ->assertSee('Customer Demo');
    }

    public function test_midtrans_cancel_callback_requires_signature_and_cancels_both_statuses(): void
    {
        config(['midtrans.server_key' => 'regression-secret']);
        $consultant = Consultant::factory()->create(['status' => Consultant::ACCEPT]);
        $package = ConsultantPackage::factory()->create(['consultant_id' => $consultant->id]);
        $schedule = Schedule::factory()->create(['consultant_id' => $consultant->id]);
        $session = ConsultationSession::create([
            'schedule_id' => $schedule->id,
            'consultant_id' => $consultant->id,
            'consultant_package_id' => $package->id,
            'order_number' => 'KB-CANCEL-1',
            'payment' => '250000',
            'amount' => 250000,
            'order_at' => now(),
            'payment_status' => 'waiting',
            'status' => 'waiting',
        ]);

        $payload = [
            'order_id' => $session->order_number,
            'status_code' => '202',
            'gross_amount' => '250000.00',
        ];

        $this->postJson(route('midtrans-cancel'), $payload + ['signature_key' => 'invalid'])
            ->assertForbidden();
        $this->assertDatabaseHas('consultation_sessions', [
            'id' => $session->id,
            'payment_status' => 'waiting',
            'status' => 'waiting',
        ]);

        $payload['signature_key'] = hash(
            'sha512',
            $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . 'regression-secret'
        );
        $this->postJson(route('midtrans-cancel'), $payload)->assertOk();
        $this->assertDatabaseHas('consultation_sessions', [
            'id' => $session->id,
            'payment_status' => 'canceled',
            'status' => 'canceled',
        ]);
    }

    public function test_super_admin_can_manage_markup_and_mark_hpa_pervorma_paid(): void
    {
        $admin = $this->userWithRole('admin');
        $associate = $this->userWithRole('associate');
        $klinik = $this->activeKlinik($associate);
        $consultant = Consultant::factory()->create(['status' => Consultant::ACCEPT, 'is_active' => true]);
        $package = ConsultantPackage::factory()->create([
            'consultant_id' => $consultant->id,
            'price' => 300_000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.kliniks.markups.index', $klinik))
            ->assertOk()
            ->assertSee($package->name);

        $this->actingAs($admin)->post(route('admin.kliniks.markups.store', $klinik), [
            'consultant_package_id' => $package->id,
            'markup_amount' => 110_000,
        ])->assertSessionHasErrors('markup_amount');

        $this->actingAs($admin)->post(route('admin.kliniks.markups.store', $klinik), [
            'consultant_package_id' => $package->id,
            'markup_amount' => 40_000,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('markup_rules', [
            'klinik_id' => $klinik->id,
            'consultant_package_id' => $package->id,
            'markup_amount' => 40_000,
        ]);

        $pervorma = HapPervorma::create([
            'klinik_id' => $klinik->id,
            'associate_id' => $associate->id,
            'stage' => 1,
            'amount' => 250_000,
            'status' => 'due',
            'due_at' => now()->addWeek(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pervormas.pay', ['vertical' => 'hap', 'pervorma' => $pervorma->id]))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertDatabaseHas('hap_pervormas', [
            'id' => $pervorma->id,
            'status' => 'paid',
        ]);
        $this->assertNotNull($pervorma->fresh()->paid_at);
    }

    public function test_associate_can_request_due_pervorma_payout_and_check_status(): void
    {
        $associate = $this->userWithRole('associate');
        $otherAssociate = $this->userWithRole('associate');
        $klinik = $this->activeKlinik($associate);

        $pervorma = HapPervorma::create([
            'klinik_id' => $klinik->id,
            'associate_id' => $associate->id,
            'stage' => 1,
            'amount' => 250_000,
            'status' => 'due',
            'due_at' => now()->addWeek(),
        ]);

        $this->actingAs($associate)
            ->get(route('associate.dashboard'))
            ->assertOk()
            ->assertSee('Minta cairkan dana')
            ->assertSee('Siap diajukan');

        $this->actingAs($otherAssociate)
            ->post(route('associate.pervormas.request-payout', ['vertical' => 'hap', 'pervorma' => $pervorma->id]))
            ->assertForbidden();

        $this->actingAs($associate)
            ->post(route('associate.pervormas.request-payout', ['vertical' => 'hap', 'pervorma' => $pervorma->id]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('hap_pervormas', [
            'id' => $pervorma->id,
            'requested_by' => $associate->id,
        ]);
        $this->assertNotNull($pervorma->fresh()->requested_at);

        $this->actingAs($associate)
            ->get(route('associate.dashboard'))
            ->assertOk()
            ->assertSee('Diproses finance')
            ->assertSee('Estimasi cair');
    }

    /**
     * Race §17.7: slot diisi request lain tepat sebelum bayar. Link TIDAK boleh
     * terbakar (used_at) dan tidak boleh ada waiting-session kedua.
     */
    public function test_slot_terisi_tidak_membakar_checkout_link(): void
    {
        $this->app['env'] = 'local';
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        config(['midtrans.server_key' => null]);

        DB::table('provinces')->insert(['id' => '31', 'name' => 'DKI JAKARTA']);
        DB::table('regencies')->insert(['id' => '3171', 'province_id' => '31', 'name' => 'KOTA JAKARTA SELATAN']);

        $klinik = $this->activeKlinik();
        $consultant = Consultant::factory()->create(['status' => Consultant::ACCEPT, 'is_active' => true]);
        $package = ConsultantPackage::factory()->create([
            'consultant_id' => $consultant->id,
            'price' => 250_000,
            'mode' => ConsultantPackage::MODE_ONLINE,
        ]);
        $schedule = Schedule::factory()->create([
            'consultant_id' => $consultant->id,
            'klinik_id' => $klinik->id,
            'mode' => Schedule::MODE_ONLINE,
            'status' => Schedule::STATUS_CONFIRMED,
        ]);
        MarkupRule::create([
            'klinik_id' => $klinik->id,
            'consultant_package_id' => $package->id,
            'markup_amount' => 25_000,
        ]);

        // Link publik sudah ada (dibuat admin), lalu slot diisi sesi terverifikasi lain.
        $link = \App\Models\Klinik\CheckoutLink::create([
            'token' => \App\Models\Klinik\CheckoutLink::generateToken(),
            'klinik_id' => $klinik->id,
            'consultant_id' => $consultant->id,
            'schedule_id' => $schedule->id,
            'consultant_package_id' => $package->id,
            'net_price' => 250_000,
            'markup_amount' => 25_000,
            'final_price' => 275_000,
            'expires_at' => now()->addDay(),
        ]);
        ConsultationSession::create([
            'schedule_id' => $schedule->id,
            'consultant_id' => $consultant->id,
            'consultant_package_id' => $package->id,
            'klinik_id' => $klinik->id,
            'order_number' => 'T-race',
            'payment' => '275000',
            'amount' => 275_000,
            'order_at' => now(),
            'payment_status' => ConsultationSession::PAYMENT_STATUS_VERIFIED,
            'status' => ConsultationSession::STATUS_SCHEDULED,
        ]);

        $this->post(route('checkout.pay', $link->token), [
            'customer_name' => 'Customer Demo',
            'customer_email' => 'customer.demo@example.test',
            'customer_phone' => '081234567890',
            'customer_dob' => '1995-01-01',
            'customer_regency_id' => '3171',
            'customer_gender' => 'male',
            'consent' => '1',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertNull($link->fresh()->used_at, 'link tidak boleh terbakar saat slot sudah terisi');
        $this->assertSame(1, ConsultationSession::where('schedule_id', $schedule->id)->count());
    }
}
