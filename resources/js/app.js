import './bootstrap';

import Alpine from 'alpinejs';
import Swal from 'sweetalert2';

window.Alpine = Alpine;

window.App = window.App || {};

window.App.toast = ({ type = 'success', message = '' } = {}) => {
    if (!message) {
        return;
    }

    const isError = type === 'error';

    if (isError) {
        Swal.fire({
            icon: 'error',
            title: 'No se pudo completar la accion',
            text: message,
            confirmButtonText: 'Entendido',
            buttonsStyling: false,
            width: '32rem',
            customClass: {
                popup: 'noia-swal-modal noia-swal-modal-error',
                icon: 'noia-swal-icon',
                title: 'noia-swal-modal-title',
                htmlContainer: 'noia-swal-modal-text',
                actions: 'noia-swal-actions',
                confirmButton: 'noia-swal-confirm',
            },
        });

        return;
    }

    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: message,
        showConfirmButton: false,
        timer: 3200,
        timerProgressBar: true,
        width: '22rem',
        customClass: {
            popup: 'noia-swal-toast noia-swal-toast-success',
            title: 'noia-swal-title',
            timerProgressBar: 'noia-swal-progress-success',
        },
    });
};

window.App.conversationSound = {
    storageKey: 'noiachat.conversationSound.enabled',
    isEnabled() {
        return window.localStorage.getItem(this.storageKey) === '1';
    },
    toggle() {
        const enabled = !this.isEnabled();
        window.localStorage.setItem(this.storageKey, enabled ? '1' : '0');

        if (enabled) {
            this.play();
        }

        return enabled;
    },
    play() {
        if (!this.isEnabled()) {
            return;
        }

        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) {
                return;
            }

            const context = new AudioContext();
            const oscillator = context.createOscillator();
            const gain = context.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(880, context.currentTime);
            gain.gain.setValueAtTime(0.0001, context.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.08, context.currentTime + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, context.currentTime + 0.22);

            oscillator.connect(gain);
            gain.connect(context.destination);
            oscillator.start();
            oscillator.stop(context.currentTime + 0.24);
        } catch (error) {
            // Some browsers block audio until the user interacts with the page.
        }
    },
};

window.App.conversationInbox = (refreshUrl) => ({
    refreshUrl,
    timer: null,
    unreadTotal: null,
    unreadCount() {
        return Array.from(this.$refs.list.querySelectorAll('[data-unread-count]')).reduce((total, element) => {
            return total + Number.parseInt(element.dataset.unreadCount || '0', 10);
        }, 0);
    },
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

        const previousUnreadTotal = this.unreadTotal ?? this.unreadCount();

        this.$refs.list.innerHTML = await response.text();

        const nextUnreadTotal = this.unreadCount();
        if (nextUnreadTotal > previousUnreadTotal) {
            window.App.conversationSound.play();
        }

        this.unreadTotal = nextUnreadTotal;
    },
    start() {
        this.unreadTotal = this.unreadCount();
        this.timer = window.setInterval(() => this.refresh(), 10000);
    },
    stop() {
        if (this.timer) {
            window.clearInterval(this.timer);
        }
    },
});

window.App.templateComposer = (templates) => ({
    templates,
    selectedId: '',
    values: {},
    get selectedTemplate() {
        return this.templates.find((template) => String(template.id) === String(this.selectedId)) || null;
    },
    get serializedVariables() {
        if (!this.selectedTemplate) {
            return '';
        }

        return this.selectedTemplate.variables
            .map((variable) => (this.values[variable.key] || '').trim())
            .filter((value) => value !== '')
            .join('|');
    },
    get canSubmit() {
        if (!this.selectedTemplate) {
            return false;
        }

        return this.selectedTemplate.variables.length === 0
            || this.selectedTemplate.variables.every((variable) => (this.values[variable.key] || '').trim() !== '');
    },
    get preview() {
        if (!this.selectedTemplate) {
            return '';
        }

        return this.selectedTemplate.body.replace(/\{\{\s*(\d+)\s*\}\}/g, (match, key) => {
            return (this.values[key] || '').trim() || `{{${key}}}`;
        });
    },
});

Alpine.start();
