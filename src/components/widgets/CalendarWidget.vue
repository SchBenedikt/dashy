<template>
	<div class="calendar-widget" :class="[`view-${displayMode}`]">
		<div v-if="loading" class="loading">
			{{ t('dashy', 'Loading calendar events...') }}
		</div>
		<div v-else-if="events.length === 0" class="no-events">
			{{ t('dashy', 'No upcoming events') }}
		</div>
		
		<!-- Compact view for small widgets -->
		<div v-else-if="displayMode === 'compact'" class="events-compact">
			<div
				v-for="event in events.slice(0, maxEvents)"
				:key="event.id"
				class="event-compact"
			>
				<div class="event-time-compact">
					{{ formatCompactTime(event.start) }}
				</div>
				<div class="event-title-compact" :title="event.title">
					{{ event.title }}
				</div>
			</div>
		</div>
		
		<!-- List view for medium widgets -->
		<div v-else-if="displayMode === 'list'" class="events-list">
			<div
				v-for="event in events.slice(0, maxEvents)"
				:key="event.id"
				class="event-item"
			>
				<div class="event-time">
					{{ formatEventTime(event.start) }}
				</div>
				<div class="event-details">
					<div class="event-title">{{ event.title }}</div>
					<div v-if="event.location" class="event-location">
						{{ event.location }}
					</div>
				</div>
			</div>
		</div>
		
		<!-- Detailed view for large widgets -->
		<div v-else-if="displayMode === 'detailed'" class="events-detailed">
			<div
				v-for="event in events.slice(0, maxEvents)"
				:key="event.id"
				class="event-detailed"
			>
				<div class="event-header">
					<div class="event-date">
						{{ formatDetailedDate(event.start) }}
					</div>
					<div class="event-time-detailed">
						{{ formatDetailedTime(event.start) }}
					</div>
				</div>
				<div class="event-content">
					<div class="event-title-detailed">{{ event.title }}</div>
					<div v-if="event.location" class="event-location-detailed">
						<span class="location-icon">📍</span>
						{{ event.location }}
					</div>
					<div v-if="event.description" class="event-description">
						{{ truncateDescription(event.description) }}
					</div>
				</div>
			</div>
		</div>
		
		<!-- Calendar grid view for extra large widgets -->
		<div v-else-if="displayMode === 'calendar'" class="events-calendar">
			<div class="calendar-header">
				<h3>{{ currentMonthName }}</h3>
			</div>
			<div class="calendar-grid">
				<div class="weekday-header">
					<div v-for="day in weekDays" :key="day" class="weekday">
						{{ day }}
					</div>
				</div>
				<div class="calendar-days">
					<div
						v-for="day in calendarDays"
						:key="day.date"
						class="calendar-day"
						:class="{
							'other-month': !day.isCurrentMonth,
							'today': day.isToday,
							'has-events': day.events.length > 0
						}"
					>
						<div class="day-number">{{ day.dayNumber }}</div>
						<div v-if="day.events.length > 0" class="day-events">
							<div
								v-for="event in day.events.slice(0, 3)"
								:key="event.id"
								class="day-event"
								:title="event.title"
							>
								{{ event.title }}
							</div>
							<div v-if="day.events.length > 3" class="more-events">
								+{{ day.events.length - 3 }}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'CalendarWidget',
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
			events: [],
			loading: true,
		}
	},
	computed: {
		maxEvents() {
			return this.settings.maxEvents || 5
		},
		displayMode() {
			// Auto-determine display mode based on widget settings or size
			if (this.settings.displayMode) {
				return this.settings.displayMode
			}
			
			// Auto-detect based on widget dimensions (if available)
			const widgetWidth = this.widget.w || 4
			const widgetHeight = this.widget.h || 4
			
			if (widgetWidth >= 8 && widgetHeight >= 6) {
				return 'calendar'
			} else if (widgetWidth >= 6 || widgetHeight >= 5) {
				return 'detailed'
			} else if (widgetWidth >= 4 && widgetHeight >= 3) {
				return 'list'
			} else {
				return 'compact'
			}
		},
		currentMonthName() {
			return new Date().toLocaleDateString([], { month: 'long', year: 'numeric' })
		},
		weekDays() {
			const days = []
			const startOfWeek = new Date()
			startOfWeek.setDate(startOfWeek.getDate() - startOfWeek.getDay() + 1) // Monday
			
			for (let i = 0; i < 7; i++) {
				const day = new Date(startOfWeek)
				day.setDate(startOfWeek.getDate() + i)
				days.push(day.toLocaleDateString([], { weekday: 'short' }))
			}
			return days
		},
		calendarDays() {
			const today = new Date()
			const currentMonth = today.getMonth()
			const currentYear = today.getFullYear()
			
			// First day of the month
			const firstDay = new Date(currentYear, currentMonth, 1)
			// Last day of the month
			const lastDay = new Date(currentYear, currentMonth + 1, 0)
			
			// Start from Monday of the week containing the first day
			const startDate = new Date(firstDay)
			const startDayOfWeek = firstDay.getDay()
			const daysToSubtract = startDayOfWeek === 0 ? 6 : startDayOfWeek - 1
			startDate.setDate(firstDay.getDate() - daysToSubtract)
			
			const days = []
			const currentDate = new Date(startDate)
			
			// Generate 6 weeks (42 days)
			for (let i = 0; i < 42; i++) {
				const isCurrentMonth = currentDate.getMonth() === currentMonth
				const isToday = currentDate.toDateString() === today.toDateString()
				
				// Find events for this day
				const dayEvents = this.events.filter(event => {
					const eventDate = new Date(event.start)
					return eventDate.toDateString() === currentDate.toDateString()
				})
				
				days.push({
					date: currentDate.toISOString(),
					dayNumber: currentDate.getDate(),
					isCurrentMonth,
					isToday,
					events: dayEvents
				})
				
				currentDate.setDate(currentDate.getDate() + 1)
			}
			
			return days
		}
	},
	async mounted() {
		await this.loadEvents()
	},
	watch: {
		// Watch for widget dimension changes to trigger display mode recalculation
		'widget.w'() {
			this.$forceUpdate()
		},
		'widget.h'() {
			this.$forceUpdate()
		}
	},
	methods: {
		t,
		async loadEvents() {
			try {
				const response = await axios.get(generateUrl('/apps/dashy/api/calendar/events'))
				this.events = response.data.events.map(event => ({
					...event,
					start: new Date(event.start),
				}))
			} catch (error) {
				console.error('Failed to load calendar events:', error)
				// Show user-friendly message
				this.events = []
			} finally {
				this.loading = false
			}
		},
		formatEventTime(date) {
			const now = new Date()
			const diffHours = Math.floor((date - now) / (1000 * 60 * 60))
			const diffDays = Math.floor(diffHours / 24)

			if (diffDays === 0) {
				return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
			} else if (diffDays === 1) {
				return t('dashy', 'Tomorrow')
			} else if (diffDays < 7) {
				return date.toLocaleDateString([], { weekday: 'long' })
			} else {
				return date.toLocaleDateString([], { month: 'short', day: 'numeric' })
			}
		},
		formatCompactTime(date) {
			const now = new Date()
			const diffDays = Math.floor((date - now) / (1000 * 60 * 60 * 24))
			
			if (diffDays === 0) {
				return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
			} else if (diffDays === 1) {
				return 'Tom'
			} else if (diffDays < 7) {
				return date.toLocaleDateString([], { weekday: 'short' })
			} else {
				return date.toLocaleDateString([], { month: 'numeric', day: 'numeric' })
			}
		},
		formatDetailedDate(date) {
			const now = new Date()
			const diffDays = Math.floor((date - now) / (1000 * 60 * 60 * 24))
			
			if (diffDays === 0) {
				return t('dashy', 'Today')
			} else if (diffDays === 1) {
				return t('dashy', 'Tomorrow')
			} else if (diffDays < 7) {
				return date.toLocaleDateString([], { weekday: 'long' })
			} else {
				return date.toLocaleDateString([], { weekday: 'long', month: 'short', day: 'numeric' })
			}
		},
		formatDetailedTime(date) {
			return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
		},
		truncateDescription(description) {
			if (!description) return ''
			return description.length > 100 ? description.substring(0, 100) + '...' : description
		},
	},
}
</script>

<style scoped lang="scss">
.calendar-widget {
	height: 100%;
	overflow: hidden;
}

.loading,
.no-events {
	display: flex;
	align-items: center;
	justify-content: center;
	height: 100%;
	color: var(--color-text-lighter);
	font-style: italic;
}

// Compact view (small widgets)
.view-compact .events-compact {
	display: flex;
	flex-direction: column;
	gap: 4px;
	overflow-y: auto;
	height: 100%;
}

.event-compact {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 4px 6px;
	border-radius: 4px;
	background-color: var(--color-background-hover);
	border-left: 2px solid var(--color-primary);
	min-height: 24px;
}

.event-time-compact {
	font-size: 10px;
	font-weight: 600;
	color: var(--color-primary);
	min-width: 35px;
	flex-shrink: 0;
}

.event-title-compact {
	font-size: 11px;
	flex: 1;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}

// List view (medium widgets)
.view-list .events-list {
	display: flex;
	flex-direction: column;
	gap: 8px;
	overflow-y: auto;
	height: 100%;
}

.event-item {
	display: flex;
	gap: 12px;
	padding: 8px;
	border-radius: 6px;
	background-color: var(--color-background-hover);
	border-left: 3px solid var(--color-primary);
}

.event-time {
	flex-shrink: 0;
	font-size: 12px;
	font-weight: 600;
	color: var(--color-primary);
	min-width: 60px;
}

.event-details {
	flex: 1;
	min-width: 0;
}

.event-title {
	font-weight: 500;
	margin-bottom: 2px;
	word-wrap: break-word;
}

.event-location {
	font-size: 12px;
	color: var(--color-text-lighter);
}

// Detailed view (large widgets)
.view-detailed .events-detailed {
	display: flex;
	flex-direction: column;
	gap: 12px;
	overflow-y: auto;
	height: 100%;
}

.event-detailed {
	padding: 12px;
	border-radius: 8px;
	background-color: var(--color-background-hover);
	border-left: 4px solid var(--color-primary);
}

.event-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 8px;
}

.event-date {
	font-size: 13px;
	font-weight: 600;
	color: var(--color-primary);
}

.event-time-detailed {
	font-size: 12px;
	color: var(--color-text-lighter);
}

.event-content {
	display: flex;
	flex-direction: column;
	gap: 4px;
}

.event-title-detailed {
	font-weight: 600;
	font-size: 14px;
}

.event-location-detailed {
	display: flex;
	align-items: center;
	gap: 4px;
	font-size: 12px;
	color: var(--color-text-lighter);
}

.location-icon {
	font-size: 10px;
}

.event-description {
	font-size: 12px;
	color: var(--color-text-lighter);
	line-height: 1.4;
}

// Calendar view (extra large widgets)
.view-calendar .events-calendar {
	height: 100%;
	display: flex;
	flex-direction: column;
}

.calendar-header {
	text-align: center;
	padding: 8px 0;
	border-bottom: 1px solid var(--color-border);
	margin-bottom: 8px;

	h3 {
		margin: 0;
		font-size: 16px;
		font-weight: 600;
	}
}

.calendar-grid {
	flex: 1;
	display: flex;
	flex-direction: column;
	overflow: hidden;
}

.weekday-header {
	display: grid;
	grid-template-columns: repeat(7, 1fr);
	gap: 1px;
	margin-bottom: 4px;
}

.weekday {
	text-align: center;
	font-size: 11px;
	font-weight: 600;
	color: var(--color-text-lighter);
	padding: 4px 2px;
}

.calendar-days {
	display: grid;
	grid-template-columns: repeat(7, 1fr);
	grid-template-rows: repeat(6, 1fr);
	gap: 1px;
	flex: 1;
	overflow: hidden;
}

.calendar-day {
	background-color: var(--color-main-background);
	border: 1px solid var(--color-border);
	padding: 2px;
	position: relative;
	display: flex;
	flex-direction: column;
	min-height: 40px;

	&.other-month {
		opacity: 0.4;
	}

	&.today {
		background-color: var(--color-primary-light);
		border-color: var(--color-primary);
	}

	&.has-events {
		background-color: var(--color-background-hover);
	}
}

.day-number {
	font-size: 11px;
	font-weight: 600;
	text-align: center;
	margin-bottom: 2px;
}

.day-events {
	flex: 1;
	display: flex;
	flex-direction: column;
	gap: 1px;
	overflow: hidden;
}

.day-event {
	font-size: 9px;
	padding: 1px 3px;
	background-color: var(--color-primary);
	color: white;
	border-radius: 2px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}

.more-events {
	font-size: 8px;
	color: var(--color-text-lighter);
	text-align: center;
	padding: 1px;
}
</style>
