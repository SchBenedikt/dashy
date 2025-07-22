<template>
  <NcAppContent>
	<div :id="'dashy'" :class="{'landing-no-scroll': layout.length === 0}">
	  <div class="dashboard-bg">
		<div v-if="layout.length === 0" class="dashboard-landing">
		  <div class="landing-content">
			<img :src="appLogo" alt="Dashy" class="landing-logo" />
			<h2 class="landing-title">{{ t('dashy', 'Welcome!') }}</h2>
			<div class="landing-sub">
			  <span class="landing-highlight">{{ t('dashy', 'Create your personal dashboard in seconds.') }}</span>
			  <span class="landing-desc">{{ t('dashy', 'Add widgets for calendar, tasks, weather, AI chat and more – alles individuell anpassbar.') }}</span>
			</div>
			<div class="landing-actions">
			  <NcButton type="primary" class="landing-add-btn" @click="showAddWidget = true">
				<template #icon>
				  <PlusIcon />
				</template>
				{{ t('dashy', 'Add Widget') }}
			  </NcButton>
			</div>
			<ul class="landing-features">
			  <li><span>🧩</span> {{ t('dashy', 'Drag & drop your widgets anywhere') }}</li>
			  <li><span>⚡️</span> {{ t('dashy', 'Instantly customize and resize') }}</li>
			  <li><span>🔒</span> {{ t('dashy', 'Your data stays private') }}</li>
			</ul>
		  </div>
		</div>
		<div v-else>
  <!-- Floating Action Button (FAB) for edit actions -->
  <div class="fab-menu" :class="{ open: showFabMenu || editMode }">
	<!-- Speed Dial Actions -->
	<transition-group name="fab-action-fade" tag="div">
	  <button
		v-if="editMode"
		key="add-widget"
		class="fab-btn fab-action"
		@click="showAddWidget = true"
		@mouseenter="showTooltip('add')"
		@mouseleave="hideTooltip"
		aria-label="Add Widget"
		tabindex="0"
	  >
		<PlusIcon />
		<span v-if="tooltip === 'add'" class="fab-tooltip">{{ t('dashy', 'Add Widget') }}</span>
	  </button>
	</transition-group>
	<!-- Main FAB -->
	<button
	  class="fab-btn main-fab"
	  @click="toggleFabMenu"
	  @mouseenter="showTooltip('edit')"
	  @mouseleave="hideTooltip"
	  :aria-label="editMode ? t('dashy', 'Exit Edit Mode') : t('dashy', 'Edit Dashboard')"
	  tabindex="0"
	>
	  <PencilIcon :style="editMode ? 'opacity:0.7;' : ''" />
	  <span v-if="tooltip === 'edit'" class="fab-tooltip">
		{{ editMode ? t('dashy', 'Exit Edit Mode') : t('dashy', 'Edit Dashboard') }}
	  </span>
	</button>
  </div>
		  <div class="dashboard-container">
			<grid-layout
			  :layout="layout"
			  :col-num="12"
			  :row-height="60"
			  :is-draggable="editMode"
			  :is-resizable="editMode"
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
				  :editMode="editMode"
				  @remove="removeWidget"
				  @update-settings="updateWidgetSettings"
				/>
			  </grid-item>
			</grid-layout>
		  </div>
		</div>
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
import appLogo from '../img/app.svg'
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import PencilIcon from 'vue-material-design-icons/Pencil.vue'
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
import RobotIcon from './icons/RobotIcon.vue'
import { nextTick } from 'vue'

export default {
	name: 'App',
  components: {
		NcAppContent,
		NcButton,
		PlusIcon,
		PencilIcon,
		GridLayout,
		GridItem,
		WidgetContainer,
  },
  data() {
  return {
	layout: [],
	widgets: {},
	showAddWidget: false,
	editMode: false,
	showFabMenu: false,
	tooltip: null,
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
		  type: 'ai',
		  name: t('dashy', 'AI Chat'),
		  description: t('dashy', 'Chatte mit Gemini AI'),
		  icon: RobotIcon,
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
	  appLogo,
	}
  },
  async mounted() {
	await this.loadDashboard()
	this.updateLandingScroll()
  },
  watch: {
	layout: {
	  handler() {
		this.updateLandingScroll()
	  },
	  deep: true
	}
  },
  methods: {
  toggleFabMenu() {
	// Toggle edit mode and menu visibility
	if (!this.editMode) {
	  this.editMode = true;
	  this.showFabMenu = true;
	} else {
	  this.editMode = false;
	  this.showFabMenu = false;
	}
  },
  showTooltip(type) {
	this.tooltip = type;
  },
  hideTooltip() {
	this.tooltip = null;
  },
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
  // toggleEditMode entfernt, da Logik jetzt in toggleFabMenu enthalten ist
	updateLandingScroll() {
	  const appContent = document.getElementById('app-content-vue')
	  if (appContent) {
		if (this.layout.length === 0) {
		  appContent.classList.add('landing-no-scroll')
		} else {
		  appContent.classList.remove('landing-no-scroll')
		}
	  }
	},
	t,
  },
}
</script>

<style scoped lang="scss">

.fab-menu {
  position: fixed;
  right: 28px;
  bottom: 28px;
  z-index: 3000;
  display: flex;
  flex-direction: column-reverse;
  align-items: flex-end;
  pointer-events: none;
}
.fab-btn {
  background: #f5f5f7;
  border: none;
  border-radius: 50%;
  width: 48px;
  height: 48px;
  box-shadow: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.18s, box-shadow 0.18s, transform 0.18s;
  outline: none;
  margin-bottom: 0;
  margin-top: 0;
  pointer-events: auto;
  position: relative;
  &:hover, &:focus {
	background: #ececec;
	box-shadow: none;
	z-index: 2;
  }
}
.main-fab {
  background: #f5f5f7;
  color: #222;
  width: 52px;
  height: 52px;
  box-shadow: none;
  font-size: 1.7rem;
  z-index: 2;
}
.fab-action {
  background: #1976d2;
  color: #fff;
  width: 44px;
  height: 44px;
  margin-bottom: 16px;
  margin-right: 4px;
  box-shadow: 0 2px 8px rgba(25,118,210,0.18);
  font-size: 1.4rem;
  z-index: 1;
  transition: transform 0.22s cubic-bezier(.4,2,.6,1), opacity 0.18s;
  opacity: 1;
  &:hover, &:focus {
	background: #1565c0;
	color: #fff;
  }
}
.fab-tooltip {
  position: absolute;
  right: 110%;
  top: 50%;
  transform: translateY(-50%);
  background: #222;
  color: #fff;
  padding: 6px 14px;
  border-radius: 6px;
  font-size: 0.98rem;
  white-space: nowrap;
  box-shadow: 0 2px 8px rgba(0,0,0,0.13);
  pointer-events: none;
  opacity: 0.97;
  z-index: 10;
  transition: opacity 0.18s;
}

/* Animation for Speed Dial */
.fab-action-fade-enter-active, .fab-action-fade-leave-active {
  transition: all 0.22s cubic-bezier(.4,2,.6,1);
}
.fab-action-fade-enter-from, .fab-action-fade-leave-to {
  opacity: 0;
  transform: translateY(20px) scale(0.7);
}
.fab-action-fade-enter-to, .fab-action-fade-leave-from {
  opacity: 1;
  transform: translateY(0) scale(1);
}


#dashy {
  padding: 0;
  height: 100vh;
  min-height: 100vh;
  overflow: hidden;
}


// Verhindere Scrollen im übergeordneten Bereich IMMER (Landing & Widgets)
:global(#app-content-vue) {
  height: 100vh !important;
  min-height: 100vh !important;
  overflow: hidden !important;
}

.dashboard-header {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  margin-bottom: 20px;
}


  .dashboard-bg {
	background: #fff;
	min-height: 100vh;
	height: 100vh;
	width: 100vw;
	display: flex;
	flex-direction: column;
	align-items: stretch;
	justify-content: stretch;
	overflow: hidden;
  }

  @media (max-width: 700px), (max-height: 600px) {
	.dashboard-bg {
	  overflow-y: auto;
	  height: 100dvh;
	  min-height: 100dvh;
	}
  }

.dashboard-landing {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  height: 100vh;
  max-height: 100vh;
  border-radius: 0;
  padding: 0 2vw;
  box-sizing: border-box;
  transition: background 0.4s;
  overflow: hidden;
}
.landing-content {
  text-align: center;
  padding: 6vw 2vw;
  max-width: 480px;
  margin: 0 auto;
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
}
.landing-logo {
  width: 80px;
  height: 80px;
  margin-bottom: 28px;
  animation: logo-bounce 1.2s cubic-bezier(.68,-0.55,.27,1.55);
}
@keyframes logo-bounce {
  0% { transform: scale(0.7) rotate(-10deg); opacity: 0; }
  60% { transform: scale(1.1) rotate(8deg); opacity: 1; }
  80% { transform: scale(0.95) rotate(-4deg); }
  100% { transform: scale(1) rotate(0deg); }
}
.landing-title {
  font-size: clamp(1.7rem, 4vw, 2.4rem);
  margin-bottom: 10px;
  font-weight: 700;
  letter-spacing: -0.5px;
  animation: fade-in-down 0.8s 0.2s both;
}
.landing-sub {
  display: flex;
  flex-direction: column;
  gap: 2px;
  margin-bottom: 18px;
}
.landing-highlight {
  color: var(--color-primary);
  font-size: clamp(1.08rem, 2vw, 1.18rem);
  font-weight: 600;
  letter-spacing: 0.1px;
  animation: fade-in-up 0.7s 0.3s both;
}
.landing-desc {
  color: var(--color-text-lighter);
  font-size: clamp(0.98rem, 1.7vw, 1.08rem);
  animation: fade-in-up 0.7s 0.4s both;
}
.landing-features {
  list-style: none;
  padding: 0;
  margin: 24px 0 0 0;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 18px;
  font-size: clamp(0.97rem, 1.5vw, 1.08rem);
  color: var(--color-text-lighter);
  animation: fade-in-up 0.7s 0.6s both;
}
.landing-features li {
  background: var(--color-background-dark);
  border-radius: 8px;
  padding: 7px 16px 7px 12px;
  display: flex;
  align-items: center;
  gap: 7px;
  font-weight: 500;
  box-shadow: 0 2px 8px 0 rgba(0,0,0,0.04);
}
.landing-actions {
  display: flex;
  justify-content: center;
  margin: 40px 0 18px 0;
}
.landing-add-btn {
  font-size: 1.25rem !important;
  padding: 18px 38px !important;
  border-radius: 10px !important;
  min-width: 220px;
  min-height: 56px;
  font-weight: 600;
  animation: pop-in 0.7s 0.6s both;
  box-shadow: 0 4px 24px 0 rgba(0,0,0,0.10);
  transition: transform 0.2s, box-shadow 0.2s;
}
.landing-add-btn:hover {
  transform: scale(1.08) translateY(-2px);
  box-shadow: 0 8px 32px 0 rgba(0,0,0,0.13);
}
.landing-info {
  color: var(--color-text-lighter);
  font-size: clamp(0.95rem, 1.7vw, 1.08rem);
  margin-top: 18px;
  animation: fade-in-up 0.7s 0.7s both;
}
@keyframes fade-in-down {
  0% { opacity: 0; transform: translateY(-30px); }
  100% { opacity: 1; transform: translateY(0); }
}
@keyframes fade-in-up {
  0% { opacity: 0; transform: translateY(30px); }
  100% { opacity: 1; transform: translateY(0); }
}
@keyframes pop-in {
  0% { opacity: 0; transform: scale(0.7); }
  80% { opacity: 1; transform: scale(1.08); }
  100% { opacity: 1; transform: scale(1); }
}

@media (max-width: 700px) {
  .dashboard-landing {
	min-height: 50vh;
	padding: 0 1vw;
  }
  .landing-content {
	padding: 8vw 2vw;
	max-width: 98vw;
  }
  .landing-logo {
	width: 56px;
	height: 56px;
  }
}

.dashboard-container {
  flex: 1 1 auto;
  min-height: 0;
  height: 100%;
  overflow: visible;
}

:deep(.vue-grid-layout) {
  min-height: 0 !important;
  height: 100% !important;
  max-height: 100% !important;
  overflow: visible !important;
  background-color: transparent;
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
