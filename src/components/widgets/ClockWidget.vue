<template>
	<div class="clock-widget">
		<div class="time-display">
			<div class="current-time">
				{{ currentTime }}
			</div>
			<div class="current-date">
				{{ currentDate }}
			</div>
			<div v-if="settings.showTimezone" class="timezone">
				{{ timezone }}
			</div>
		</div>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'ClockWidget',
	props: {
		widget: {
			type: Object,
			required: true,
		},
		settings: {
			type: Object,
			default: () => ({}),
		},
	},
	data() {
		return {
			currentTime: '',
			currentDate: '',
			timezone: '',
			timer: null,
		}
	},
	mounted() {
		this.updateTime()
		this.timer = setInterval(this.updateTime, 1000)
	},
	beforeDestroy() {
		if (this.timer) {
			clearInterval(this.timer)
		}
	},
	methods: {
		t,
		updateTime() {
			const now = new Date()
			const timeFormat = this.settings.format24h ? '24h' : '12h'
			
			const timeOptions = {
				hour: '2-digit',
				minute: '2-digit',
				hour12: timeFormat !== '24h',
			}

			if (this.settings.showSeconds) {
				timeOptions.second = '2-digit'
			}

			this.currentTime = now.toLocaleTimeString([], timeOptions)
			
			const dateOptions = {
				weekday: 'long',
				year: 'numeric',
				month: 'long',
				day: 'numeric',
			}
			
			this.currentDate = now.toLocaleDateString([], dateOptions)
			this.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone
		},
	},
}
</script>

<style scoped lang="scss">
.clock-widget {
	height: 100%;
	display: flex;
	align-items: center;
	justify-content: center;
}

.time-display {
	text-align: center;
}

.current-time {
	font-size: 2.5rem;
	font-weight: 300;
	color: var(--color-text-dark);
	margin-bottom: 8px;
	font-family: 'Roboto Mono', 'Monaco', 'Consolas', monospace;
}

.current-date {
	font-size: 1rem;
	color: var(--color-text-lighter);
	margin-bottom: 4px;
}

.timezone {
	font-size: 0.8rem;
	color: var(--color-text-lighter);
	opacity: 0.8;
}
</style>
