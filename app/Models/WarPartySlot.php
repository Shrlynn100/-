<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarPartySlot extends Model
{
    protected $fillable = [
        'map_slug',
        'room_number',
        'party_number',
        'slot_number',
        'member_name',
        'class_job',
        'role',
        'notes',
    ];

    protected $casts = [
        'room_number' => 'integer',
        'party_number' => 'integer',
        'slot_number' => 'integer',
    ];

    public static function mapNames(): array
    {
        return [
            'stella_clash'   => 'Stella Clash',
            'valor_of_clash' => 'Valor of Clash',
            'vigid_map'      => 'Vigid Map',
            'overrun'        => 'OVERRUN',
        ];
    }

    /**
     * Get URL of the class badge icon.
     */
    public function getJobIconUrlAttribute(): ?string
    {
        $slug = GuildMember::jobSlug($this->class_job);
        return $slug ? asset("images/classes/icons/{$slug}.png") : null;
    }

    /**
     * Get URL of the character illustration.
     */
    public function getCharacterImageUrlAttribute(): ?string
    {
        $slug = GuildMember::jobSlug($this->class_job);
        return $slug ? asset("images/classes/portraits/{$slug}.png") : null;
    }
}
