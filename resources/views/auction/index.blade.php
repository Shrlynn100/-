@extends('layouts.app')

@section('title', 'ROOC - ' . $activeCategory->name . ' (' . $filledCount . '/' . $totalItems . ')')

@section('content')
<div class="auction-dashboard">
    <!-- Header Section matching the reference image -->
    <div class="dashboard-header-card">
        <div class="header-main-title header-flex">
            @if(file_exists(public_path('images/' . $activeCategory->slug . '.png')))
                <img src="{{ asset('images/' . $activeCategory->slug . '.png') }}" class="header-category-icon" alt="{{ $activeCategory->name }}">
            @endif
            <div>
                <h1 class="page-heading" id="pageTitleHeading">{{ $activeCategory->name }}</h1>
                <p class="page-subheading">กรอกชื่อและติ๊กคุณลักษณะได้เลย ระบบจะบันทึกให้อัตโนมัติทุกครั้งที่แก้ไข</p>
            </div>
        </div>

        <!-- Badges Row -->
        <div class="badges-row">
            <div class="badge-pill badge-pill-cyan" id="filledBadge">
                <span class="badge-icon">📊</span>
                <span>กรอกแล้ว <strong id="filledCounterText">{{ $filledCount }}</strong> / <span id="totalCounterText">{{ $totalItems }}</span></span>
            </div>

            @if($isAdmin)
                <div class="badge-pill badge-pill-gray">
                    <span class="badge-icon">✏️</span>
                    <span>โหมดแอดมิน แก้ไขได้</span>
                </div>
                <div id="saveStatusBadge" class="badge-pill badge-pill-saved">
                    <span class="save-status-icon">✓</span>
                    <span class="save-status-text">พร้อมบันทึกอัตโนมัติ</span>
                </div>
            @else
                <div class="badge-pill badge-pill-locked">
                    <span class="badge-icon">🔒</span>
                    <span>โหมดผู้เข้าชม (ดูได้อย่างเดียว)</span>
                </div>
            @endif
        </div>
        <!-- ⭐ Shining Stars / OVERRUN Rewards Calculator Widget -->
        <div class="rewards-calculator-card">
            <div class="rewards-calc-top">
                <div class="event-mode-switcher-group">
                    <div class="mode-tabs-wrap" role="tablist" aria-label="เลือกหมวดกิจกรรมการประมูล">
                        <button type="button" class="mode-tab-pill {{ $eventMode === 'shining_stars' ? 'is-active' : '' }}" 
                                data-mode="shining_stars" id="modeTab_shining_stars" 
                                title="กิจกรรม GUILD LEAGUE (Shining Stars)">
                            <span class="mode-pill-icon">⭐</span>
                            <span class="mode-pill-text"><strong>GUILD LEAGUE</strong> <span class="mode-pill-sub">(Shining Stars)</span></span>
                        </button>
                        <button type="button" class="mode-tab-pill {{ $eventMode === 'overrun' ? 'is-active' : '' }}" 
                                data-mode="overrun" id="modeTab_overrun" 
                                title="กิจกรรม OVERRUN (กิลด์โอเวอร์รัน)">
                            <span class="mode-pill-icon">⚡</span>
                            <span class="mode-pill-text"><strong>OVERRUN</strong> <span class="mode-pill-sub">(กิลด์โอเวอร์รัน)</span></span>
                        </button>
                    </div>
                    <span class="rank-section-title">รางวัลการประมูล (เมื่อแข่งจบจะได้ของมาประมูล)</span>
                </div>

                <!-- Match Outcome Options with Confirm / Change -->
                <div class="outcome-selector-wrap">
                    <div class="outcome-header-row">
                        <span class="outcome-title-label">ผลการแข่งขัน / อันดับ:</span>
                        <div id="outcomeStatusBadge" class="outcome-status-tag {{ $outcomeConfirmed ? 'is-confirmed' : 'is-pending' }}">
                            @if($outcomeConfirmed)
                                <span class="tag-icon">✅</span>
                                <span class="tag-text">ยืนยันผลแล้ว</span>
                            @else
                                <span class="tag-icon">✏️</span>
                                <span class="tag-text">เลือกแล้ว (ยังไม่ยืนยัน)</span>
                            @endif
                        </div>
                    </div>

                    <div class="outcome-actions-bar">
                        <!-- Guild League Buttons (Shining Stars) -->
                        <div class="outcome-button-group {{ $eventMode === 'shining_stars' ? '' : 'hidden' }}" role="group" id="outcomeGroup_shining_stars">
                            <button type="button" class="outcome-btn {{ ($eventMode === 'shining_stars' && $outcomeKey === 'win_100') ? 'is-active' : '' }}" 
                                    data-key="win_100" data-mult="2.0" data-label="1. ชนะ (100%)" {{ (!$isAdmin && $outcomeConfirmed) ? 'disabled' : '' }}>
                                <span class="outcome-icon">🏆</span> 1. ชนะ (100%)
                            </button>
                            <button type="button" class="outcome-btn {{ ($eventMode === 'shining_stars' && $outcomeKey === 'lose_70') ? 'is-active' : '' }}" 
                                    data-key="lose_70" data-mult="1.7" data-label="2. แพ้ (70%)" {{ (!$isAdmin && $outcomeConfirmed) ? 'disabled' : '' }}>
                                <span class="outcome-icon">🥈</span> 2. แพ้ (70%)
                            </button>
                            <button type="button" class="outcome-btn {{ ($eventMode === 'shining_stars' && $outcomeKey === 'lose_50') ? 'is-active' : '' }}" 
                                    data-key="lose_50" data-mult="1.5" data-label="3. แพ้ (50%)" {{ (!$isAdmin && $outcomeConfirmed) ? 'disabled' : '' }}>
                                <span class="outcome-icon">🥉</span> 3. แพ้ (50%)
                            </button>
                            <button type="button" class="outcome-btn {{ ($eventMode === 'shining_stars' && $outcomeKey === 'lose_0') ? 'is-active' : '' }}" 
                                    data-key="lose_0" data-mult="1.0" data-label="4. แพ้ขาด (0%)" {{ (!$isAdmin && $outcomeConfirmed) ? 'disabled' : '' }}>
                                <span class="outcome-icon">❌</span> 4. แพ้ขาด (0%)
                            </button>
                        </div>

                        <!-- OVERRUN Buttons -->
                        <div class="outcome-button-group {{ $eventMode === 'overrun' ? '' : 'hidden' }}" role="group" id="outcomeGroup_overrun">
                            <button type="button" class="outcome-btn {{ ($eventMode === 'overrun' && $outcomeKey === 'rank_1') ? 'is-active' : '' }}" 
                                    data-key="rank_1" data-label="1. ได้อันดับ 1" {{ (!$isAdmin && $outcomeConfirmed) ? 'disabled' : '' }}>
                                <span class="outcome-icon">🥇</span> 1. ได้อันดับ 1
                            </button>
                            <button type="button" class="outcome-btn {{ ($eventMode === 'overrun' && $outcomeKey === 'rank_2_3') ? 'is-active' : '' }}" 
                                    data-key="rank_2_3" data-label="2. ได้อันดับ 2-3" {{ (!$isAdmin && $outcomeConfirmed) ? 'disabled' : '' }}>
                                <span class="outcome-icon">🥈</span> 2. ได้อันดับ 2-3
                            </button>
                            <button type="button" class="outcome-btn {{ ($eventMode === 'overrun' && $outcomeKey === 'rank_4_6') ? 'is-active' : '' }}" 
                                    data-key="rank_4_6" data-label="3. ได้อันดับ 4-6" {{ (!$isAdmin && $outcomeConfirmed) ? 'disabled' : '' }}>
                                <span class="outcome-icon">🥉</span> 3. ได้อันดับ 4-6
                            </button>
                            <button type="button" class="outcome-btn {{ ($eventMode === 'overrun' && $outcomeKey === 'rank_7_8') ? 'is-active' : '' }}" 
                                    data-key="rank_7_8" data-label="4. ได้อันดับ 7-8" {{ (!$isAdmin && $outcomeConfirmed) ? 'disabled' : '' }}>
                                <span class="outcome-icon">🎖️</span> 4. ได้อันดับ 7-8
                            </button>
                        </div>

                        @if($isAdmin)
                            <div class="outcome-control-buttons">
                                <button type="button" id="confirmOutcomeBtn" class="btn-confirm-outcome {{ $outcomeConfirmed ? 'hidden' : '' }}" title="กดยืนยันผลการแข่งขันและปรับตารางอัตโนมัติ">
                                    <span>✅ กดยืนยัน</span>
                                </button>
                                <button type="button" id="changeOutcomeBtn" class="btn-change-outcome {{ !$outcomeConfirmed ? 'hidden' : '' }}" title="คลิกเพื่อปลดล็อกและเปลี่ยนผลการแข่งขัน">
                                    <span>🔄 เปลี่ยนผลการแข่ง</span>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 3 Items Display Grid (ถอดแบบตามภาพที่ 1) -->
            <div class="rewards-display-grid">
                <!-- ขนขาว (Base 50) -->
                <a href="{{ route('auction.index', ['category' => 'white_feather']) }}" 
                   class="reward-display-card {{ $activeCategory->slug === 'white_feather' ? 'is-selected-cat' : '' }}" 
                   title="คลิกเพื่อส่องหมวด ขนขาว">
                    <div class="reward-img-container">
                        <img src="{{ asset('images/white_feather.png') }}" alt="ขนขาว" class="reward-card-img">
                        <span class="reward-counter-number" id="calc_white_feather">{{ $targets['white_feather'] }}</span>
                    </div>
                    <div class="reward-info-footer">
                        <span class="reward-title-name">ขนขาว</span>
                        <span class="reward-sub-calc" id="sub_white_feather">{{ $targets['sub_calc_white'] ?? ('ฐาน 50 × ' . $targets['label']) }}</span>
                    </div>
                </a>

                <!-- ขนแดงดำ (Base 90) -->
                <a href="{{ route('auction.index', ['category' => 'red_black_feather']) }}" 
                   class="reward-display-card {{ $activeCategory->slug === 'red_black_feather' ? 'is-selected-cat' : '' }}" 
                   title="คลิกเพื่อส่องหมวด ขนแดงดำ">
                    <div class="reward-img-container">
                        <img src="{{ asset('images/red_black_feather.png') }}" alt="ขนแดงดำ" class="reward-card-img">
                        <span class="reward-counter-number" id="calc_red_black_feather">{{ $targets['red_black_feather'] }}</span>
                    </div>
                    <div class="reward-info-footer">
                        <span class="reward-title-name">ขนแดงดำ</span>
                        <span class="reward-sub-calc" id="sub_red_black_feather">{{ $targets['sub_calc_red'] ?? ('ฐาน 90 × ' . $targets['label']) }}</span>
                    </div>
                </a>

                <!-- การ์ด (Base 6) -->
                <a href="{{ route('auction.index', ['category' => 'card']) }}" 
                   class="reward-display-card {{ $activeCategory->slug === 'card' ? 'is-selected-cat' : '' }}" 
                   title="คลิกเพื่อส่องหมวด การ์ด">
                    <div class="reward-img-container">
                        <img src="{{ asset('images/card.png') }}" alt="การ์ด" class="reward-card-img">
                        <span class="reward-counter-number" id="calc_card">{{ $targets['card'] }}</span>
                    </div>
                    <div class="reward-info-footer">
                        <span class="reward-title-name">การ์ด</span>
                        <span class="reward-sub-calc" id="sub_card">{{ $targets['sub_calc_card'] ?? ('ฐาน 6 × ' . $targets['label']) }}</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- Category Tabs Row -->
        <div class="category-tabs-container">
            <div class="category-tabs-list" role="tablist">
                @foreach($categories as $category)
                    <a href="{{ route('auction.index', ['category' => $category->slug]) }}" 
                       class="category-tab-btn {{ $activeCategory->id === $category->id ? 'is-active' : '' }}" 
                       data-slug="{{ $category->slug }}">
                        @if(file_exists(public_path('images/' . $category->slug . '.png')))
                            <img src="{{ asset('images/' . $category->slug . '.png') }}" class="tab-category-img" alt="{{ $category->name }}">
                        @else
                            <span class="tab-icon">{{ $category->icon }}</span>
                        @endif
                        <span class="tab-name">{{ $category->name }}</span>
                        <span class="tab-counter-badge" id="tabBadge_{{ $category->slug }}">{{ $category->display_filled }}/{{ $category->display_target }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Controls Toolbar (Search, Filter, Quick Actions) -->
    <div class="toolbar-section">
        <div class="toolbar-left">
            <!-- Search bar -->
            <div class="search-input-wrapper">
                <span class="search-icon">🔍</span>
                <input type="text" id="itemSearchInput" class="search-input" placeholder="ค้นหาชื่อไอเทมในหน้านี้..." autocomplete="off">
                <button type="button" id="clearSearchBtn" class="clear-search-btn hidden" title="ล้างการค้นหา">&times;</button>
            </div>

            <!-- Filter Buttons -->
            <div class="filter-btn-group">
                <button type="button" class="filter-btn is-active" data-filter="all">ทั้งหมด (<span id="filterTotalNum">{{ $totalItems }}</span>)</button>
                <button type="button" class="filter-btn" data-filter="filled">เฉพาะที่กรอกแล้ว (<span id="filterFilledNum">{{ $filledCount }}</span>)</button>
                <button type="button" class="filter-btn" data-filter="unfilled">ยังไม่กรอก</button>
            </div>
        </div>
        <div class="toolbar-right">
            <!-- Export / Summary Button -->
            <button type="button" class="tool-btn btn-summary" onclick="openAuctionOverviewModal()">
                <span>📋 สรุปรายการ (กิลด์)</span>
            </button>

            @if($isAdmin)
                <!-- Reset Category Items Button -->
                <button type="button" class="tool-btn btn-danger-outline" onclick="confirmResetCategory('{{ $activeCategory->slug }}', '{{ $activeCategory->name }}')">
                    <span>🔄 ล้างข้อมูลทั้งหมด</span>
                </button>
            @endif
        </div>
    </div>

    @if(!$isAdmin)
        <!-- Guest Notice Banner -->
        <div class="guest-notice-bar">
            <div class="notice-content">
                <span class="notice-icon">ℹ️</span>
                <span>คุณกำลังเปิดใน <strong>โหมดผู้เข้าชม (Viewer)</strong> ซึ่งสามารถส่องดูรายการและเช็คบ็อกซ์ได้แบบเรียลไทม์ หากคุณเป็นแอดมิน กรุณากดปุ่ม <strong>"เข้าสู่ระบบ Admin"</strong> ด้านบนขวาเพื่อแก้ไข</span>
            </div>
        </div>
    @endif

        <!-- Main Table Container -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="rooc-table" id="auctionTable">
                @if($activeCategory->slug === 'card')
                    <!-- ================= CARD TABLE (ปรับให้มีตารางเหมือนกับพวกขนขาว) ================= -->
                    <thead>
                        <tr>
                            <th class="col-feather-member">ชื่อผู้ได้รับ (เลือกจากสมาชิกกิลด์)</th>
                            <th class="col-feather-page text-center">หน้าที่</th>
                            <th class="col-feather-date text-center">วันที่</th>
                            <th class="col-feather-notes">หมายเหตุ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            @php
                                $cardPage = (int) ceil($item->slot_number / 4);
                                $cardBook = (($item->slot_number - 1) % 4) + 1;
                                $cardLabel = "หน้า {$cardPage} เล่ม {$cardBook}";
                            @endphp
                            <tr class="item-row {{ !empty(trim($item->name ?? '')) ? 'is-filled' : 'is-empty' }}" 
                                id="row_{{ $item->id }}" 
                                data-id="{{ $item->id }}" 
                                data-slot="{{ $item->slot_number }}"
                                data-page="{{ $cardPage }}"
                                data-name="{{ strtolower($item->name ?? '') }}">
                                
                                <!-- 1. Member Name (ชื่อผู้ได้รับ เลือกจากสมาชิกกิลด์) -->
                                <td class="col-feather-member">
                                    @if($isAdmin)
                                        <div class="member-input-container">
                                            <input type="text" 
                                                   class="item-name-input admin-editable feather-member-input" 
                                                   data-id="{{ $item->id }}" 
                                                   data-field="name" 
                                                   value="{{ $item->name }}" 
                                                   placeholder="พิมพ์หรือเลือกสมาชิกกิลด์..." 
                                                   list="guildMembersDatalist"
                                                   autocomplete="off">
                                        </div>
                                    @else
                                        <div class="guest-name-view {{ !empty($item->name) ? 'has-value' : 'is-placeholder' }}">
                                            {{ $item->name ?: '— ว่าง —' }}
                                        </div>
                                    @endif
                                </td>

                                <!-- 2. Page / Slot (หน้า 1 เล่ม 1..4) -->
                                <td class="col-feather-page text-center">
                                    <span class="feather-page-pill">{{ $cardLabel }}</span>
                                </td>

                                <!-- 3. Date (วันที่) -->
                                <td class="col-feather-date text-center">
                                    @if($isAdmin)
                                        <div class="date-picker-wrap">
                                            <input type="text" 
                                                   class="item-date-input admin-editable" 
                                                   id="date_input_{{ $item->id }}"
                                                   data-id="{{ $item->id }}" 
                                                   data-field="item_date" 
                                                   value="{{ $item->item_date ?: '18/09/69' }}" 
                                                   placeholder="18/09/69">
                                            <input type="date" class="native-date-hidden" id="native_date_{{ $item->id }}" onchange="syncDateToText(this, {{ $item->id }})">
                                            <button type="button" class="btn-date-trigger" onclick="openNativeDatePicker({{ $item->id }})" title="เลือกวันที่">📅</button>
                                        </div>
                                    @else
                                        <span class="date-badge">{{ $item->item_date ?: '18/09/69' }}</span>
                                    @endif
                                </td>

                                {{-- 4. Notes --}}
                                <td class="col-feather-notes">
                                    @if($isAdmin)
                                        <div class="feather-notes-box">
                                            <input type="text" 
                                                   class="item-notes-input admin-editable" 
                                                   id="notes_input_{{ $item->id }}"
                                                   data-id="{{ $item->id }}" 
                                                   data-field="notes" 
                                                   value="{{ $item->notes }}" 
                                                   placeholder="หมายเหตุ...">
                                        </div>
                                    @else
                                        @if(!empty($item->notes))
                                            <span class="badge-custom-note">{{ $item->notes }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                @else
                    <!-- ================= FEATHER TABLE (ขนขาว & ขนแดงดำ ตามภาพที่ 2) ================= -->
                    <thead>
                        <tr>
                            <th class="col-feather-member">ชื่อผู้ได้รับ (เลือกจากสมาชิกกิลด์)</th>
                            <th class="col-feather-page text-center">หน้าที่</th>
                            <th class="col-feather-date text-center">วันที่</th>
                            <th class="col-feather-notes">หมายเหตุ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($groupedPages as $pageNumber => $pageItems)
                            @php
                                $item = $pageItems->first();
                                $displayPage = ($pageOffset ?? 0) + $pageNumber;
                                $itemsInThisPage = $pageItems->count();
                                $pieceLabel = $itemsInThisPage >= 4 ? "อันที่ 1-4" : ($itemsInThisPage === 1 ? "อันที่ 1" : "อันที่ 1-{$itemsInThisPage}");
                                $featherLabel = "หน้าที่ {$displayPage} ({$pieceLabel})";
                            @endphp
                            <tr class="item-row {{ !empty(trim($item->name ?? '')) ? 'is-filled' : 'is-empty' }}" 
                                id="row_{{ $item->id }}" 
                                data-id="{{ $item->id }}" 
                                data-slot="{{ $item->slot_number }}"
                                data-page="{{ $displayPage }}"
                                data-name="{{ strtolower($item->name ?? '') }}">
                                
                                <!-- 1. Member Name -->
                                <td class="col-feather-member">
                                    @if($isAdmin)
                                        <div class="member-input-container">
                                            <input type="text" 
                                                   class="item-name-input admin-editable feather-member-input" 
                                                   data-id="{{ $item->id }}" 
                                                   data-field="name" 
                                                   value="{{ $item->name }}" 
                                                   placeholder="พิมพ์หรือเลือกสมาชิกกิลด์..." 
                                                   list="guildMembersDatalist"
                                                   autocomplete="off">
                                        </div>
                                    @else
                                        <div class="guest-name-view {{ !empty($item->name) ? 'has-value' : 'is-placeholder' }}">
                                            {{ $item->name ?: '— ว่าง —' }}
                                        </div>
                                    @endif
                                </td>

                                <!-- 2. Page Number (หน้าที่ 1 (อันที่ 1-4)) -->
                                <td class="col-feather-page text-center">
                                    <span class="feather-page-pill">{{ $featherLabel }}</span>
                                </td>

                                <!-- 3. Date -->
                                <td class="col-feather-date text-center">
                                    @if($isAdmin)
                                        <div class="date-picker-wrap">
                                            <input type="text" 
                                                   class="item-date-input admin-editable" 
                                                   id="date_input_{{ $item->id }}"
                                                   data-id="{{ $item->id }}" 
                                                   data-field="item_date" 
                                                   value="{{ $item->item_date ?: '18/09/69' }}" 
                                                   placeholder="18/09/69">
                                            <input type="date" class="native-date-hidden" id="native_date_{{ $item->id }}" onchange="syncDateToText(this, {{ $item->id }})">
                                            <button type="button" class="btn-date-trigger" onclick="openNativeDatePicker({{ $item->id }})" title="เลือกวันที่">📅</button>
                                        </div>
                                    @else
                                        <span class="date-badge">{{ $item->item_date ?: '18/09/69' }}</span>
                                    @endif
                                </td>

                                {{-- 4. Notes --}}
                                <td class="col-feather-notes">
                                    @if($isAdmin)
                                        <div class="feather-notes-box">
                                            <input type="text" 
                                                   class="item-notes-input admin-editable" 
                                                   id="notes_input_{{ $item->id }}"
                                                   data-id="{{ $item->id }}" 
                                                   data-field="notes" 
                                                   value="{{ $item->notes }}" 
                                                   placeholder="หมายเหตุ...">
                                        </div>
                                    @else
                                        @if(!empty($item->notes))
                                            <span class="badge-custom-note">{{ $item->notes }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                @endif
            </table>
        </div>
    </div>
</div>

<!-- Datalist for Guild Members Autocomplete -->
<datalist id="guildMembersDatalist">
    @if(isset($guildMembers))
        @foreach($guildMembers as $gm)
            @if(!empty(trim($gm->name ?? '')))
                <option value="{{ $gm->name }}">{{ $gm->slot_number }}. {{ $gm->name }} ({{ $gm->class_job ?: 'สมาชิก' }})</option>
            @endif
        @endforeach
    @endif
</datalist>
@endsection
