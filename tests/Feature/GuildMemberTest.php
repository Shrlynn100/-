<?php

namespace Tests\Feature;

use App\Models\GuildMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuildMemberTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_can_view_guild_members_page_with_80_slots(): void
    {
        $response = $this->get('/members');

        $response->assertStatus(200);
        $response->assertSee('สมาชิกกิลด์ FUNRAIRANKGOLD (DADDY)');
        $response->assertSee('โหมดผู้เข้าชม (ดูได้อย่างเดียว)');
        $response->assertDontSee('<th>มาวอร์</th>', false);
        $response->assertDontSee('มาวอร์ (', false);
        $this->assertEquals(80, GuildMember::count());
    }

    public function test_available_classes_matches_official_list_without_clown_and_creator(): void
    {
        $classes = GuildMember::availableClasses();
        $classNames = array_column($classes, 'name');
        $this->assertCount(13, $classNames);
        $this->assertContains('Rebellion', $classNames);
        $this->assertContains('Lord Knight', $classNames);
        $this->assertContains('Paladin', $classNames);
        $this->assertContains('High Priest', $classNames);
        $this->assertContains('Champion', $classNames);
        $this->assertContains('High Wizard', $classNames);
        $this->assertContains('Professor', $classNames);
        $this->assertContains('Assassin Cross', $classNames);
        $this->assertContains('Sniper', $classNames);
        $this->assertContains('Gypsy', $classNames);
        $this->assertContains('Mastersmith', $classNames);
        $this->assertContains('Biochemist', $classNames);
        $this->assertContains('Summoner', $classNames);

        $this->assertNotContains('Clown', $classNames);
        $this->assertNotContains('Creator', $classNames);
        $this->assertNotContains('Whitesmith', $classNames);
        $this->assertNotContains('Stalker', $classNames);

        // Check assets exist on disk for each class
        foreach ($classNames as $className) {
            $slug = GuildMember::jobSlug($className);
            $this->assertFileExists(public_path("images/classes/icons/{$slug}.png"));
            $this->assertFileExists(public_path("images/classes/characters/{$slug}.png"));
        }
    }

    public function test_guest_cannot_update_guild_member(): void
    {
        $member = GuildMember::first();

        $response = $this->postJson("/api/members/{$member->id}", [
            'name' => 'เทพซ่า007',
            'class_job' => 'Lord Knight',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_guild_member_with_auto_save(): void
    {
        $member = GuildMember::first();

        $response = $this->withSession(['is_admin' => true])
            ->postJson("/api/members/{$member->id}", [
                'name' => 'กิลด์มาสเตอร์',
                'class_job' => 'Lord Knight',
                'role' => 'หัวกิลด์',
                'notes' => 'แกนนำทัพหน้า',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'filled_count' => 1,
        ]);

        $this->assertDatabaseHas('guild_members', [
            'id' => $member->id,
            'name' => 'กิลด์มาสเตอร์',
            'class_job' => 'Lord Knight',
        ]);
    }

    public function test_export_guild_members_summary(): void
    {
        $member = GuildMember::first();
        $member->update([
            'name' => 'สายฟ้าฟาด',
            'class_job' => 'High Wizard',
        ]);

        $response = $this->getJson('/api/members/export');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertStringContainsString('สายฟ้าฟาด', $response->json('summary'));
        $this->assertStringContainsString('High Wizard', $response->json('summary'));
        $this->assertStringNotContainsString('[มาวอร์]', $response->json('summary'));
        $this->assertStringNotContainsString('[ไม่มา]', $response->json('summary'));
    }

    public function test_admin_can_reset_guild_members(): void
    {
        $member = GuildMember::first();
        $member->update([
            'name' => 'ทดสอบรีเซ็ต',
        ]);

        $response = $this->withSession(['is_admin' => true])
            ->postJson('/api/members/reset');

        $response->assertStatus(200);
        $this->assertEquals(0, GuildMember::whereNotNull('name')->count());
    }
}
