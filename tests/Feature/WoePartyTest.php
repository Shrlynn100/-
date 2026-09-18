<?php

namespace Tests\Feature;

use App\Models\GuildMember;
use App\Models\WarPartySlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WoePartyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_can_view_woe_page_with_parties_and_members_roster(): void
    {
        $response = $this->get('/woe');

        $response->assertStatus(200);
        $response->assertSee('จัดตี้วอ (War of Emperium)');
        $response->assertSee('ห้องที่ 1');
        $response->assertSee('ห้องที่ 2');
        $response->assertSee('ปาร์ตี้ 1');
        $response->assertSee('ปาร์ตี้ 8');
        $response->assertSee('สมาชิกกิลด์');
        $response->assertSee('rosterSearchInput');
        $response->assertSee('woe-main-layout');
    }

    public function test_admin_can_update_woe_party_slot(): void
    {
        $slot = WarPartySlot::first();

        $response = $this->withSession(['is_admin' => true])
            ->postJson("/api/woe/{$slot->id}", [
                'member_name' => 'กิลด์มาสเตอร์',
                'class_job'   => 'Lord Knight',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('war_party_slots', [
            'id'          => $slot->id,
            'member_name' => 'กิลด์มาสเตอร์',
            'class_job'   => 'Lord Knight',
        ]);

        $this->assertStringContains_or_ends_with('lord_knight.png', $response->json('job_icon_url'));
    }

    public function test_admin_can_clear_woe_party_slot(): void
    {
        $slot = WarPartySlot::first();
        $slot->update([
            'member_name' => 'ผู้เล่นทดสอบ',
            'class_job'   => 'Sniper',
        ]);

        $response = $this->withSession(['is_admin' => true])
            ->postJson("/api/woe/{$slot->id}", [
                'member_name' => '',
                'class_job'   => '',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('war_party_slots', [
            'id'          => $slot->id,
            'member_name' => null,
            'class_job'   => null,
        ]);
    }

    public function test_guest_cannot_update_woe_party_slot(): void
    {
        $slot = WarPartySlot::first();

        $response = $this->postJson("/api/woe/{$slot->id}", [
            'member_name' => 'ผู้บุกรุก',
        ]);

        $response->assertStatus(403);
    }

    public function test_woe_slot_has_job_icon_url_accessor(): void
    {
        $slot = WarPartySlot::first();
        $slot->class_job = 'High Priest';

        $this->assertNotNull($slot->job_icon_url);
        $this->assertStringContainsString('high_priest.png', $slot->job_icon_url);
    }

    public function test_overrun_map_is_available_and_viewable(): void
    {
        GuildMember::where('slot_number', 1)->update([
            'name' => 'หัวหน้ากิลด์',
            'class_job' => 'Lord Knight',
        ]);

        $response = $this->get('/woe?map=overrun');

        $response->assertStatus(200);
        $response->assertSee('OVERRUN');
        $response->assertSee('ห้องที่ 1 — OVERRUN');
        $response->assertSee('ห้องที่ 2 — OVERRUN');
        $response->assertSee('roster-character-bg');
    }

    public function test_member_and_slot_have_character_image_url_accessor(): void
    {
        $slot = WarPartySlot::first();
        $slot->class_job = 'High Wizard';

        $this->assertNotNull($slot->character_image_url);
        $this->assertStringContainsString('high_wizard.png', $slot->character_image_url);

        $member = GuildMember::first();
        $member->class_job = 'Rebellion';
        $this->assertNotNull($member->character_image_url);
        $this->assertStringContainsString('rebellion.png', $member->character_image_url);
    }

    private function assertStringContains_or_ends_with(string $needle, ?string $haystack): void
    {
        $this->assertNotNull($haystack);
        $this->assertStringContainsString($needle, $haystack);
    }
}
