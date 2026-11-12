window.hotspotEditor = (config) => ({
    config,
    profiles: [],
    selected: null,
    design: {...config.defaults},
    settings: {},
    settingsOpen: false,
    creating: false,
    busy: '',
    online: false,
    message: '',
    failed: false,
    device: 'Phone',
    previewState: 'Sign in',
    review: '',
    stageId: null,
    serverId: null,

    init() {
        this.$watch('review', (value) => {
            if (value && !this.$refs.reviewDialog.open) this.$refs.reviewDialog.showModal();
            if (!value && this.$refs.reviewDialog.open) this.$refs.reviewDialog.close();
        });
    },

    get active() {
        return this.profiles.find((profile) => profile['.id'] === this.selected);
    },

    get testTargets() {
        return this.profiles.filter((profile) => profile['.id'] !== this.selected && profile.networks.length === 1 && !['true', 'yes'].includes(profile.networks[0].disabled));
    },

    get assignableServers() {
        return this.profiles.filter((profile) => profile['.id'] !== this.selected).flatMap((profile) =>
            profile.networks.map((network) => ({network, profile})));
    },

    get previewHtml() {
        const doc = new DOMParser().parseFromString(config.preview, 'text/html');
        doc.documentElement.dataset.layout = this.design.layout;
        doc.body.style.setProperty('--accent', this.design.accent);
        const channels = this.design.accent.slice(1).match(/../g).map((hex) => {
            const channel = Number.parseInt(hex, 16) / 255;
            return channel <= .04045 ? channel / 12.92 : ((channel + .055) / 1.055) ** 2.4;
        });
        const luminance = .2126 * channels[0] + .7152 * channels[1] + .0722 * channels[2];
        doc.body.style.setProperty('--button-text', luminance > .179 ? '#101828' : '#ffffff');
        for (const field of ['headline', 'message', 'button']) {
            doc.getElementById('portal-' + field).textContent = this.design[field] ?? '';
        }
        doc.getElementById('portal-error').hidden = this.previewState !== 'Invalid login';
        doc.getElementById('portal-success').hidden = this.previewState !== 'Connected';
        doc.getElementById('portal-form').hidden = this.previewState === 'Connected';

        return '<!doctype html>' + doc.documentElement.outerHTML;
    },

    affected(profile) {
        if (!profile) return '';
        if (!profile.networks.length) return 'No HotSpot servers use this profile.';
        return 'Affected networks: ' + profile.networks.map((network) => network.name + ' (' + network.interface + ')').join(', ');
    },

    select(id) {
        this.selected = id;
        this.design = {...config.defaults, ...(this.active?.draft ?? this.active?.live?.design)};
        this.settingsOpen = false;
        this.previewState = 'Sign in';
    },

    openSettings(creating) {
        this.creating = creating;
        const profile = creating ? {} : this.active;
        const settings = {
            name: '',
            'dns-name': '',
            'hotspot-address': '0.0.0.0',
            'ssl-certificate': 'none',
            'use-radius': 'no',
            'https-redirect': 'yes',
        };
        for (const key of Object.keys(settings)) {
            settings[key] = profile[key] ?? settings[key];
            if (settings[key] === 'true') settings[key] = 'yes';
            if (settings[key] === 'false') settings[key] = 'no';
        }
        for (const key of ['html-directory', 'html-directory-override', 'split-user-domain', 'radius-accounting', 'radius-interim-update', 'radius-location-id', 'radius-location-name', 'radius-default-domain', 'nas-port-type', 'http-cookie-lifetime', 'http-proxy', 'trial-uptime-limit', 'trial-uptime-reset', 'trial-user-profile', 'mac-auth-mode', 'smtp-server']) {
            if (key in profile) settings[key] = profile[key];
        }
        settings['login-by'] = (profile['login-by'] ?? 'http-chap,cookie').split(',').filter(Boolean);
        this.settings = settings;
        this.settingsOpen = true;
    },

    async request(url, data) {
        const response = await fetch(url, {
            method: data ? 'POST' : 'GET',
            headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf},
            ...(data && {body: JSON.stringify(data)}),
            signal: AbortSignal.timeout(75000),
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(Object.values(result.errors ?? {}).flat()[0] ?? result.message ?? 'SkyBase could not complete this request.');
        }
        return result;
    },

    async refresh(keepDesign = false, name = null) {
        try {
            const result = await this.request(config.syncUrl);
            this.profiles = result.profiles;
            this.online = true;
            const id = (name && this.profiles.find((profile) => profile.name === name)?.['.id']) || this.active?.['.id'] || this.profiles[0]?.['.id'] || null;
            if (!keepDesign || this.selected !== id) this.select(id);
        } catch (error) {
            this.online = false;
            throw error;
        }
    },

    async sync() {
        if (this.busy) return;
        this.busy = 'sync';
        this.message = '';
        try {
            await this.refresh(true);
            this.settingsOpen = false;
        } catch (error) {
            this.failed = true;
            this.message = error.message;
        } finally {
            this.busy = '';
        }
    },

    async run(action, payload = {}, {target = this.active, keepDesign = false, name = null} = {}) {
        if (this.busy) return;
        this.busy = action;
        this.failed = false;
        this.message = action === 'publish' ? 'Transferring and verifying portal files…' : 'Working…';
        try {
            const result = await this.request(config.actionUrl, {
                action, profile: target?.['.id'], revision: target?.revision, ...payload,
            });
            this.message = result.message;
            if (action === 'draft' && this.active) this.active.draft = {...this.design};
            if (action === 'check' || action === 'draft') return;
            try {
                await this.refresh(keepDesign, name);
            } catch {
                this.message += ' Sync from RouterOS to confirm the current state.';
            }
        } catch (error) {
            this.failed = true;
            this.message = error.message;
        } finally {
            this.busy = '';
        }
    },

    saveProfile() {
        return this.run(this.creating ? 'create' : 'update', {settings: this.settings}, {name: this.settings.name});
    },

    confirm() {
        const action = this.review;
        this.review = '';
        if (action === 'stage') {
            const target = this.testTargets.find((profile) => profile['.id'] === this.stageId);
            if (target) this.run('publish', {design: this.design, stage: true}, {target, name: target.name});
        } else if (action === 'assign') {
            const entry = this.assignableServers.find(({network}) => network['.id'] === this.serverId);
            if (entry) this.run('assign', {server: this.serverId, source: entry.profile['.id'], source_revision: entry.profile.revision}, {keepDesign: true});
        } else if (action === 'publish') {
            this.run('publish', {design: this.design});
        } else {
            this.run(action);
        }
    },
});
