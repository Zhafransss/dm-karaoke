/** Design tokens from the Noir & Gold design system (prototype v3). */
/** @type {import('tailwindcss').Config} */
export default {
    content: ['./resources/views/**/*.blade.php', './resources/js/**/*.js', './app/**/*.php'],
    theme: {
        screens: { sm: '560px', md: '768px', lg: '900px', xl: '1100px' },
        extend: {
            colors: {
                bg: '#0a0907',
                surface: { DEFAULT: '#12100c', raised: '#1a1712', overlay: '#231f18' },
                line: { DEFAULT: '#2f2a21', strong: '#6e6351' },
                ink: { DEFAULT: '#f3ecdd', muted: '#b3aa98', subtle: '#958c7b' },
                gold: { DEFAULT: '#c9a54c', bright: '#e8cb82', deep: '#8c6d2c', soft: '#2a2314', on: '#0a0907' },
                velvet: { DEFAULT: '#e06a8b', soft: '#2b1219' },
                success: { DEFAULT: '#6fc7b5', soft: '#10241f' },
                danger: { DEFAULT: '#f08463', soft: '#2c140d' },
            },
            fontFamily: {
                display: ['"Cormorant Garamond"', 'Cormorant', '"Times New Roman"', 'Georgia', 'serif'],
                sans: ['Jost', '"Avenir Next"', '"Century Gothic"', '"Segoe UI"', 'sans-serif'],
            },
            borderRadius: { sm: '2px', md: '4px', lg: '8px', arch: '240px' },
            backgroundImage: {
                'gold-sheen': 'linear-gradient(135deg, #8c6d2c 0%, #e8cb82 48%, #b8913a 100%)',
                'gold-fade': 'linear-gradient(90deg, rgba(201,165,76,0) 0%, #c9a54c 50%, rgba(201,165,76,0) 100%)',
                'scrim-bottom': 'linear-gradient(180deg, rgba(10,9,7,0) 0%, rgba(10,9,7,0.92) 100%)',
                spotlight: 'radial-gradient(ellipse at 50% 0%, rgba(201,165,76,0.18) 0%, rgba(10,9,7,0) 60%)',
            },
            boxShadow: {
                'glow-gold': '0 0 0 1px rgba(201,165,76,0.35), 0 8px 32px rgba(201,165,76,0.22)',
                card: '0 16px 40px rgba(0,0,0,0.55)',
                modal: '0 24px 80px rgba(0,0,0,0.7)',
            },
            letterSpacing: { eyebrow: '.24em', label: '.16em', btn: '.12em' },
            maxWidth: { wrap: '1200px' },
        },
    },
    plugins: [],
};
