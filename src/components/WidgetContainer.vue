<template>
	<div v-if="widget" class="widget-container">
		<div class="widget-header">
			<h3>{{ widget.title || 'Untitled Widget' }}</h3>
			<div class="widget-actions">
				<NcButton
					type="tertiary"
					:aria-label="t('dashy', 'Widget settings')"
					@click="openWidgetSettings"
				>
					<template #icon>
						<CogIcon :size="16" />
					</template>
				</NcButton>
				<NcButton
					type="tertiary"
					:aria-label="t('dashy', 'Remove widget')"
					@click="$emit('remove', widget.id)"
				>
					<template #icon>
						<CloseIcon :size="16" />
					</template>
				</NcButton>
			</div>
		</div>
		<div class="widget-content">
			<component
				:is="widgetComponent"
				:widget="widget"
				:settings="widget.settings || {}"
				@update-settings="updateSettings"
			/>
		</div>
		<div class="widget-settings">
			<component
				:is="settingsComponent"
				ref="settingsComponent"
				:settings="widget.settings || {}"
				@update="updateSettings"
			/>
		</div>
	</div>
	<div v-else class="widget-container widget-error">
		<div class="widget-header">
			<h3>{{ t('dashy', 'Widget Error') }}</h3>
			<div class="widget-actions">
				<NcButton
					type="tertiary"
					:aria-label="t('dashy', 'Remove widget')"
					@click="$emit('remove', 'unknown')"
				>
					<template #icon>
						<CloseIcon :size="16" />
					</template>
				</NcButton>
			</div>
		</div>
		<div class="widget-content">
			<p>{{ t('dashy', 'This widget could not be loaded.') }}</p>
		</div>
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import CogIcon from 'vue-material-design-icons/Cog.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
import { translate as t } from '@nextcloud/l10n'

// Widget components
import CalendarWidget from './widgets/CalendarWidget.vue'
import TodoWidget from './widgets/TodoWidget.vue'
import ClockWidget from './widgets/ClockWidget.vue'
import WeatherWidget from './widgets/WeatherWidget.vue'
import ContactsWidget from './widgets/ContactsWidget.vue'
import FilesWidget from './widgets/FilesWidget.vue'
import NotesWidget from './widgets/NotesWidget.vue'
import BookmarksWidget from './widgets/BookmarksWidget.vue'

// Settings components
import CalendarSettings from './widgets/CalendarSettings.vue'
import TodoSettings from './widgets/TodoSettings.vue'
import ClockSettings from './widgets/ClockSettings.vue'
import WeatherSettings from './widgets/WeatherSettings.vue'
import ContactsSettings from './widgets/ContactsSettings.vue'
import FilesSettings from './widgets/FilesSettings.vue'
import NotesSettings from './widgets/NotesSettings.vue'
import BookmarksSettings from './widgets/BookmarksSettings.vue'

export default {
	name: 'WidgetContainer',
	components: {
		NcButton,
		CogIcon,
		CloseIcon,
	},
	props: {
		widget: {
			type: Object,
			required: false,
			default: null,
		},
	},
	computed: {
		widgetComponent() {
			if (!this.widget || !this.widget.type) return null
			const components = {
				calendar: CalendarWidget,
				todo: TodoWidget,
				clock: ClockWidget,
				weather: WeatherWidget,
				contacts: ContactsWidget,
				files: FilesWidget,
				notes: NotesWidget,
				bookmarks: BookmarksWidget,
			}
			return components[this.widget.type] || null
		},
		settingsComponent() {
			if (!this.widget || !this.widget.type) return null
			const components = {
				calendar: CalendarSettings,
				todo: TodoSettings,
				clock: ClockSettings,
				weather: WeatherSettings,
				contacts: ContactsSettings,
				files: FilesSettings,
				notes: NotesSettings,
				bookmarks: BookmarksSettings,
			}
			return components[this.widget.type] || null
		},
	},
	methods: {
		t,
		updateSettings(newSettings) {
			if (this.widget && this.widget.id) {
				this.$emit('update-settings', this.widget.id, newSettings)
			}
		},
		openWidgetSettings() {
			// Try to call openSettings method on the settings component
			const settingsComponent = this.$refs.settingsComponent
			console.log('Opening widget settings for:', this.widget.type, settingsComponent)
			if (settingsComponent && settingsComponent.openSettings) {
				settingsComponent.openSettings()
			} else {
				console.warn('Settings component not found or openSettings method missing:', settingsComponent)
			}
		},
	},
}
</script>

<style scoped lang="scss">
.widget-container {
	height: 100%;
	display: flex;
	flex-direction: column;
	position: relative;
}

.widget-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 12px 16px;
	border-bottom: 1px solid var(--color-border);
	background-color: var(--color-background-hover);
	border-radius: 8px 8px 0 0;

	h3 {
		margin: 0;
		font-size: 14px;
		font-weight: 600;
		color: var(--color-text-dark);
	}
}

.widget-actions {
	display: flex;
	gap: 4px;
}

.widget-content {
	flex: 1;
	padding: 16px;
	overflow: auto;
}

.widget-settings {
	display: none; /* Settings components are hidden, triggered from header */
}
</style>
