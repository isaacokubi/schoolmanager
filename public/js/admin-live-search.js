(() => {
    'use strict';

    const MIN_QUERY_LENGTH = 1;
    const DEBOUNCE_MS = 180;
    const MAX_RESULTS = 8;
    let activeController = null;
    let debounceTimer = null;

    const scopeFromLocation = () => {
        const path = window.location.pathname.replace(/\/+$/, '');
        const params = new URLSearchParams(window.location.search);

        if (/\/admin\/students$/.test(path)) return 'students';
        if (/\/admin\/admissions$/.test(path)) return 'admissions';
        if (/\/admin\/payments$/.test(path)) return 'payments';
        if (/\/admin\/operations$/.test(path)) return params.get('section') || 'parents';
        return '';
    };

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const createPanel = (input) => {
        const wrapper = input.closest('.filter-search, .search, .form-group, .field, .filter-field') || input.parentElement;
        if (!wrapper) return null;

        wrapper.classList.add('admin-live-search-wrap');
        const panel = document.createElement('div');
        panel.className = 'admin-live-search-results';
        panel.setAttribute('role', 'listbox');
        panel.setAttribute('aria-label', 'Live search results');
        panel.hidden = true;
        wrapper.appendChild(panel);

        const listId = `admin-live-search-${Math.random().toString(36).slice(2)}`;
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-controls', listId);
        panel.id = listId;

        return panel;
    };

    const hidePanel = (panel) => {
        if (!panel) return;
        panel.hidden = true;
        panel.innerHTML = '';
    };

    const showState = (panel, message, type = '') => {
        if (!panel) return;
        panel.innerHTML = `<div class="admin-live-search-state ${type}" role="status">${escapeHtml(message)}</div>`;
        panel.hidden = false;
    };

    const renderResults = (panel, results) => {
        if (!results.length) {
            showState(panel, 'No matching results.');
            return;
        }

        panel.innerHTML = results.slice(0, MAX_RESULTS).map((result, index) => `
            <a class="admin-live-search-result" href="${escapeHtml(result.url)}" role="option" data-index="${index}">
                <span class="admin-live-search-result-title">${escapeHtml(result.title)}</span>
                <span class="admin-live-search-result-subtitle">${escapeHtml(result.subtitle || '')}</span>
            </a>
        `).join('');
        panel.hidden = false;
    };

    const search = async (input, panel, scope) => {
        const query = input.value.trim();
        if (!query) {
            if (activeController) activeController.abort();
            hidePanel(panel);
            input.removeAttribute('aria-activedescendant');
            return;
        }

        if (!scope) return;
        if (query.length < MIN_QUERY_LENGTH) return;

        if (activeController) activeController.abort();
        activeController = new AbortController();
        showState(panel, 'Searching…', 'loading');

        const endpoint = window.schoolManagerAdminSearchUrl || '/admin/search';
        const url = new URL(endpoint, window.location.origin);
        url.searchParams.set('q', query);
        url.searchParams.set('scope', scope);

        try {
            const response = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                signal: activeController.signal,
            });

            if (!response.ok) throw new Error(`Search failed with status ${response.status}`);
            const payload = await response.json();
            renderResults(panel, Array.isArray(payload.data) ? payload.data : []);
        } catch (error) {
            if (error.name === 'AbortError') return;
            showState(panel, 'Search is temporarily unavailable.', 'error');
        }
    };

    const initInput = (input) => {
        if (input.dataset.adminLiveSearchReady === '1') return;
        input.dataset.adminLiveSearchReady = '1';

        const scope = input.dataset.liveSearchScope || scopeFromLocation();
        const panel = createPanel(input);
        if (!panel || !scope) return;

        input.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => search(input, panel, scope), DEBOUNCE_MS);
        });

        input.addEventListener('focus', () => {
            if (input.value.trim() && panel.innerHTML) panel.hidden = false;
        });

        input.addEventListener('keydown', (event) => {
            const items = [...panel.querySelectorAll('.admin-live-search-result')];
            if (!items.length || panel.hidden) {
                if (event.key === 'Escape') hidePanel(panel);
                return;
            }

            const current = items.findIndex((item) => item.classList.contains('is-active'));
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                const next = event.key === 'ArrowDown'
                    ? (current + 1) % items.length
                    : (current - 1 + items.length) % items.length;
                items.forEach((item) => item.classList.remove('is-active'));
                items[next].classList.add('is-active');
                items[next].scrollIntoView({ block: 'nearest' });
            } else if (event.key === 'Enter' && current >= 0) {
                event.preventDefault();
                items[current].click();
            } else if (event.key === 'Escape') {
                hidePanel(panel);
                input.focus();
            }
        });

        document.addEventListener('click', (event) => {
            if (!input.closest('.admin-live-search-wrap')?.contains(event.target)) hidePanel(panel);
        });
    };

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('input[name="search"], input[data-live-search]').forEach(initInput);
    });
})();
