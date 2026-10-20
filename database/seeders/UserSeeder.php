<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Role;
use App\Models\SocialMedia;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        try {
            DB::beginTransaction();
            // create role
            $roleAdmin = Role::Create([
                'name' => 'admin',
            ]);
            $roleCustomer = Role::Create([
                'name' => 'customer',
            ]);
            $roleConsultant = Role::Create([
                'name' => 'consultant',
            ]);

            // create user
            User::create([
                'username' => 'Admin',
                'firstname' => 'Admin',
                'lastname' => '',
                'email' => 'admin@gmail.com',
                'password' => bcrypt('password'),
                'role_id' => $roleAdmin->id,
                'is_active' => true
            ]);

            User::create([
                'username' => 'Customer',
                'firstname' => 'Customer',
                'lastname' => '',
                'email' => 'customer@gmail.com',
                'password' => bcrypt('password'),
                'role_id' => $roleCustomer->id,
                'is_active' => true
            ]);

            $userConsultant = User::create([
                'username' => 'Consultant',
                'firstname' => 'Consultant',
                'lastname' => '',
                'email' => 'consultant@gmail.com',
                'password' => bcrypt('password'),
                'role_id' => $roleConsultant->id,
                'is_active' => true
            ]);

            // create consultant
            $consultant1 = $userConsultant->consultants()->create([
                'about' => 'About consultant',
                'profit_sharing' => 10,
                'status' => 'accept',
                'is_active' => true,
                'bank' => 'BCA',
                'account_number' => '1234567890',
            ]);

            $userConsultant->consultants()->create([
                'about' => 'Konsultant Belum di Accept',
                'profit_sharing' => 10,
                'status' => 'pending',
                'is_active' => true,
                'bank' => 'MANDIRI',
                'account_number' => '091823612',
            ]);

            // create consultant category
            $categoryKeuangan = Category::create([
                'code_category' => 'KEU',
                'name' => 'Keuangan',
            ]);

            $categoryPsikologi = Category::create([
                'code_category' => 'PSI',
                'name' => 'Psikologi',
            ]);

            $categoryTI = Category::create([
                'code_category' => 'TI',
                'name' => 'Teknologi Informasi',
            ]);

            $consultant1->categories()->attach($categoryKeuangan->id);
            $consultant1->categories()->attach($categoryTI->id);
            $consultant1->categories()->attach($categoryPsikologi->id);

            // create consultant social media
            $facebook = SocialMedia::create([
                'name' => 'Facebook',
                'logo' => 'facebook.png'
            ]);

            $linkedIn = SocialMedia::create([
                'name' => 'LinkedIn',
                'logo' => 'facebook.png'
            ]);

            $instagram = SocialMedia::create([
                'name' => 'Instagram',
                'logo' => 'facebook.png'
            ]);

            $consultant1->socialMedia()->attach($facebook->id, ['url' => 'https://facebook.com']);
            $consultant1->socialMedia()->attach($linkedIn->id, ['url' => 'https://linkedin.com']);
            $consultant1->socialMedia()->attach($instagram->id, ['url' => 'https://instagram.com']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        }
    }
}
