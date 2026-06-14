import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

window.App = window.App || {};

window.App.conversationInbox = (refreshUrl) => ({
    refreshUrl,
    timer: null,
    async refresh() {
        if (!this.refreshUrl || document.hidden) {
            return;
        }

        const response = await fetch(this.refreshUrl, {
            headers: {
                'Accept': 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            return;
        }

        this.$refs.list.innerHTML = await response.text();
    },
    start() {
        this.timer = window.setInterval(() => this.refresh(), 10000);
    },
    stop() {
        if (this.timer) {
            window.clearInterval(this.timer);
        }
    },
});

Alpine.start();
