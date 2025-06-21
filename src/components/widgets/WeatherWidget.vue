<template>
	<div class="weather-widget">
		<div v-if="loading" class="loading">
			{{ t('dashy', 'Loading weather...') }}
		</div>
		<div v-else-if="error" class="error">
			{{ error }}
		</div>
		<div v-else-if="weather" class="weather-content">
			<div class="current-weather">
				<div class="weather-icon">
					<component :is="weatherIcon" :size="48" />
				</div>
				<div class="weather-info">
					<div class="temperature">
						{{ Math.round(weather.temperature) }}°{{ settings.unit || 'C' }}
					</div>
					<div class="description">
						{{ weather.description }}
					</div>
					<div class="location">
						{{ weather.location }}
					</div>
				</div>
			</div>
			<div v-if="settings.showDetails" class="weather-details">
				<div class="detail-item">
					<span class="label">{{ t('dashy', 'Feels like') }}:</span>
					<span>{{ Math.round(weather.feelsLike) }}°{{ settings.unit || 'C' }}</span>
				</div>
				<div class="detail-item">
					<span class="label">{{ t('dashy', 'Humidity') }}:</span>
					<span>{{ weather.humidity }}%</span>
				</div>
				<div class="detail-item">
					<span class="label">{{ t('dashy', 'Wind') }}:</span>
					<span>{{ weather.windSpeed }} km/h</span>
				</div>
			</div>
		</div>
		<div v-else class="no-data">
			{{ t('dashy', 'No weather data available') }}
		</div>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import WeatherSunnyIcon from 'vue-material-design-icons/WeatherSunny.vue'
import WeatherCloudyIcon from 'vue-material-design-icons/WeatherCloudy.vue'
import WeatherPartlyCloudyIcon from 'vue-material-design-icons/WeatherPartlyCloudy.vue'
import WeatherPouring from 'vue-material-design-icons/WeatherPouring.vue'
import WeatherSnowy from 'vue-material-design-icons/WeatherSnowy.vue'

export default {
	name: 'WeatherWidget',
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
			weather: null,
			loading: true,
			error: null,
			refreshInterval: null,
		}
	},
	computed: {
		weatherIcon() {
			if (!this.weather) return WeatherSunnyIcon

			const condition = this.weather.condition.toLowerCase()
			if (condition.includes('rain')) return WeatherPouring
			if (condition.includes('snow')) return WeatherSnowy
			if (condition.includes('cloud')) return WeatherCloudyIcon
			if (condition.includes('partly')) return WeatherPartlyCloudyIcon
			return WeatherSunnyIcon
		},
	},
	async mounted() {
		await this.loadWeather()
		// Refresh weather every 10 minutes
		this.refreshInterval = setInterval(this.loadWeather, 600000)
	},
	beforeDestroy() {
		if (this.refreshInterval) {
			clearInterval(this.refreshInterval)
		}
	},
	methods: {
		t,
		async loadWeather() {
			try {
				this.loading = true
				this.error = null

				const params = new URLSearchParams()
				if (this.settings.location) {
					params.append('location', this.settings.location)
				}
				if (this.settings.unit) {
					params.append('unit', this.settings.unit)
				}

				const response = await axios.get(generateUrl('/apps/dashy/api/weather') + '?' + params.toString())
				
				if (response.data.data.weather) {
					this.weather = response.data.data.weather
				} else if (response.data.data.error) {
					this.error = response.data.data.error
				}
			} catch (error) {
				console.error('Failed to load weather:', error)
				this.error = t('dashy', 'Failed to load weather data')
			} finally {
				this.loading = false
			}
		},
	},
	watch: {
		settings: {
			handler() {
				// Reload weather when settings change
				this.loadWeather()
			},
			deep: true,
		},
	},
}
</script>

<style scoped lang="scss">
.weather-widget {
	height: 100%;
}

.loading,
.error,
.no-data {
	display: flex;
	align-items: center;
	justify-content: center;
	height: 100%;
	color: var(--color-text-lighter);
	font-style: italic;
}

.error {
	color: var(--color-error);
}

.weather-content {
	height: 100%;
	display: flex;
	flex-direction: column;
	gap: 16px;
}

.current-weather {
	display: flex;
	align-items: center;
	gap: 16px;
}

.weather-icon {
	flex-shrink: 0;
	color: var(--color-primary);
}

.weather-info {
	flex: 1;
}

.temperature {
	font-size: 2rem;
	font-weight: 300;
	color: var(--color-text-dark);
	margin-bottom: 4px;
}

.description {
	font-size: 1rem;
	color: var(--color-text-dark);
	margin-bottom: 2px;
}

.location {
	font-size: 0.9rem;
	color: var(--color-text-lighter);
}

.weather-details {
	display: flex;
	flex-direction: column;
	gap: 8px;
	padding-top: 8px;
	border-top: 1px solid var(--color-border);
}

.detail-item {
	display: flex;
	justify-content: space-between;
	font-size: 0.9rem;

	.label {
		color: var(--color-text-lighter);
	}
}
</style>
