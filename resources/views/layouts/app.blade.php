<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'DADDY x YESTOOTH x FUNRAIRANKGOLD')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/rooc_logo.png') }}">
    
    <!-- Google Fonts: Prompt & Kanit for beautiful modern Thai typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom Auction System CSS -->
    <link rel="stylesheet" href="{{ asset('css/auction.css') }}?v={{ time() }}">
    
    @stack('styles')
</head>
<body>
    <!-- Top Navigation / Brand Bar -->
    <header class="navbar-header">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="{{ route('auction.index') }}" style="display:flex; align-items:center; gap:12px;">
                    <img src="{{ asset('images/rooc_logo.png') }}" alt="ROOC Logo" class="brand-logo-img" style="height: 38px; max-height: 38px; width: auto;">
                    <div class="brand-text">
                        <span class="brand-title">DADDY x YESTOOTH x FUNRAIRANKGOLD</span>
                        <span class="brand-subtitle">ระบบเช็ครายการประมูลกิลด์ Ragnarok Origin Classic</span>
                    </div>
                </a>
            </div>

            <!-- Page Navigation Menu -->
            <nav class="nav-links">
                <a href="{{ route('auction.index') }}" class="nav-tab-link {{ request()->routeIs('auction.index') ? 'is-active' : '' }}">
                    <span>⚔️</span> รายการประมูล
                </a>
                <a href="{{ route('members.index') }}" class="nav-tab-link {{ request()->routeIs('members.index') ? 'is-active' : '' }}">
                    <span>👥</span> สมาชิกกิล (80 คน)
                </a>
                <a href="{{ route('woe.index') }}" class="nav-tab-link {{ request()->routeIs('woe.index') ? 'is-active' : '' }}">
                    <span>🏰</span> ตี้วอ
                </a>
            </nav>

            <div class="nav-actions">
                <!-- Theme toggle button -->
                <button type="button" class="btn-icon" id="themeToggleBtn" title="สลับโหมดมืด/สว่าง">
                    <span id="themeIcon">🌙</span>
                </button>

                <!-- Admin Mode Status & Buttons -->
                @if($isAdmin)
                    <div class="admin-chip is-active">
                        <span class="status-dot green"></span>
                        <span>โหมดแอดมิน (แก้ไขได้)</span>
                    </div>
                    <button type="button" class="btn-action btn-outline" id="openChangePassBtn" onclick="openModal('changePassModal')" title="เปลี่ยนรหัสผ่านแอดมิน">
                        🔑 เปลี่ยนรหัส
                    </button>
                    <form action="{{ route('admin.logout') }}" method="POST" style="display:inline;" onsubmit="return confirm('ต้องการออกจากโหมดแอดมินใช่หรือไม่?');">
                        @csrf
                        <button type="submit" class="btn-action btn-danger-soft">
                            🚪 ออกจากระบบ
                        </button>
                    </form>
                @else
                    <div class="admin-chip is-guest">
                        <span class="status-dot gray"></span>
                        <span>โหมดผู้เข้าชม (ดูได้อย่างเดียว)</span>
                    </div>
                    <button type="button" class="btn-action btn-primary-gradient" id="openLoginModalBtn" onclick="openModal('loginModal')">
                        🔐 เข้าสู่ระบบ Admin
                    </button>
                @endif
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="main-wrapper">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="footer-content">
            <p>ROOC Guild Auction Tracker &copy; {{ date('Y') }} &bull; ระบบประมูลและตรวจเช็คของรางวัลเกม Ragnarok Origin</p>
        </div>
    </footer>

    <!-- Toast Notification Container -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- Admin Login Modal -->
    <div id="loginModal" class="modal-backdrop hidden">
        <div class="modal-card">
            <div class="modal-header">
                <div class="modal-title-group">
                    <span class="modal-icon">🔐</span>
                    <h3>เข้าสู่ระบบแอดมิน (Admin Login)</h3>
                </div>
                <button type="button" class="btn-close" onclick="closeModal('loginModal')">&times;</button>
            </div>
            <form id="adminLoginForm" action="{{ route('admin.login') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="modal-desc">กรุณากรอกรหัสผ่านแอดมินเพื่อปลดล็อกการแก้ไขชื่อไอเทมและเช็คบ็อกซ์</p>
                    <div class="form-group">
                        <label for="adminPasswordInput">รหัสผ่านแอดมิน:</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="adminPasswordInput" name="password" class="form-control" placeholder="ใส่รหัสผ่าน..." required autofocus autocomplete="current-password">
                            <button type="button" class="toggle-password-btn" onclick="togglePasswordVisibility('adminPasswordInput', this)">👁️</button>
                        </div>
                        <div id="loginErrorMsg" class="form-error hidden"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('loginModal')">ยกเลิก</button>
                    <button type="submit" class="btn-primary" id="loginSubmitBtn">
                        <span>เข้าสู่ระบบ</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Admin Change Password Modal -->
    @if($isAdmin)
    <div id="changePassModal" class="modal-backdrop hidden">
        <div class="modal-card">
            <div class="modal-header">
                <div class="modal-title-group">
                    <span class="modal-icon">🔑</span>
                    <h3>เปลี่ยนรหัสผ่านแอดมิน</h3>
                </div>
                <button type="button" class="btn-close" onclick="closeModal('changePassModal')">&times;</button>
            </div>
            <form id="changePassForm" action="{{ route('admin.change-password') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="oldPasswordInput">รหัสผ่านเดิม:</label>
                        <input type="password" id="oldPasswordInput" name="old_password" class="form-control" required placeholder="ใส่รหัสผ่านเดิม">
                    </div>
                    <div class="form-group" style="margin-top: 12px;">
                        <label for="newPasswordInput">รหัสผ่านใหม่ (อย่างน้อย 4 ตัวอักษร):</label>
                        <input type="password" id="newPasswordInput" name="new_password" class="form-control" required minlength="4" placeholder="ใส่รหัสผ่านใหม่">
                    </div>
                    <div id="changePassErrorMsg" class="form-error hidden"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('changePassModal')">ยกเลิก</button>
                    <button type="submit" class="btn-primary" id="changePassSubmitBtn">บันทึกรหัสผ่านใหม่</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Auction Overview Table Modal (ภาพรวมตารางสรุปผลการประมูล ตรงกลางจอ ดูง่าย) -->
    <div id="auctionOverviewModal" class="modal-backdrop hidden">
        <div class="modal-card modal-overview">
            <div class="modal-header">
                <div class="modal-title-group">
                    <span class="modal-icon">📊</span>
                    <div>
                        <h3 class="overview-modal-title">สรุปภาพรวมตารางการประมูล (Auction Overview)</h3>
                        <p class="modal-desc" style="margin: 0;">รายชื่อผู้ได้รับของประมูลทั้งหมด ดูง่าย ครบทุกหมวดหมู่</p>
                    </div>
                </div>
                <button type="button" class="btn-close" onclick="closeModal('auctionOverviewModal')">&times;</button>
            </div>

            <div class="modal-body modal-overview-body">
                <!-- Overview Stats Cards -->
                <div class="overview-stats-grid" id="overviewStatsGrid">
                    <div class="ov-stat-card card-highlight">
                        <img src="{{ asset('images/card.png') }}" class="ov-stat-img" alt="การ์ด">
                        <div class="ov-stat-info">
                            <span class="ov-stat-label">การ์ด</span>
                            <span class="ov-stat-value" id="ovStatCard">0 / 0</span>
                        </div>
                    </div>
                    <div class="ov-stat-card feather-white-highlight">
                        <img src="{{ asset('images/white_feather.png') }}" class="ov-stat-img" alt="ขนขาว">
                        <div class="ov-stat-info">
                            <span class="ov-stat-label">ขนขาว</span>
                            <span class="ov-stat-value" id="ovStatWhite">0 / 0</span>
                        </div>
                    </div>
                    <div class="ov-stat-card feather-red-highlight">
                        <img src="{{ asset('images/red_black_feather.png') }}" class="ov-stat-img" alt="ขนแดงดำ">
                        <div class="ov-stat-info">
                            <span class="ov-stat-label">ขนแดงดำ</span>
                            <span class="ov-stat-value" id="ovStatRed">0 / 0</span>
                        </div>
                    </div>
                    <div class="ov-stat-card total-highlight">
                        <div class="ov-stat-circle-icon">🏆</div>
                        <div class="ov-stat-info">
                            <span class="ov-stat-label">ได้ของทั้งหมด</span>
                            <span class="ov-stat-value" id="ovStatTotal">0 รายการ</span>
                        </div>
                    </div>
                </div>

                <!-- Tab filters inside modal -->
                <div class="overview-tabs-bar">
                    <div class="overview-tabs-left">
                        <button type="button" class="ov-tab-btn is-active" data-tab="all" onclick="filterOverviewTab('all')">ทั้งหมด</button>
                        <button type="button" class="ov-tab-btn" data-tab="card" onclick="filterOverviewTab('card')">
                            <img src="{{ asset('images/card.png') }}" class="ov-tab-img" alt="การ์ด"> การ์ด
                        </button>
                        <button type="button" class="ov-tab-btn" data-tab="white_feather" onclick="filterOverviewTab('white_feather')">
                            <img src="{{ asset('images/white_feather.png') }}" class="ov-tab-img" alt="ขนขาว"> ขนขาว
                        </button>
                        <button type="button" class="ov-tab-btn" data-tab="red_black_feather" onclick="filterOverviewTab('red_black_feather')">
                            <img src="{{ asset('images/red_black_feather.png') }}" class="ov-tab-img" alt="ขนแดงดำ"> ขนแดงดำ
                        </button>
                        <button type="button" class="ov-tab-btn" data-tab="by_member" onclick="filterOverviewTab('by_member')">👥 สรุปแยกตามรายชื่อคน</button>
                    </div>
                    <div class="overview-search-wrap">
                        <input type="text" id="overviewSearchInput" class="overview-search-box" placeholder="ค้นหาชื่อในตารางสรุป..." oninput="filterOverviewSearch(this.value)">
                    </div>
                </div>

                <!-- Overview Table Container -->
                <div class="overview-table-container">
                    <table class="overview-table" id="overviewDataTable">
                        <thead>
                            <tr>
                                <th class="ov-col-num text-center">ลำดับ</th>
                                <th class="ov-col-cat">หมวดหมู่</th>
                                <th class="ov-col-page text-center">หน้าที่</th>
                                <th class="ov-col-name">ชื่อผู้ได้รับ (สมาชิกกิลด์)</th>
                                <th class="ov-col-date text-center">วันที่</th>
                                <th class="ov-col-notes">หมายเหตุ</th>
                            </tr>
                        </thead>
                        <tbody id="overviewTableBody">
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">กำลังโหลดข้อมูลภาพรวม...</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Member Summary Container (Shown when 'by_member' tab is active) -->
                    <div id="overviewMemberSummaryView" class="hidden">
                        <div class="member-summary-grid" id="memberSummaryGrid"></div>
                    </div>
                </div>
            </div>

            <div class="modal-footer overview-modal-footer">
                <div class="footer-left-info">
                    <span id="overviewFooterInfo">แสดงรายการประมูลทั้งหมด</span>
                </div>
                <div class="footer-btn-group">
                    <button type="button" class="btn-secondary" onclick="openTextSummaryFromOverview()">📋 คัดลอกข้อความ (LINE/Discord)</button>
                    <button type="button" class="btn-primary" onclick="closeModal('auctionOverviewModal')">ปิดหน้าต่าง</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Copy Summary Modal -->
    <div id="copySummaryModal" class="modal-backdrop hidden">
        <div class="modal-card modal-large">
            <div class="modal-header">
                <div class="modal-title-group">
                    <span class="modal-icon">📋</span>
                    <h3>คัดลอกสรุปรายการ (สำหรับ Discord / LINE)</h3>
                </div>
                <button type="button" class="btn-close" onclick="closeModal('copySummaryModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p class="modal-desc">ข้อความจัดรูปแบบพร้อมนำไปส่งในกลุ่มกิลด์:</p>
                <textarea id="summaryTextarea" class="form-control summary-textarea" rows="12" readonly></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('copySummaryModal')">ปิด</button>
                <button type="button" class="btn-primary" id="copyToClipboardBtn" onclick="copySummaryText()">
                    📋 คัดลอกไปยังคลิปบอร์ด
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        window.APP_CONFIG = {
            isAdmin: {{ $isAdmin ? 'true' : 'false' }},
            activeCategorySlug: '{{ $activeCategory->slug ?? "card" }}',
            csrfToken: '{{ csrf_token() }}',
            eventMode: '{{ $eventMode ?? \App\Models\Setting::get("auction_event_mode", "shining_stars") }}',
            outcomeKey: '{{ $outcomeKey ?? \App\Models\Setting::get("auction_outcome", "win_100") }}',
            outcomeConfirmed: {{ ($outcomeConfirmed ?? true) ? 'true' : 'false' }},
            targets: @json($targets ?? \App\Http\Controllers\AuctionController::getOutcomeTargets()),
            routes: {
                itemUpdate: "{{ url('/api/items') }}",
                syncDate: "{{ route('api.items.sync-date') }}",
                overview: "{{ route('api.auction.overview') }}",
                categoryReset: "{{ url('/api/category') }}",
                categoryExport: "{{ url('/api/category') }}",
                categoryData: "{{ url('/api/category') }}",
                memberUpdate: "{{ url('/api/members') }}",
                memberReset: "{{ route('api.members.reset') }}",
                memberExport: "{{ route('api.members.export') }}",
                woeUpdate: "{{ url('/api/woe') }}",
                adminLogin: "{{ route('admin.login') }}",
                changePassword: "{{ route('admin.change-password') }}",
                saveOutcome: "{{ route('api.settings.outcome') }}"
            }
        };
    </script>
    <script src="{{ asset('js/auction.js') }}"></script>
    @stack('scripts')
</body>
</html>
