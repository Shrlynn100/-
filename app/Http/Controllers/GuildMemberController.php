<?php

namespace App\Http\Controllers;

use App\Models\GuildMember;
use Illuminate\Http\Request;

class GuildMemberController extends Controller
{
    /**
     * Display the 80 guild members checklist page.
     */
    public function index(Request $request)
    {
        $members = GuildMember::orderBy('slot_number')->get();
        $isAdmin = (bool) session('is_admin', false);

        $filledCount = $members->filter(fn($m) => !empty(trim($m->name ?? '')))->count();
        $attendedCount = $members->filter(fn($m) => (bool) $m->attended)->count();
        $totalMembers = $members->count();

        return view('members.index', [
            'members' => $members,
            'isAdmin' => $isAdmin,
            'filledCount' => $filledCount,
            'attendedCount' => $attendedCount,
            'totalMembers' => $totalMembers,
            'availableClasses' => GuildMember::availableClasses(),
        ]);
    }

    /**
     * Auto-save endpoint for updating a member's details.
     */
    public function update(Request $request, $id)
    {
        if (!session('is_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์แก้ไขข้อมูล กรุณาเข้าสู่ระบบแอดมินก่อน',
            ], 403);
        }

        $member = GuildMember::findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'class_job' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:255',
            'attended' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $member->update($validated);

        $allMembers = GuildMember::all();
        $filledCount = $allMembers->filter(fn($m) => !empty(trim($m->name ?? '')))->count();
        $attendedCount = $allMembers->filter(fn($m) => (bool) $m->attended)->count();

        return response()->json([
            'success' => true,
            'message' => 'บันทึกข้อมูลสมาชิกเรียบร้อยแล้ว',
            'member' => $member,
            'filled_count' => $filledCount,
            'attended_count' => $attendedCount,
            'total_members' => 80,
        ]);
    }

    /**
     * Reset all 80 member slots (Admin only).
     */
    public function reset(Request $request)
    {
        if (!session('is_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์ดำเนินการนี้',
            ], 403);
        }

        foreach (GuildMember::all() as $member) {
            $member->update([
                'name' => null,
                'class_job' => null,
                'role' => $member->slot_number === 1 ? 'หัวกิลด์' : ($member->slot_number <= 3 ? 'รองหัวกิลด์' : 'สมาชิก'),
                'attended' => false,
                'notes' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'ล้างข้อมูลสมาชิกกิลด์ทั้งหมดเรียบร้อยแล้ว',
            'filled_count' => 0,
            'attended_count' => 0,
            'total_members' => 80,
        ]);
    }

    /**
     * Export member list for Discord / LINE.
     */
    public function export()
    {
        $members = GuildMember::whereNotNull('name')->where('name', '!=', '')->orderBy('slot_number')->get();

        $lines = [];
        $lines[] = "👥 【 รายชื่อสมาชิกกิลด์ FUNRAIRANKGOLD (DADDY) 】";
        $lines[] = "🗓 อัปเดตล่าสุด: " . now()->format('d/m/Y H:i');
        $lines[] = "📊 สมาชิกที่ลงทะเบียน: " . $members->count() . " / 80 คน";
        $lines[] = "----------------------------------------";

        if ($members->isEmpty()) {
            $lines[] = "(ยังไม่มีข้อมูลรายชื่อสมาชิก)";
        } else {
            foreach ($members as $m) {
                $roleBadge = !empty($m->role) && $m->role !== 'สมาชิก' ? "[{$m->role}] " : '';
                $jobStr = !empty($m->class_job) ? " ({$m->class_job})" : '';
                $notesStr = !empty($m->notes) ? " - {$m->notes}" : '';

                $lines[] = sprintf("%02d. %s%s%s%s", $m->slot_number, $roleBadge, $m->name, $jobStr, $notesStr);
            }
        }
        $lines[] = "----------------------------------------";

        return response()->json([
            'success' => true,
            'summary' => implode("\n", $lines),
        ]);
    }
}
