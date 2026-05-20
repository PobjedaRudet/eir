const configuredBase = (import.meta.env.VITE_APP_BASE ?? '').replace(/\/$/, '')

function isCurrentPathWithinBase(base) {
	if (!base || typeof window === 'undefined') {
		return false
	}

	const { pathname } = window.location

	return pathname === base || pathname.startsWith(`${base}/`)
}

export const BASE = isCurrentPathWithinBase(configuredBase) ? configuredBase : ''
