/**
 * ROOC Guild Auction Tracker - Frontend JavaScript
 * Auto-save, Admin Controls, Search, Modals, & Theme Toggle
 */

document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initRewardCalculator();
    initAutoSave();
    initSearchAndFilter();
    initGuildMembers();
    initModals();
    initAdminForms();
    initWoeTeams();
});

/* ================= 1. Theme Management (Light/Dark) ================= */
function initTheme() {
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const themeIcon = document.getElementById('themeIcon');
    const savedTheme = localStorage.getItem('rooc_theme') || 'light';

    applyTheme(savedTheme);

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            applyTheme(newTheme);
            localStorage.setItem('rooc_theme', newTheme);
        });
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        if (themeIcon) {
            themeIcon.textContent = theme === 'dark' ? '☀️' : '🌙';
        }
    }
}

/* ================= 2. Auto-Save Mechanism (Admin Only) ================= */
let debounceTimers = {};

function initAutoSave() {
    if (!window.APP_CONFIG.isAdmin) return;

    // A. Checkboxes: Instant save on change
    document.querySelectorAll('.item-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const itemId = this.getAttribute('data-id');
            const field = this.getAttribute('data-field');
            const isChecked = this.checked;

            saveItemField(itemId, { [field]: isChecked ? 1 : 0 });
        });
    });

    // B. Text inputs: Debounced save on input + immediate save on blur
    document.querySelectorAll('.admin-editable').forEach(input => {
        const itemId = input.getAttribute('data-id');
        const field = input.getAttribute('data-field');

        if (field === 'item_date') {
            input.addEventListener('change', function () {
                if (this.value.trim() !== '') {
                    window.syncAllDates(this.value.trim());
                }
            });
            input.addEventListener('blur', function () {
                if (this.value.trim() !== '') {
                    window.syncAllDates(this.value.trim());
                }
            });
            return;
        }

        input.addEventListener('input', function () {
            // Live highlight row
            if (field === 'name') {
                const row = document.getElementById(`row_${itemId}`);
                if (row) {
                    row.setAttribute('data-name', this.value.toLowerCase().trim());
                    if (this.value.trim() !== '') {
                        row.classList.add('is-filled');
                        row.classList.remove('is-empty');
                    } else {
                        row.classList.remove('is-filled');
                        row.classList.add('is-empty');
                    }
                }
            }

            setSaveStatus('saving');
            clearTimeout(debounceTimers[`${itemId}_${field}`]);
            debounceTimers[`${itemId}_${field}`] = setTimeout(() => {
                saveItemField(itemId, { [field]: input.value });
            }, 600);
        });

        input.addEventListener('blur', function () {
            clearTimeout(debounceTimers[`${itemId}_${field}`]);
            saveItemField(itemId, { [field]: input.value });
        });
    });
}

function saveItemField(itemId, payload) {
    setSaveStatus('saving');

    fetch(`${window.APP_CONFIG.routes.itemUpdate}/${itemId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': window.APP_CONFIG.csrfToken
        },
        body: JSON.stringify(payload)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            setSaveStatus('saved');
            updateCounters(data.filled_count, data.total_items);
            updatePageCounters();
        } else {
            setSaveStatus('error');
            showToast(data.message || 'บันทึกข้อมูลไม่สำเร็จ', 'error');
        }
    })
    .catch(error => {
        console.error('Save error:', error);
        setSaveStatus('error');
        showToast('เกิดข้อผิดพลาดในการบันทึกอัตโนมัติ', 'error');
    });
}

function setSaveStatus(state) {
    const badge = document.getElementById('saveStatusBadge');
    if (!badge) return;

    if (state === 'saving') {
        badge.className = 'badge-pill badge-pill-saving';
        badge.innerHTML = '<span class="save-status-icon">⏳</span><span class="save-status-text">กำลังบันทึก...</span>';
    } else if (state === 'saved') {
        badge.className = 'badge-pill badge-pill-saved';
        badge.innerHTML = '<span class="save-status-icon">✓</span><span class="save-status-text">บันทึกอัตโนมัติแล้ว</span>';
    } else if (state === 'error') {
        badge.className = 'badge-pill badge-pill-locked';
        badge.innerHTML = '<span class="save-status-icon">⚠️</span><span class="save-status-text">บันทึกไม่สำเร็จ</span>';
    }
}

function updateCounters(filledCount, totalCount) {
    const filledEl = document.getElementById('filledCounterText');
    const totalEl = document.getElementById('totalCounterText');
    const filterFilledEl = document.getElementById('filterFilledNum');
    const tabBadge = document.getElementById(`tabBadge_${window.APP_CONFIG.activeCategorySlug}`);

    if (filledEl) filledEl.textContent = filledCount;
    if (totalEl) totalEl.textContent = totalCount;
    if (filterFilledEl) filterFilledEl.textContent = filledCount;
    if (tabBadge) tabBadge.textContent = `${filledCount}/${totalCount}`;
}

function updatePageCounters() {
    // Recount per page divider
    document.querySelectorAll('.page-divider-row').forEach(divider => {
        const pageGroup = divider.getAttribute('data-page-group');
        const pageRows = document.querySelectorAll(`tr.item-row[data-page="${pageGroup}"]`);
        let pageFilled = 0;
        pageRows.forEach(r => {
            const nameInput = r.querySelector('.item-name-input');
            if (nameInput && nameInput.value.trim() !== '') {
                pageFilled++;
            }
        });
        const counterEl = document.getElementById(`pageCounter_${pageGroup}`);
        if (counterEl) {
            counterEl.textContent = `กรอกแล้ว ${pageFilled} / 4`;
        }
    });
}

/* ================= 3. Search and Filtering ================= */
function initSearchAndFilter() {
    const searchInput = document.getElementById('itemSearchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const filterButtons = document.querySelectorAll('.filter-btn');

    let currentFilter = 'all';
    let currentSearchTerm = '';

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            currentSearchTerm = this.value.toLowerCase().trim();
            if (clearSearchBtn) {
                clearSearchBtn.classList.toggle('hidden', currentSearchTerm === '');
            }
            applyFilters();
        });
    }

    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function () {
            if (searchInput) {
                searchInput.value = '';
                currentSearchTerm = '';
                this.classList.add('hidden');
                applyFilters();
                searchInput.focus();
            }
        });
    }

    filterButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            filterButtons.forEach(b => b.classList.remove('is-active'));
            this.classList.add('is-active');
            currentFilter = this.getAttribute('data-filter');
            applyFilters();
        });
    });

    window.applyCurrentFilters = applyFilters;

    function applyFilters() {
        const rows = document.querySelectorAll('.item-row');
        const pagesVisibility = {};
        const activeSlug = window.APP_CONFIG?.activeCategorySlug || 'card';
        const currentTarget = window.CURRENT_OUTCOME_TARGET || (window.APP_CONFIG?.targets ? window.APP_CONFIG.targets[activeSlug] : 80);

        rows.forEach(row => {
            const pageNum = row.getAttribute('data-page');
            const itemName = row.getAttribute('data-name') || '';
            const isFilled = row.classList.contains('is-filled');
            const slotNum = parseInt(row.getAttribute('data-slot') || '0', 10);

            // Hide rows beyond current outcome target
            if (slotNum > currentTarget) {
                row.style.display = 'none';
                return;
            }

            let matchesFilter = true;
            if (currentFilter === 'filled' && !isFilled) matchesFilter = false;
            if (currentFilter === 'unfilled' && isFilled) matchesFilter = false;

            let matchesSearch = true;
            if (currentSearchTerm !== '' && !itemName.includes(currentSearchTerm)) {
                matchesSearch = false;
            }

            const visible = matchesFilter && matchesSearch;
            row.style.display = visible ? '' : 'none';

            // Also hide/show extra row if open
            const extraRow = document.getElementById(`extra_row_${row.getAttribute('data-id')}`);
            if (extraRow && !visible) {
                extraRow.classList.add('hidden');
            }

            if (!pagesVisibility[pageNum]) {
                pagesVisibility[pageNum] = false;
            }
            if (visible) {
                pagesVisibility[pageNum] = true;
            }
        });

        // Hide/Show page headers depending on whether any items in that page are visible
        document.querySelectorAll('.page-divider-row').forEach(divider => {
            const pageNum = parseInt(divider.getAttribute('data-page-group'), 10);
            const startSlot = (pageNum - 1) * 4 + 1;
            if (startSlot > currentTarget) {
                divider.style.display = 'none';
            } else {
                divider.style.display = pagesVisibility[pageNum] ? '' : 'none';
            }
        });
    }
}

/* ================= 4. Extra Row Toggle ================= */
window.toggleExtraRow = function (itemId) {
    const extraRow = document.getElementById(`extra_row_${itemId}`);
    if (extraRow) {
        extraRow.classList.toggle('hidden');
    }
};

/* ================= 5. Modals Management ================= */
function initModals() {
    const openLoginBtn = document.getElementById('openLoginModalBtn');
    if (openLoginBtn) {
        openLoginBtn.addEventListener('click', () => openModal('loginModal'));
    }

    const openChangePassBtn = document.getElementById('openChangePassBtn');
    if (openChangePassBtn) {
        openChangePassBtn.addEventListener('click', () => openModal('changePassModal'));
    }

    // Close on clicking backdrop
    document.querySelectorAll('.modal-backdrop').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-backdrop').forEach(modal => {
                modal.classList.add('hidden');
            });
        }
    });
}

window.openModal = function (modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        const firstInput = modal.querySelector('input:not([type="hidden"])');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }
};

window.closeModal = function (modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
    }
};

window.togglePasswordVisibility = function (inputId, btn) {
    const input = document.getElementById(inputId);
    if (input) {
        if (input.type === 'password') {
            input.type = 'text';
            btn.textContent = '🙈';
        } else {
            input.type = 'password';
            btn.textContent = '👁️';
        }
    }
};

/* ================= 6. Admin Forms (Login / Change Pass) ================= */
function initAdminForms() {
    const loginForm = document.getElementById('adminLoginForm');
    const loginError = document.getElementById('loginErrorMsg');

    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (loginError) loginError.classList.add('hidden');

            const submitBtn = document.getElementById('loginSubmitBtn');
            if (submitBtn) submitBtn.disabled = true;

            const formData = new FormData(this);

            fetch(window.APP_CONFIG.routes.adminLogin, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': window.APP_CONFIG.csrfToken
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('เข้าสู่ระบบแอดมินสำเร็จ!', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    if (loginError) {
                        loginError.textContent = data.message || 'รหัสผ่านไม่ถูกต้อง';
                        loginError.classList.remove('hidden');
                    }
                    if (submitBtn) submitBtn.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                if (loginError) {
                    loginError.textContent = 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ';
                    loginError.classList.remove('hidden');
                }
                if (submitBtn) submitBtn.disabled = false;
            });
        });
    }

    const changePassForm = document.getElementById('changePassForm');
    const changePassError = document.getElementById('changePassErrorMsg');

    if (changePassForm) {
        changePassForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (changePassError) changePassError.classList.add('hidden');

            const formData = new FormData(this);

            fetch(window.APP_CONFIG.routes.changePassword, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': window.APP_CONFIG.csrfToken
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('เปลี่ยนรหัสผ่านแอดมินสำเร็จแล้ว!', 'success');
                    closeModal('changePassModal');
                    changePassForm.reset();
                } else {
                    if (changePassError) {
                        changePassError.textContent = data.message || 'ไม่สามารถเปลี่ยนรหัสผ่านได้';
                        changePassError.classList.remove('hidden');
                    }
                }
            })
            .catch(err => {
                console.error(err);
                if (changePassError) {
                    changePassError.textContent = 'เกิดข้อผิดพลาด';
                    changePassError.classList.remove('hidden');
                }
            });
        });
    }
}

/* ================= 7. Summary Export Modal ================= */
window.openSummaryModal = function (slug) {
    const textarea = document.getElementById('summaryTextarea');
    if (textarea) textarea.value = 'กำลังโหลดข้อมูลสรุป...';
    openModal('copySummaryModal');

    fetch(`${window.APP_CONFIG.routes.categoryExport}/${slug}/export`, {
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && textarea) {
            textarea.value = data.summary;
        }
    })
    .catch(err => {
        console.error(err);
        if (textarea) textarea.value = 'เกิดข้อผิดพลาดในการโหลดสรุป';
    });
};

window.copySummaryText = function () {
    const textarea = document.getElementById('summaryTextarea');
    if (!textarea || !textarea.value) return;

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(textarea.value)
            .then(() => {
                showToast('คัดลอกข้อความสรุปเรียบร้อยแล้ว!', 'success');
                closeModal('copySummaryModal');
            })
            .catch(() => fallbackCopy(textarea));
    } else {
        fallbackCopy(textarea);
    }

    function fallbackCopy(el) {
        el.select();
        try {
            document.execCommand('copy');
            showToast('คัดลอกข้อความสรุปเรียบร้อยแล้ว!', 'success');
            closeModal('copySummaryModal');
        } catch (e) {
            showToast('กรุณากดคัดลอกด้วยตนเอง (Ctrl+C)', 'info');
        }
    }
};

/* ================= 8. Reset Category ================= */
window.confirmResetCategory = function (slug, name) {
    if (!confirm(`คำเตือน: คุณต้องการล้างข้อมูลไอเทมและเช็คบ็อกซ์ทั้งหมดของหมวดหมู่ "${name}" ใช่หรือไม่? (การกระทำนี้ไม่สามารถย้อนกลับได้)`)) {
        return;
    }

    fetch(`${window.APP_CONFIG.routes.categoryReset}/${slug}/reset`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': window.APP_CONFIG.csrfToken
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 600);
        } else {
            showToast(data.message || 'ไม่สามารถล้างข้อมูลได้', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('เกิดข้อผิดพลาดในการล้างข้อมูล', 'error');
    });
};

/* ================= 8.2. Guild Members Page Handlers ================= */
let memberDebounceTimers = {};
let activeJobMemberId = null;

function initGuildMembers() {
    const membersTable = document.getElementById('membersTable');
    if (!membersTable) return;

    // Text inputs and Selects: Auto-save
    document.querySelectorAll('.member-editable').forEach(input => {
        const memberId = input.getAttribute('data-id');
        const field = input.getAttribute('data-field');

        input.addEventListener('input', function () {
            if (field === 'name') {
                const row = document.getElementById(`member_row_${memberId}`);
                if (row) {
                    row.setAttribute('data-name', this.value.toLowerCase().trim());
                    if (this.value.trim() !== '') {
                        row.classList.add('is-filled');
                        row.classList.remove('is-empty');
                    } else {
                        row.classList.remove('is-filled');
                        row.classList.add('is-empty');
                    }
                }
            } else if (field === 'class_job') {
                const row = document.getElementById(`member_row_${memberId}`);
                if (row) row.setAttribute('data-job', this.value.toLowerCase().trim());
            }

            setMemberSaveStatus('saving');
            clearTimeout(memberDebounceTimers[`${memberId}_${field}`]);
            memberDebounceTimers[`${memberId}_${field}`] = setTimeout(() => {
                saveMemberField(memberId, { [field]: input.value });
            }, 600);
        });

        if (input.tagName === 'SELECT') {
            input.addEventListener('change', function () {
                const row = document.getElementById(`member_row_${memberId}`);
                if (row) row.setAttribute('data-role', this.value.toLowerCase().trim());
                saveMemberField(memberId, { [field]: this.value });
            });
        }

        input.addEventListener('blur', function () {
            clearTimeout(memberDebounceTimers[`${memberId}_${field}`]);
            saveMemberField(memberId, { [field]: input.value });
        });
    });

    // Search & Filter for Members
    const searchInput = document.getElementById('memberSearchInput');
    const clearSearchBtn = document.getElementById('clearMemberSearchBtn');
    const filterButtons = document.querySelectorAll('.member-filter-btn');

    let currentFilter = 'all';
    let currentSearch = '';

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            currentSearch = this.value.toLowerCase().trim();
            if (clearSearchBtn) {
                clearSearchBtn.classList.toggle('hidden', currentSearch === '');
            }
            applyMemberFilters();
        });
    }

    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function () {
            if (searchInput) {
                searchInput.value = '';
                currentSearch = '';
                this.classList.add('hidden');
                applyMemberFilters();
                searchInput.focus();
            }
        });
    }

    filterButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            filterButtons.forEach(b => b.classList.remove('is-active'));
            this.classList.add('is-active');
            currentFilter = this.getAttribute('data-filter');
            applyMemberFilters();
        });
    });

    function applyMemberFilters() {
        document.querySelectorAll('.member-row').forEach(row => {
            const name = row.getAttribute('data-name') || '';
            const job = row.getAttribute('data-job') || '';
            const role = row.getAttribute('data-role') || '';
            const isFilled = row.classList.contains('is-filled');

            let matchFilter = true;
            if (currentFilter === 'filled' && !isFilled) matchFilter = false;
            if (currentFilter === 'unfilled' && isFilled) matchFilter = false;

            let matchSearch = true;
            if (currentSearch !== '') {
                const combined = `${name} ${job} ${role}`;
                if (!combined.includes(currentSearch)) {
                    matchSearch = false;
                }
            }

            row.style.display = (matchFilter && matchSearch) ? '' : 'none';
        });
    }

    // Close floating job menu on window events
    window.addEventListener('scroll', closeJobDropdown, { passive: true });
    window.addEventListener('resize', closeJobDropdown);
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#floatingJobDropdown') && !e.target.closest('.job-dropdown-trigger')) {
            closeJobDropdown();
        }
    });
}

/* ================= 8.3. Custom Job Selector Dropdown Handlers ================= */
window.toggleJobDropdown = function(memberId, event) {
    if (event) event.stopPropagation();
    const dropdown = document.getElementById('floatingJobDropdown');
    const trigger = document.getElementById(`jobTrigger_${memberId}`);
    if (!dropdown || !trigger) return;

    if (dropdown.classList.contains('is-open') && activeJobMemberId === memberId) {
        closeJobDropdown();
        return;
    }

    activeJobMemberId = memberId;
    const currentVal = document.getElementById(`jobInput_${memberId}`)?.value || '';

    // Highlight selected option in menu
    dropdown.querySelectorAll('.job-option-item').forEach(opt => {
        const isSelected = (opt.dataset.value === currentVal);
        opt.classList.toggle('is-selected', isSelected);
    });

    // Position dropdown relative to trigger button
    const rect = trigger.getBoundingClientRect();
    const scrollY = window.scrollY || window.pageYOffset;
    const scrollX = window.scrollX || window.pageXOffset;

    dropdown.style.top = `${rect.bottom + scrollY + 4}px`;
    dropdown.style.left = `${rect.left + scrollX}px`;
    dropdown.style.width = `${Math.max(rect.width, 220)}px`;

    dropdown.classList.add('is-open');

    // Flip upwards if exceeding viewport bottom
    const dropdownRect = dropdown.getBoundingClientRect();
    if (dropdownRect.bottom > window.innerHeight && rect.top > dropdownRect.height) {
        dropdown.style.top = `${rect.top + scrollY - dropdownRect.height - 4}px`;
    }
};

window.selectJobOption = function(jobName, jobSlug) {
    if (!activeJobMemberId) return;
    const memberId = activeJobMemberId;
    const input = document.getElementById(`jobInput_${memberId}`);
    const trigger = document.getElementById(`jobTrigger_${memberId}`);
    const triggerText = document.getElementById(`jobTriggerText_${memberId}`);
    const triggerImg = document.getElementById(`jobTriggerImg_${memberId}`);
    const row = document.getElementById(`member_row_${memberId}`);

    if (input) input.value = jobName;
    if (triggerText) triggerText.textContent = jobName || 'เลือกอาชีพ...';
    if (trigger) {
        trigger.classList.toggle('has-val', !!jobName);
        trigger.classList.toggle('is-empty', !jobName);
    }

    if (triggerImg) {
        if (jobSlug && jobName) {
            triggerImg.outerHTML = `<img src="/images/classes/icons/${jobSlug}.png" class="job-trigger-icon" alt="${jobName}" id="jobTriggerImg_${memberId}">`;
        } else {
            triggerImg.outerHTML = `<span class="job-trigger-placeholder-icon" id="jobTriggerImg_${memberId}">🎭</span>`;
        }
    }

    if (row) {
        row.setAttribute('data-job', (jobName || '').toLowerCase().trim());
    }

    saveMemberField(memberId, { class_job: jobName });
    closeJobDropdown();
};

window.closeJobDropdown = function() {
    const dropdown = document.getElementById('floatingJobDropdown');
    if (dropdown) {
        dropdown.classList.remove('is-open');
    }
    activeJobMemberId = null;
};

function saveMemberField(memberId, payload) {
    setMemberSaveStatus('saving');

    fetch(`${window.APP_CONFIG.routes.memberUpdate}/${memberId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': window.APP_CONFIG.csrfToken
        },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            setMemberSaveStatus('saved');
            const filledEl = document.getElementById('memberFilledText');
            const attendedEl = document.getElementById('memberAttendedText');
            const filterFilledEl = document.getElementById('memberFilterFilledNum');
            const filterAttendedEl = document.getElementById('memberFilterAttendedNum');

            if (filledEl) filledEl.textContent = data.filled_count;
            if (attendedEl) attendedEl.textContent = data.attended_count;
            if (filterFilledEl) filterFilledEl.textContent = data.filled_count;
            if (filterAttendedEl) filterAttendedEl.textContent = data.attended_count;
        } else {
            setMemberSaveStatus('error');
            showToast(data.message || 'บันทึกข้อมูลสมาชิกไม่สำเร็จ', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        setMemberSaveStatus('error');
    });
}

function setMemberSaveStatus(state) {
    const badge = document.getElementById('memberSaveStatusBadge');
    if (!badge) return;

    if (state === 'saving') {
        badge.className = 'badge-pill badge-pill-saving';
        badge.innerHTML = '<span class="save-status-icon">⏳</span><span class="save-status-text">กำลังบันทึก...</span>';
    } else if (state === 'saved') {
        badge.className = 'badge-pill badge-pill-saved';
        badge.innerHTML = '<span class="save-status-icon">✓</span><span class="save-status-text">บันทึกอัตโนมัติแล้ว</span>';
    } else if (state === 'error') {
        badge.className = 'badge-pill badge-pill-locked';
        badge.innerHTML = '<span class="save-status-icon">⚠️</span><span class="save-status-text">บันทึกไม่สำเร็จ</span>';
    }
}

window.openMemberExportModal = function () {
    const textarea = document.getElementById('memberSummaryTextarea');
    if (textarea) textarea.value = 'กำลังโหลดรายชื่อสมาชิก...';
    openModal('memberExportModal');

    fetch(window.APP_CONFIG.routes.memberExport, {
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && textarea) {
            textarea.value = data.summary;
        }
    })
    .catch(err => {
        console.error(err);
        if (textarea) textarea.value = 'เกิดข้อผิดพลาดในการโหลดรายชื่อ';
    });
};

window.copyMemberSummaryText = function () {
    const textarea = document.getElementById('memberSummaryTextarea');
    if (!textarea || !textarea.value) return;

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(textarea.value)
            .then(() => {
                showToast('คัดลอกรายชื่อสมาชิกเรียบร้อยแล้ว!', 'success');
                closeModal('memberExportModal');
            })
            .catch(() => fallbackCopy(textarea));
    } else {
        fallbackCopy(textarea);
    }

    function fallbackCopy(el) {
        el.select();
        try {
            document.execCommand('copy');
            showToast('คัดลอกรายชื่อสมาชิกเรียบร้อยแล้ว!', 'success');
            closeModal('memberExportModal');
        } catch (e) {
            showToast('กรุณากดคัดลอกด้วยตนเอง (Ctrl+C)', 'info');
        }
    }
};

window.confirmResetMembers = function () {
    if (!confirm('คำเตือน: คุณต้องการล้างข้อมูลสมาชิกกิลด์ทั้งหมด 80 ช่องใช่หรือไม่? (การกระทำนี้ไม่สามารถย้อนกลับได้)')) {
        return;
    }

    fetch(window.APP_CONFIG.routes.memberReset, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': window.APP_CONFIG.csrfToken
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => window.location.reload(), 600);
        } else {
            showToast(data.message || 'ไม่สามารถล้างข้อมูลได้', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('เกิดข้อผิดพลาดในการล้างข้อมูลสมาชิก', 'error');
    });
};

/* ================= 9. Toast Notification System ================= */
window.showToast = function (message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;

    let icon = 'ℹ️';
    if (type === 'success') icon = '✅';
    if (type === 'error') icon = '❌';

    toast.innerHTML = `<span>${icon}</span><span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3200);
};

/* ================= 10. Reward Calculator (ชนะ/แพ้ Outcome Buttons) ================= */
/**
 * Base rewards per category:
 *   ขนขาว   = 50 ชิ้น
 *   ขนแดงดำ = 90 ชิ้น
 *   การ์ด   = 6 ใบ
 *
 * Multiplier logic (bonus added on top of base):
 *   ชนะ 100%   → base × 2.0
 *   แพ้ 70%    → base × 1.7
 *   แพ้ 50%    → base × 1.5
 *   แพ้ขาด 0% → base × 1.0
 */
function initRewardCalculator() {
    const modeTabs = document.querySelectorAll('.mode-tab-pill');
    const outcomeButtons = document.querySelectorAll('.outcome-btn');
    const confirmBtn = document.getElementById('confirmOutcomeBtn');
    const changeBtn = document.getElementById('changeOutcomeBtn');
    const statusBadge = document.getElementById('outcomeStatusBadge');

    if (!outcomeButtons.length && !modeTabs.length) return;

    const BASE_REWARDS = {
        white_feather:     { base: 50, unit: 'ชิ้น' },
        red_black_feather: { base: 90, unit: 'ชิ้น' },
        card:              { base: 6,  unit: 'ใบ'   },
    };

    const MULTIPLIERS = {
        win_100: { mult: 2.0, label: '1. ชนะ (100%)' },
        lose_70: { mult: 1.7, label: '2. แพ้ (70%)' },
        lose_50: { mult: 1.5, label: '3. แพ้ (50%)' },
        lose_0:  { mult: 1.0, label: '4. แพ้ขาด (0%)' },
    };

    const OVERRUN_TIERS = {
        rank_1: {
            label: '1. ได้อันดับ 1',
            card: 20,
            white_feather: 150,
            red_black_feather: 170,
            sub_calc: 'OVERRUN • 1. ได้อันดับ 1'
        },
        rank_2_3: {
            label: '2. ได้อันดับ 2-3',
            card: 20,
            white_feather: 140,
            red_black_feather: 160,
            sub_calc: 'OVERRUN • 2. ได้อันดับ 2-3'
        },
        rank_4_6: {
            label: '3. ได้อันดับ 4-6',
            card: 15,
            white_feather: 120,
            red_black_feather: 150,
            sub_calc: 'OVERRUN • 3. ได้อันดับ 4-6'
        },
        rank_7_8: {
            label: '4. ได้อันดับ 7-8',
            card: 12,
            white_feather: 100,
            red_black_feather: 150,
            sub_calc: 'OVERRUN • 4. ได้อันดับ 7-8'
        }
    };

    let currentEventMode = window.APP_CONFIG?.eventMode || 'shining_stars';
    let currentOutcomeKey = window.APP_CONFIG?.outcomeKey || (currentEventMode === 'overrun' ? 'rank_1' : 'win_100');
    let isConfirmed = window.APP_CONFIG?.outcomeConfirmed ?? true;
    const isAdmin = window.APP_CONFIG?.isAdmin ?? false;

    function getTargetsForOutcome(mode, key) {
        if (mode === 'overrun') {
            const tier = OVERRUN_TIERS[key] || OVERRUN_TIERS['rank_1'];
            return {
                mode: 'overrun',
                key: key,
                label: tier.label,
                card: tier.card,
                white_feather: tier.white_feather,
                red_black_feather: tier.red_black_feather,
                sub_calc: {
                    white_feather: tier.sub_calc,
                    red_black_feather: tier.sub_calc,
                    card: tier.sub_calc,
                }
            };
        }

        // Shining Stars (Guild League)
        const multInfo = MULTIPLIERS[key] || MULTIPLIERS['win_100'];
        const mult = multInfo.mult;
        return {
            mode: 'shining_stars',
            key: key,
            mult: mult,
            label: multInfo.label,
            card: Math.round(BASE_REWARDS.card.base * mult),
            white_feather: Math.round(BASE_REWARDS.white_feather.base * mult),
            red_black_feather: Math.round(BASE_REWARDS.red_black_feather.base * mult),
            sub_calc: {
                white_feather: `ฐาน ${BASE_REWARDS.white_feather.base} × ${multInfo.label}`,
                red_black_feather: `ฐาน ${BASE_REWARDS.red_black_feather.base} × ${multInfo.label}`,
                card: `ฐาน ${BASE_REWARDS.card.base} × ${multInfo.label}`,
            }
        };
    }

    function applyOutcomeView(mode, key) {
        const targets = getTargetsForOutcome(mode, key);
        const activeSlug = window.APP_CONFIG?.activeCategorySlug || 'card';
        const currentTarget = targets[activeSlug] || 80;
        window.CURRENT_OUTCOME_TARGET = currentTarget;

        // 1. Update the 3 reward cards numbers and subtitles
        ['white_feather', 'red_black_feather', 'card'].forEach(slug => {
            const calcEl = document.getElementById(`calc_${slug}`);
            const subEl = document.getElementById(`sub_${slug}`);
            const qty = targets[slug];

            if (calcEl) {
                calcEl.textContent = qty;
                calcEl.style.transform = 'scale(1.25)';
                setTimeout(() => calcEl.style.transform = 'scale(1)', 200);
            }
            if (subEl) {
                subEl.textContent = targets.sub_calc[slug] || '';
            }
        });

        // 2. Update category tab badges (e.g. 0/12, 0/100, 0/180)
        ['card', 'white_feather', 'red_black_feather'].forEach(slug => {
            const badge = document.getElementById(`tabBadge_${slug}`);
            if (badge) {
                const parts = badge.textContent.split('/');
                const filled = parts[0].trim();
                badge.textContent = `${filled}/${targets[slug]}`;
            }
        });

        // 3. Update header total counter & filter button
        const totalCounter = document.getElementById('totalCounterText');
        const filterTotal = document.getElementById('filterTotalNum');
        if (totalCounter) totalCounter.textContent = currentTarget;
        if (filterTotal) filterTotal.textContent = currentTarget;

        // 4. Update table rows visibility and page dividers
        if (typeof window.applyCurrentFilters === 'function') {
            window.applyCurrentFilters();
        }

        // Recount per page divider
        const isCard = (activeSlug === 'card');
        const whitePages = Math.ceil(targets.white_feather / 4);
        const pageOffset = (activeSlug === 'red_black_feather') ? whitePages : 0;

        document.querySelectorAll('.page-divider-row').forEach(divider => {
            const pageNum = parseInt(divider.getAttribute('data-page-group'), 10);
            const localPage = isCard ? pageNum : (pageNum - pageOffset);
            const startSlot = (localPage - 1) * 4 + 1;
            const endSlot = Math.min(localPage * 4, currentTarget);

            if (startSlot > currentTarget || localPage < 1) {
                divider.style.display = 'none';
                return;
            }

            const titleEl = divider.querySelector('.page-title');
            if (titleEl) {
                if (isCard) {
                    titleEl.textContent = `หน้า ${pageNum} (เล่ม 1-${endSlot - startSlot + 1})`;
                } else {
                    const countInPage = endSlot - startSlot + 1;
                    const pieceLabel = countInPage >= 4 ? 'อันที่ 1-4' : (countInPage === 1 ? 'อันที่ 1' : `อันที่ 1-${countInPage}`);
                    titleEl.textContent = `หน้า ${pageNum} (${pieceLabel})`;
                }
            }

            const pageRows = document.querySelectorAll(`tr.item-row[data-page="${pageNum}"]`);
            let pageFilled = 0;
            let pageTotal = 0;
            pageRows.forEach(r => {
                const slot = parseInt(r.getAttribute('data-slot') || '0', 10);
                if (slot <= currentTarget) {
                    pageTotal++;
                    const nameInput = r.querySelector('.item-name-input');
                    const guestView = r.querySelector('.guest-name-view');
                    const hasVal = (nameInput && nameInput.value.trim() !== '') || 
                                   (guestView && !guestView.classList.contains('is-placeholder') && guestView.textContent.trim() !== '' && guestView.textContent.trim() !== 'ชื่อ..' && guestView.textContent.trim() !== '(ยังไม่มีคนได้)');
                    if (hasVal) {
                        pageFilled++;
                    }
                }
            });

            const counterEl = document.getElementById(`pageCounter_${pageNum}`);
            if (counterEl) {
                counterEl.textContent = `กรอกแล้ว ${pageFilled} / ${pageTotal}`;
            }
        });

        // Recount total filled within current target
        let totalFilled = 0;
        document.querySelectorAll('tr.item-row').forEach(r => {
            const slot = parseInt(r.getAttribute('data-slot') || '0', 10);
            if (slot <= currentTarget && r.classList.contains('is-filled')) {
                totalFilled++;
            }
        });

        const filledCounter = document.getElementById('filledCounterText');
        const filterFilled = document.getElementById('filterFilledNum');
        if (filledCounter) filledCounter.textContent = totalFilled;
        if (filterFilled) filterFilled.textContent = totalFilled;

        const currentTabBadge = document.getElementById(`tabBadge_${activeSlug}`);
        if (currentTabBadge) {
            currentTabBadge.textContent = `${totalFilled}/${currentTarget}`;
        }
    }

    // Switch Event Mode UI
    function switchMode(newMode) {
        currentEventMode = newMode;
        modeTabs.forEach(t => t.classList.toggle('is-active', t.dataset.mode === newMode));

        const groupShining = document.getElementById('outcomeGroup_shining_stars');
        const groupOverrun = document.getElementById('outcomeGroup_overrun');
        if (groupShining) groupShining.classList.toggle('hidden', newMode !== 'shining_stars');
        if (groupOverrun) groupOverrun.classList.toggle('hidden', newMode !== 'overrun');

        const activeGroup = newMode === 'overrun' ? groupOverrun : groupShining;
        const activeBtn = activeGroup?.querySelector('.outcome-btn.is-active') || activeGroup?.querySelector('.outcome-btn');
        if (activeBtn) {
            activeGroup.querySelectorAll('.outcome-btn').forEach(b => b.classList.remove('is-active'));
            activeBtn.classList.add('is-active');
            currentOutcomeKey = activeBtn.dataset.key;
        } else {
            currentOutcomeKey = newMode === 'overrun' ? 'rank_1' : 'win_100';
        }

        applyOutcomeView(currentEventMode, currentOutcomeKey);

        if (isAdmin) {
            if (statusBadge) {
                statusBadge.className = 'outcome-status-tag is-pending';
                statusBadge.innerHTML = '<span class="tag-icon">✏️</span><span class="tag-text">เลือกแล้ว (ยังไม่ยืนยัน)</span>';
            }
            if (confirmBtn) confirmBtn.classList.remove('hidden');
            if (changeBtn) changeBtn.classList.add('hidden');
            isConfirmed = false;
            outcomeButtons.forEach(b => b.disabled = false);
        }
    }

    // Mode tabs event listener
    modeTabs.forEach(tab => {
        tab.addEventListener('click', function () {
            const mode = this.dataset.mode;
            if (mode && mode !== currentEventMode) {
                switchMode(mode);
            }
        });
    });

    // Initialize display with current mode & outcome
    applyOutcomeView(currentEventMode, currentOutcomeKey);

    // If confirmed and not admin, disable outcome buttons
    if (isConfirmed && !isAdmin) {
        outcomeButtons.forEach(b => b.disabled = true);
    }

    // Button click handler: select outcome
    outcomeButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            if (this.disabled) return;
            const parentGroup = this.closest('.outcome-button-group');
            if (parentGroup) {
                parentGroup.querySelectorAll('.outcome-btn').forEach(b => b.classList.remove('is-active'));
            } else {
                outcomeButtons.forEach(b => b.classList.remove('is-active'));
            }
            this.classList.add('is-active');
            currentOutcomeKey = this.dataset.key;

            // Live preview immediately
            applyOutcomeView(currentEventMode, currentOutcomeKey);

            if (statusBadge) {
                statusBadge.className = 'outcome-status-tag is-pending';
                statusBadge.innerHTML = '<span class="tag-icon">✏️</span><span class="tag-text">เลือกแล้ว (ยังไม่ยืนยัน)</span>';
            }
            if (confirmBtn) confirmBtn.classList.remove('hidden');
            if (changeBtn) changeBtn.classList.add('hidden');
        });
    });

    // Confirm button handler (Admin only)
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            const activeGroup = currentEventMode === 'overrun' 
                ? document.getElementById('outcomeGroup_overrun') 
                : document.getElementById('outcomeGroup_shining_stars');
            const activeBtn = activeGroup ? activeGroup.querySelector('.outcome-btn.is-active') : document.querySelector('.outcome-btn.is-active');
            if (!activeBtn) return;
            const outcomeKey = activeBtn.dataset.key;

            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<span>⏳ กำลังบันทึก...</span>';

            fetch(window.APP_CONFIG.routes.saveOutcome, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': window.APP_CONFIG.csrfToken
                },
                body: JSON.stringify({
                    event_mode: currentEventMode,
                    outcome: outcomeKey,
                    confirmed: true
                })
            })
            .then(res => res.json())
            .then(data => {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<span>✅ กดยืนยัน</span>';

                if (data.success) {
                    showToast('ยืนยันผลการแข่งขันเรียบร้อยแล้ว!', 'success');
                    isConfirmed = true;
                    if (statusBadge) {
                        statusBadge.className = 'outcome-status-tag is-confirmed';
                        statusBadge.innerHTML = '<span class="tag-icon">✅</span><span class="tag-text">ยืนยันผลแล้ว</span>';
                    }
                    confirmBtn.classList.add('hidden');
                    if (changeBtn) changeBtn.classList.remove('hidden');

                    applyOutcomeView(currentEventMode, outcomeKey);
                } else {
                    showToast(data.message || 'ไม่สามารถบันทึกผลการแข่งขันได้', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<span>✅ กดยืนยัน</span>';
                showToast('เกิดข้อผิดพลาดในการบันทึก', 'error');
            });
        });
    }

    // Change button handler (Admin only)
    if (changeBtn) {
        changeBtn.addEventListener('click', function () {
            isConfirmed = false;
            outcomeButtons.forEach(b => b.disabled = false);

            if (statusBadge) {
                statusBadge.className = 'outcome-status-tag is-pending';
                statusBadge.innerHTML = '<span class="tag-icon">✏️</span><span class="tag-text">เลือกแล้ว (ยังไม่ยืนยัน)</span>';
            }
            changeBtn.classList.add('hidden');
            if (confirmBtn) {
                confirmBtn.classList.remove('hidden');
                confirmBtn.disabled = false;
            }
            showToast('ปลดล็อกแล้ว สามารถเลือกผลการแข่งขันใหม่และกดยืนยันได้', 'info');
        });
    }
}

/* ================= 11. WoE Team Auto-save & Drag-and-Drop ================= */
let activeDragData = null;

function initWoeTeams() {
    const woeContainer = document.getElementById('woeContainer');
    const woeMembersList = document.getElementById('woeMembersList');
    if (!woeContainer && !woeMembersList) return;

    const isAdmin = window.APP_CONFIG?.isAdmin ?? false;

    // 1. Setup Roster Search & Filter (Available in both Admin & Guest mode)
    initRosterControls();

    // If not admin, no drag-drop or editing needed
    if (!isAdmin) return;

    let woeDebounce = {};

    // 2. Input/Change listeners for manual editing
    if (woeContainer) {
        woeContainer.addEventListener('change', function (e) {
            const target = e.target;
            if (target.matches('.woe-member-input')) {
                const slotId = target.dataset.slotId;
                const field  = target.dataset.field;
                if (slotId && field) {
                    saveWoeSlot(slotId, field, target.value);
                    syncSlotAfterManualInput(target.closest('.woe-slot'), target.value);
                }
            }
        });

        woeContainer.addEventListener('input', function (e) {
            const target = e.target;
            if (target.matches('.woe-member-input')) {
                const slotId = target.dataset.slotId;
                const field  = target.dataset.field;
                if (!slotId || !field) return;
                const key = `${slotId}_${field}`;
                clearTimeout(woeDebounce[key]);
                woeDebounce[key] = setTimeout(() => {
                    saveWoeSlot(slotId, field, target.value);
                    syncSlotAfterManualInput(target.closest('.woe-slot'), target.value);
                }, 700);
            }
        });

        woeContainer.addEventListener('focusout', function (e) {
            const target = e.target;
            if (target.matches('.woe-member-input')) {
                const slotId = target.dataset.slotId;
                const field  = target.dataset.field;
                if (!slotId || !field) return;
                clearTimeout(woeDebounce[`${slotId}_${field}`]);
                saveWoeSlot(slotId, field, target.value);
                syncSlotAfterManualInput(target.closest('.woe-slot'), target.value);
            }
        });

        // 3. Setup Drag and Drop Listeners
        initWoeDragAndDrop(woeContainer, woeMembersList);
    }

    function saveWoeSlot(slotId, field, value) {
        const routes = window.APP_CONFIG?.routes ?? {};
        if (!routes.woeUpdate) return;

        return fetch(`${routes.woeUpdate}/${slotId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': window.APP_CONFIG.csrfToken
            },
            body: JSON.stringify({ [field]: value })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('บันทึกข้อมูลตี้วอแล้ว ✓', 'success');
            }
            return data;
        })
        .catch(() => {
            showToast('เกิดข้อผิดพลาดในการบันทึกตี้วอ', 'error');
        });
    }
}

/**
 * Job slug and icon resolver helper
 */
window.getJobIconUrl = function(jobName) {
    if (!jobName) return '';
    const map = {
        'rebellion': 'rebellion',
        'lord knight': 'lord_knight',
        'paladin': 'paladin',
        'high priest': 'high_priest',
        'champion': 'champion',
        'high wizard': 'high_wizard',
        'professor': 'professor',
        'assassin cross': 'assassin_cross',
        'sniper': 'sniper',
        'gypsy': 'gypsy',
        'mastersmith': 'mastersmith',
        'biochemist': 'biochemist',
        'summoner': 'summoner',
        'whitesmith': 'mastersmith',
        'creator': 'biochemist'
    };
    const slug = map[jobName.trim().toLowerCase()];
    return slug ? `/images/classes/icons/${slug}.png` : '';
};

/**
 * Character illustration URL resolver helper
 */
window.getCharacterImageUrl = function(jobName) {
    if (!jobName) return '';
    const map = {
        'rebellion': 'rebellion',
        'lord knight': 'lord_knight',
        'paladin': 'paladin',
        'high priest': 'high_priest',
        'champion': 'champion',
        'high wizard': 'high_wizard',
        'professor': 'professor',
        'assassin cross': 'assassin_cross',
        'sniper': 'sniper',
        'gypsy': 'gypsy',
        'mastersmith': 'mastersmith',
        'biochemist': 'biochemist',
        'summoner': 'summoner',
        'whitesmith': 'mastersmith',
        'creator': 'biochemist'
    };
    const slug = map[jobName.trim().toLowerCase()];
    return slug ? `/images/classes/portraits/${slug}.png` : '';
};

/**
 * Initialize Drag and Drop & Click-to-Place interaction for WoE
 */
function initWoeDragAndDrop(woeContainer, woeMembersList) {
    let selectedMember = null;

    // A. Drag from Roster List
    if (woeMembersList) {
        woeMembersList.addEventListener('dragstart', function (e) {
            const row = e.target.closest('.roster-member-row');
            if (!row) return;

            activeDragData = {
                type: 'roster',
                id: row.dataset.id,
                name: row.dataset.name,
                job: row.dataset.job,
                iconUrl: row.dataset.iconUrl,
                characterUrl: row.dataset.characterUrl,
                sourceRow: row
            };

            row.classList.add('is-dragging');
            document.body.classList.add('is-dragging-member');

            if (e.dataTransfer) {
                e.dataTransfer.effectAllowed = 'copyMove';
                e.dataTransfer.setData('text/plain', row.dataset.name);
                e.dataTransfer.setData('application/json', JSON.stringify(activeDragData));
            }
        });

        woeMembersList.addEventListener('dragend', function (e) {
            const row = e.target.closest('.roster-member-row');
            if (row) row.classList.remove('is-dragging');
            document.body.classList.remove('is-dragging-member');
            document.querySelectorAll('.woe-slot.is-drag-over').forEach(el => el.classList.remove('is-drag-over'));
            activeDragData = null;
        });

        // Click on member to select (Click-to-Place feature)
        woeMembersList.addEventListener('click', function (e) {
            const row = e.target.closest('.roster-member-row');
            if (!row) return;

            if (selectedMember && selectedMember.id === row.dataset.id) {
                // Deselect
                selectedMember = null;
                row.classList.remove('is-selected-for-drop');
                document.body.classList.remove('has-selected-member');
            } else {
                // Select
                document.querySelectorAll('.roster-member-row.is-selected-for-drop').forEach(r => r.classList.remove('is-selected-for-drop'));
                row.classList.add('is-selected-for-drop');
                selectedMember = {
                    id: row.dataset.id,
                    name: row.dataset.name,
                    job: row.dataset.job,
                    iconUrl: row.dataset.iconUrl,
                    characterUrl: row.dataset.characterUrl
                };
                document.body.classList.add('has-selected-member');
                showToast(`เลือก ${selectedMember.name} แล้ว — คลิกช่องปาร์ตี้ที่ต้องการเพื่อจัดตี้ได้เลย`, 'info');
            }
        });
    }

    // B. Drag from an already filled Slot (to swap or move)
    if (woeContainer) {
        woeContainer.addEventListener('dragstart', function (e) {
            const slot = e.target.closest('.woe-slot');
            if (!slot || slot.classList.contains('is-empty')) return;

            // Don't drag if user is actively highlighting text inside input
            if (e.target.matches('input') && e.target === document.activeElement) {
                return;
            }

            activeDragData = {
                type: 'slot',
                fromSlotId: slot.dataset.slotId,
                fromRoom: slot.dataset.room,
                fromParty: slot.dataset.party,
                fromSlotNum: slot.dataset.slot,
                name: slot.dataset.memberName,
                job: slot.dataset.classJob,
                iconUrl: slot.querySelector('.woe-slot-job-icon')?.src || '',
                sourceSlot: slot
            };

            slot.classList.add('is-dragging');
            document.body.classList.add('is-dragging-member');

            if (e.dataTransfer) {
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', slot.dataset.memberName);
                e.dataTransfer.setData('application/json', JSON.stringify(activeDragData));
            }
        });

        woeContainer.addEventListener('dragend', function (e) {
            const slot = e.target.closest('.woe-slot');
            if (slot) slot.classList.remove('is-dragging');
            document.body.classList.remove('is-dragging-member');
            document.querySelectorAll('.woe-slot.is-drag-over').forEach(el => el.classList.remove('is-drag-over'));
            activeDragData = null;
        });

        // Click-to-Place handler on party slots
        woeContainer.addEventListener('click', function (e) {
            if (!selectedMember) return;
            const slot = e.target.closest('.woe-slot');
            if (!slot) return;

            e.preventDefault();
            e.stopPropagation();

            assignMemberToSlot(slot, selectedMember.name, selectedMember.job, selectedMember.iconUrl, selectedMember.characterUrl);

            // Clear selected member
            selectedMember = null;
            document.querySelectorAll('.roster-member-row.is-selected-for-drop').forEach(r => r.classList.remove('is-selected-for-drop'));
            document.body.classList.remove('has-selected-member');
        });

        // C. Drop Targets on Slots
        woeContainer.addEventListener('dragenter', function (e) {
            const slot = e.target.closest('.woe-slot');
            if (slot) {
                e.preventDefault();
                slot.classList.add('is-drag-over');
            }
        });

        woeContainer.addEventListener('dragover', function (e) {
            const slot = e.target.closest('.woe-slot');
            if (!slot) return;

            e.preventDefault();
            if (e.dataTransfer) {
                e.dataTransfer.dropEffect = (activeDragData && activeDragData.type === 'slot') ? 'move' : 'copy';
            }
            slot.classList.add('is-drag-over');
        });

        woeContainer.addEventListener('dragleave', function (e) {
            const slot = e.target.closest('.woe-slot');
            if (slot && !slot.contains(e.relatedTarget)) {
                slot.classList.remove('is-drag-over');
            }
        });

        woeContainer.addEventListener('drop', function (e) {
            const targetSlot = e.target.closest('.woe-slot');
            if (!targetSlot) return;

            e.preventDefault();
            e.stopPropagation();
            targetSlot.classList.remove('is-drag-over');
            document.body.classList.remove('is-dragging-member');

            const targetSlotId = targetSlot.dataset.slotId;
            if (!targetSlotId) return;

            // Resolve drag data (from memory or dataTransfer)
            let dragInfo = activeDragData;
            if (!dragInfo && e.dataTransfer) {
                try {
                    const json = e.dataTransfer.getData('application/json');
                    if (json) dragInfo = JSON.parse(json);
                } catch (err) {}

                if (!dragInfo) {
                    const text = e.dataTransfer.getData('text/plain');
                    if (text) {
                        const row = document.querySelector(`.roster-member-row[data-name="${text}"]`);
                        if (row) {
                            dragInfo = {
                                type: 'roster',
                                id: row.dataset.id,
                                name: row.dataset.name,
                                job: row.dataset.job,
                                iconUrl: row.dataset.iconUrl
                            };
                        }
                    }
                }
            }

            if (!dragInfo) return;

            if (dragInfo.type === 'roster') {
                // Drop from roster to slot
                assignMemberToSlot(targetSlot, dragInfo.name, dragInfo.job, dragInfo.iconUrl, dragInfo.characterUrl);
            } else if (dragInfo.type === 'slot') {
                // Drop from one slot to another (swap or move)
                if (dragInfo.fromSlotId === targetSlotId) return; // Dropped onto itself

                const sourceSlot = dragInfo.sourceSlot || document.querySelector(`[data-slot-id="${dragInfo.fromSlotId}"]`);
                const sourceName = dragInfo.name;
                const sourceJob = dragInfo.job;
                const sourceIcon = dragInfo.iconUrl;
                const sourceChar = dragInfo.characterUrl || window.getCharacterImageUrl(sourceJob);

                const targetName = targetSlot.dataset.memberName || '';
                const targetJob = targetSlot.dataset.classJob || '';
                const targetIcon = targetSlot.querySelector('.woe-slot-job-icon')?.src || '';
                const targetChar = window.getCharacterImageUrl(targetJob);

                // Put source into target
                assignMemberToSlot(targetSlot, sourceName, sourceJob, sourceIcon, sourceChar);

                // If target had a player, move target player to source slot (swap), else clear source slot
                if (sourceSlot) {
                    if (targetName) {
                        assignMemberToSlot(sourceSlot, targetName, targetJob, targetIcon, targetChar);
                    } else {
                        performClearSlot(sourceSlot);
                    }
                }
            }

            activeDragData = null;
        });
    }
}

/**
 * Assign a member into a slot and auto-save
 */
function assignMemberToSlot(slotEl, memberName, classJob, iconUrl, characterUrl) {
    if (!slotEl || !memberName) return;
    const slotId = slotEl.dataset.slotId;
    const room = slotEl.dataset.room;
    const party = slotEl.dataset.party;

    const prevMemberName = slotEl.dataset.memberName;

    // 1. Update UI immediately
    slotEl.classList.remove('is-empty');
    slotEl.classList.add('is-filled');
    slotEl.dataset.memberName = memberName;
    slotEl.dataset.classJob = classJob || '';
    slotEl.setAttribute('draggable', 'true');

    // Update input value or text span
    const input = slotEl.querySelector('.woe-member-input');
    if (input) {
        input.value = memberName;
    }
    const nameSpan = slotEl.querySelector('.woe-member-name');
    if (nameSpan) {
        nameSpan.textContent = memberName;
        nameSpan.classList.add('has-value');
        nameSpan.classList.remove('is-placeholder');
    }

    // Resolve icon URL (from parameter or global resolver)
    const resolvedIcon = iconUrl || window.getJobIconUrl(classJob);

    // Update job icon
    const iconWrap = slotEl.querySelector('.woe-slot-icon-wrap');
    let iconImg = slotEl.querySelector('.woe-slot-job-icon');
    if (resolvedIcon) {
        if (!iconImg && iconWrap) {
            iconImg = document.createElement('img');
            iconImg.className = 'woe-slot-job-icon';
            iconWrap.appendChild(iconImg);
        }
        if (iconImg) {
            iconImg.src = resolvedIcon;
            iconImg.alt = classJob || '';
            iconImg.title = classJob || '';
        }
        if (iconWrap) iconWrap.classList.remove('is-hidden');
    } else if (iconWrap) {
        iconWrap.classList.add('is-hidden');
    }

    // Update faint character illustration watermark in party slot
    const charBg = slotEl.querySelector('.woe-slot-character-bg');
    const resolvedChar = characterUrl || window.getCharacterImageUrl(classJob);
    if (charBg) {
        if (resolvedChar) {
            charBg.style.backgroundImage = `url('${resolvedChar}')`;
            charBg.style.display = 'block';
        } else {
            charBg.style.display = 'none';
        }
    }

    // Add clear button if missing
    let clearBtn = slotEl.querySelector('.woe-slot-clear-btn');
    if (!clearBtn) {
        clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'woe-slot-clear-btn';
        clearBtn.innerHTML = '&times;';
        clearBtn.title = `ปลด ${memberName} ออกจากปาร์ตี้`;
        clearBtn.onclick = function(e) { window.clearWoeSlot(slotId, e); };
        slotEl.appendChild(clearBtn);
    } else {
        clearBtn.title = `ปลด ${memberName} ออกจากปาร์ตี้`;
    }

    // 2. Update Party counter
    updatePartyCounter(room, party);

    // 3. Update Roster status badges
    if (prevMemberName && prevMemberName !== memberName) {
        updateRosterMemberStatus(prevMemberName, null);
    }
    updateRosterMemberStatus(memberName, { room, party });
    updateRosterSummaryCounts();

    // 4. Save to API
    const routes = window.APP_CONFIG?.routes ?? {};
    if (!routes.woeUpdate) return;

    fetch(`${routes.woeUpdate}/${slotId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': window.APP_CONFIG.csrfToken
        },
        body: JSON.stringify({
            member_name: memberName,
            class_job: classJob || null
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(`✓ จัด ${memberName} ลงห้อง ${room} ปาร์ตี้ ${party} แล้ว`, 'success');
            if (data.job_icon_url && iconImg) {
                iconImg.src = data.job_icon_url;
                if (iconWrap) iconWrap.classList.remove('is-hidden');
            }
        }
    })
    .catch(() => {
        showToast('เกิดข้อผิดพลาดในการจัดตี้', 'error');
    });
}

/**
 * Perform clearing of a slot
 */
function performClearSlot(slotEl) {
    if (!slotEl) return;
    const slotId = slotEl.dataset.slotId;
    const room = slotEl.dataset.room;
    const party = slotEl.dataset.party;
    const oldName = slotEl.dataset.memberName;

    // Reset slot element
    slotEl.classList.remove('is-filled');
    slotEl.classList.add('is-empty');
    slotEl.dataset.memberName = '';
    slotEl.dataset.classJob = '';
    slotEl.removeAttribute('draggable');

    const input = slotEl.querySelector('.woe-member-input');
    if (input) input.value = '';

    const iconWrap = slotEl.querySelector('.woe-slot-icon-wrap');
    if (iconWrap) iconWrap.classList.add('is-hidden');

    const charBg = slotEl.querySelector('.woe-slot-character-bg');
    if (charBg) {
        charBg.style.display = 'none';
        charBg.style.backgroundImage = 'none';
    }

    const clearBtn = slotEl.querySelector('.woe-slot-clear-btn');
    if (clearBtn) clearBtn.remove();

    // Update counter
    updatePartyCounter(room, party);

    // Update roster status
    if (oldName) {
        updateRosterMemberStatus(oldName, null);
        updateRosterSummaryCounts();
    }

    // Call API
    const routes = window.APP_CONFIG?.routes ?? {};
    if (!routes.woeUpdate) return;

    fetch(`${routes.woeUpdate}/${slotId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': window.APP_CONFIG.csrfToken
        },
        body: JSON.stringify({
            member_name: '',
            class_job: ''
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('ปลดสมาชิกออกจากปาร์ตี้แล้ว', 'success');
        }
    });
}

/**
 * Global clear function called from button
 */
window.clearWoeSlot = function(slotId, event) {
    if (event) event.stopPropagation();
    const slotEl = document.getElementById(`woeSlot_${slotId}`) || document.querySelector(`[data-slot-id="${slotId}"]`);
    if (slotEl) {
        performClearSlot(slotEl);
    }
};

/**
 * Recalculate filled count for a party (e.g. "3/5")
 */
function updatePartyCounter(room, party) {
    const partyBadge = document.getElementById(`partyFilled_${room}_${party}`);
    if (!partyBadge) return;

    const filledCount = document.querySelectorAll(
        `.woe-slot[data-room="${room}"][data-party="${party}"].is-filled`
    ).length;

    partyBadge.textContent = `${filledCount}/5`;
}

/**
 * Update member status pill in the right roster sidebar
 */
function updateRosterMemberStatus(memberName, partyInfo) {
    if (!memberName) return;
    const lower = memberName.trim().toLowerCase();
    const row = document.querySelector(`.roster-member-row[data-lower-name="${lower}"]`);
    if (!row) return;

    const pill = row.querySelector('.roster-status-pill');
    if (!pill) return;

    if (partyInfo) {
        row.classList.remove('is-unassigned');
        row.classList.add('is-assigned');
        row.dataset.status = 'assigned';

        pill.classList.remove('is-free');
        pill.classList.add('is-in-party');
        pill.textContent = `ห้อง ${partyInfo.room} ตี้ ${partyInfo.party}`;
    } else {
        row.classList.remove('is-assigned');
        row.classList.add('is-unassigned');
        row.dataset.status = 'unassigned';

        pill.classList.remove('is-in-party');
        pill.classList.add('is-free');
        pill.textContent = 'ว่าง';
    }
}

/**
 * Update stats numbers in roster header
 */
function updateRosterSummaryCounts() {
    const totalEl = document.getElementById('rosterTotalCount');
    const assignedEl = document.getElementById('rosterAssignedCount');
    const unassignedEl = document.getElementById('rosterUnassignedCount');

    const total = document.querySelectorAll('.roster-member-row').length;
    const assigned = document.querySelectorAll('.roster-member-row.is-assigned').length;
    const unassigned = Math.max(0, total - assigned);

    if (totalEl) totalEl.textContent = total;
    if (assignedEl) assignedEl.textContent = assigned;
    if (unassignedEl) unassignedEl.textContent = unassigned;

    // Update filter tab button badges
    const allTab = document.querySelector('.roster-tab-btn[data-filter="all"]');
    const unassignedTab = document.querySelector('.roster-tab-btn[data-filter="unassigned"]');
    const assignedTab = document.querySelector('.roster-tab-btn[data-filter="assigned"]');

    if (allTab) allTab.textContent = `ทั้งหมด (${total})`;
    if (unassignedTab) unassignedTab.textContent = `ว่าง (${unassigned})`;
    if (assignedTab) assignedTab.textContent = `ในตี้ (${assigned})`;
}

/**
 * Handle manual input inside slot
 */
function syncSlotAfterManualInput(slotEl, val) {
    if (!slotEl) return;
    const trimmed = (val || '').trim();
    const room = slotEl.dataset.room;
    const party = slotEl.dataset.party;

    if (trimmed) {
        slotEl.classList.remove('is-empty');
        slotEl.classList.add('is-filled');
        slotEl.dataset.memberName = trimmed;
        slotEl.setAttribute('draggable', 'true');
        updateRosterMemberStatus(trimmed, { room, party });
    } else {
        const oldName = slotEl.dataset.memberName;
        slotEl.classList.remove('is-filled');
        slotEl.classList.add('is-empty');
        slotEl.dataset.memberName = '';
        slotEl.removeAttribute('draggable');
        if (oldName) updateRosterMemberStatus(oldName, null);
    }

    updatePartyCounter(room, party);
    updateRosterSummaryCounts();
}

/**
 * Initialize search input and filter tabs for right roster sidebar
 */
function initRosterControls() {
    const searchInput = document.getElementById('rosterSearchInput');
    const clearBtn = document.getElementById('rosterSearchClear');
    const filterTabs = document.querySelectorAll('.roster-tab-btn');
    const rows = document.querySelectorAll('.roster-member-row');

    if (!searchInput && filterTabs.length === 0) return;

    let activeFilter = 'all';
    let searchQuery = '';

    function applyFilter() {
        const query = searchQuery.trim().toLowerCase();

        rows.forEach(row => {
            const name = (row.dataset.name || '').toLowerCase();
            const job = (row.dataset.job || '').toLowerCase();
            const status = row.dataset.status || 'unassigned';

            // Filter match
            let matchesFilter = true;
            if (activeFilter === 'unassigned') matchesFilter = (status === 'unassigned');
            else if (activeFilter === 'assigned') matchesFilter = (status === 'assigned');

            // Search match
            let matchesSearch = true;
            if (query) {
                matchesSearch = name.includes(query) || job.includes(query);
            }

            if (matchesFilter && matchesSearch) {
                row.style.display = 'flex';
            } else {
                row.style.display = 'none';
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            searchQuery = this.value;
            if (clearBtn) {
                clearBtn.style.display = searchQuery ? 'block' : 'none';
            }
            applyFilter();
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            if (searchInput) {
                searchInput.value = '';
                searchQuery = '';
                this.style.display = 'none';
                searchInput.focus();
                applyFilter();
            }
        });
    }

    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            filterTabs.forEach(t => t.classList.remove('is-active'));
            this.classList.add('is-active');
            activeFilter = this.dataset.filter || 'all';
            applyFilter();
        });
    });
}

/* ================= Custom Card & Feather Table Helpers ================= */
window.openNativeDatePicker = function(itemId) {
    const picker = document.getElementById(`native_date_${itemId}`);
    if (picker) {
        if (picker.showPicker) {
            picker.showPicker();
        } else {
            picker.click();
        }
    }
};

window.syncDateToText = function(picker, itemId) {
    if (!picker.value) return;
    const parts = picker.value.split('-');
    if (parts.length === 3) {
        const thaiYear = String(parseInt(parts[0], 10) + 543).slice(-2);
        const formatted = `${parts[2]}/${parts[1]}/${thaiYear}`;
        window.syncAllDates(formatted);
    }
};

window.syncAllDates = function(formattedDate) {
    if (!formattedDate) return;

    // 1. Update all date text inputs and view badges on current screen immediately
    document.querySelectorAll('.item-date-input').forEach(input => {
        input.value = formattedDate;
    });
    document.querySelectorAll('.date-badge').forEach(badge => {
        badge.textContent = formattedDate;
    });

    setSaveStatus('saving');

    // 2. Call backend sync-date endpoint to update database for ALL items
    fetch(window.APP_CONFIG.routes.syncDate, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': window.APP_CONFIG.csrfToken
        },
        body: JSON.stringify({ item_date: formattedDate })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            setSaveStatus('saved');
            showToast(`ปรับวันที่ทุกช่องเป็น "${formattedDate}" เรียบร้อยแล้ว ✓`, 'success');
        } else {
            setSaveStatus('error');
            showToast(data.message || 'เกิดข้อผิดพลาดในการบันทึกวันที่', 'error');
        }
    })
    .catch(err => {
        console.error('syncDate error:', err);
        setSaveStatus('error');
        showToast('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error');
    });
};

/* ================= Interactive Central Overview Summary Modal ================= */
window.AUCTION_OVERVIEW_DATA = null;
window.CURRENT_OVERVIEW_TAB = 'all';
window.CURRENT_OVERVIEW_SEARCH = '';

window.openAuctionOverviewModal = function() {
    openModal('auctionOverviewModal');
    const tbody = document.getElementById('overviewTableBody');
    if (tbody) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted"><span class="spinner-icon">⏳</span> กำลังโหลดข้อมูลภาพรวม...</td></tr>`;
    }

    fetch(window.APP_CONFIG.routes.overview, {
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.AUCTION_OVERVIEW_DATA = data;
            
            // Update Stats Badges
            const cardCat = data.categories.find(c => c.slug === 'card');
            const whiteCat = data.categories.find(c => c.slug === 'white_feather');
            const redCat = data.categories.find(c => c.slug === 'red_black_feather');

            const cardStatEl = document.getElementById('ovStatCard');
            if (cardStatEl && cardCat) cardStatEl.textContent = `${cardCat.filled} / ${cardCat.target} ใบ`;

            const whiteStatEl = document.getElementById('ovStatWhite');
            if (whiteStatEl && whiteCat) whiteStatEl.textContent = `${whiteCat.filled} / ${whiteCat.target} หน้า`;

            const redStatEl = document.getElementById('ovStatRed');
            if (redStatEl && redCat) redStatEl.textContent = `${redCat.filled} / ${redCat.target} หน้า`;

            const totalStatEl = document.getElementById('ovStatTotal');
            if (totalStatEl) totalStatEl.textContent = `${data.total_filled} รายการ (${data.winners.length} รางวัล)`;

            window.renderOverviewTable(window.CURRENT_OVERVIEW_TAB, window.CURRENT_OVERVIEW_SEARCH);
            window.renderMemberSummary();
        } else {
            if (tbody) tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">ไม่สามารถโหลดข้อมูลได้</td></tr>`;
        }
    })
    .catch(err => {
        console.error('Overview load error:', err);
        if (tbody) tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>`;
    });
};

window.filterOverviewTab = function(tabKey) {
    window.CURRENT_OVERVIEW_TAB = tabKey;
    document.querySelectorAll('.ov-tab-btn').forEach(btn => {
        btn.classList.toggle('is-active', btn.getAttribute('data-tab') === tabKey);
    });

    const tableEl = document.getElementById('overviewDataTable');
    const memberSummaryEl = document.getElementById('overviewMemberSummaryView');

    if (tabKey === 'by_member') {
        if (tableEl) tableEl.classList.add('hidden');
        if (memberSummaryEl) memberSummaryEl.classList.remove('hidden');
        window.renderMemberSummary();
    } else {
        if (tableEl) tableEl.classList.remove('hidden');
        if (memberSummaryEl) memberSummaryEl.classList.add('hidden');
        window.renderOverviewTable(tabKey, window.CURRENT_OVERVIEW_SEARCH);
    }
};

window.filterOverviewSearch = function(query) {
    window.CURRENT_OVERVIEW_SEARCH = (query || '').toLowerCase().trim();
    if (window.CURRENT_OVERVIEW_TAB === 'by_member') {
        window.renderMemberSummary();
    } else {
        window.renderOverviewTable(window.CURRENT_OVERVIEW_TAB, window.CURRENT_OVERVIEW_SEARCH);
    }
};

window.renderOverviewTable = function(categoryFilter, searchQuery) {
    const data = window.AUCTION_OVERVIEW_DATA;
    const tbody = document.getElementById('overviewTableBody');
    const footerInfo = document.getElementById('overviewFooterInfo');
    if (!data || !tbody) return;

    let itemsToDisplay = [];

    data.categories.forEach(cat => {
        if (categoryFilter !== 'all' && cat.slug !== categoryFilter) {
            return;
        }
        cat.items.forEach(item => {
            if (item.is_filled) {
                itemsToDisplay.push(item);
            }
        });
    });

    if (searchQuery) {
        itemsToDisplay = itemsToDisplay.filter(item => 
            (item.name && item.name.toLowerCase().includes(searchQuery)) ||
            (item.notes && item.notes.toLowerCase().includes(searchQuery)) ||
            (item.category_name && item.category_name.toLowerCase().includes(searchQuery))
        );
    }

    if (footerInfo) {
        footerInfo.innerHTML = `แสดงทั้งหมด <strong>${itemsToDisplay.length}</strong> รายการที่ได้รับของประมูล (${data.outcome.label})`;
    }

    if (itemsToDisplay.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-5 text-muted">
                    <div style="font-size: 1.8rem; margin-bottom: 8px;">📭</div>
                    <div>ยังไม่มีรายชื่อผู้ได้รับของประมูลในหมวดนี้</div>
                </td>
            </tr>
        `;
        return;
    }

    let rowsHtml = '';
    itemsToDisplay.forEach((item, idx) => {
        let catBadgeClass = 'badge-cat-card';
        let catImg = '/images/card.png';
        if (item.category_slug === 'white_feather') {
            catBadgeClass = 'badge-cat-white';
            catImg = '/images/white_feather.png';
        } else if (item.category_slug === 'red_black_feather') {
            catBadgeClass = 'badge-cat-red';
            catImg = '/images/red_black_feather.png';
        }

        const isCycle = (item.notes && (item.notes.includes('CYCLE') || item.notes.includes('cycle')));

        rowsHtml += `
            <tr class="ov-item-row ${isCycle ? 'row-cycle' : ''}">
                <td class="ov-col-num text-center">${idx + 1}</td>
                <td class="ov-col-cat">
                    <span class="ov-category-badge ${catBadgeClass}">
                        <img src="${catImg}" class="ov-cat-mini-img" alt="${item.category_name}"> ${item.category_name}
                    </span>
                </td>
                <td class="ov-col-page text-center">
                    <span class="feather-page-pill">${item.page_label}</span>
                </td>
                <td class="ov-col-name">
                    <div class="ov-member-winner">
                        <span class="ov-winner-icon">🏆</span>
                        <strong class="ov-winner-name">${escapeHtml(item.name)}</strong>
                    </div>
                </td>
                <td class="ov-col-date text-center">
                    <span class="date-badge">${escapeHtml(item.item_date)}</span>
                </td>
                <td class="ov-col-notes">
                    ${item.notes ? `<span class="badge-custom-note">${escapeHtml(item.notes)}</span>` : '<span class="text-muted">-</span>'}
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = rowsHtml;
};

window.renderMemberSummary = function() {
    const data = window.AUCTION_OVERVIEW_DATA;
    const grid = document.getElementById('memberSummaryGrid');
    const footerInfo = document.getElementById('overviewFooterInfo');
    if (!data || !grid) return;

    let members = data.member_summary || [];
    const search = window.CURRENT_OVERVIEW_SEARCH;
    if (search) {
        members = members.filter(m => m.name.toLowerCase().includes(search));
    }

    if (footerInfo) {
        footerInfo.innerHTML = `สรุปตามรายชื่อสมาชิกทั้งหมด <strong>${members.length}</strong> คน`;
    }

    if (members.length === 0) {
        grid.innerHTML = `
            <div class="text-center py-5 text-muted" style="grid-column: 1 / -1;">
                <div style="font-size: 1.8rem; margin-bottom: 8px;">👥</div>
                <div>ไม่พบรายชื่อสมาชิกที่ได้ของประมูล</div>
            </div>
        `;
        return;
    }

    let cardsHtml = '';
    members.forEach(m => {
        let itemsBadges = '';
        m.items.forEach(it => {
            let catImg = '/images/card.png';
            if (it.category_slug === 'white_feather') catImg = '/images/white_feather.png';
            if (it.category_slug === 'red_black_feather') catImg = '/images/red_black_feather.png';
            itemsBadges += `
                <div class="member-item-tag">
                    <span class="member-item-cat-title"><img src="${catImg}" class="ov-cat-mini-img" alt="${it.category}"> ${it.category}: <strong>${it.page_label}</strong> (${it.date})</span>
                    ${it.notes ? `<small class="member-item-subnote">${escapeHtml(it.notes)}</small>` : ''}
                </div>
            `;
        });

        cardsHtml += `
            <div class="member-summary-card">
                <div class="member-card-header">
                    <div class="member-name-wrap">
                        <span class="member-avatar-circle">👤</span>
                        <span class="member-name-title">${escapeHtml(m.name)}</span>
                    </div>
                    <span class="member-win-count-badge">ได้ ${m.count} รายการ</span>
                </div>
                <div class="member-card-body">
                    <div class="member-items-stack">
                        ${itemsBadges}
                    </div>
                </div>
            </div>
        `;
    });

    grid.innerHTML = cardsHtml;
};

window.openTextSummaryFromOverview = function() {
    closeModal('auctionOverviewModal');
    openSummaryModal(window.APP_CONFIG.activeCategorySlug || 'card');
};

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
