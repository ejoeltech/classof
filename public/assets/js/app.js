/* Classof — interactions */
(function () {
    'use strict';

    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

    /* Sidebar --------------------------------------------------------- */
    const sidebar = $('#sidebar');
    const overlay = $('.sidebar-overlay');

    const openSidebar = () => {
        if (!sidebar) return;
        sidebar.classList.add('is-open');
        if (overlay) overlay.classList.add('is-visible');
        document.body.style.overflow = 'hidden';
    };

    const closeSidebar = () => {
        if (!sidebar) return;
        sidebar.classList.remove('is-open');
        if (overlay) overlay.classList.remove('is-visible');
        document.body.style.overflow = '';
    };

    $$('[data-sidebar-open]').forEach((button) => button.addEventListener('click', openSidebar));
    $$('[data-sidebar-close]').forEach((button) => button.addEventListener('click', closeSidebar));

    /* User dropdown --------------------------------------------------- */
    const userMenu = $('[data-user-menu]');
    const userToggle = $('[data-user-toggle]');
    const userDropdown = $('[data-user-dropdown]');

    if (userMenu && userToggle && userDropdown) {
        userToggle.addEventListener('click', (event) => {
            event.stopPropagation();
            const open = userDropdown.classList.toggle('is-open');
            userToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        document.addEventListener('click', (event) => {
            if (!userMenu.contains(event.target)) {
                userDropdown.classList.remove('is-open');
                userToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    /* Flash messages -------------------------------------------------- */
    $$('[data-dismiss]').forEach((button) => {
        button.addEventListener('click', () => {
            const alert = button.closest('[data-flash]');
            if (alert) {
                alert.style.transition = 'opacity .2s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 200);
            }
        });
    });

    $$('[data-flash].alert-success').forEach((alert) => {
        setTimeout(() => {
            if (!alert.isConnected) return;
            alert.style.transition = 'opacity .3s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 320);
        }, 9000);
    });

    /* Modals ---------------------------------------------------------- */
    const openModal = (id) => {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        const focusable = modal.querySelector('input, select, textarea, button');
        if (focusable) setTimeout(() => focusable.focus(), 60);
    };

    const closeModal = (modal) => {
        if (!modal) return;
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
    };

    $$('[data-modal-open]').forEach((trigger) => {
        trigger.addEventListener('click', () => openModal(trigger.getAttribute('data-modal-open')));
    });

    $$('[data-modal-close]').forEach((trigger) => {
        trigger.addEventListener('click', () => closeModal(trigger.closest('.modal')));
    });

    $$('.modal').forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal || event.target.classList.contains('modal-backdrop')) {
                closeModal(modal);
            }
        });
    });

    /* Copy to clipboard ----------------------------------------------- */
    $$('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const value = button.getAttribute('data-copy');
            try {
                await navigator.clipboard.writeText(value);
                const original = button.textContent;
                button.textContent = 'Copied';
                setTimeout(() => { button.textContent = original; }, 1800);
            } catch (error) {
                const input = button.parentElement && button.parentElement.querySelector('input');
                if (input) {
                    input.select();
                    document.execCommand('copy');
                }
            }
        });
    });

    /* Select input on click ------------------------------------------- */
    $$('[data-select]').forEach((input) => {
        input.addEventListener('focus', () => input.select());
    });

    /* Confirm destructive actions ------------------------------------- */
    $$('[data-confirm]').forEach((element) => {
        element.addEventListener('click', (event) => {
            if (!window.confirm(element.getAttribute('data-confirm'))) {
                event.preventDefault();
                event.stopPropagation();
            }
        });
    });

    $$('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                event.preventDefault();
            }
        });
    });

    /* Scope-aware group fields ---------------------------------------- */
    const scopeSelect = document.querySelector('[data-scope-select]');

    if (scopeSelect) {
        const nameInput = document.querySelector('[data-group-name]');

        const syncScope = () => {
            const scope = scopeSelect.value;

            document.querySelectorAll('[data-scope-for]').forEach((field) => {
                const visible = field.getAttribute('data-scope-for').split(/\s+/).includes(scope);
                field.hidden = !visible;

                field.querySelectorAll('[data-required-scope]').forEach((input) => {
                    input.required = visible && input.getAttribute('data-required-scope') === scope;
                });
            });

            if (nameInput) {
                const placeholder = nameInput.getAttribute('data-name-placeholder-' + scope);
                if (placeholder) nameInput.placeholder = placeholder;
            }
        };

        scopeSelect.addEventListener('change', syncScope);
        syncScope();
    }

    /* Dependent catalogue selects ------------------------------------- */
    const institutionSelect = document.querySelector('[data-institution-select]');

    if (institutionSelect) {
        const facultySelect = document.querySelector('[data-faculty-select]');
        const departmentSelect = document.querySelector('[data-department-select]');
        const facultyUrl = institutionSelect.getAttribute('data-faculty-url');
        const departmentUrl = facultySelect ? facultySelect.getAttribute('data-department-url') : null;

        const clearSelect = (select, placeholder) => {
            if (!select) return;
            select.innerHTML = '<option value="">' + placeholder + '</option>';
            select.disabled = true;
        };

        const populate = (select, rows, placeholder) => {
            if (!select) return;
            const selected = select.getAttribute('data-selected') || select.value;
            const options = ['<option value="">' + placeholder + '</option>'];

            rows.forEach((row) => {
                options.push('<option value="' + row.id + '">' + row.name.replace(/[&<>"]/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;'}[c])) + '</option>');
            });

            select.innerHTML = options.join('');
            select.disabled = false;

            if (selected && Array.from(select.options).some((option) => option.value === String(selected))) {
                select.value = String(selected);
            }
        };

        const loadDepartments = async (selected = '') => {
            if (!facultySelect || !departmentSelect || !departmentUrl) return;

            const facultyId = facultySelect.value;
            departmentSelect.setAttribute('data-selected', selected || '');
            clearSelect(departmentSelect, departmentSelect.getAttribute('data-placeholder') || 'Choose department…');

            if (!facultyId) return;

            try {
                const response = await fetch(departmentUrl + '?faculty_id=' + encodeURIComponent(facultyId), { credentials: 'same-origin' });
                if (response.ok) populate(departmentSelect, await response.json(), departmentSelect.getAttribute('data-placeholder') || 'Choose department…');
            } catch (error) {
                // Leave the select empty; server validation still protects the form.
            }
        };

        const loadFaculties = async (selected = '') => {
            if (!facultySelect || !facultyUrl) return;

            const institutionId = institutionSelect.value;
            facultySelect.setAttribute('data-selected', selected || '');
            clearSelect(facultySelect, facultySelect.getAttribute('data-placeholder') || 'Choose faculty…');
            clearSelect(departmentSelect, departmentSelect ? (departmentSelect.getAttribute('data-placeholder') || 'Choose department…') : '');

            if (!institutionId) return;

            try {
                const response = await fetch(facultyUrl + '?institution_id=' + encodeURIComponent(institutionId), { credentials: 'same-origin' });

                if (response.ok) {
                    populate(facultySelect, await response.json(), facultySelect.getAttribute('data-placeholder') || 'Choose faculty…');
                    await loadDepartments(departmentSelect ? (departmentSelect.getAttribute('data-selected') || '') : '');
                }
            } catch (error) {
                // Leave the select empty; server validation still protects the form.
            }
        };

        institutionSelect.addEventListener('change', () => {
            if (facultySelect) facultySelect.setAttribute('data-selected', '');
            if (departmentSelect) departmentSelect.setAttribute('data-selected', '');
            loadFaculties('');
        });

        if (facultySelect) {
            facultySelect.addEventListener('change', () => loadDepartments(''));
        }

        loadFaculties(facultySelect ? (facultySelect.getAttribute('data-selected') || '') : '');
    }

    /* Amount preset buttons ------------------------------------------- */
    $$('[data-amount]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = document.querySelector(button.getAttribute('data-amount-target') || '#amount');
            if (target) {
                target.value = button.getAttribute('data-amount');
                target.focus();
            }
        });
    });

    /* Keep the active group tab in view on narrow screens ------------ */
    const activeTab = document.querySelector('.tabs .tab.is-active');
    if (activeTab) {
        activeTab.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    }

    /* Close sidebar/dropdown on Escape -------------------------------- */
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        closeSidebar();
        if (userDropdown) userDropdown.classList.remove('is-open');
        $$('.modal.is-open').forEach((modal) => closeModal(modal));
    });

    /* Submitting state ------------------------------------------------- */
    $$('form[data-submit-once]').forEach((form) => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                button.dataset.original = button.textContent;
                button.textContent = 'Working…';
            }
        });
    });
})();
