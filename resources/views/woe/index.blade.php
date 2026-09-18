@extends('layouts.app')

@section('title', 'ตี้วอ - ROOC AUCTION CHECKLIST FUNRAIRANKGOLD (DADDY)')

@section('content')
<div class="auction-dashboard woe-dashboard">

    {{-- Header --}}
    <div class="dashboard-header-card">
        <div class="header-main-title header-flex">
            <img src="{{ asset('images/rooc_logo.png') }}" class="header-category-icon" alt="ROOC Logo">
            <div>
                <h1 class="page-heading">จัดตี้วอ (War of Emperium)</h1>
                <p class="page-subheading">จัดทีม 8 ปาร์ตี้ × 5 คน ต่อห้อง · 2 ห้อง · 3 แมพ · บันทึกอัตโนมัติ</p>
            </div>
        </div>

        {{-- Badges --}}
        <div class="badges-row">
            @if($isAdmin)
                <div class="badge-pill badge-pill-gray">
                    <span class="badge-icon">✏️</span>
                    <span>โหมดแอดมิน แก้ไขได้</span>
                </div>
            @else
                <div class="badge-pill badge-pill-locked">
                    <span class="badge-icon">🔒</span>
                    <span>โหมดผู้เข้าชม (ดูได้อย่างเดียว)</span>
                </div>
            @endif
        </div>

        {{-- Map Tabs --}}
        @php
            $mapIcons = [
                'stella_clash'   => '📖',
                'valor_of_clash' => '📖',
                'vigid_map'      => '🗺️',
                'overrun'        => '⚡',
            ];
        @endphp
        <div class="category-tabs-container">
            <div class="category-tabs-list" role="tablist">
                @foreach($mapNames as $slug => $name)
                    <a href="{{ route('woe.index', ['map' => $slug]) }}"
                       class="category-tab-btn {{ $activeMap === $slug ? 'is-active' : '' }}"
                       data-map="{{ $slug }}">
                        <span class="tab-icon">{{ $mapIcons[$slug] ?? '🗺️' }}</span>
                        <span class="tab-name">{{ $name }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    @if(!$isAdmin)
    <div class="guest-notice-bar">
        <div class="notice-content">
            <span class="notice-icon">ℹ️</span>
            <span>คุณกำลังเปิดใน <strong>โหมดผู้เข้าชม</strong> หากต้องการแก้ไข กรุณากด <strong>"เข้าสู่ระบบ Admin"</strong> ด้านบนขวา</span>
        </div>
    </div>
    @endif

    {{-- Admin Toolbar --}}
    @if($isAdmin)
    <div class="toolbar-section" style="margin-bottom:12px;">
        <div class="toolbar-left"></div>
        <div class="toolbar-right">
            <button type="button" class="tool-btn btn-danger-outline"
                    onclick="confirmResetWoeMap('{{ $activeMap }}', '{{ $mapNames[$activeMap] }}')">
                <span>🔄 ล้างข้อมูลทั้งหมด ({{ $mapNames[$activeMap] }})</span>
            </button>
        </div>
    </div>
    @endif

    {{-- Main 2-Column Layout: Parties on Left, Guild Members on Right --}}
    <div class="woe-main-layout">
        {{-- Left Column: Rooms & Parties --}}
        <div class="woe-parties-column">
            <div id="woeContainer">
                @for($room = 1; $room <= 2; $room++)
                <div class="woe-room-card">
                    <div class="woe-room-header">
                        <span class="woe-room-icon">🏰</span>
                        <h2 class="woe-room-title">ห้องที่ {{ $room }} — {{ $mapNames[$activeMap] }}</h2>
                        <span class="woe-room-badge">8 ปาร์ตี้ · 40 คน</span>
                    </div>

                    <div class="woe-parties-grid">
                        @for($party = 1; $party <= 8; $party++)
                        <div class="woe-party-card">
                            <div class="woe-party-header">
                                <span class="woe-party-number">ปาร์ตี้ {{ $party }}</span>
                                <span class="woe-party-filled" id="partyFilled_{{ $room }}_{{ $party }}">
                                    {{ collect($data[$activeMap][$room][$party])->filter(fn($s) => $s && !empty(trim($s->member_name ?? '')))->count() }}/5
                                </span>
                            </div>
                            <div class="woe-party-slots">
                                @for($slot = 1; $slot <= 5; $slot++)
                                    @php
                                        $s = $data[$activeMap][$room][$party][$slot];
                                        $hasMember = $s && !empty(trim($s->member_name ?? ''));
                                    @endphp
                                <div class="woe-slot {{ $hasMember ? 'is-filled' : 'is-empty' }}"
                                     id="woeSlot_{{ $s ? $s->id : "r{$room}_p{$party}_s{$slot}" }}"
                                     data-slot-id="{{ $s ? $s->id : '' }}"
                                     data-room="{{ $room }}"
                                     data-party="{{ $party }}"
                                     data-slot="{{ $slot }}"
                                     data-member-name="{{ $s ? ($s->member_name ?? '') : '' }}"
                                     data-class-job="{{ $s ? ($s->class_job ?? '') : '' }}"
                                     @if($isAdmin && $s && $hasMember) draggable="true" @endif>
                                    <span class="woe-slot-num">{{ $slot }}</span>

                                    {{-- Class Icon Badge (if available) --}}
                                    <span class="woe-slot-icon-wrap {{ ($s && $s->job_icon_url) ? '' : 'is-hidden' }}">
                                        <img src="{{ $s ? $s->job_icon_url : '' }}"
                                             class="woe-slot-job-icon"
                                             alt="{{ $s ? ($s->class_job ?? '') : '' }}"
                                             title="{{ $s ? ($s->class_job ?? '') : '' }}">
                                    </span>

                                    @if($isAdmin && $s)
                                        <div class="woe-slot-content">
                                            <input type="text"
                                                   class="woe-member-input"
                                                   data-slot-id="{{ $s->id }}"
                                                   data-field="member_name"
                                                   value="{{ $s->member_name }}"
                                                   placeholder="— ลากวางหรือพิมพ์..."
                                                   autocomplete="off">
                                        </div>
                                        @if($hasMember)
                                            <button type="button"
                                                    class="woe-slot-clear-btn"
                                                    onclick="clearWoeSlot({{ $s->id }}, event)"
                                                    title="ปลด {{ $s->member_name }} ออกจากปาร์ตี้">
                                                &times;
                                            </button>
                                        @endif
                                    @elseif($s)
                                        <div class="woe-slot-content">
                                            <span class="woe-member-name {{ $hasMember ? 'has-value' : 'is-placeholder' }}">
                                                {{ $s->member_name ?: '— ว่าง' }}
                                            </span>
                                        </div>
                                    @else
                                        <div class="woe-slot-content">
                                            <span class="woe-member-name is-placeholder">— ว่าง</span>
                                        </div>
                                    @endif

                                    {{-- Faint character illustration watermark on the right side of filled slot --}}
                                    <div class="woe-slot-character-bg"
                                         style="{{ ($s && $s->character_image_url) ? "background-image: url('{$s->character_image_url}');" : 'display:none;' }}"></div>
                                </div>
                                @endfor
                            </div>
                        </div>
                        @endfor
                    </div>
                </div>
                @endfor
            </div>
        </div>

        {{-- Right Column: Guild Members Roster (Sticky Sidebar Table) --}}
        <aside class="woe-members-sidebar">
            <div class="woe-members-card">
                {{-- Panel Header --}}
                <div class="woe-members-header">
                    <div class="woe-members-title-wrap">
                        <span class="woe-members-title-icon">👥</span>
                        <div>
                            <h3 class="woe-members-title">สมาชิกกิลด์</h3>
                            <div class="woe-members-stats">
                                <span class="stat-pill stat-total">มีชื่อ: <strong id="rosterTotalCount">{{ $guildMembers->count() }}</strong></span>
                                <span class="stat-pill stat-assigned">ในตี้: <strong id="rosterAssignedCount">{{ count($assignedMap) }}</strong></span>
                                <span class="stat-pill stat-unassigned">ว่าง: <strong id="rosterUnassignedCount">{{ max(0, $guildMembers->count() - count($assignedMap)) }}</strong></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Search & Filter Section --}}
                <div class="woe-members-controls">
                    <div class="roster-search-wrap">
                        <span class="roster-search-icon">🔍</span>
                        <input type="text"
                               id="rosterSearchInput"
                               class="roster-search-input"
                               placeholder="ค้นหาชื่อตัวละคร หรือ อาชีพ..."
                               autocomplete="off">
                        <button type="button" id="rosterSearchClear" class="roster-clear-btn" title="ล้างการค้นหา">&times;</button>
                    </div>

                    <div class="roster-filter-tabs">
                        <button type="button" class="roster-tab-btn is-active" data-filter="all">
                            ทั้งหมด ({{ $guildMembers->count() }})
                        </button>
                        <button type="button" class="roster-tab-btn" data-filter="unassigned">
                            ว่าง ({{ max(0, $guildMembers->count() - count($assignedMap)) }})
                        </button>
                        <button type="button" class="roster-tab-btn" data-filter="assigned">
                            ในตี้ ({{ count($assignedMap) }})
                        </button>
                    </div>
                </div>

                {{-- Hint for Admins --}}
                @if($isAdmin)
                    <div class="roster-drag-hint">
                        <span class="hint-icon">💡</span>
                        <span>คลิกลาก <strong style="color:var(--primary);">รายชื่อ</strong> ไปวางในช่องปาร์ตี้ได้เลย</span>
                    </div>
                @endif

                {{-- Members List / Table --}}
                <div class="woe-members-list-wrapper" id="woeMembersList">
                    @forelse($guildMembers as $member)
                        @php
                            $nameKey = mb_strtolower(trim($member->name));
                            $isAssigned = array_key_exists($nameKey, $assignedMap);
                            $assignment = $isAssigned ? $assignedMap[$nameKey] : null;
                        @endphp
                        <div class="roster-member-row {{ $isAssigned ? 'is-assigned' : 'is-unassigned' }}"
                             id="rosterMember_{{ $member->id }}"
                             data-id="{{ $member->id }}"
                             data-name="{{ $member->name }}"
                             data-lower-name="{{ $nameKey }}"
                             data-job="{{ $member->class_job ?? '' }}"
                             data-job-slug="{{ \App\Models\GuildMember::jobSlug($member->class_job) }}"
                             data-icon-url="{{ $member->job_icon_url ?? '' }}"
                             data-character-url="{{ $member->character_image_url ?? '' }}"
                             data-status="{{ $isAssigned ? 'assigned' : 'unassigned' }}"
                             @if($isAdmin)
                                draggable="true"
                                title="คลิกลาก {{ $member->name }} ไปวางลงในช่องปาร์ตี้"
                             @endif>
                            
                            @if($isAdmin)
                                <div class="roster-drag-handle" title="ลากเพื่อจัดตี้">⋮⋮</div>
                            @endif

                            <span class="roster-slot-num">#{{ $member->slot_number }}</span>

                            <div class="roster-member-icon-wrap">
                                @if($member->job_icon_url)
                                    <img src="{{ $member->job_icon_url }}" class="roster-job-icon" alt="{{ $member->class_job }}">
                                @else
                                    <span class="roster-job-icon-empty">⚔️</span>
                                @endif
                            </div>

                            <div class="roster-member-info">
                                <span class="roster-member-name">{{ $member->name }}</span>
                                <span class="roster-member-job">{{ $member->class_job ?: 'ไม่ระบุอาชีพ' }}</span>
                            </div>

                            <div class="roster-member-badge-wrap">
                                <span class="roster-status-pill {{ $isAssigned ? 'is-in-party' : 'is-free' }}"
                                      id="rosterStatus_{{ $member->id }}">
                                    {{ $isAssigned ? $assignment['label'] : 'ว่าง' }}
                                </span>
                            </div>

                            {{-- Faint character illustration watermark on the right side of member card --}}
                            @if($member->character_image_url)
                                <div class="roster-character-bg"
                                     style="background-image: url('{{ $member->character_image_url }}');"></div>
                            @endif
                        </div>
                    @empty
                        <div class="roster-empty-notice">
                            <span>ยังไม่มีข้อมูลสมาชิกกิลด์</span>
                            <a href="{{ route('members.index') }}" style="color:var(--primary); font-weight:600; margin-top:4px;">ไปเพิ่มรายชื่อสมาชิก</a>
                        </div>
                    @endforelse
                </div>
            </div>
        </aside>
    </div>
</div>

@push('scripts')
<script>
window.confirmResetWoeMap = function(mapSlug, mapName) {
    if (!confirm(`คำเตือน: ต้องการล้างข้อมูลตี้วอแมพ "${mapName}" ทั้งหมดใช่หรือไม่?`)) return;

    fetch(`/api/woe/map/${mapSlug}/reset`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': window.APP_CONFIG.csrfToken
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => window.location.reload(), 600);
        } else {
            showToast(data.message || 'ไม่สามารถล้างข้อมูลได้', 'error');
        }
    })
    .catch(() => showToast('เกิดข้อผิดพลาด', 'error'));
};
</script>
@endpush
@endsection
