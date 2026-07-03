/**
 * University-wide teacher autocomplete for curriculum chair selection.
 */
(function (window, $) {
    'use strict';

    const badgeClass = {
        chair: 'chair-ac-badge-chair',
        coordinator: 'chair-ac-badge-coordinator',
        member: 'chair-ac-badge-member',
    };

    const ChairTeacherAutocomplete = {
        container: null,
        input: null,
        hiddenEmail: null,
        list: null,
        statusEl: null,
        debounceTimer: null,
        activeIndex: -1,
        results: [],
        selected: null,
        curriculumId: null,
        onSelectionChange: null,

        init(containerSelector, options) {
            this.container = $(containerSelector);
            if (!this.container.length) {
                return;
            }

            this.onSelectionChange = typeof options?.onSelectionChange === 'function'
                ? options.onSelectionChange
                : null;

            this.container.empty().append(`
                <input type="hidden" id="chairSelectedEmail" value="">
                <label for="chairTeacherSearch" class="sr-only">ค้นหาอาจารย์</label>
                <input type="text" id="chairTeacherSearch" autocomplete="off"
                    class="chair-ac-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    placeholder="พิมพ์ชื่อหรืออีเมลอาจารย์..." aria-autocomplete="list" aria-controls="chairTeacherList" aria-expanded="false">
                <div id="chairTeacherList" class="chair-ac-list hidden" role="listbox" aria-label="ผลการค้นหาอาจารย์"></div>
                <p id="chairTeacherStatus" class="text-xs text-gray-500 mt-1" aria-live="polite"></p>
            `);

            this.input = $('#chairTeacherSearch');
            this.hiddenEmail = $('#chairSelectedEmail');
            this.list = $('#chairTeacherList');
            this.statusEl = $('#chairTeacherStatus');

            this.bindEvents();
        },

        bindEvents() {
            const self = this;

            this.input.on('input', function () {
                clearTimeout(self.debounceTimer);
                self.hiddenEmail.val('');
                self.selected = null;
                self.notifySelectionChange(null);

                const q = $(this).val().trim();
                if (q.length < 2) {
                    self.hideList();
                    self.setStatus('พิมพ์อย่างน้อย 2 ตัวอักษรเพื่อค้นหา');
                    return;
                }

                self.debounceTimer = setTimeout(function () {
                    self.search(q);
                }, 300);
            });

            this.input.on('keydown', function (e) {
                if (self.list.hasClass('hidden') || self.results.length === 0) {
                    if (e.key === 'Escape') {
                        self.hideList();
                    }
                    return;
                }

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    self.moveActive(1);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    self.moveActive(-1);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (self.activeIndex >= 0 && self.results[self.activeIndex]) {
                        self.selectResult(self.results[self.activeIndex]);
                    }
                } else if (e.key === 'Escape') {
                    self.hideList();
                }
            });

            this.list.on('mousedown', '.chair-ac-option', function (e) {
                e.preventDefault();
                const index = parseInt($(this).attr('data-index'), 10);
                if (!Number.isNaN(index) && self.results[index]) {
                    self.selectResult(self.results[index]);
                }
            });

            $(document).on('click.chairAc', function (e) {
                if (!self.container.is(e.target) && self.container.has(e.target).length === 0) {
                    self.hideList();
                }
            });
        },

        setCurriculumId(curriculumId) {
            this.curriculumId = curriculumId;
        },

        setStatus(message) {
            this.statusEl.text(message || '');
        },

        search(query) {
            const self = this;
            if (!this.curriculumId) {
                return;
            }

            this.setStatus('กำลังค้นหา...');

            $.ajax({
                url: appRoute('admin/searchTeachersForChairSelection'),
                method: 'POST',
                dataType: 'json',
                data: {
                    q: query,
                    curriculum_id: this.curriculumId,
                },
                success: function (response) {
                    if (!response.success) {
                        self.setStatus(response.message || 'ค้นหาไม่สำเร็จ');
                        self.renderResults([]);
                        return;
                    }

                    self.renderResults(response.data || []);
                    const count = (response.data || []).length;
                    self.setStatus(count > 0
                        ? 'พบ ' + count + ' รายการ — ใช้ลูกศรเลือก แล้วกด Enter'
                        : 'ไม่พบอาจารย์ที่ตรงกับคำค้นหา');
                },
                error: function () {
                    self.setStatus('ไม่สามารถค้นหาได้ กรุณาลองใหม่');
                    self.renderResults([]);
                },
            });
        },

        loadByEmail(email) {
            const self = this;
            if (!email || !this.curriculumId) {
                this.clear();
                return;
            }

            $.ajax({
                url: appRoute('admin/searchTeachersForChairSelection'),
                method: 'POST',
                dataType: 'json',
                data: {
                    email: email,
                    curriculum_id: this.curriculumId,
                },
                success: function (response) {
                    if (response.success && response.data && response.data[0]) {
                        self.selectResult(response.data[0], false);
                    } else {
                        self.input.val(email);
                        self.hiddenEmail.val(email);
                        self.selected = { email: email, name: email, has_conflict: false, warnings: [] };
                        self.notifySelectionChange(self.selected);
                    }
                },
            });
        },

        renderResults(items) {
            this.results = items;
            this.activeIndex = items.length > 0 ? 0 : -1;
            this.list.empty();

            if (items.length === 0) {
                this.hideList();
                return;
            }

            items.forEach(function (item, index) {
                const badges = (item.role_badges || []).map(function (badge) {
                    const cls = badgeClass[badge.type] || badgeClass.member;
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ' + cls + ' mr-1 mb-1">' + ChairTeacherAutocomplete.escapeHtml(badge.label) + '</span>';
                }).join('');

                const conflictMark = item.has_conflict
                    ? '<span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700 ml-1"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path></svg>มีตำแหน่งอื่น</span>'
                    : '';

                const option = $('<button type="button" class="chair-ac-option w-full text-left px-3 py-2 border-b border-gray-100 last:border-b-0"></button>');
                option.attr({
                    'data-index': index,
                    role: 'option',
                    id: 'chair-ac-option-' + index,
                });
                option.html(
                    '<div class="font-medium text-gray-900">' + ChairTeacherAutocomplete.escapeHtml(item.name) + conflictMark + '</div>' +
                    '<div class="text-xs text-gray-500">' + ChairTeacherAutocomplete.escapeHtml(item.email) + '</div>' +
                    '<div class="mt-1 flex flex-wrap gap-1">' + badges + '</div>'
                );

                if (index === 0) {
                    option.addClass('is-active');
                }

                ChairTeacherAutocomplete.list.append(option);
            });

            this.list.removeClass('hidden');
            this.input.attr('aria-expanded', 'true');
        },

        moveActive(delta) {
            if (this.results.length === 0) {
                return;
            }

            this.activeIndex = (this.activeIndex + delta + this.results.length) % this.results.length;
            this.list.find('.chair-ac-option').removeClass('is-active');
            const active = this.list.find('.chair-ac-option[data-index="' + this.activeIndex + '"]');
            active.addClass('is-active');
            this.input.attr('aria-activedescendant', 'chair-ac-option-' + this.activeIndex);
        },

        selectResult(item, hideList) {
            if (hideList !== false) {
                this.hideList();
            }

            this.selected = item;
            this.hiddenEmail.val(item.email || '');
            this.input.val(item.name || item.email || '');
            this.setStatus('เลือก: ' + (item.name || item.email));
            this.notifySelectionChange(item);
        },

        notifySelectionChange(item) {
            if (this.onSelectionChange) {
                this.onSelectionChange(item);
            }
        },

        hideList() {
            this.list.addClass('hidden').empty();
            this.results = [];
            this.activeIndex = -1;
            this.input.attr({ 'aria-expanded': 'false', 'aria-activedescendant': '' });
        },

        getSelectedEmail() {
            return (this.hiddenEmail.val() || '').trim();
        },

        getSelected() {
            return this.selected;
        },

        clear() {
            this.input.val('');
            this.hiddenEmail.val('');
            this.selected = null;
            this.hideList();
            this.setStatus('');
            this.notifySelectionChange(null);
        },

        escapeHtml(text) {
            if (!text) {
                return '';
            }
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        },
    };

    window.ChairTeacherAutocomplete = ChairTeacherAutocomplete;
})(window, jQuery);
