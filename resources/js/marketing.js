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

Alpine.start();
