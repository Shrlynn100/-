<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuildMember extends Model
{
    protected $fillable = [
        'slot_number',
        'name',
        'class_job',
        'role',
        'attended',
        'notes',
    ];

    protected $casts = [
        'slot_number' => 'integer',
        'attended' => 'boolean',
    ];

    /**
     * List of official 13 ROOC classes matching game screenshots.
     */
    public static function availableClasses(): array
    {
        return [
            ['name' => 'Rebellion', 'slug' => 'rebellion'],
            ['name' => 'Lord Knight', 'slug' => 'lord_knight'],
            ['name' => 'Paladin', 'slug' => 'paladin'],
            ['name' => 'High Priest', 'slug' => 'high_priest'],
            ['name' => 'Champion', 'slug' => 'champion'],
            ['name' => 'High Wizard', 'slug' => 'high_wizard'],
            ['name' => 'Professor', 'slug' => 'professor'],
            ['name' => 'Assassin Cross', 'slug' => 'assassin_cross'],
            ['name' => 'Sniper', 'slug' => 'sniper'],
            ['name' => 'Gypsy', 'slug' => 'gypsy'],
            ['name' => 'Mastersmith', 'slug' => 'mastersmith'],
            ['name' => 'Biochemist', 'slug' => 'biochemist'],
            ['name' => 'Summoner', 'slug' => 'summoner'],
        ];
    }

    /**
     * Get image slug for a job name.
     */
    public static function jobSlug(?string $job): ?string
    {
        if (empty($job)) return null;
        $map = [
            'rebellion' => 'rebellion',
            'lord knight' => 'lord_knight',
            'paladin' => 'paladin',
            'high priest' => 'high_priest',
            'champion' => 'champion',
            'high wizard' => 'high_wizard',
            'professor' => 'professor',
            'assassin cross' => 'assassin_cross',
            'sniper' => 'sniper',
            'gypsy' => 'gypsy',
            'mastersmith' => 'mastersmith',
            'biochemist' => 'biochemist',
            'summoner' => 'summoner',
            'whitesmith' => 'mastersmith',
            'creator' => 'biochemist',
        ];

        return $map[strtolower(trim($job))] ?? null;
    }

    /**
     * Get URL of the class badge icon.
     */
    public function getJobIconUrlAttribute(): ?string
    {
        $slug = self::jobSlug($this->class_job);
        return $slug ? asset("images/classes/icons/{$slug}.png") : null;
    }

    /**
     * Get URL of the character illustration.
     */
    public function getCharacterImageUrlAttribute(): ?string
    {
        $slug = self::jobSlug($this->class_job);
        return $slug ? asset("images/classes/portraits/{$slug}.png") : null;
    }
}
