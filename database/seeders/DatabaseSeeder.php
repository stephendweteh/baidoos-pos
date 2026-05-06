<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BusinessType;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // ─── Business Categories ──────────────────────────────────
        $barbing = BusinessType::create([
            'name'        => 'Barbing Salon',
            'description' => 'Men\'s hair cutting and grooming services',
        ]);

        $ladies = BusinessType::create([
            'name'        => 'Ladies Hair & Wellness Centre',
            'description' => 'Ladies hair styling, treatment, and wellness services',
        ]);

        $drinking = BusinessType::create([
            'name'        => 'Drinking & Game Centre',
            'description' => 'Bar, beverages, and recreational games',
        ]);

        // ─── Branches ─────────────────────────────────────────────
        $b1 = Branch::create([
            'name'             => 'Baidoos Barbing - Main',
            'business_type_id' => $barbing->id,
            'address'          => 'No 1, Main Street',
            'phone'            => '050-000-0001',
        ]);

        $b2 = Branch::create([
            'name'             => 'Baidoos Ladies Hair & Wellness',
            'business_type_id' => $ladies->id,
            'address'          => 'No 2, Market Road',
            'phone'            => '050-000-0002',
        ]);

        $b3 = Branch::create([
            'name'             => 'Baidoos Drinks & Games',
            'business_type_id' => $drinking->id,
            'address'          => 'No 3, Club Avenue',
            'phone'            => '050-000-0003',
        ]);

        // ─── Items: Barbing Salon ─────────────────────────────────
        $barbingItems = [
            ['name' => 'Haircut',           'price' => 15.00, 'type' => 'service'],
            ['name' => 'Shaving',           'price' => 10.00, 'type' => 'service'],
            ['name' => 'Haircut + Shave',   'price' => 22.00, 'type' => 'service'],
            ['name' => 'Beard Trim',        'price' => 8.00,  'type' => 'service'],
            ['name' => 'Hair Wash',         'price' => 7.00,  'type' => 'service'],
            ['name' => 'Kids Haircut',      'price' => 10.00, 'type' => 'service'],
            ['name' => 'Edge Up',           'price' => 5.00,  'type' => 'service'],
        ];
        foreach ($barbingItems as $item) {
            Item::create(array_merge($item, ['branch_id' => $b1->id]));
        }

        // ─── Items: Ladies Hair & Wellness ───────────────────────
        $ladiesItems = [
            ['name' => 'Hair Relaxer',         'price' => 60.00,  'type' => 'service'],
            ['name' => 'Braiding',              'price' => 80.00,  'type' => 'service'],
            ['name' => 'Hair Wash & Set',       'price' => 30.00,  'type' => 'service'],
            ['name' => 'Weave Attachment',      'price' => 100.00, 'type' => 'service'],
            ['name' => 'Manicure',              'price' => 25.00,  'type' => 'service'],
            ['name' => 'Pedicure',              'price' => 30.00,  'type' => 'service'],
            ['name' => 'Eyebrow Threading',     'price' => 15.00,  'type' => 'service'],
            ['name' => 'Hair Colour',           'price' => 70.00,  'type' => 'service'],
            ['name' => 'Facial Treatment',      'price' => 50.00,  'type' => 'service'],
            ['name' => 'Conditioning Treatment','price' => 40.00,  'type' => 'service'],
        ];
        foreach ($ladiesItems as $item) {
            Item::create(array_merge($item, ['branch_id' => $b2->id]));
        }

        // ─── Items: Drinking & Game Centre ───────────────────────
        $drinkItems = [
            ['name' => 'Beer (Bottle)',     'price' => 8.00,  'type' => 'product'],
            ['name' => 'Beer (Can)',        'price' => 6.00,  'type' => 'product'],
            ['name' => 'Soft Drink',        'price' => 5.00,  'type' => 'product'],
            ['name' => 'Water (Bottle)',    'price' => 3.00,  'type' => 'product'],
            ['name' => 'Spirit (Shot)',     'price' => 12.00, 'type' => 'product'],
            ['name' => 'Spirit (Half)',     'price' => 22.00, 'type' => 'product'],
            ['name' => 'Spirit (Full)',     'price' => 40.00, 'type' => 'product'],
            ['name' => 'Juice',             'price' => 6.00,  'type' => 'product'],
            ['name' => 'Snacks',            'price' => 5.00,  'type' => 'product'],
            ['name' => 'Playstation (hr)',  'price' => 10.00, 'type' => 'service'],
            ['name' => 'Pool Game',         'price' => 5.00,  'type' => 'service'],
            ['name' => 'Darts',             'price' => 5.00,  'type' => 'service'],
        ];
        foreach ($drinkItems as $item) {
            Item::create(array_merge($item, ['branch_id' => $b3->id]));
        }

        // ─── Owner Account ────────────────────────────────────────
        User::create([
            'name'      => 'Business Owner',
            'email'     => 'owner@baidoos.com',
            'password'  => Hash::make('password'),
            'role'      => 'owner',
            'branch_id' => null,
        ]);

        // ─── Cashier Accounts ─────────────────────────────────────
        User::create([
            'name'      => 'Cashier - Barbing',
            'email'     => 'cashier1@baidoos.com',
            'password'  => Hash::make('password'),
            'role'      => 'cashier',
            'branch_id' => $b1->id,
        ]);

        User::create([
            'name'      => 'Cashier - Ladies',
            'email'     => 'cashier2@baidoos.com',
            'password'  => Hash::make('password'),
            'role'      => 'cashier',
            'branch_id' => $b2->id,
        ]);

        User::create([
            'name'      => 'Cashier - Drinks',
            'email'     => 'cashier3@baidoos.com',
            'password'  => Hash::make('password'),
            'role'      => 'cashier',
            'branch_id' => $b3->id,
        ]);
    }
}
