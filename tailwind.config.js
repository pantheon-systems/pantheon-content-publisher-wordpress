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
	plugins: [],
}
