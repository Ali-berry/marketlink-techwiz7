// Ctrl+K / Cmd+K palette - kholna / band karna Alpine (command-palette.blade.php) karta hai,
// ye file fetch, results aur arrow keys sambhalti hai
const paletteRoot = document.querySelector('[data-command-palette]');

if (paletteRoot) {
    const searchInput = paletteRoot.querySelector('[data-command-palette-input]');
    const resultsBox = paletteRoot.querySelector('[data-command-palette-results]');

    // aakhri key ke itni der baad search, taake har key pe request na jaye
    const SEARCH_DEBOUNCE_MS = 300;

    let debounceTimer = null;
    let currentResults = [];
    let activeResultIndex = -1;

    const TYPE_ICONS = {
        Product: 'tabler:apple',
        Farmer: 'tabler:tractor',
        Market: 'tabler:building-store',
    };

    function showHint(hintText) {
        resultsBox.innerHTML = '';
        const hint = document.createElement('p');
        hint.className = 'px-3 py-6 text-center text-sm text-soil-muted';
        hint.textContent = hintText;
        resultsBox.appendChild(hint);
    }

    function resultRowClasses(isActive) {
        return [
            'flex items-center gap-3 rounded-xl px-3 py-2.5 transition',
            isActive ? 'bg-leaf-50' : 'hover:bg-cream',
        ].join(' ');
    }

    function buildResultRow(result, index) {
        const link = document.createElement('a');
        link.href = result.url;
        link.className = resultRowClasses(index === activeResultIndex);

        const icon = document.createElement('span');
        icon.className = 'icon-chip h-9 w-9 shrink-0 bg-leaf-50 text-base text-leaf-700';
        icon.innerHTML = `<iconify-icon icon="${TYPE_ICONS[result.type] ?? 'tabler:search'}"></iconify-icon>`;

        const textBlock = document.createElement('span');
        textBlock.className = 'min-w-0 flex-1';
        textBlock.innerHTML = `
            <span class="block truncate text-sm font-medium text-soil"></span>
            <span class="block truncate text-xs text-soil-muted"></span>
        `;
        textBlock.querySelector('span:first-child').textContent = result.title;
        textBlock.querySelector('span:last-child').textContent = result.subtitle ?? '';

        const typeBadge = document.createElement('span');
        typeBadge.className = 'shrink-0 rounded-full bg-cream px-2.5 py-1 text-xs font-medium text-soil-muted';
        typeBadge.textContent = result.type;

        link.append(icon, textBlock, typeBadge);

        return link;
    }

    function renderResults(results) {
        currentResults = results;
        activeResultIndex = results.length ? 0 : -1;

        if (! results.length) {
            showHint('No matches found. Try a different word.');
            return;
        }

        resultsBox.innerHTML = '';
        results.forEach((result, index) => resultsBox.appendChild(buildResultRow(result, index)));
    }

    // arrow key pe sirf highlight classes badlo, poori list dobara banane ki zaroorat nahi
    function highlightActiveResult() {
        resultsBox.querySelectorAll('a').forEach((link, index) => {
            link.className = resultRowClasses(index === activeResultIndex);
        });
    }

    async function runSearch(searchTerm) {
        if (searchTerm.length < 2) {
            showHint('Keep typing - at least 2 letters needed.');
            return;
        }

        showHint('Searching...');

        try {
            const response = await fetch(`/search?q=${encodeURIComponent(searchTerm)}`, {
                headers: { Accept: 'application/json' },
            });
            const data = await response.json();
            renderResults(data.results ?? []);
        } catch (error) {
            showHint("Search failed - check your connection and try again.");
        }
    }

    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const searchTerm = searchInput.value.trim();
        debounceTimer = setTimeout(() => runSearch(searchTerm), SEARCH_DEBOUNCE_MS);
    });

    searchInput.addEventListener('keydown', (event) => {
        if (! currentResults.length) return;

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            activeResultIndex = (activeResultIndex + 1) % currentResults.length;
            highlightActiveResult();
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            activeResultIndex = (activeResultIndex - 1 + currentResults.length) % currentResults.length;
            highlightActiveResult();
        } else if (event.key === 'Enter' && activeResultIndex >= 0) {
            event.preventDefault();
            window.location.href = currentResults[activeResultIndex].url;
        }
    });

    // palette har baar khulne pe Alpine isay call karta hai - purani search saaf, stale results na dikhen
    paletteRoot.addEventListener('command-palette-opened', () => {
        clearTimeout(debounceTimer);
        searchInput.value = '';
        currentResults = [];
        activeResultIndex = -1;
        showHint('Start typing to search across MarketLink.');
        searchInput.focus();
    });
}
