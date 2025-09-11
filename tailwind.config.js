/** @type {import('tailwindcss').Config} */
/** @type {import('tailwindcss').Config} */
export default {
	content: ["./admin/**/*.{php,html,js}", "./src/admin/**/*.{ts,tsx,js,jsx}"],
	theme: {
		fontSize: {
			'xs': ['0.75rem', '1.125rem'], // 12px, 18px
			'sm': ['0.875rem', '1.3125rem'], // 14px, 21px
			'base': ['1rem', '1.5rem'], // 16px, 24px
			'lg': ['1.125rem', '1.8rem'], // 18px, 28.8px
			'19.2': ['1.2rem', '1.8rem'], // 19.2px, 28.8px
			'xl': ['1.25rem', '1.875rem'], // 20px, 30px
			'2xl': ['1.5rem', '2.25rem'], // 24px, 36px
			'3xl': ['1.5rem', '2.4rem'], // 24px, 38.4px
			'6xl': ['2rem', '3.2rem'], // 32px, 51.2px
			'7xl': ['2rem', '3rem'], // 32px, 48px
			'8xl': ['3rem', '3rem'], // 48px, 48px
			'9xl': ['4rem', '6rem'], // 64px, 96px
		},
		colors: {
			'primary':'#3017A1',
			'secondary':'#664bd6',
			'checked':'#0e65e9',
			'black':'#000000',
			'light-red':'#CA3521',
			'hover-red':'#FFEDEB',
			'grey':'#6D6D78',
			'light':'#CFCFD3',
			'light-grey':'#eeeeee',
			'light-blue':'#0F62FE',
			'royal-purple':'#654BD5',
			'white':'#ffffff',
		},
		extend: {
			fontFamily: {
				poppins: ['Poppins'],
			},
			colors: {
				'pds-color-foreground-default': 'var(--pds-color-foreground-default)',
				'pds-color-text-default': 'var(--pds-color-text-default)',
				'pds-color-text-default-secondary': 'var(--pds-color-text-default-secondary)',
				'pds-color-link-active': 'var(--pds-color-link-active)',
				'pds-color-link-cta-active': 'var(--pds-color-link-cta-active)',
				'pds-color-link-cta-default': 'var(--pds-color-link-cta-default)',
				'pds-color-link-cta-hover': 'var(--pds-color-link-cta-hover)',
				'pds-color-link-default': 'var(--pds-color-link-default)',
				'pds-color-link-hover': 'var(--pds-color-link-hover)',
				'pds-color-link-visited': 'var(--pds-color-link-visited)',
				'pds-color-button-brand-background-active': 'var(--pds-color-button-brand-background-active)',
				'pds-color-button-brand-background-default': 'var(--pds-color-button-brand-background-default)',
				'pds-color-button-brand-background-hover': 'var(--pds-color-button-brand-background-hover)',
				'pds-color-button-brand-border-active': 'var(--pds-color-button-brand-border-active)',
				'pds-color-button-brand-border-default': 'var(--pds-color-button-brand-border-default)',
				'pds-color-button-brand-border-hover': 'var(--pds-color-button-brand-border-hover)',
				'pds-color-button-brand-foreground-active': 'var(--pds-color-button-brand-foreground-active)',
				'pds-color-button-brand-foreground-default': 'var(--pds-color-button-brand-foreground-default)',
				'pds-color-button-brand-foreground-hover': 'var(--pds-color-button-brand-foreground-hover)',
				'pds-color-button-brand-secondary-background-active': 'var(--pds-color-button-brand-secondary-background-active)',
				'pds-color-button-brand-secondary-background-default': 'var(--pds-color-button-brand-secondary-background-default)',
				'pds-color-button-brand-secondary-background-hover': 'var(--pds-color-button-brand-secondary-background-hover)',
				'pds-color-button-brand-secondary-border-active': 'var(--pds-color-button-brand-secondary-border-active)',
				'pds-color-button-brand-secondary-border-default': 'var(--pds-color-button-brand-secondary-border-default)',
				'pds-color-button-brand-secondary-border-hover': 'var(--pds-color-button-brand-secondary-border-hover)',
				'pds-color-button-brand-secondary-foreground-active': 'var(--pds-color-button-brand-secondary-foreground-active)',
				'pds-color-button-brand-secondary-foreground-default': 'var(--pds-color-button-brand-secondary-foreground-default)',
				'pds-color-button-brand-secondary-foreground-hover': 'var(--pds-color-button-brand-secondary-foreground-hover)',
				'pds-color-button-critical-background-active': 'var(--pds-color-button-critical-background-active)',
				'pds-color-button-critical-background-default': 'var(--pds-color-button-critical-background-default)',
				'pds-color-button-critical-background-hover': 'var(--pds-color-button-critical-background-hover)',
				'pds-color-button-critical-border-active': 'var(--pds-color-button-critical-border-active)',
				'pds-color-button-critical-border-default': 'var(--pds-color-button-critical-border-default)',
				'pds-color-button-critical-border-hover': 'var(--pds-color-button-critical-border-hover)',
				'pds-color-button-critical-foreground-active': 'var(--pds-color-button-critical-foreground-active)',
				'pds-color-button-critical-foreground-default': 'var(--pds-color-button-critical-foreground-default)',
				'pds-color-button-critical-foreground-hover': 'var(--pds-color-button-critical-foreground-hover)',
				'pds-color-button-navbar-foreground-active': 'var(--pds-color-button-navbar-foreground-active)',
				'pds-color-button-navbar-foreground-default': 'var(--pds-color-button-navbar-foreground-default)',
				'pds-color-button-navbar-foreground-hover': 'var(--pds-color-button-navbar-foreground-hover)',
				'pds-color-button-primary-background-active': 'var(--pds-color-button-primary-background-active)',
				'pds-color-button-primary-background-default': 'var(--pds-color-button-primary-background-default)',
				'pds-color-button-primary-background-hover': 'var(--pds-color-button-primary-background-hover)',
				'pds-color-button-primary-border-active': 'var(--pds-color-button-primary-border-active)',
				'pds-color-button-primary-border-default': 'var(--pds-color-button-primary-border-default)',
				'pds-color-button-primary-border-hover': 'var(--pds-color-button-primary-border-hover)',
				'pds-color-button-primary-foreground-active': 'var(--pds-color-button-primary-foreground-active)',
				'pds-color-button-primary-foreground-default': 'var(--pds-color-button-primary-foreground-default)',
				'pds-color-button-primary-foreground-hover': 'var(--pds-color-button-primary-foreground-hover)',
				'pds-color-button-secondary-background-active': 'var(--pds-color-button-secondary-background-active)',
				'pds-color-button-secondary-background-default': 'var(--pds-color-button-secondary-background-default)',
				'pds-color-button-secondary-background-hover': 'var(--pds-color-button-secondary-background-hover)',
				'pds-color-button-secondary-border-active': 'var(--pds-color-button-secondary-border-active)',
				'pds-color-button-secondary-border-default': 'var(--pds-color-button-secondary-border-default)',
				'pds-color-button-secondary-border-hover': 'var(--pds-color-button-secondary-border-hover)',
				'pds-color-button-secondary-foreground-active': 'var(--pds-color-button-secondary-foreground-active)',
				'pds-color-button-secondary-foreground-default': 'var(--pds-color-button-secondary-foreground-default)',
				'pds-color-button-secondary-foreground-hover': 'var(--pds-color-button-secondary-foreground-hover)',
				'pds-color-button-subtle-background-default': 'var(--pds-color-button-subtle-background-default)',
				'pds-color-button-subtle-border-default': 'var(--pds-color-button-subtle-border-default)',
				'pds-color-button-subtle-foreground-active': 'var(--pds-color-button-subtle-foreground-active)',
				'pds-color-button-subtle-foreground-default': 'var(--pds-color-button-subtle-foreground-default)',
				'pds-color-button-subtle-foreground-hover': 'var(--pds-color-button-subtle-foreground-hover)',
			},
			screens: {
				'2xl': '1440px', // Custom breakpoint for 1440px
				'3xl': '1600px', // Custom breakpoint for 1600px
				'4xl': '1920px', // Custom breakpoint for 1920px
			},
			container: {
				center:true,
				padding: '2.5rem',
				screens: {
					sm: '100%',
					md: '100%',
					lg: '1024px',
					xl: '1160px',
					'2xl': '1280px',
					'3xl': '1280px',
					'4xl': '1280px',
				},
			},
		},
	},
	corePlugins: {
		preflight: false,
	},
	plugins: [],
}
