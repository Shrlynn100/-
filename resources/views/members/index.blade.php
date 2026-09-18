@extends('layouts.app')

@section('title', 'สมาชิกกิลด์ FUNRAIRANKGOLD (DADDY) - 80 คน')

@section('content')
<div class="auction-dashboard members-dashboard">
    <!-- Header Section -->
    <div class="dashboard-header-card">
        <div class="header-main-title header-flex">
            <img src="{{ asset('images/rooc_logo.png') }}" class="header-category-icon" alt="ROOC Logo">
            <div>
                <h1 class="page-heading">สมาชิกกิลด์ FUNRAIRANKGOLD (DADDY)</h1>
                <p class="page-subheading">ตารางข้อมูลสมาชิกกิลด์ 80 คน เช็คสถานะการเข้ากิลด์วอร์ และบันทึกข้อมูลอัตโนมัติ</p>
            </div>
        </div>

        <!-- Badges Row -->
        <div class="badges-row">
            <div class="badge-pill badge-pill-cyan">
                <span class="badge-icon">👥</span>
                <span>ลงทะเบียนแล้ว <strong id="memberFilledText">{{ $filledCount }}</strong> / <span>{{ $totalMembers }}</span> คน</span>
            </div>

            @if($isAdmin)
                <div class="badge-pill badge-pill-gray">
                    <span class="badge-icon">✏️</span>
                    <span>โหมดแอดมิน แก้ไขได้</span>
                </div>
                <div id="memberSaveStatusBadge" class="badge-pill badge-pill-saved">
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
    </div>

    <!-- Controls Toolbar -->
    <div class="toolbar-section">
        <div class="toolbar-left">
            <!-- Search bar -->
            <div class="search-input-wrapper">
                <span class="search-icon">🔍</span>
                <input type="text" id="memberSearchInput" class="search-input" placeholder="ค้นหาชื่อตัวละคร, อาชีพ, ตำแหน่ง..." autocomplete="off">
                <button type="button" id="clearMemberSearchBtn" class="clear-search-btn hidden" title="ล้างการค้นหา">&times;</button>
            </div>

            <!-- Filter Buttons -->
            <div class="filter-btn-group">
                <button type="button" class="member-filter-btn is-active" data-filter="all">ทั้งหมด (80)</button>
                <button type="button" class="member-filter-btn" data-filter="filled">มีชื่อแล้ว (<span id="memberFilterFilledNum">{{ $filledCount }}</span>)</button>
                <button type="button" class="member-filter-btn" data-filter="unfilled">ช่องว่าง</button>
            </div>
        </div>

        <div class="toolbar-right">
            <!-- Export Members Button -->
            <button type="button" class="tool-btn btn-summary" onclick="openMemberExportModal()">
                <span>📋 สรุปรายชื่อสมาชิก (Discord / LINE)</span>
            </button>

            @if($isAdmin)
                <!-- Reset Members Button -->
                <button type="button" class="tool-btn btn-danger-outline" onclick="confirmResetMembers()">
                    <span>🔄 ล้างข้อมูลทั้งหมด</span>
                </button>
            @endif
        </div>
    </div>

    @if(!$isAdmin)
        <!-- Guest Notice Bar -->
        <div class="guest-notice-bar">
            <div class="notice-content">
                <span class="notice-icon">ℹ️</span>
                <span>คุณกำลังเปิดใน <strong>โหมดผู้เข้าชม (Viewer)</strong> รายชื่อและสถานะจะแสดงผลแบบเรียลไทม์ หากต้องการแก้ไข กรุณากด <strong>"เข้าสู่ระบบ Admin"</strong> ด้านบนขวา</span>
            </div>
        </div>
    @endif

    <!-- Members Table Card -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="rooc-table" id="membersTable">
                <thead>
                    <tr>
                        <th class="col-index">ที่</th>
                        <th style="min-width: 180px;">ชื่อตัวละคร</th>
                        <th style="min-width: 180px;">อาชีพ (Class / Job)</th>
                        <th style="width: 140px;">ตำแหน่ง</th>
                        <th style="min-width: 180px;">หมายเหตุ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($members as $m)
                        @php
                            $jobSlug = \App\Models\GuildMember::jobSlug($m->class_job);
                            $hasIcon = $jobSlug && file_exists(public_path("images/classes/icons/{$jobSlug}.png"));
                        @endphp
                        <tr class="member-row {{ !empty(trim($m->name ?? '')) ? 'is-filled' : 'is-empty' }}"
                            id="member_row_{{ $m->id }}"
                            data-id="{{ $m->id }}"
                            data-slot="{{ $m->slot_number }}"
                            data-name="{{ strtolower($m->name ?? '') }}"
                            data-job="{{ strtolower($m->class_job ?? '') }}"
                            data-role="{{ strtolower($m->role ?? '') }}">
                            
                            <!-- Slot No. -->
                            <td class="col-index">
                                <span class="slot-badge">{{ $m->slot_number }}</span>
                            </td>

                            <!-- Character Name -->
                            <td>
                                @if($isAdmin)
                                    <input type="text" 
                                           class="form-control-sm member-editable" 
                                           style="width: 100%;"
                                           data-id="{{ $m->id }}" 
                                           data-field="name" 
                                           value="{{ $m->name }}" 
                                           placeholder="ชื่อตัวละคร..."
                                           autocomplete="off">
                                @else
                                    <span class="member-name-view {{ !empty($m->name) ? 'has-val' : 'no-val' }}">
                                        {{ $m->name ?: '-' }}
                                    </span>
                                @endif
                            </td>

                            <!-- Class / Job -->
                            <td>
                                @if($isAdmin)
                                    <div class="job-custom-dropdown" data-id="{{ $m->id }}">
                                        <button type="button" class="job-dropdown-trigger {{ !empty($m->class_job) ? 'has-val' : 'is-empty' }}" 
                                                id="jobTrigger_{{ $m->id }}" 
                                                onclick="toggleJobDropdown({{ $m->id }}, event)" 
                                                title="คลิกเพื่อเลือกอาชีพ">
                                            <span class="job-trigger-content">
                                                @if($hasIcon)
                                                    <img src="{{ asset("images/classes/icons/{$jobSlug}.png") }}" class="job-trigger-icon" alt="{{ $m->class_job }}" id="jobTriggerImg_{{ $m->id }}">
                                                @else
                                                    <span class="job-trigger-placeholder-icon" id="jobTriggerImg_{{ $m->id }}">🎭</span>
                                                @endif
                                                <span class="job-trigger-text" id="jobTriggerText_{{ $m->id }}">{{ $m->class_job ?: 'เลือกอาชีพ...' }}</span>
                                            </span>
                                            <span class="job-trigger-caret">▾</span>
                                        </button>
                                        <input type="hidden" 
                                               class="member-job-input member-editable" 
                                               data-id="{{ $m->id }}" 
                                               data-field="class_job" 
                                               id="jobInput_{{ $m->id }}"
                                               value="{{ $m->class_job }}">
                                    </div>
                                @else
                                    @if(!empty($m->class_job))
                                        <span class="member-job-pill has-job">
                                            @if($hasIcon)
                                                <img src="{{ asset("images/classes/icons/{$jobSlug}.png") }}" class="job-badge-icon" alt="{{ $m->class_job }}">
                                            @endif
                                            <span class="job-pill-text">{{ $m->class_job }}</span>
                                        </span>
                                    @else
                                        <span class="member-job-pill is-empty">-</span>
                                    @endif
                                @endif
                            </td>

                            <!-- Role -->
                            <td>
                                @if($isAdmin)
                                    <select class="form-control-sm member-editable" data-id="{{ $m->id }}" data-field="role" style="width: 100%;">
                                        <option value="สมาชิก" {{ $m->role === 'สมาชิก' ? 'selected' : '' }}>สมาชิก</option>
                                        <option value="หัวกิลด์" {{ $m->role === 'หัวกิลด์' ? 'selected' : '' }}>หัวกิลด์</option>
                                        <option value="รองหัวกิลด์" {{ $m->role === 'รองหัวกิลด์' ? 'selected' : '' }}>รองหัวกิลด์</option>
                                        <option value="เสนาธิการ" {{ $m->role === 'เสนาธิการ' ? 'selected' : '' }}>เสนาธิการ</option>
                                    </select>
                                @else
                                    @php
                                        $roleClass = match($m->role) {
                                            'หัวกิลด์' => 'role-leader',
                                            'รองหัวกิลด์' => 'role-subleader',
                                            'เสนาธิการ' => 'role-officer',
                                            default => 'role-member'
                                        };
                                    @endphp
                                    <span class="role-pill {{ $roleClass }}">{{ $m->role ?: 'สมาชิก' }}</span>
                                @endif
                            </td>

                            <!-- Notes -->
                            <td>
                                @if($isAdmin)
                                    <input type="text" 
                                           class="form-control-sm member-editable" 
                                           style="width: 100%;"
                                           data-id="{{ $m->id }}" 
                                           data-field="notes" 
                                           value="{{ $m->notes }}" 
                                           placeholder="โน้ตเพิ่มเติม...">
                                @else
                                    <span class="text-muted" style="font-size: 0.88rem;">{{ $m->notes ?: '-' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Floating Job Selector Dropdown Menu (13 Official Classes from Game) -->
<div id="floatingJobDropdown" class="floating-job-menu">
    <div class="job-menu-header">
        <span class="job-menu-title">เลือกอาชีพ (13 อาชีพในเกม)</span>
    </div>
    <div class="job-menu-list">
        <button type="button" class="job-option-item is-clear" onclick="selectJobOption('', '')">
            <span class="job-option-icon-wrap is-clear-icon">✕</span>
            <span class="job-option-name">(ไม่ระบุอาชีพ)</span>
        </button>
        @foreach(\App\Models\GuildMember::availableClasses() as $c)
            <button type="button" class="job-option-item" data-value="{{ $c['name'] }}" data-slug="{{ $c['slug'] }}" 
                    onclick="selectJobOption('{{ $c['name'] }}', '{{ $c['slug'] }}')">
                <img src="{{ asset('images/classes/icons/' . $c['slug'] . '.png') }}" class="job-option-icon" alt="{{ $c['name'] }}">
                <span class="job-option-name">{{ $c['name'] }}</span>
            </button>
        @endforeach
    </div>
</div>

<!-- Datalist fallback for Class suggestions -->
<datalist id="classList">
    @foreach(\App\Models\GuildMember::availableClasses() as $c)
        <option value="{{ $c['name'] }}">
    @endforeach
</datalist>

<!-- Member Export Modal -->
<div id="memberExportModal" class="modal-backdrop hidden">
    <div class="modal-card modal-large">
        <div class="modal-header">
            <div class="modal-title-group">
                <span class="modal-icon">👥</span>
                <h3>สรุปรายชื่อสมาชิกกิลด์ (สำหรับ Discord / LINE)</h3>
            </div>
            <button type="button" class="btn-close" onclick="closeModal('memberExportModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p class="modal-desc">ข้อความสรุปรายชื่อสมาชิก 80 คน จัดรูปแบบเรียบร้อย:</p>
            <textarea id="memberSummaryTextarea" class="form-control summary-textarea" rows="14" readonly></textarea>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeModal('memberExportModal')">ปิด</button>
            <button type="button" class="btn-primary" onclick="copyMemberSummaryText()">
                📋 คัดลอกไปยังคลิปบอร์ด
            </button>
        </div>
    </div>
</div>
@endsection
