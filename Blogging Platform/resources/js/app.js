import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.data('theme', () => ({
        dark: false,

        init() {
            this.dark = localStorage.getItem('theme') === 'dark' ||
                (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);
            this.$watch('dark', val => {
                localStorage.setItem('theme', val ? 'dark' : 'light');
                if (val) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            });
        },

        toggle() {
            this.dark = !this.dark;
        }
    }));
});

Alpine.start();
