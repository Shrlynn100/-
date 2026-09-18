<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\AuctionItem;
use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Admin Password Setting (Default: DADDYKUYALL)
        Setting::set('admin_password', Hash::make('DADDYKUYALL'));
        Setting::set('site_title', 'ระบบประมูล ROOC');

        // 2. Categories
        $categories = [
            [
                'slug' => 'card',
                'name' => 'การ์ด',
                'icon' => '🃏',
                'total_items' => 80,
                'sort_order' => 1,
            ],
            [
                'slug' => 'white_feather',
                'name' => 'ขนขาว',
                'icon' => '🎁',
                'total_items' => 80,
                'sort_order' => 2,
            ],
            [
                'slug' => 'red_black_feather',
                'name' => 'ขนแดงดำ',
                'icon' => '📦',
                'total_items' => 80,
                'sort_order' => 3,
            ],
        ];

        foreach ($categories as $catData) {
            $category = Category::updateOrCreate(
                ['slug' => $catData['slug']],
                $catData
            );

            // Populate slots: red_black_feather up to 180, white_feather up to 180, card up to 80
            $maxSlots = match($catData['slug']) {
                'red_black_feather', 'white_feather' => 180,
                default => 80,
            };

            for ($slot = 1; $slot <= $maxSlots; $slot++) {
                $pageNumber = (int) ceil($slot / 4);
                AuctionItem::firstOrCreate(
                    [
                        'category_id' => $category->id,
                        'slot_number' => $slot,
                    ],
                    [
                        'page_number' => $pageNumber,
                        'name' => null,
                        'book1' => false,
                        'book2' => false,
                        'book3' => false,
                        'book4' => false,
                    ]
                );
            }
        }

        // 3. Populate 80 Guild Members slots
        for ($memberSlot = 1; $memberSlot <= 80; $memberSlot++) {
            \App\Models\GuildMember::firstOrCreate(
                ['slot_number' => $memberSlot],
                [
                    'name' => null,
                    'class_job' => null,
                    'role' => $memberSlot === 1 ? 'หัวกิลด์' : ($memberSlot <= 3 ? 'รองหัวกิลด์' : 'สมาชิก'),
                    'attended' => false,
                    'notes' => null,
                ]
            );
        }

        // 4. Populate WoE Party Slots (4 maps × 2 rooms × 8 parties × 5 slots = 320 slots)
        $maps = array_keys(\App\Models\WarPartySlot::mapNames());
        $now = now();
        foreach ($maps as $mapSlug) {
            if (\App\Models\WarPartySlot::where('map_slug', $mapSlug)->exists()) {
                continue;
            }
            $rows = [];
            for ($room = 1; $room <= 2; $room++) {
                for ($party = 1; $party <= 8; $party++) {
                    for ($slot = 1; $slot <= 5; $slot++) {
                        $rows[] = [
                            'map_slug'     => $mapSlug,
                            'room_number'  => $room,
                            'party_number' => $party,
                            'slot_number'  => $slot,
                            'member_name'  => null,
                            'class_job'    => null,
                            'role'         => 'สมาชิก',
                            'notes'        => null,
                            'created_at'   => $now,
                            'updated_at'   => $now,
                        ];
                    }
                }
            }
            foreach (array_chunk($rows, 50) as $chunk) {
                \App\Models\WarPartySlot::insert($chunk);
            }
        }
    }
}
