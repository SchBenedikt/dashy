<template>
	<NcAppContent>
		<div id="dashy">
			<div class="dashboard-header">
				<h1>{{ t('dashy', 'Personal Dashboard') }}</h1>
				<NcButton type="primary" @click="showAddWidget = true">
					<template #icon>
						<PlusIcon />
					</template>
					{{ t('dashy', 'Add Widget') }}
				</NcButton>
			</div>

			<div class="dashboard-container">
				<grid-layout
					:layout="layout"
					:col-num="12"
					:row-height="60"
					:is-draggable="true"
					:is-resizable="true"
					:is-mirrored="false"
					:vertical-compact="true"
					:margin="[10, 10]"
					:use-css-transforms="true"
					@layout-updated="saveLayout"
				>
					<grid-item
						v-for="item in layout"
						:key="item.i"
						:x="item.x"
						:y="item.y"
						:w="item.w"
						:h="item.h"
						:i="item.i"
					>
						<widget-container
							:widget="getWidgetWithLayout(item.i, item)"
							@remove="removeWidget"
							@update-settings="updateWidgetSettings"
						/>
					</grid-item>
				</grid-layout>
			</div>

			<!-- Add Widget Modal -->
			<div v-if="showAddWidget" class="add-widget-overlay" @click="closeAddWidget">
				<div class="add-widget-modal" @click.stop>
					<div class="modal-header">
						<h3>{{ t('dashy', 'Add Widget') }}</h3>
						<button class="close-btn" type="button" title="Close" @click="closeAddWidget">✕</button>
					</div>
					<div class="modal-content">
						<div class="widget-types">
							<div
								v-for="widgetType in availableWidgets"
								:key="widgetType.type"
								class="widget-type-card"
								@click="addWidget(widgetType)"
							>
								<component :is="widgetType.icon" :size="32" />
								<h3>{{ widgetType.name }}</h3>
								<p>{{ widgetType.description }}</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</NcAppContent>
</template>

<script>
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import { GridLayout, GridItem } from 'vue-grid-layout'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'

import WidgetContainer from './components/WidgetContainer.vue'
import CalendarIcon from 'vue-material-design-icons/Calendar.vue'
import CheckboxMarkedCircleIcon from 'vue-material-design-icons/CheckboxMarkedCircle.vue'
import ClockIcon from 'vue-material-design-icons/Clock.vue'
import WeatherPartlyCloudyIcon from 'vue-material-design-icons/WeatherPartlyCloudy.vue'
import AccountMultipleIcon from 'vue-material-design-icons/AccountMultiple.vue'
import FolderIcon from 'vue-material-design-icons/Folder.vue'
import NoteTextIcon from 'vue-material-design-icons/NoteText.vue'
import BookmarkIcon from 'vue-material-design-icons/Bookmark.vue'

export default {
	name: 'App',
	components: {
		NcAppContent,
		NcButton,
		PlusIcon,
		GridLayout,
		GridItem,
		WidgetContainer,
	},
	data() {
		return {
			layout: [],
			widgets: {},
			showAddWidget: false,
			availableWidgets: [
				{
					type: 'calendar',
					name: t('dashy', 'Calendar'),
					description: t('dashy', 'View your upcoming events'),
					icon: CalendarIcon,
				},
				{
					type: 'todo',
					name: t('dashy', 'Tasks'),
					description: t('dashy', 'Manage your tasks'),
					icon: CheckboxMarkedCircleIcon,
				},
				{
					type: 'clock',
					name: t('dashy', 'Clock'),
					description: t('dashy', 'Display current time'),
					icon: ClockIcon,
				},
				{
					type: 'weather',
					name: t('dashy', 'Weather'),
					description: t('dashy', 'Current weather information'),
					icon: WeatherPartlyCloudyIcon,
				},
				{
					type: 'contacts',
					name: t('dashy', 'Contacts'),
					description: t('dashy', 'Quick access to your contacts'),
					icon: AccountMultipleIcon,
				},
				{
					type: 'files',
					name: t('dashy', 'Files'),
					description: t('dashy', 'Recent and favorite files'),
					icon: FolderIcon,
				},
				{
					type: 'notes',
					name: t('dashy', 'Notes'),
					description: t('dashy', 'Quick notes and reminders'),
					icon: NoteTextIcon,
				},
				{
					type: 'bookmarks',
					name: t('dashy', 'Bookmarks'),
					description: t('dashy', 'Your saved bookmarks'),
					icon: BookmarkIcon,
				},
			],
		}
	},
	async mounted() {
		await this.loadDashboard()
	},
	methods: {
		t,
		async loadDashboard() {
			try {
				const response = await axios.get(generateUrl('/apps/dashy/api/dashboard'))
				console.log('Dashboard loaded:', response.data)
				this.layout = response.data.layout || []
				this.widgets = response.data.widgets || {}
				console.log('Layout:', this.layout)
				console.log('Widgets:', this.widgets)
			} catch (error) {
				console.error('Failed to load dashboard:', error)
				// Initialize with empty data on error
				this.layout = []
				this.widgets = {}
			}
		},
		async saveLayout(newLayout) {
			this.layout = newLayout
			await this.saveDashboard()
		},
		async saveDashboard() {
			try {
				console.log('Saving dashboard:', { layout: this.layout, widgets: this.widgets })
				const response = await axios.post(generateUrl('/apps/dashy/api/dashboard'), {
					layout: this.layout,
					widgets: this.widgets,
				})
				console.log('Dashboard saved:', response.data)
			} catch (error) {
				console.error('Failed to save dashboard:', error)
			}
		},
		closeAddWidget() {
			this.showAddWidget = false
		},
		addWidget(widgetType) {
			const widgetId = 'widget_' + Date.now()
			const newWidget = {
				id: widgetId,
				type: widgetType.type,
				title: widgetType.name,
				settings: {},
			}

			// Add to widgets - create new object to ensure reactivity
			this.widgets = {
				...this.widgets,
				[widgetId]: newWidget
			}

			// Add to layout
			this.layout.push({
				i: widgetId,
				x: 0,
				y: 0,
				w: 4,
				h: 4,
			})

			this.showAddWidget = false
			this.saveDashboard()
		},
		removeWidget(widgetId) {
			// Remove from widgets
			this.$delete(this.widgets, widgetId)

			// Remove from layout
			this.layout = this.layout.filter(item => item.i !== widgetId)

			this.saveDashboard()
		},
		getWidget(widgetId) {
			return this.widgets[widgetId]
		},
		getWidgetWithLayout(widgetId, layoutItem) {
			const widget = this.widgets[widgetId]
			if (!widget) {
				console.warn('Widget not found for ID:', widgetId, 'Available widgets:', Object.keys(this.widgets))
				return null
			}
			
			// Add layout dimensions to widget for automatic display mode detection
			return {
				...widget,
				w: layoutItem.w,
				h: layoutItem.h
			}
		},
		updateWidgetSettings(widgetId, newSettings) {
			if (this.widgets[widgetId]) {
				this.$set(this.widgets[widgetId], 'settings', newSettings)
				this.saveDashboard()
			}
		},
	},
}
</script>

<style scoped lang="scss">
#dashy {
	padding: 20px;
	height: 100vh;
	overflow: auto;
}

.dashboard-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 20px;

	h1 {
		margin: 0;
	}
}

.dashboard-container {
	min-height: calc(100vh - 120px);
}

/* Add Widget Modal Overlay */
.add-widget-overlay {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.5);
	backdrop-filter: blur(4px);
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 2100;
	padding: 20px;
}

.add-widget-modal {
	background: var(--color-main-background);
	border-radius: 12px;
	box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
	max-width: 600px;
	width: 100%;
	max-height: 90vh;
	overflow: auto;
	border: 1px solid var(--color-border);
}

.modal-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 20px 24px;
	border-bottom: 1px solid var(--color-border);
	background: var(--color-background-dark);
	border-radius: 12px 12px 0 0;

	h3 {
		margin: 0;
		font-size: 18px;
		font-weight: 600;
		color: var(--color-main-text);
	}

	.close-btn {
		background: none;
		border: none;
		font-size: 20px;
		cursor: pointer;
		color: var(--color-text-maxcontrast);
		padding: 8px;
		border-radius: 6px;
		transition: all 0.2s ease;
		min-width: 32px;
		min-height: 32px;
		display: flex;
		align-items: center;
		justify-content: center;

		&:hover {
			background: var(--color-background-hover);
			color: var(--color-main-text);
		}
	}
}

.modal-content {
	padding: 24px;
}

.widget-types {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 15px;
}

.widget-type-card {
	border: 2px solid var(--color-border);
	border-radius: 8px;
	padding: 20px;
	text-align: center;
	cursor: pointer;
	transition: all 0.2s ease;

	&:hover {
		border-color: var(--color-primary);
		background-color: var(--color-background-hover);
	}

	h3 {
		margin: 10px 0 5px 0;
	}

	p {
		margin: 0;
		color: var(--color-text-lighter);
		font-size: 14px;
	}
}

// Vue Grid Layout styles
:deep(.vue-grid-layout) {
	background-color: transparent;
}

:deep(.vue-grid-item) {
	border: 1px solid var(--color-border);
	border-radius: 8px;
	background-color: var(--color-main-background);
	box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
	transition: all 0.2s ease;

	&:hover {
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
	}

	&.vue-grid-item--resizing {
		opacity: 0.8;
	}

	&.vue-grid-item--dragging {
		transition: none;
		z-index: 3;
	}
}

:deep(.vue-resizable-handle) {
	position: absolute;
	width: 20px;
	height: 20px;
	bottom: 0;
	right: 0;
	background: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNiIgaGVpZ2h0PSI2IiB2aWV3Qm94PSIwIDAgNiA2IiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPgo8ZG90cyBmaWxsPSIjODg4IiBkPSJtMTUgMTJBMyAzIDAgMSAxIDkgNmEzIDMgMCAwIDEgNiA2eiIvPgo8L3N2Zz4K');
	background-position: bottom right;
	padding: 0 3px 3px 0;
	background-repeat: no-repeat;
	background-origin: content-box;
	box-sizing: border-box;
	cursor: se-resize;
}
</style>
