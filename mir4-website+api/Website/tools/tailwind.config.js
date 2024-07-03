/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './src/pages/**/*.{js,ts,jsx,tsx}',
    './src/components/**/*.{js,ts,jsx,tsx}',
    './src/app/**/*.{js,ts,jsx,tsx}',
  ],
  theme: {
    extend: {
      screens: {
        '3xl': '2200px',
      },
      fontFamily: {
        main: ['var(--font-main)'],
        ptSerif: ['var(--font-ptSerif)'],
      },
      backgroundImage: {
        'inventory-gradient':
          'radial-gradient(97.57% 210.75% at 0.9% 2.98%, rgba(39, 32, 67, 0.4) 0%, rgba(39, 32, 67, 0) 100%)',
        'input-top-to-bottom':
          'linear-gradient(180deg, #272043 0%, rgba(39, 32, 67, 0) 100%)',
        'input-bottom-to-top':
          'linear-gradient(180deg, rgba(39, 32, 67, 0) 0%, #272043 100%)',
        'primary-radial':
          'radial-gradient(52.27% 52.27% at 50% 50%, rgba(162, 122, 195, 0.15) 0%, rgba(186, 111, 248, 0.1) 46.87%, rgba(44, 37, 66, 0) 100%);',
        'secondary-radial':
          'radial-gradient(52.27% 52.27% at 50% 50%, rgba(255, 255, 255, 0.15) 0%, rgba(126, 115, 173, 0.1) 0.01%, rgba(126, 115, 173, 0) 100%);',
        'default-frame': 'linear-gradient(180deg, #1A1730 0%, #272043 100%);',
        'legendary-frame': 'linear-gradient(180deg, #693D0C 0%, #C6B214 100%);',
        'epic-frame': 'linear-gradient(180deg, #2F0D16 0%, #A42522 100%);',
        'rare-frame': 'linear-gradient(180deg, #152333 0%, #295491 100%);',
        'uncommon-frame': 'linear-gradient(180deg, #153627 0%, #368D6E 100%);',
        'common-frame': 'linear-gradient(180deg, #2B3136 0%, #677479 100%);',
      },
      colors: {
        primary: {
          100: '#DDD4FF',
          200: '#B8B0DB',
          400: '#7E73AD',
          450: '#635189',
          500: '#4B406A',
          600: '#2C2542',
          700: '#272043',
          800: '#1A1830',
          900: '#1D1822',
        },
        csred: {
          400: '#ed5f69',
        },
        success: {
          400: '#62CA63',
        },
      },
      keyframes: {
        contentShow: {
          from: { opacity: 0 },
          to: { opacity: 1 },
        },
        contentHide: {
          from: { opacity: 1 },
          to: { opacity: 0 },
        },
        showPopover: {
          from: { opacity: 0, transform: 'translateY(20px)' },
          to: { opacity: 1, transform: 'translateY(0px)' },
        },
        hidePopover: {
          from: { opacity: 1, transform: 'translateY(0px)' },
          to: { opacity: 0, transform: 'translateY(20px)' },
        },
        vibrate: {
          '0%': { transform: 'rotate(30deg)' },
          '3%': { transform: 'rotate(-30deg)' },
          '6%': { transform: 'rotate(30deg)' },
          '9%': { transform: 'rotate(-30deg)' },
          '10%': { transform: 'rotate(30deg)' },
          '15%': { transform: 'rotate(0deg)' },
          '35%': { transform: 'rotate(0deg)' },
          '40%': { transform: 'rotate(30deg)' },
          '43%': { transform: 'rotate(-30deg)' },
          '46%': { transform: 'rotate(30deg)' },
          '49%': { transform: 'rotate(-30deg)' },
          '52%': { transform: 'rotate(30deg)' },
          '55%': { transform: 'rotate(0deg)' },
          '95%': { transform: 'rotate(0deg)' },
        },
        rotate: {
          '0%': { transform: 'rotate(0deg)' },
          '100%': { transform: 'rotate(720deg)' },
        },
        rotateInvert: {
          '0%': { transform: 'rotate(0deg)' },
          '100%': { transform: 'rotate(-720deg)' },
        },
      },
      animation: {
        overlayShow: 'overlayShow 150ms cubic-bezier(0.16, 1, 0.3, 1)',
        contentShow: 'contentShow 200ms cubic-bezier(0.16, 1, 0.3, 1)',
        contentHide: 'contentHide 300ms cubic-bezier(0.16, 1, 0.3, 1)',
        showPopover: 'showPopover 150ms cubic-bezier(0.16, 1, 0.3, 1)',
        hidePopover: 'hidePopover 300ms cubic-bezier(0.16, 1, 0.3, 1)',
        vibrate: 'vibrate 4s cubic-bezier(0.16, 1, 0.3, 1) infinite',
        rotate: 'rotate 4s ease-in-out forwards',
        rotateInvert: 'rotateInvert 4s ease-in-out forwards',
      },
    },
  },
  plugins: [],
}
