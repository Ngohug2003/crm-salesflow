import './echo';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';
import {
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    DoughnutController,
    Legend,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
} from 'chart.js';

Chart.register(
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    DoughnutController,
    Legend,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
);

window.salesflow = {
    toggleTheme() {
        const appearance = document.documentElement.classList.contains('dark') ? 'light' : 'dark';

        if (window.Flux?.appearance !== undefined) {
            window.Flux.appearance = appearance;

            return;
        }

        if (typeof window.Flux?.applyAppearance === 'function') {
            window.Flux.applyAppearance(appearance);

            return;
        }

        document.documentElement.classList.toggle('dark', appearance === 'dark');
        localStorage.setItem('flux.appearance', appearance);
    },
};

window.salesflowMoneyInput = (wire, model) => ({
    display: '',
    init() { this.display = this.toDisplay(wire.get(model)); },
    sync(event) {
        const raw = String(event.target.value ?? '').replace(/[^\d]/g, '');
        wire.set(model, raw === '' ? '0' : raw);
        this.display = this.toDisplay(raw);
    },
    format() { this.display = this.toDisplay(wire.get(model)); },
    syncFromServer(event) {
        if (event.detail.model === model) {
            this.display = this.toDisplay(event.detail.value);
        }
    },
    toDisplay(value) {
        const raw = String(value ?? '').split('.')[0].replace(/[^\d]/g, '');
        return raw === '' ? '' : new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(Number(raw));
    },
});

window.salesflowRichTextEditor = (wire, model, placeholder) => ({
    editor: null,

    init() {
        const editorElement = this.$refs.editor;

        if (this.editor !== null) {
            return;
        }

        const existingEditor = Quill.find(editorElement);

        if (existingEditor && typeof existingEditor.getModule === 'function') {
            this.editor = existingEditor;
            this.removeDuplicateToolbars();

            return;
        }

        this.editor = new Quill(editorElement, {
            theme: 'snow',
            placeholder,
            modules: {
                toolbar: [
                    [{ header: [2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['blockquote', 'link'],
                    ['clean'],
                ],
            },
        });

        this.removeDuplicateToolbars();

        const initialValue = wire.get(model) ?? '';

        if (initialValue !== '') {
            this.editor.clipboard.dangerouslyPasteHTML(initialValue);
        }

        this.editor.on('text-change', (_delta, _oldDelta, source) => {
            if (source !== 'user') {
                return;
            }

            const isEmpty = this.editor.getText().trim() === '';
            wire.set(model, isEmpty ? '' : this.editor.getSemanticHTML());
        });
    },

    removeDuplicateToolbars() {
        const activeToolbar = this.editor.getModule('toolbar')?.container;
        const editorWrapper = this.$refs.editor.parentElement;

        if (!activeToolbar || !editorWrapper) {
            return;
        }

        Array.from(editorWrapper.children)
            .filter((element) => element.classList.contains('ql-toolbar') && element !== activeToolbar)
            .forEach((element) => element.remove());
    },
});

window.salesflowChart = (configuration) => ({
    chart: null,
    themeObserver: null,
    stopHandler: null,
    darkMode: false,

    init() {
        this.darkMode = document.documentElement.classList.contains('dark');
        this.stopHandler = () => this.stopChart();
        window.addEventListener('salesflow:charts-stop', this.stopHandler);
        this.render();
        this.themeObserver = new MutationObserver(() => {
            const nextDarkMode = document.documentElement.classList.contains('dark');

            if (nextDarkMode !== this.darkMode) {
                this.darkMode = nextDarkMode;
                this.render();
            }
        });
        this.themeObserver.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class'],
        });
    },

    render() {
        this.destroyChart();

        const textColor = this.darkMode ? '#cbd5e1' : '#475569';
        const gridColor = this.darkMode ? 'rgba(148, 163, 184, 0.15)' : 'rgba(148, 163, 184, 0.2)';
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const suppliedOptions = configuration.options ?? {};
        const suppliedPlugins = suppliedOptions.plugins ?? {};
        const suppliedTooltip = suppliedPlugins.tooltip ?? {};

        this.chart = new Chart(this.$refs.canvas, {
            ...configuration,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: reducedMotion ? false : {
                    duration: 320,
                    easing: 'easeOutQuart',
                },
                interaction: {
                    mode: 'nearest',
                    intersect: false,
                },
                ...suppliedOptions,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: textColor,
                            boxWidth: 12,
                            usePointStyle: true,
                        },
                        ...(suppliedPlugins.legend ?? {}),
                    },
                    ...suppliedPlugins,
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const isHorizontal = context.chart.options.indexAxis === 'y';
                                const value = (isHorizontal ? context.parsed?.x : context.parsed?.y) ?? context.parsed?.y ?? context.parsed?.x ?? context.raw ?? 0;
                                const formatted = configuration.currency
                                    ? `${new Intl.NumberFormat('vi-VN').format(value)} đ`
                                    : new Intl.NumberFormat('vi-VN').format(value);

                                return `${context.dataset.label ?? context.label}: ${formatted}`;
                            },
                            ...(suppliedTooltip.callbacks ?? {}),
                        },
                        ...suppliedTooltip,
                    },
                },
                scales: suppliedOptions.scales === undefined ? undefined : Object.fromEntries(
                    Object.entries(suppliedOptions.scales).map(([axis, options]) => [
                        axis,
                        {
                            ...options,
                            ticks: {
                                color: textColor,
                                callback: (value, index, ticks) => {
                                    if (options.ticks?.callback) {
                                        return options.ticks.callback(value, index, ticks);
                                    }
                                    if (configuration.currency && typeof value === 'number' && value >= 1000) {
                                        if (value >= 1_000_000_000) {
                                            return `${(value / 1_000_000_000).toLocaleString('vi-VN', { maximumFractionDigits: 1 })} Tỷ`;
                                        }
                                        if (value >= 1_000_000) {
                                            return `${(value / 1_000_000).toLocaleString('vi-VN', { maximumFractionDigits: 1 })} Tr`;
                                        }
                                        return `${new Intl.NumberFormat('vi-VN').format(value)} đ`;
                                    }
                                    return value;
                                },
                                ...(options.ticks ?? {}),
                            },
                            grid: { color: gridColor, ...(options.grid ?? {}) },
                        },
                    ]),
                ),
            },
        });
    },

    stopChart() {
        this.chart?.stop();
    },

    destroyChart() {
        this.stopChart();
        this.chart?.destroy();
        this.chart = null;
    },

    destroy() {
        window.removeEventListener('salesflow:charts-stop', this.stopHandler);
        this.themeObserver?.disconnect();
        this.destroyChart();
    },
});

document.addEventListener('livewire:navigating', () => {
    window.dispatchEvent(new CustomEvent('salesflow:charts-stop'));
    document.documentElement.classList.add('is-navigating');
});

document.addEventListener('livewire:navigated', () => {
    requestAnimationFrame(() => document.documentElement.classList.remove('is-navigating'));
});
