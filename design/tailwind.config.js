/** AR Rahman International Cadet Madrasa — Tailwind tokens.
 * Colors reference CSS variables from madrasa-theme.css so light/dark switch via [data-theme="dark"].
 * Use: <html data-theme="light|dark" data-lang="bn|en"> */
module.exports = {
  darkMode: ['selector', '[data-theme="dark"]'],
  content: ['./**/*.{html,js,jsx,ts,tsx,php,blade.php}'],
  theme: {
    extend: {
      colors: {
        green: { 50: 'var(--green-50)', 100: 'var(--green-100)', 500: 'var(--green-500)', 700: 'var(--green-700)', 900: 'var(--green-900)' },
        red: { 50: 'var(--red-50)', 600: 'var(--red-600)', 700: 'var(--red-700)' },
        navy: { 50: 'var(--navy-50)', 800: 'var(--navy-800)' },
        yellow: { 50: 'var(--yellow-50)', 400: 'var(--yellow-400)' },
        bg: 'var(--bg)', surface: { DEFAULT: 'var(--surface)', 2: 'var(--surface-2)' },
        ink: 'var(--ink)', muted: 'var(--muted)', faint: 'var(--faint)',
        line: { DEFAULT: 'var(--line)', strong: 'var(--line-strong)' },
        heading: 'var(--heading)', brand: 'var(--brand)', accent: 'var(--accent)',
        success: { DEFAULT: 'var(--success)', bg: 'var(--success-bg)' },
        warn: { DEFAULT: 'var(--warn)', bg: 'var(--warn-bg)' },
        danger: { DEFAULT: 'var(--danger)', bg: 'var(--danger-bg)' },
        info: { DEFAULT: 'var(--info)', bg: 'var(--info-bg)' },
      },
      fontFamily: {
        body: ['Noto Sans Bengali', 'Hind Siliguri', 'Poppins', 'sans-serif'],
        head: ['Noto Serif Bengali', 'Noto Sans Bengali', 'serif'],
        en: ['Poppins', 'sans-serif'],
        ar: ['Amiri', 'serif'],
      },
      fontSize: {
        xs: ['13px', '1.6'], sm: ['14px', '1.65'], base: ['16px', '1.7'], lg: ['18px', '1.7'],
        xl: ['20px', '1.6'], '2xl': ['24px', '1.45'], '3xl': ['30px', '1.35'], '4xl': ['38px', '1.3'], '5xl': ['48px', '1.25'], '6xl': ['56px', '1.2'],
      },
      borderRadius: { sm: '8px', md: '12px', xl: '16px', '2xl': '20px' },
      boxShadow: { sm: 'var(--shadow-sm)', md: 'var(--shadow-md)', lg: 'var(--shadow-lg)' },
      spacing: { 11: '44px', 13: '52px' },
      screens: { sm: '480px', md: '768px', lg: '1024px', xl: '1280px', '2xl': '1440px' },
      maxWidth: { page: '1240px' },
    },
  },
};
