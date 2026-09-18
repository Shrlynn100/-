<?php

namespace App\Http\Controllers;

use App\Models\GuildMember;
use App\Models\WarPartySlot;
use Illuminate\Http\Request;

class WoeController extends Controller
{
    /**
     * Display the WoE (War of Emperium) team composition page.
     * Shows 3 maps, 2 rooms each, 8 parties each room, 5 slots per party.
     */
    public function index(Request $request)
    {
        $isAdmin = (bool) session('is_admin', false);
        $mapNames = WarPartySlot::mapNames();

        // Seed slots for any maps that don't have slots yet
        $this->ensureMapSlots();

        // Load all slots
        $slots = WarPartySlot::all();

        // Organise: [map_slug][room_number][party_number][slot_number] => slot
        $data = [];
        foreach ($mapNames as $slug => $name) {
            $data[$slug] = [];
            for ($room = 1; $room <= 2; $room++) {
                $data[$slug][$room] = [];
                for ($party = 1; $party <= 8; $party++) {
                    $data[$slug][$room][$party] = [];
                    for ($slot = 1; $slot <= 5; $slot++) {
                        $data[$slug][$room][$party][$slot] = null;
                    }
                }
            }
        }

        foreach ($slots as $slot) {
            if (isset($data[$slot->map_slug][$slot->room_number][$slot->party_number])) {
                $data[$slot->map_slug][$slot->room_number][$slot->party_number][$slot->slot_number] = $slot;
            }
        }

        // Active map (default: first map)
        $activeMap = $request->query('map', array_key_first($mapNames));
        if (!array_key_exists($activeMap, $mapNames)) {
            $activeMap = array_key_first($mapNames);
        }

        // Fetch guild members who have a name entered
        $guildMembers = GuildMember::whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('slot_number')
            ->get();

        // Map which guild members are currently assigned in this active map
        $assignedMap = [];
        $activeSlots = WarPartySlot::where('map_slug', $activeMap)
            ->whereNotNull('member_name')
            ->where('member_name', '!=', '')
            ->get();

        foreach ($activeSlots as $as) {
            $nameKey = mb_strtolower(trim($as->member_name));
            $assignedMap[$nameKey] = [
                'room'    => $as->room_number,
                'party'   => $as->party_number,
                'slot'    => $as->slot_number,
                'slot_id' => $as->id,
                'label'   => "ห้อง {$as->room_number} ตี้ {$as->party_number}",
            ];
        }

        return view('woe.index', [
            'isAdmin'       => $isAdmin,
            'mapNames'      => $mapNames,
            'activeMap'     => $activeMap,
            'data'          => $data,
            'guildMembers'  => $guildMembers,
            'assignedMap'   => $assignedMap,
        ]);
    }

    /**
     * Update a single WoE slot field (Admin only).
     */
    public function updateSlot(Request $request, $id)
    {
        if (!session('is_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์แก้ไขข้อมูล',
            ], 403);
        }

        $slot = WarPartySlot::findOrFail($id);

        $validated = $request->validate([
            'member_name' => 'nullable|string|max:255',
            'class_job'   => 'nullable|string|max:255',
            'role'        => 'nullable|string|max:100',
            'notes'       => 'nullable|string|max:500',
        ]);

        // Clean values
        if (array_key_exists('member_name', $validated)) {
            $validated['member_name'] = trim($validated['member_name'] ?? '') ?: null;
        }
        if (array_key_exists('class_job', $validated)) {
            $validated['class_job'] = trim($validated['class_job'] ?? '') ?: null;
        }

        // If member_name is provided but class_job is empty, attempt auto-fill from GuildMember roster
        if (!empty($validated['member_name']) && empty($validated['class_job'])) {
            $member = GuildMember::where('name', $validated['member_name'])->first();
            if ($member && !empty($member->class_job)) {
                $validated['class_job'] = $member->class_job;
            }
        }

        // If member is cleared, also clear class_job unless explicitly specified
        if (array_key_exists('member_name', $validated) && is_null($validated['member_name']) && !array_key_exists('class_job', $validated)) {
            $validated['class_job'] = null;
        }

        $slot->update($validated);

        return response()->json([
            'success'      => true,
            'message'      => 'บันทึกข้อมูลสมาชิกตี้วอแล้ว',
            'slot'         => $slot,
            'job_icon_url' => $slot->job_icon_url,
        ]);
    }

    /**
     * Reset all WoE slots for a specific map (Admin only).
     */
    public function resetMap(Request $request, $mapSlug)
    {
        if (!session('is_admin')) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์'], 403);
        }

        $mapNames = WarPartySlot::mapNames();
        if (!array_key_exists($mapSlug, $mapNames)) {
            return response()->json(['success' => false, 'message' => 'ไม่พบแมพที่ระบุ'], 404);
        }

        WarPartySlot::where('map_slug', $mapSlug)->update([
            'member_name' => null,
            'class_job'   => null,
            'role'        => 'สมาชิก',
            'notes'       => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "ล้างข้อมูลแมพ {$mapNames[$mapSlug]} เรียบร้อยแล้ว",
        ]);
    }

    /**
     * Seed empty slots for all maps (4 maps × 2 rooms × 8 parties × 5 members = 320 slots).
     */
    private function ensureMapSlots(): void
    {
        $maps = array_keys(WarPartySlot::mapNames());
        $now  = now();

        foreach ($maps as $mapSlug) {
            if (WarPartySlot::where('map_slug', $mapSlug)->exists()) {
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
                WarPartySlot::insert($chunk);
            }
        }
    }
}
