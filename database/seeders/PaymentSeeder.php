<?php

namespace Database\Seeders;

use App\Models\Payment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $payments = [
            [
                'paymentcode' => 'BCA',
                'paymentname' => 'BCA',
                'biaya_admin' => 4000,
                'type' => 'FIXED',
                'order' => 1,
            ],
            [
                'paymentcode' => 'MANDIRI',
                'paymentname' => 'MANDIRI',
                'biaya_admin' => 4000,
                'type' => 'FIXED',
                'order' => 2,
            ],
            [
                'paymentcode' => 'BNI',
                'paymentname' => 'BNI',
                'biaya_admin' => 4000,
                'type' => 'FIXED',
                'order' => 3,
            ],
            [
                'paymentcode' => 'BRI',
                'paymentname' => 'BRI',
                'biaya_admin' => 4000,
                'type' => 'FIXED',
                'order' => 4,
            ],
            [
                'paymentcode' => 'QRIS',
                'paymentname' => 'QRIS',
                'biaya_admin' => 0.07,
                'type' => 'PERCENT',
                'order' => 5,
            ],
        ];

        foreach ($payments as $payment) {
            Payment::create($payment);
        }
    }
}
