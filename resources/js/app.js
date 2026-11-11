import './bootstrap';
import selectorCliente from './cliente-selector';

import Alpine from 'alpinejs';
import {
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    DoughnutController,
    Filler,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';

Chart.register(ArcElement, BarController, BarElement, CategoryScale, DoughnutController, Filler, Legend, LinearScale, LineController, LineElement, PointElement, Tooltip);

window.Alpine = Alpine;
Alpine.data('selectorCliente', selectorCliente);

Alpine.data('proveedorCombobox', (config) => ({
    activeIndex: -1,
    debounceTimer: null,
    loading: false,
    open: false,
    query: config.selectedName || '',
    requestId: 0,
    results: [],
    searchError: false,
    selectedId: config.selectedId ? String(config.selectedId) : '',
    invalidSelection: false,

    init() {
        const form = this.$root.closest('form');
        form?.addEventListener('submit', (event) => {
            if (!this.selectedId && this.query.trim()) {
                event.preventDefault();
                this.invalidSelection = true;
                this.open = true;
                this.$nextTick(() => this.$refs.searchInput.focus());
            }
        });

        if (config.restoreDraft) {
            this.$nextTick(() => this.restoreDraft());
        }
    },

    focus() {
        this.open = true;
        this.search();
    },

    input() {
        this.selectedId = '';
        this.activeIndex = -1;
        this.invalidSelection = false;
        this.results = [];
        this.requestId++;
        this.loading = false;
        this.searchError = false;
        this.open = true;
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => this.search(), 220);
    },

    async search() {
        const currentRequest = ++this.requestId;
        this.loading = true;
        this.searchError = false;

        try {
            const url = new URL(config.searchUrl, window.location.origin);
            url.searchParams.set('q', this.query.trim());
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                throw new Error('No se pudo buscar proveedores.');
            }

            const payload = await response.json();
            if (currentRequest === this.requestId) {
                this.results = Array.isArray(payload.data) ? payload.data : [];
            }
        } catch {
            if (currentRequest === this.requestId) {
                this.results = [];
                this.searchError = true;
            }
        } finally {
            if (currentRequest === this.requestId) {
                this.loading = false;
            }
        }
    },

    select(provider) {
        this.selectedId = String(provider.id);
        this.query = provider.nombre;
        this.invalidSelection = false;
        this.open = false;
    },

    clear() {
        this.selectedId = '';
        this.query = '';
        this.activeIndex = -1;
        this.invalidSelection = false;
        this.open = false;
    },

    move(step) {
        if (!this.open) {
            this.focus();
            return;
        }

        const total = this.results.length + 1;
        this.activeIndex = (this.activeIndex + step + total) % total;
    },

    chooseActive() {
        if (this.activeIndex < 0 && this.results.length === 1) {
            this.select(this.results[0]);
            return;
        }

        if (this.activeIndex === 0) {
            this.clear();
            return;
        }

        const provider = this.results[this.activeIndex - 1];
        if (provider) {
            this.select(provider);
        } else if (this.query.trim()) {
            this.invalidSelection = true;
        }
    },

    activeOptionId(baseId) {
        return this.activeIndex >= 0 ? `${baseId}-option-${this.activeIndex}` : null;
    },

    saveDraft(event) {
        const form = event.currentTarget.closest('form');
        if (!form || !config.draftKey) {
            return;
        }

        const fields = Array.from(form.elements)
            .filter((field) => field.name && field.name !== '_token' && field.name !== '_method' && field.name !== 'proveedor_id' && field.type !== 'file')
            .map((field) => ({
                checked: field.checked,
                name: field.name,
                type: field.type,
                value: field.value,
            }));

        try {
            sessionStorage.setItem(config.draftKey, JSON.stringify(fields));
        } catch {
            return;
        }
    },

    restoreDraft() {
        // Restaura borrador del ingrediente.
        const form = this.$root.closest('form');
        let raw = null;
        try {
            raw = config.draftKey ? sessionStorage.getItem(config.draftKey) : null;
        } catch {
            return;
        }
        if (!form || !raw) {
            return;
        }

        try {
            const fields = JSON.parse(raw);
            fields.forEach((saved) => {
                const name = window.CSS.escape(saved.name);
                const candidates = Array.from(form.querySelectorAll(`[name="${name}"]`));

                candidates.forEach((field) => {
                    if (field.type === 'radio' || field.type === 'checkbox') {
                        field.checked = saved.value === field.value ? saved.checked : field.checked;
                    } else if (candidates.length === 1 || field.value === saved.value) {
                        field.value = saved.value;
                    }

                    field.dispatchEvent(new Event('input', { bubbles: true }));
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        } finally {
            try {
                sessionStorage.removeItem(config.draftKey);
            } catch {
                return;
            }
        }
    },
}));

window.Laravel = {
    prefetch(url) {
        if (document.querySelector(`link[data-prefetch="${url}"]`)) {
            return;
        }

        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = url;
        link.dataset.prefetch = url;
        document.head.appendChild(link);
    }
};

const currency = { style: 'currency', currency: 'USD', currencyDisplay: 'narrowSymbol' };
const money = new Intl.NumberFormat('es-GT', currency);
const axisMoney = new Intl.NumberFormat('es-GT', { ...currency, minimumFractionDigits: 0 });
const quantity = new Intl.NumberFormat('es-GT', { maximumFractionDigits: 4 });
const percent = new Intl.NumberFormat('es-GT', { maximumFractionDigits: 1 });

window.rochaTrapTab = (event, panel) => {
    const controls = [...panel.querySelectorAll('a[href], button, input, select, textarea, summary, [tabindex]')]
        .filter((control) => !control.matches(':disabled, [tabindex="-1"]') && control.getClientRects().length);
    const first = controls[0];
    const last = controls.at(-1);
    const current = document.activeElement;
    if (!first || !panel.contains(current) || (event.shiftKey ? current === first : current === last)) {
        event.preventDefault();
        (event.shiftKey ? last : first)?.focus();
    }
};

window.RochaFormat = {
    money: (value) => money.format(value),
    quantity: (value) => quantity.format(value),
    unitCost: (value) => (value < 0 ? '-$' : '$') + quantity.format(Math.abs(value)),
};

function resizeMoneyAxis(chart, { width }) {
    chart.options.scales.y.ticks.maxTicksLimit = width < 480 ? 5 : 6;
}

function moneyAxis() {
    return {
        beginAtZero: true,
        border: { display: false },
        grid: { color: 'rgba(92, 64, 51, 0.08)' },
        ticks: {
            callback: (value) => axisMoney.format(value),
            precision: 2,
            maxTicksLimit: 5,
            color: '#6f5d46',
            font: { size: 11 },
        },
    };
}

function chartDetails(canvas, labels, datasets, { count, total, showZero = false, format = (value) => money.format(value) } = {}) {
    const frame = canvas.parentElement;
    if (!frame.chartDetails) {
        const status = document.createElement('p');
        status.className = 'chart-status';
        status.setAttribute('role', 'status');
        const details = document.createElement('details');
        details.className = 'chart-details';
        const summary = document.createElement('summary');
        summary.textContent = 'Ver datos';
        const scroll = document.createElement('div');
        scroll.className = 'chart-data-scroll';
        scroll.tabIndex = 0;
        scroll.setAttribute('role', 'region');
        scroll.setAttribute('aria-label', 'Tabla de datos de la gráfica');
        const table = document.createElement('table');
        scroll.append(table);
        details.append(summary, scroll);
        frame.after(status, details);
        frame.chartDetails = { status, details, table };
    }

    const { status, details, table } = frame.chartDetails;
    const hasPlot = datasets.some(({ data }) => data.some((value) => Number.isFinite(value) && (showZero || value !== 0)));
    const hasRows = count === undefined ? labels.length > 0 : count > 0;
    frame.hidden = !hasPlot;
    status.hidden = hasPlot;
    status.textContent = hasPlot ? '' : hasRows && total !== undefined
        ? 'Total del periodo: ' + money.format(total)
        : hasRows && showZero ? 'No calculable: sin ingresos en estos periodos' : 'Sin movimientos en este periodo';
    details.hidden = !hasRows;
    table.replaceChildren();
    table.createCaption().textContent = canvas.getAttribute('aria-label');

    const header = table.createTHead().insertRow();
    for (const label of ['Periodo o categoría', ...datasets.map(({ label }) => label || 'Importe (USD)')]) {
        const cell = document.createElement('th');
        cell.scope = 'col';
        cell.textContent = label;
        header.append(cell);
    }

    const body = table.createTBody();
    labels.forEach((label, index) => {
        const row = body.insertRow();
        const cell = document.createElement('th');
        cell.scope = 'row';
        cell.textContent = label;
        row.append(cell);
        for (const { data } of datasets) {
            const value = data[index];
            row.insertCell().textContent = value == null ? 'No calculable' : format(value);
        }
    });

    return hasPlot;
}

function refreshChart(chart, labels, datasets, summary) {
    chart.data.labels = labels;
    chart.data.datasets = datasets;
    if (chartDetails(chart.canvas, labels, datasets, summary)) {
        chart.resize();
        chart.update('none');
    }
}

function bindRangeTabs(root, { selector, endpoint, rangeOf, loading, error, retry, apply }) {
    const tabs = [...root.querySelectorAll(selector)];
    let pending;
    let requested;

    async function load(tab) {
        requested = tab;
        pending?.abort();
        const request = new AbortController();
        pending = request;
        const url = new URL(endpoint, window.location.href);
        url.searchParams.set('range', rangeOf(tab));
        error.hidden = true;
        loading.hidden = false;
        try {
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                signal: request.signal,
            });
            if (!response.ok) throw new Error('No se pudo cargar el periodo.');
            const payload = await response.json();
            if (pending !== request) return;
            apply(payload);
            root.querySelector('[role=tabpanel]')?.setAttribute('aria-labelledby', tab.id);
            tabs.forEach((candidate) => {
                candidate.setAttribute('aria-selected', String(candidate === tab));
                candidate.tabIndex = candidate === tab ? 0 : -1;
            });
        } catch {
            if (pending === request) error.hidden = false;
        } finally {
            if (pending === request) loading.hidden = true;
        }
    }

    tabs.forEach((tab, index) => {
        tab.tabIndex = tab.getAttribute('aria-selected') === 'true' ? 0 : -1;
        tab.addEventListener('click', () => load(tab));
        tab.addEventListener('keydown', (event) => {
            const offsets = { ArrowLeft: -1, ArrowRight: 1 };
            const offset = offsets[event.key];
            if (!offset) return;
            event.preventDefault();
            const next = tabs[(index + offset + tabs.length) % tabs.length];
            next.focus();
            load(next);
        });
    });
    retry.addEventListener('click', () => {
        if (requested) {
            requested.focus();
            load(requested);
        }
    });
}

function readChartData(canvas) {
    try {
        return JSON.parse(canvas.dataset.chart || canvas.dataset.items || '[]');
    } catch {
        return [];
    }
}

function initRevenueChart(canvas) {
    const items = readChartData(canvas);

    const context = canvas.getContext('2d');
    const gradient = context.createLinearGradient(0, 0, 0, 260);
    gradient.addColorStop(0, 'rgba(196, 149, 106, 0.22)');
    gradient.addColorStop(1, 'rgba(196, 149, 106, 0.02)');

    const chart = new Chart(canvas, {
        type: 'line',
        data: {
            labels: items.map((item) => item.label),
            datasets: [{
                data: items.map((item) => Number(item.total)),
                backgroundColor: gradient,
                borderColor: '#3d2e24',
                borderWidth: 2,
                fill: true,
                pointBackgroundColor: 'rgb(250, 247, 242)',
                pointBorderColor: '#3d2e24',
                pointBorderWidth: 2,
                pointHoverRadius: 5,
                pointRadius: 3,
                tension: 0.32,
            }],
        },
        options: {
            onResize: resizeMoneyAxis,
            maintainAspectRatio: false,
            responsive: true,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(61, 46, 36, 0.95)',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255, 255, 255, 0.14)',
                    borderWidth: 1,
                    callbacks: {
                        label: (context) => {
                            const avg = context.dataset.data.reduce((a, b) => a + b, 0) / context.dataset.data.length || 0;
                            const pct = avg > 0 ? Math.round((context.parsed.y / avg) * 100) : 0;
                            return `${context.label}: ${money.format(context.parsed.y)}${avg > 0 ? ` - ${pct}% del promedio` : ''}`;
                        },
                    },
                    displayColors: false,
                    padding: 12,
                    titleColor: '#fff',
                },
            },
            scales: {
                x: {
                    border: { display: false },
                    grid: { display: false },
                    ticks: { color: '#6f5d46', font: { size: 11, weight: 600 } },
                },
                y: moneyAxis(),
            },
        },
    });

    chartDetails(canvas, chart.data.labels, chart.data.datasets, {
        count: items.reduce((sum, item) => sum + Number(item.cantidad || 0), 0),
        total: items.reduce((sum, item) => sum + Number(item.total), 0),
    });
    canvas.$chart = chart;
}

function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function animateCountUp(el, target, duration = 600, formatter = (v) => v) {
    if (prefersReducedMotion()) {
        el.textContent = formatter(target);
        el.dataset.countValue = String(target);
        return;
    }

    const startValue = Number(el.dataset.countValue || 0);
    const start = performance.now();

    const animate = (now) => {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        const current = startValue + ((target - startValue) * eased);

        el.textContent = formatter(current);

        if (progress < 1) {
            requestAnimationFrame(animate);
        } else {
            el.dataset.countValue = String(target);
        }
    };

    requestAnimationFrame(animate);
}

window.animateCountUp = animateCountUp;

function initChartTabs(container) {
    const canvas = container.querySelector('[data-dashboard-revenue-chart]');
    const chart = canvas.$chart;
    const headerNumber = container.querySelector('[data-chart-total]');
    const headerMeta = container.querySelector('[data-chart-meta]');
    const insight = container.querySelector('[data-insight]');

    bindRangeTabs(container, {
        selector: '[data-range]',
        endpoint: container.dataset.endpoint,
        rangeOf: (tab) => tab.dataset.range,
        loading: container.querySelector('[data-chart-loading]'),
        error: container.querySelector('[data-chart-error]'),
        retry: container.querySelector('[data-dashboard-retry]'),
        apply(data) {
            const items = data.items || [];
            const total = Number(data.total || 0);
            const count = Number(data.count || 0);
            const datasets = [{ ...chart.data.datasets[0], data: items.map(({ total }) => Number(total)) }];
            refreshChart(chart, items.map(({ label }) => label), datasets, { count, total });
            headerNumber.textContent = money.format(total);
            headerMeta.textContent = count + ' ventas · Prom. ' + money.format(count ? total / count : 0);
            insight.textContent = data.insights?.caption || '';
        },
    });
}

function initGastosSixMonthChart(canvas) {
    const items = readChartData(canvas);

    const context = canvas.getContext('2d');
    const gradient = context.createLinearGradient(0, 0, 0, 180);
    gradient.addColorStop(0, 'rgba(92, 64, 51, 0.12)');
    gradient.addColorStop(1, 'rgba(92, 64, 51, 0.01)');

    const chart = new Chart(canvas, {
        type: 'line',
        data: {
            labels: items.map((item) => item.label),
            datasets: [{
                data: items.map((item) => Number(item.total)),
                backgroundColor: gradient,
                borderColor: 'rgb(92, 64, 51)',
                borderWidth: 2,
                fill: true,
                pointBackgroundColor: 'rgb(255, 255, 255)',
                pointBorderColor: 'rgb(92, 64, 51)',
                pointBorderWidth: 2,
                pointHoverRadius: 5,
                pointRadius: 3,
                tension: 0.34,
            }],
        },
        options: {
            animation: prefersReducedMotion() ? false : {
                duration: 450,
                easing: 'easeOutQuart',
            },
            hover: {
                animationDuration: prefersReducedMotion() ? 0 : 150,
            },
            onResize: resizeMoneyAxis,
            maintainAspectRatio: false,
            responsive: true,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    backgroundColor: 'rgba(61, 46, 36, 0.95)',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255, 255, 255, 0.14)',
                    borderWidth: 1,
                    callbacks: {
                        label: (context) => money.format(context.parsed.y),
                    },
                    displayColors: false,
                    padding: 12,
                    titleColor: '#fff',
                },
            },
            scales: {
                x: {
                    border: {
                        display: false,
                    },
                    grid: {
                        display: false,
                    },
                    ticks: {
                        color: '#6f5d46',
                        font: {
                            size: 11,
                            weight: 600,
                        },
                    },
                },
                y: moneyAxis(),
            },
        },
    });
    chartDetails(canvas, chart.data.labels, chart.data.datasets, {
        count: items.reduce((sum, item) => sum + Number(item.cantidad || 0), 0),
        total: items.reduce((sum, item) => sum + Number(item.total), 0),
    });
}

function readFinanceRanges(root) {
    try {
        return JSON.parse(root.dataset.ranges || '{}');
    } catch {
        return {};
    }
}

function financeDatasets(items) {
    return [
        ['Dinero recibido', 'rgb(41, 93, 45)', 'rgba(41, 93, 45, 0.08)', 'ingresos'],
        ['Compras', 'rgb(92, 64, 51)', 'rgba(92, 64, 51, 0.08)', 'compras'],
        ['Gastos', 'rgb(178, 91, 62)', 'rgba(178, 91, 62, 0.08)', 'gastos'],
        ['Resultado', 'rgb(61, 46, 36)', 'rgba(196, 149, 106, 0.10)', 'resultado'],
    ].map(([label, borderColor, backgroundColor, key]) => ({
        label,
        data: items.map((item) => Number(item[key] || 0)),
        backgroundColor,
        borderColor,
        borderWidth: key === 'resultado' ? 3 : 2,
        fill: false,
        pointBackgroundColor: 'rgb(250, 247, 242)',
        pointBorderColor: borderColor,
        pointBorderWidth: 2,
        pointHoverRadius: 5,
        pointRadius: 3,
        tension: 0.28,
    }));
}

function applyFinanceRange(root, chart, payload) {
    const items = Array.isArray(payload.items) ? payload.items : [];
    const result = Number(payload.total || 0);
    refreshChart(chart, items.map(({ label }) => label), financeDatasets(items), {
        count: Number(payload.cantidad || 0),
        total: result,
    });

    const total = root.querySelector('[data-finance-range-total]');
    if (total) total.textContent = money.format(result);

    const windowLabel = root.querySelector('[data-finance-range-window]');
    if (windowLabel) {
        windowLabel.textContent = payload.window || '';
    }

    const insight = root.querySelector('[data-finance-range-insight]');
    if (insight) {
        insight.textContent = payload.insight?.caption || '';
    }

    const frame = root.querySelector('.finance-chart-frame');
    if (frame && !prefersReducedMotion()) {
        frame.classList.remove('is-refreshing');
        void frame.offsetWidth;
        frame.classList.add('is-refreshing');
    }
}

function financePercent(numerator, denominator) {
    const base = Number(denominator || 0);
    if (base <= 0) {
        return null;
    }

    return (Number(numerator || 0) / base) * 100;
}

function financeProfitSeries(items, type) {
    return items.map((item) => {
        if (type === 'margen') {
            return financePercent(item.resultado, item.ingresos);
        }

        return financePercent(item[type], item.ingresos);
    });
}

function initFinanceProfitChart(canvas) {
    const items = readChartData(canvas);
    const type = canvas.dataset.financeProfitChart || 'margen';
    const config = {
        margen: ['Margen', 'rgb(41, 93, 45)', 'rgba(41, 93, 45, 0.08)'],
        gastos: ['Gastos / dinero', 'rgb(178, 91, 62)', 'rgba(178, 91, 62, 0.08)'],
        compras: ['Compras / dinero', 'rgb(92, 64, 51)', 'rgba(92, 64, 51, 0.08)'],
    }[type] || ['Rentabilidad', 'rgb(92, 64, 51)', 'rgba(92, 64, 51, 0.08)'];
    const values = financeProfitSeries(items, type);

    const chart = new Chart(canvas, {
        type: 'line',
        data: {
            labels: items.map((item) => item.label),
            datasets: [{
                label: config[0],
                data: values,
                borderColor: config[1],
                backgroundColor: config[2],
                borderWidth: 2,
                fill: true,
                pointBackgroundColor: 'rgb(250, 247, 242)',
                pointBorderColor: config[1],
                pointBorderWidth: 2,
                pointHoverRadius: 5,
                pointRadius: 2,
                tension: 0.32,
            }],
        },
        options: {
            animation: prefersReducedMotion() ? false : {
                duration: 220,
                easing: 'easeOutQuart',
            },
            maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(61, 46, 36, 0.95)',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255, 255, 255, 0.14)',
                    borderWidth: 1,
                    callbacks: {
                        label: (tooltip) => `${tooltip.dataset.label}: ${percent.format(tooltip.parsed.y)}%`,
                        title: (items) => items[0]?.label || '',
                    },
                    displayColors: false,
                    padding: 10,
                    titleColor: '#fff',
                },
            },
            scales: {
                x: {
                    border: { display: false },
                    grid: { display: false },
                    ticks: {
                        color: '#6f5d46',
                        font: { size: 10, weight: 600 },
                        maxRotation: 0,
                    },
                },
                y: {
                    beginAtZero: true,
                    ticks: { callback: (value) => percent.format(value) + '%', maxTicksLimit: 4, color: '#6f5d46' },
                },
            },
        },
    });
    chartDetails(canvas, chart.data.labels, chart.data.datasets, { showZero: true, format: (value) => percent.format(value) + '%', count: items.length });
}

function initFinanceTrendBars(canvas) {
    const items = readChartData(canvas);

    const chart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: items.map((item) => item.label),
            datasets: [
                {
                    label: 'Resultado',
                    data: items.map((item) => Number(item.resultado || 0)),
                    backgroundColor: 'rgba(41, 93, 45, 0.72)',
                    borderRadius: 8,
                },
                {
                    label: 'Gastos',
                    data: items.map((item) => Number(item.gastos || 0)),
                    backgroundColor: 'rgba(178, 91, 62, 0.62)',
                    borderRadius: 8,
                },
                {
                    label: 'Compras',
                    data: items.map((item) => Number(item.compras || 0)),
                    backgroundColor: 'rgba(92, 64, 51, 0.52)',
                    borderRadius: 8,
                },
            ],
        },
        options: {
            animation: prefersReducedMotion() ? false : {
                duration: 240,
                easing: 'easeOutQuart',
            },
            onResize: resizeMoneyAxis,
            maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    labels: {
                        boxHeight: 8,
                        boxWidth: 8,
                        color: '#6f5d46',
                        font: { size: 11, weight: 700 },
                        usePointStyle: true,
                    },
                    position: 'bottom',
                },
                tooltip: {
                    backgroundColor: 'rgba(61, 46, 36, 0.95)',
                    bodyColor: '#fff',
                    callbacks: {
                        label: (tooltip) => `${tooltip.dataset.label}: ${money.format(tooltip.parsed.y)}`,
                    },
                    padding: 10,
                    titleColor: '#fff',
                },
            },
            scales: {
                x: {
                    border: { display: false },
                    grid: { display: false },
                    ticks: { color: '#6f5d46', font: { size: 10, weight: 600 } },
                },
                y: moneyAxis(),
            },
        },
    });
    chartDetails(canvas, chart.data.labels, chart.data.datasets, {
        count: items.reduce((sum, item) => sum + Number(item.cantidad || 0), 0),
        total: items.reduce((sum, item) => sum + Number(item.resultado || 0), 0),
    });
}

function initFinanceInventoryDonut(canvas) {
    let payload = {};
    try {
        payload = JSON.parse(canvas.dataset.items || '{}');
    } catch {
        payload = {};
    }

    const values = Array.isArray(payload.values) ? payload.values.map((value) => Number(value || 0)) : [];
    const labels = Array.isArray(payload.labels) ? payload.labels : [];

    const chart = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                label: 'Valor de inventario (USD)',
                backgroundColor: ['rgba(92, 64, 51, 0.72)', 'rgba(196, 149, 106, 0.76)'],
                borderColor: 'rgba(250, 247, 242, 0.80)',
                borderWidth: 2,
                hoverOffset: 4,
            }],
        },
        options: {
            animation: prefersReducedMotion() ? false : {
                animateRotate: true,
                duration: 240,
                easing: 'easeOutQuart',
            },
            cutout: '66%',
            maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    labels: {
                        boxHeight: 8,
                        boxWidth: 8,
                        color: '#6f5d46',
                        font: { size: 11, weight: 700 },
                        usePointStyle: true,
                    },
                    position: 'bottom',
                },
                tooltip: {
                    backgroundColor: 'rgba(61, 46, 36, 0.95)',
                    bodyColor: '#fff',
                    callbacks: {
                        label: (tooltip) => `${tooltip.label}: ${money.format(tooltip.parsed)}`,
                    },
                    padding: 10,
                    titleColor: '#fff',
                },
            },
        },
    });
    chartDetails(canvas, chart.data.labels, chart.data.datasets, { total: values.reduce((sum, value) => sum + value, 0) });
}

function initFinanceRangeWidget(root) {
    const canvas = root.querySelector('[data-finance-range-chart]');
    if (!canvas) {
        return;
    }

    const ranges = readFinanceRanges(root);
    const initial = ranges.mes || { items: readChartData(canvas), insight: { caption: '' }, window: '' };
    const chart = new Chart(canvas, {
        type: 'line',
        data: {
            labels: (initial.items || []).map((item) => item.label),
            datasets: financeDatasets(initial.items || []),
        },
        options: {
            animation: prefersReducedMotion() ? false : {
                duration: 220,
                easing: 'easeOutQuart',
            },
            onResize: resizeMoneyAxis,
            maintainAspectRatio: false,
            responsive: true,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: {
                    labels: {
                        boxHeight: 8,
                        boxWidth: 8,
                        color: '#6f5d46',
                        font: {
                            size: 11,
                            weight: 700,
                        },
                        usePointStyle: true,
                    },
                    position: 'bottom',
                },
                tooltip: {
                    backgroundColor: 'rgba(61, 46, 36, 0.95)',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255, 255, 255, 0.14)',
                    borderWidth: 1,
                    callbacks: {
                        label: (tooltip) => `${tooltip.dataset.label}: ${money.format(tooltip.parsed.y)}`,
                        title: (items) => items[0]?.label || '',
                    },
                    displayColors: true,
                    padding: 12,
                    titleColor: '#fff',
                },
            },
            scales: {
                x: {
                    border: { display: false },
                    grid: { display: false },
                    ticks: {
                        color: '#6f5d46',
                        font: {
                            size: 11,
                            weight: 600,
                        },
                    },
                },
                y: moneyAxis(),
            },
        },
    });

    bindRangeTabs(root, {
        selector: '[data-finance-range-tab]',
        endpoint: root.dataset.endpoint,
        rangeOf: (tab) => tab.dataset.financeRangeTab,
        loading: root.querySelector('[data-finance-range-loading]'),
        error: root.querySelector('[data-finance-range-error]'),
        retry: root.querySelector('[data-finance-retry]'),
        apply: (payload) => applyFinanceRange(root, chart, payload),
    });

    applyFinanceRange(root, chart, initial);
}

document.addEventListener('alpine:init', () => {
    Alpine.store('dashboard', {
        state: 'success',
        range: 'hoy',

        setRange(range) {
            this.range = range;
        },

        retry() {
            const activeTab = document.querySelector('[role="tab"][aria-selected="true"]');
            if (activeTab) activeTab.click();
        },

        countUp(el, target, formatter = (v) => v) {
            animateCountUp(el, target, 600, formatter);
        },
    });
});

Alpine.start();

function initDashboardCharts() {
    document.querySelectorAll('[data-dashboard-revenue-chart]').forEach((canvas) => {
        initRevenueChart(canvas);
        const container = canvas.closest('.dashboard-revenue, [x-data]');
        if (container) initChartTabs(container);
    });
    document.querySelectorAll('[data-gastos-six-month-chart]').forEach(initGastosSixMonthChart);
    document.querySelectorAll('[data-finance-range-widget]').forEach(initFinanceRangeWidget);
    document.querySelectorAll('[data-finance-profit-chart]').forEach(initFinanceProfitChart);
    document.querySelectorAll('[data-finance-trend-bars]').forEach(initFinanceTrendBars);
    document.querySelectorAll('[data-finance-inventory-donut]').forEach(initFinanceInventoryDonut);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboardCharts);
} else {
    initDashboardCharts();
}
