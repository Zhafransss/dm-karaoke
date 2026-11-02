import Alpine from 'alpinejs';

import { distribute } from './masonry-layout';

/*
 * Public gallery grid: children are .gal-item[data-ratio]. Before this runs (or without JS) they sit in a
 * plain CSS grid; here they are moved into 1/2/3 flex columns depending on the screen width.
 */
Alpine.data('masonry', () => ({
    items: [],
    n: 0,
    init() {
        this.items = [...this.$el.children];
        this.layout();
        let t;
        window.addEventListener('resize', () => { clearTimeout(t); t = setTimeout(() => this.layout(), 120); });
    },
    columns() {
        if (window.matchMedia('(min-width: 1024px)').matches) return 3;
        return window.matchMedia('(min-width: 640px)').matches ? 2 : 1;
    },
    layout() {
        const n = this.columns();
        if (n === this.n || !this.items.length) return;
        this.n = n;
        const cols = distribute(this.items.map((el) => parseFloat(el.dataset.ratio) || 4 / 3), n);
        // Moving nodes must not make Alpine re-initialise them (they carry @click handlers).
        Alpine.mutateDom(() => {
            const els = cols.map((idx) => {
                const col = document.createElement('div');
                col.className = 'gal-col';
                idx.forEach((i) => col.appendChild(this.items[i]));
                return col;
            });
            this.$el.classList.add('is-masonry');
            this.$el.replaceChildren(...els);
        });
    },
}));
