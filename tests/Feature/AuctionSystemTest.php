<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\AuctionItem;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuctionSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed initial data
        $this->seed();
    }

    public function test_guest_can_view_auction_page_with_target_items(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('รายชื่อ 80 รายการ');
        $response->assertSee('โหมดผู้เข้าชม (ดูได้อย่างเดียว)');
        $response->assertSee('การ์ด');
        $response->assertSee('ขนขาว');
        $response->assertSee('ขนแดงดำ');
        $response->assertSee('ชื่อผู้ได้รับ (เลือกจากสมาชิกกิลด์)');
        $response->assertSee('หน้า 1 เล่ม 1');
        $response->assertSee('หน้า 3 เล่ม 4');
        $response->assertDontSee('วนCYCLEใหม่');
        $response->assertDontSee('ตกรายชื่อ');
    }

    public function test_admin_can_save_outcome(): void
    {
        $response = $this->postJson('/api/settings/outcome', [
            'outcome' => 'win_100',
            'confirmed' => true,
        ]);
        $response->assertStatus(403);

        $response = $this->withSession(['is_admin' => true])
            ->postJson('/api/settings/outcome', [
                'outcome' => 'lose_70',
                'confirmed' => true,
            ]);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_guest_cannot_update_item(): void
    {
        $item = AuctionItem::first();

        $response = $this->postJson("/api/items/{$item->id}", [
            'name' => 'การ์ดเดวิลลิ่ง',
            'book1' => true,
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_login_validation(): void
    {
        // Wrong password
        $response = $this->postJson('/admin/login', [
            'password' => 'wrongpass',
        ]);
        $response->assertStatus(422);

        // Correct password
        $response = $this->postJson('/admin/login', [
            'password' => 'DADDYKUYALL',
        ]);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_admin_can_update_item_with_auto_save(): void
    {
        $item = AuctionItem::first();

        // Login as admin
        $response = $this->withSession(['is_admin' => true])
            ->postJson("/api/items/{$item->id}", [
                'name' => 'การ์ดบาโฟเมต',
                'book1' => true,
                'book3' => true,
                'winner_name' => 'กิลด์มาสเตอร์',
                'final_price' => 50000000,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'filled_count' => 1,
        ]);

        $this->assertDatabaseHas('auction_items', [
            'id' => $item->id,
            'name' => 'การ์ดบาโฟเมต',
            'book1' => true,
            'book3' => true,
            'winner_name' => 'กิลด์มาสเตอร์',
            'final_price' => 50000000,
        ]);
    }

    public function test_export_summary(): void
    {
        $category = Category::where('slug', 'card')->first();
        $item = $category->items()->first();
        $item->update([
            'name' => 'การ์ดออร์คฮีโร่',
            'book1' => true,
            'book2' => true,
        ]);

        $response = $this->getJson('/api/category/card/export');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertStringContainsString('การ์ดออร์คฮีโร่', $response->json('summary'));
        $this->assertStringContainsString('เล่ม 1, เล่ม 2', $response->json('summary'));
    }

    public function test_admin_can_reset_category(): void
    {
        $category = Category::where('slug', 'card')->first();
        $item = $category->items()->first();
        $item->update([
            'name' => 'การ์ดทดสอบ',
            'book1' => true,
        ]);

        $response = $this->withSession(['is_admin' => true])
            ->postJson('/api/category/card/reset');

        $response->assertStatus(200);
        $this->assertEquals(0, $category->fresh()->filled_count);
    }

    public function test_navbar_has_updated_branding(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('DADDY x YESTOOTH x FUNRAIRANKGOLD');
    }

    public function test_table_shows_unified_columns_and_details(): void
    {
        // Test Card view has member columns, page numbers, date and clean notes
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('ชื่อผู้ได้รับ (เลือกจากสมาชิกกิลด์)');
        $response->assertSee('หน้า 1 เล่ม 1');
        $response->assertSee('หน้า 3 เล่ม 4');
        $response->assertDontSee('วนCYCLEใหม่');
        $response->assertDontSee('ตกรายชื่อ');

        // Test Feather view has page numbers and member columns
        $featherResponse = $this->get('/?category=white_feather');
        $featherResponse->assertStatus(200);
        $featherResponse->assertSee('หน้าที่ 1 (อันที่ 1-4)');
        $featherResponse->assertSee('หน้าที่ 25 (อันที่ 1-4)');
        $featherResponse->assertSee('ชื่อผู้ได้รับ (เลือกจากสมาชิกกิลด์)');
        $featherResponse->assertDontSee('วนCYCLEใหม่');
        $featherResponse->assertDontSee('ตกรายชื่อ');
    }

    public function test_admin_can_sync_date_across_all_items(): void
    {
        $newDate = '25/09/69';

        // Guest cannot sync date
        $guestResponse = $this->postJson('/api/items/sync-date', [
            'item_date' => $newDate,
        ]);
        $guestResponse->assertStatus(403);

        // Admin can sync date
        $adminResponse = $this->withSession(['is_admin' => true])
            ->postJson('/api/items/sync-date', [
                'item_date' => $newDate,
            ]);
        $adminResponse->assertStatus(200);
        $adminResponse->assertJson([
            'success' => true,
            'item_date' => $newDate,
        ]);

        // Verify that all items across all categories have this date
        $nonMatchingCount = AuctionItem::where('item_date', '!=', $newDate)->count();
        $this->assertEquals(0, $nonMatchingCount);
    }

    public function test_get_auction_overview_endpoint(): void
    {
        // Add a winner to an item
        $category = Category::where('slug', 'card')->first();
        $item = $category->items()->first();
        $item->update([
            'name' => 'กิลด์มาสเตอร์สุดหล่อ',
            'notes' => 'ชนะประมูลการ์ด',
        ]);

        $response = $this->getJson('/api/auction/overview');
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJsonStructure([
            'success',
            'outcome',
            'total_target',
            'total_filled',
            'categories',
            'winners',
            'member_summary',
        ]);

        $this->assertTrue(collect($response->json('winners'))->contains('name', 'กิลด์มาสเตอร์สุดหล่อ'));
    }

    public function test_overrun_outcomes_and_targets(): void
    {
        // Test all 4 Overrun tiers
        $tiers = [
            'rank_1' => ['card' => 20, 'white' => 150, 'red' => 170, 'label' => '1. ได้อันดับ 1'],
            'rank_2_3' => ['card' => 20, 'white' => 140, 'red' => 160, 'label' => '2. ได้อันดับ 2-3'],
            'rank_4_6' => ['card' => 15, 'white' => 120, 'red' => 150, 'label' => '3. ได้อันดับ 4-6'],
            'rank_7_8' => ['card' => 12, 'white' => 100, 'red' => 150, 'label' => '4. ได้อันดับ 7-8'],
        ];

        foreach ($tiers as $outcomeKey => $expected) {
            $response = $this->withSession(['is_admin' => true])
                ->postJson('/api/settings/outcome', [
                    'event_mode' => 'overrun',
                    'outcome' => $outcomeKey,
                    'confirmed' => true,
                ]);

            $response->assertStatus(200);
            $response->assertJson([
                'success' => true,
                'event_mode' => 'overrun',
                'outcome' => $outcomeKey,
                'targets' => [
                    'mode' => 'overrun',
                    'key' => $outcomeKey,
                    'label' => $expected['label'],
                    'card' => $expected['card'],
                    'white_feather' => $expected['white'],
                    'red_black_feather' => $expected['red'],
                ]
            ]);
        }

        // View main page in Overrun mode (Rank 1)
        Setting::set('auction_event_mode', 'overrun');
        Setting::set('auction_outcome', 'rank_1');

        $viewResponse = $this->get('/');
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('OVERRUN');
        $viewResponse->assertSee('1. ได้อันดับ 1');
        $viewResponse->assertSee('2. ได้อันดับ 2-3');
        $viewResponse->assertSee('3. ได้อันดับ 4-6');
        $viewResponse->assertSee('4. ได้อันดับ 7-8');
    }
}
