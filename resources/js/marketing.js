import Alpine from 'alpinejs';

window.Alpine = Alpine;

const pageName = document.body.dataset.marketingPage;
const emitMarketingEvent = (event, details) => {
    const payload = { event, page_name: pageName, ...details };

    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(payload);
    window.dispatchEvent(new CustomEvent('skybase:marketing', { detail: payload }));
};

if (pageName) {
    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');

        if (!link) {
            return;
        }

        const destination = new URL(link.href, window.location.origin);
        let action;

        if (destination.origin === window.location.origin) {
            if (destination.pathname === '/auth/register') {
                action = 'start_free';
            } else if (destination.hash === '#guided-setup') {
                action = 'guided_setup';
            } else if (destination.pathname === '/pricing') {
                action = 'view_pricing';
            }
        } else if (destination.hostname === 'wa.me') {
            action = 'whatsapp';
        }

        if (action) {
            emitMarketingEvent('marketing_cta_click', { action });
        }
    });

    const leadType = document.body.dataset.leadSubmitted;

    if (['guided_setup', 'contact'].includes(leadType)) {
        emitMarketingEvent('generate_lead', { lead_type: leadType });
    }
}

Alpine.data('firstSubscriptionGuide', () => ({
    currentChapter: 'before-you-start',
    updateChapter: null,
    init() {
        const chapters = [...this.$el.querySelectorAll('section[id]')];
        this.updateChapter = () => {
            const reached = chapters.filter((chapter) => chapter.getBoundingClientRect().top <= 160);
            this.currentChapter = reached.at(-1)?.id || 'before-you-start';
        };
        window.addEventListener('scroll', this.updateChapter, { passive: true });
        this.updateChapter();
    },
    destroy() {
        window.removeEventListener('scroll', this.updateChapter);
    },
}));

Alpine.data('changelogArchive', () => ({
    year: '',
    visibleCount: 5,
    rows: [],
    hashListener: null,
    get matchingRows() {
        return this.rows.filter((row) => !this.year || row.year === this.year);
    },
    get visibleIds() {
        return this.matchingRows.slice(0, this.visibleCount).map((row) => row.id);
    },
    init() {
        this.rows = [...this.$el.querySelectorAll('[data-release-year]')].map((row) => ({
            id: row.id,
            year: row.dataset.releaseYear,
        }));
        this.hashListener = () => this.openLinkedRelease();
        window.addEventListener('hashchange', this.hashListener);
        this.openLinkedRelease();
    },
    openLinkedRelease() {
        const target = document.getElementById(window.location.hash.slice(1));
        if (!target || !this.$el.contains(target) || target.tagName !== 'ARTICLE') {
            return;
        }
        const index = this.rows.findIndex((row) => row.id === target.id);
        if (index >= 0) {
            this.year = '';
            this.visibleCount = Math.max(this.visibleCount, index + 1);
        }
        const notes = target.querySelector('details');
        if (notes) {
            notes.open = true;
        }
        this.$nextTick(() => target.scrollIntoView({ block: 'start' }));
    },
    destroy() {
        window.removeEventListener('hashchange', this.hashListener);
    },
}));

Alpine.start();
