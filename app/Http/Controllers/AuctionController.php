<?php

namespace App\Http\Controllers;

use App\Models\AuctionItem;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Http\Request;

class AuctionController extends Controller
{
    /**
     * Calculate target counts per category based on event mode and match outcome.
     */
    public static function getOutcomeTargets(?string $outcomeKey = null, ?string $eventMode = null): array
    {
        if ($outcomeKey !== null) {
            if (str_starts_with($outcomeKey, 'rank_')) {
                $eventMode = 'overrun';
            } elseif (in_array($outcomeKey, ['win_100', 'lose_70', 'lose_50', 'lose_0'])) {
                $eventMode = 'shining_stars';
            }
        }

        $eventMode = $eventMode ?: Setting::get('auction_event_mode', 'shining_stars');
        if (!in_array($eventMode, ['shining_stars', 'overrun'])) {
            $eventMode = 'shining_stars';
        }

        if ($eventMode === 'overrun') {
            $overrunTiers = [
                'rank_1' => [
                    'label' => '1. ได้อันดับ 1',
                    'card' => 20,
                    'white_feather' => 150,
                    'red_black_feather' => 170,
                ],
                'rank_2_3' => [
                    'label' => '2. ได้อันดับ 2-3',
                    'card' => 20,
                    'white_feather' => 140,
                    'red_black_feather' => 160,
                ],
                'rank_4_6' => [
                    'label' => '3. ได้อันดับ 4-6',
                    'card' => 15,
                    'white_feather' => 120,
                    'red_black_feather' => 150,
                ],
                'rank_7_8' => [
                    'label' => '4. ได้อันดับ 7-8',
                    'card' => 12,
                    'white_feather' => 100,
                    'red_black_feather' => 150,
                ],
            ];

            $outcomeKey = ($outcomeKey && isset($overrunTiers[$outcomeKey]))
                ? $outcomeKey
                : Setting::get('auction_outcome', 'rank_1');

            if (!isset($overrunTiers[$outcomeKey])) {
                $outcomeKey = 'rank_1';
            }

            $tier = $overrunTiers[$outcomeKey];

            return [
                'mode' => 'overrun',
                'mode_label' => 'OVERRUN (กิลด์โอเวอร์รัน)',
                'key' => $outcomeKey,
                'mult' => null,
                'label' => $tier['label'],
                'card' => $tier['card'],
                'white_feather' => $tier['white_feather'],
                'red_black_feather' => $tier['red_black_feather'],
                'sub_calc_card' => "OVERRUN • {$tier['label']}",
                'sub_calc_white' => "OVERRUN • {$tier['label']}",
                'sub_calc_red' => "OVERRUN • {$tier['label']}",
            ];
        }

        // Shining Stars (Guild League)
        $outcomeKey = ($outcomeKey && in_array($outcomeKey, ['win_100', 'lose_70', 'lose_50', 'lose_0']))
            ? $outcomeKey
            : Setting::get('auction_outcome', 'win_100');

        $multipliers = [
            'win_100' => 2.0,
            'lose_70'  => 1.7,
            'lose_50'  => 1.5,
            'lose_0'   => 1.0,
        ];

        $labels = [
            'win_100' => '1. ชนะ (100%)',
            'lose_70'  => '2. แพ้ (70%)',
            'lose_50'  => '3. แพ้ (50%)',
            'lose_0'   => '4. แพ้ขาด (0%)',
        ];

        $mult = $multipliers[$outcomeKey] ?? 2.0;
        $label = $labels[$outcomeKey] ?? '1. ชนะ (100%)';

        return [
            'mode' => 'shining_stars',
            'mode_label' => 'GUILD LEAGUE (Shining Stars)',
            'key' => $outcomeKey,
            'mult' => $mult,
            'label' => $label,
            'card' => (int) round(6 * $mult),
            'white_feather' => (int) round(50 * $mult),
            'red_black_feather' => (int) round(90 * $mult),
            'sub_calc_card' => "ฐาน 6 × {$label}",
            'sub_calc_white' => "ฐาน 50 × {$label}",
            'sub_calc_red' => "ฐาน 90 × {$label}",
        ];
    }

    /**
     * Display the main auction/checklist view.
     */
    public function index(Request $request)
    {
        $categories = Category::orderBy('sort_order')->get();
        $activeSlug = $request->query('category', $categories->first()?->slug ?? 'card');
        $activeCategory = $categories->firstWhere('slug', $activeSlug) ?? $categories->first();

        $eventMode = Setting::get('auction_event_mode', 'shining_stars');
        $outcomeKey = Setting::get('auction_outcome', $eventMode === 'overrun' ? 'rank_1' : 'win_100');
        $outcomeConfirmed = (bool) Setting::get('auction_outcome_confirmed', true);
        $targets = self::getOutcomeTargets($outcomeKey, $eventMode);

        $targetItems = $targets[$activeCategory->slug] ?? 80;

        $allItems = $activeCategory 
            ? $activeCategory->items()->get() 
            : collect();

        // Only display items up to targetItems
        $items = $allItems->filter(fn($item) => $item->slot_number <= $targetItems);

        // Group items into pages of 4 items each
        $groupedPages = $items->groupBy('page_number');

        $whitePages = (int) ceil(($targets['white_feather'] ?? 100) / 4);
        $pageOffset = ($activeCategory->slug === 'red_black_feather') ? $whitePages : 0;

        $isAdmin = (bool) session('is_admin', false);

        $filledCount = $items->filter(fn($item) => !empty(trim($item->name ?? '')))->count();

        // Calculate dynamic targets and filled counts for all category tabs
        foreach ($categories as $cat) {
            $catTarget = $targets[$cat->slug] ?? 80;
            $cat->display_target = $catTarget;
            $cat->display_filled = $cat->items()
                ->where('slot_number', '<=', $catTarget)
                ->whereNotNull('name')
                ->where('name', '!=', '')
                ->count();
        }

        $guildMembers = \App\Models\GuildMember::orderBy('slot_number')->get();

        return view('auction.index', [
            'categories' => $categories,
            'activeCategory' => $activeCategory,
            'groupedPages' => $groupedPages,
            'items' => $items,
            'isAdmin' => $isAdmin,
            'filledCount' => $filledCount,
            'totalItems' => $targetItems,
            'eventMode' => $eventMode,
            'outcomeKey' => $outcomeKey,
            'outcomeConfirmed' => $outcomeConfirmed,
            'targets' => $targets,
            'guildMembers' => $guildMembers,
            'pageOffset' => $pageOffset,
            'whitePages' => $whitePages,
        ]);
    }

    /**
     * Get items for a category via JSON (for dynamic tab switching without page reload).
     */
    public function getCategoryData($slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $outcomeKey = Setting::get('auction_outcome', 'win_100');
        $targets = self::getOutcomeTargets($outcomeKey);
        $targetItems = $targets[$category->slug] ?? 80;

        $items = $category->items()
            ->where('slot_number', '<=', $targetItems)
            ->get();

        $filledCount = $items->filter(fn($item) => !empty(trim($item->name ?? '')))->count();

        return response()->json([
            'category' => $category,
            'items' => $items,
            'filled_count' => $filledCount,
            'total_items' => $targetItems,
        ]);
    }

    /**
     * Update an auction item (Auto-save endpoint for Admin).
     */
    public function updateItem(Request $request, $id)
    {
        if (!session('is_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์แก้ไขข้อมูล กรุณาเข้าสู่ระบบแอดมินก่อน',
            ], 403);
        }

        $item = AuctionItem::with('category')->findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'book1' => 'nullable|boolean',
            'book2' => 'nullable|boolean',
            'book3' => 'nullable|boolean',
            'book4' => 'nullable|boolean',
            'winner_name' => 'nullable|string|max:255',
            'final_price' => 'nullable|numeric',
            'notes' => 'nullable|string|max:1000',
            'item_date' => 'nullable|string|max:50',
        ]);

        if (in_array($item->category->slug, ['white_feather', 'red_black_feather'])) {
            AuctionItem::where('category_id', $item->category_id)
                ->where('page_number', $item->page_number)
                ->update($validated);
            $item->refresh();
        } else {
            $item->update($validated);
        }

        // If date was updated, synchronize across all items
        if (!empty($validated['item_date'])) {
            AuctionItem::query()->update(['item_date' => $validated['item_date']]);
            $item->refresh();
        }

        $category = $item->category;
        $outcomeKey = Setting::get('auction_outcome', 'win_100');
        $targets = self::getOutcomeTargets($outcomeKey);
        $targetItems = $targets[$category->slug] ?? 80;

        $filledCount = $category->items()
            ->where('slot_number', '<=', $targetItems)
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว',
            'item' => $item,
            'filled_count' => $filledCount,
            'total_items' => $targetItems,
        ]);
    }

    /**
     * Reset/Clear all items in a category (Admin only).
     */
    public function resetCategory(Request $request, $slug)
    {
        if (!session('is_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์ดำเนินการนี้',
            ], 403);
        }

        $category = Category::where('slug', $slug)->firstOrFail();
        $outcomeKey = Setting::get('auction_outcome', 'win_100');
        $targets = self::getOutcomeTargets($outcomeKey);
        $targetItems = $targets[$category->slug] ?? 80;

        $category->items()->update([
            'name' => null,
            'book1' => false,
            'book2' => false,
            'book3' => false,
            'book4' => false,
            'winner_name' => null,
            'final_price' => null,
            'notes' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "ล้างข้อมูลหมวดหมู่ {$category->name} เรียบร้อยแล้ว",
            'filled_count' => 0,
            'total_items' => $targetItems,
        ]);
    }

    /**
     * Save match outcome selection and confirmation status (Admin only).
     */
    public function saveOutcome(Request $request)
    {
        if (!session('is_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์แก้ไขผลการแข่งขัน กรุณาเข้าสู่ระบบแอดมินก่อน',
            ], 403);
        }

        $validated = $request->validate([
            'event_mode' => 'nullable|string|in:shining_stars,overrun',
            'outcome' => 'required|string|in:win_100,lose_70,lose_50,lose_0,rank_1,rank_2_3,rank_4_6,rank_7_8',
            'confirmed' => 'required|boolean',
        ]);

        $eventMode = $validated['event_mode'] ?? null;
        if (!$eventMode) {
            $eventMode = str_starts_with($validated['outcome'], 'rank_') ? 'overrun' : 'shining_stars';
        }

        Setting::set('auction_event_mode', $eventMode);
        Setting::set('auction_outcome', $validated['outcome']);
        Setting::set('auction_outcome_confirmed', $validated['confirmed'] ? 1 : 0);

        $targets = self::getOutcomeTargets($validated['outcome'], $eventMode);

        return response()->json([
            'success' => true,
            'message' => $validated['confirmed'] ? 'บันทึกและยืนยันผลการแข่งขันเรียบร้อยแล้ว' : 'ปลดล็อกเพื่อแก้ไขผลการแข่งขันแล้ว',
            'event_mode' => $eventMode,
            'outcome' => $validated['outcome'],
            'confirmed' => (bool) $validated['confirmed'],
            'targets' => $targets,
        ]);
    }

    /**
     * Export category summary formatted for Discord / LINE guild chats.
     */
    public function exportSummary($slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $outcomeKey = Setting::get('auction_outcome', 'win_100');
        $targets = self::getOutcomeTargets($outcomeKey);
        $targetItems = $targets[$category->slug] ?? 80;

        $items = $category->items()
            ->where('slot_number', '<=', $targetItems)
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->get();

        $lines = [];
        $lines[] = "📢 【 สรุปรายการ ROOC - {$category->name} 】";
        $lines[] = "🗓 อัปเดตล่าสุด: " . now()->format('d/m/Y H:i');
        $lines[] = "🏆 ผลการแข่งขัน: " . $targets['label'];
        $lines[] = "📊 กรอกแล้ว: " . $items->count() . " / " . $targetItems . " รายการ";
        $lines[] = "----------------------------------------";

        if ($items->isEmpty()) {
            $lines[] = "(ยังไม่มีข้อมูลรายการ)";
        } else {
            foreach ($items as $item) {
                $books = [];
                if ($item->book1) $books[] = "เล่ม 1";
                if ($item->book2) $books[] = "เล่ม 2";
                if ($item->book3) $books[] = "เล่ม 3";
                if ($item->book4) $books[] = "เล่ม 4";
                
                $bookStr = !empty($books) ? ' [' . implode(', ', $books) . ']' : '';
                $winnerStr = !empty($item->winner_name) ? " 👉 ผู้ชนะ: {$item->winner_name}" : '';
                $priceStr = !empty($item->final_price) ? " (" . number_format($item->final_price) . " Zeny)" : '';

                $lines[] = sprintf("%02d. %s%s%s%s", $item->slot_number, $item->name, $bookStr, $winnerStr, $priceStr);
            }
        }
        $lines[] = "----------------------------------------";

        return response()->json([
            'success' => true,
            'summary' => implode("\n", $lines),
        ]);
    }

    /**
     * Synchronize date across all auction items (Admin only).
     */
    public function syncDate(Request $request)
    {
        if (!session('is_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์แก้ไขข้อมูล กรุณาเข้าสู่ระบบแอดมินก่อน',
            ], 403);
        }

        $validated = $request->validate([
            'item_date' => 'required|string|max:50',
        ]);

        AuctionItem::query()->update(['item_date' => $validated['item_date']]);

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตวันที่ให้ทุกรายการเรียบร้อยแล้ว',
            'item_date' => $validated['item_date'],
        ]);
    }

    /**
     * Get full aggregated auction overview data for visual table modal.
     */
    public function getOverviewData()
    {
        $categories = Category::orderBy('sort_order')->get();
        $outcomeKey = Setting::get('auction_outcome', 'win_100');
        $targets = self::getOutcomeTargets($outcomeKey);

        $overviewData = [];
        $allWinners = [];
        $totalFilled = 0;
        $totalItemsTarget = 0;

        foreach ($categories as $category) {
            $targetCount = $targets[$category->slug] ?? 80;
            $totalItemsTarget += $targetCount;

            $items = $category->items()
                ->where('slot_number', '<=', $targetCount)
                ->get();

            $whitePages = (int) ceil(($targets['white_feather'] ?? 100) / 4);
            $pageOffset = ($category->slug === 'red_black_feather') ? $whitePages : 0;

            $categoryFilled = 0;
            $itemsList = [];

            if (in_array($category->slug, ['white_feather', 'red_black_feather'])) {
                $grouped = $items->groupBy('page_number');
                foreach ($grouped as $pageNumber => $pageItems) {
                    $item = $pageItems->first();
                    $name = trim($item->name ?? '');
                    $displayPage = $pageOffset + $pageNumber;
                    $itemsInThisPage = $pageItems->count();
                    $pieceLabel = $itemsInThisPage >= 4 ? "อันที่ 1-4" : ($itemsInThisPage === 1 ? "อันที่ 1" : "อันที่ 1-{$itemsInThisPage}");
                    $isFilled = !empty($name);
                    if ($isFilled) {
                        $categoryFilled++;
                        $totalFilled++;
                    }

                    $entry = [
                        'id' => $item->id,
                        'category_slug' => $category->slug,
                        'category_name' => $category->name,
                        'slot_number' => $item->slot_number,
                        'page_number' => $displayPage,
                        'page_label' => "หน้าที่ {$displayPage} ({$pieceLabel})",
                        'name' => $name,
                        'item_date' => $item->item_date ?: '18/09/69',
                        'notes' => $item->notes ?? '',
                        'is_filled' => $isFilled,
                    ];
                    $itemsList[] = $entry;

                    if ($isFilled) {
                        $allWinners[] = $entry;
                    }
                }
            } else {
                // Card or other items (หน้า 1 เล่ม 1..4, หน้า 2 เล่ม 1..4)
                foreach ($items as $item) {
                    $name = trim($item->name ?? '');
                    $cardPage = (int) ceil($item->slot_number / 4);
                    $cardBook = (($item->slot_number - 1) % 4) + 1;
                    $cardLabel = "หน้า {$cardPage} เล่ม {$cardBook}";
                    $isFilled = !empty($name);
                    if ($isFilled) {
                        $categoryFilled++;
                        $totalFilled++;
                    }

                    $entry = [
                        'id' => $item->id,
                        'category_slug' => $category->slug,
                        'category_name' => $category->name,
                        'slot_number' => $item->slot_number,
                        'page_number' => $cardPage,
                        'page_label' => $cardLabel,
                        'name' => $name,
                        'item_date' => $item->item_date ?: '18/09/69',
                        'notes' => $item->notes ?? '',
                        'is_filled' => $isFilled,
                    ];
                    $itemsList[] = $entry;

                    if ($isFilled) {
                        $allWinners[] = $entry;
                    }
                }
            }

            $overviewData[] = [
                'name' => $category->name,
                'slug' => $category->slug,
                'icon' => $category->icon,
                'target' => $targetCount,
                'filled' => $categoryFilled,
                'items' => $itemsList,
            ];
        }

        // Aggregate by member name
        $memberSummary = [];
        foreach ($allWinners as $w) {
            $mName = $w['name'];
            if (!isset($memberSummary[$mName])) {
                $memberSummary[$mName] = [
                    'name' => $mName,
                    'count' => 0,
                    'items' => [],
                ];
            }
            $memberSummary[$mName]['count']++;
            $memberSummary[$mName]['items'][] = [
                'category' => $w['category_name'],
                'category_slug' => $w['category_slug'],
                'page_label' => $w['page_label'],
                'date' => $w['item_date'],
                'notes' => $w['notes'],
            ];
        }

        return response()->json([
            'success' => true,
            'outcome' => [
                'key' => $outcomeKey,
                'label' => $targets['label'],
            ],
            'total_target' => $totalItemsTarget,
            'total_filled' => $totalFilled,
            'categories' => $overviewData,
            'winners' => $allWinners,
            'member_summary' => array_values($memberSummary),
        ]);
    }
}
