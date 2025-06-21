<template>
	<div class="todo-widget" :class="[`view-${displayMode}`]">
		<!-- Widget Header with Actions -->
		<div v-if="displayMode !== 'compact'" class="widget-header">
			<div class="header-actions">
				<button 
					class="add-task-btn" 
					@click="addNewTask"
					:title="t('dashy', 'Add new task')"
				>
					➕
				</button>
				<button 
					class="refresh-btn" 
					@click="loadTasks"
					:title="t('dashy', 'Refresh tasks')"
					:disabled="loading"
				>
					🔄
				</button>
			</div>
			<div v-if="displayMode === 'detailed'" class="filter-tabs">
				<button 
					:class="{ active: currentFilter === 'all' }" 
					@click="currentFilter = 'all'"
				>
					{{ t('dashy', 'All') }} ({{ tasks.length }})
				</button>
				<button 
					:class="{ active: currentFilter === 'active' }" 
					@click="currentFilter = 'active'"
				>
					{{ t('dashy', 'Active') }} ({{ todoTasks.length }})
				</button>
				<button 
					:class="{ active: currentFilter === 'completed' }" 
					@click="currentFilter = 'completed'"
				>
					{{ t('dashy', 'Done') }} ({{ completedTasks.length }})
				</button>
			</div>
		</div>

		<div v-if="loading" class="loading">
			{{ t('dashy', 'Loading tasks...') }}
		</div>
		<div v-else-if="filteredTasks.length === 0" class="no-tasks">
			<div class="no-tasks-icon">📝</div>
			<div class="no-tasks-text">{{ t('dashy', 'No tasks found') }}</div>
			<button 
				v-if="displayMode !== 'compact'" 
				class="add-first-task-btn" 
				@click="addNewTask"
			>
				{{ t('dashy', 'Add your first task') }}
			</button>
		</div>
		
		<!-- Compact view for small widgets -->
		<div v-else-if="displayMode === 'compact'" class="tasks-compact">
			<div class="compact-header">
				<button 
					class="compact-add-btn" 
					@click="addNewTask"
					:title="t('dashy', 'Add task')"
				>
					➕
				</button>
				<div class="compact-filter">
					<button 
						:class="{ active: currentFilter === 'active' }" 
						@click="toggleCompactFilter"
					>
						{{ currentFilter === 'active' ? '📋' : '✅' }}
					</button>
				</div>
			</div>
			<div
				v-for="task in filteredTasks.slice(0, maxTasks)"
				:key="task.id"
				class="task-compact"
				:class="{ completed: task.completed }"
				@click="editTask(task)"
			>
				<input
					type="checkbox"
					:checked="task.completed"
					@change="toggleTask(task.id)"
					@click.stop
				>
				<div class="task-content-compact">
					<div class="task-title-compact" :title="task.summary">
						{{ task.summary }}
					</div>
					<div v-if="task.due" class="task-due-compact" :class="{ overdue: isOverdue(task.due) }">
						{{ formatCompactDue(task.due) }}
					</div>
				</div>
				<div v-if="task.priority && task.priority >= 7" class="task-priority-compact">
					{{ getPriorityIcon(task.priority) }}
				</div>
			</div>
		</div>
		
		<!-- List view for medium widgets -->
		<div v-else-if="displayMode === 'list'" class="tasks-list">
			<!-- Task list -->
			<div
				v-for="task in filteredTasks.slice(0, maxTasks)"
				:key="task.id"
				class="task-item"
				:class="{ completed: task.completed, overdue: isOverdue(task.due) }"
			>
				<input
					type="checkbox"
					:checked="task.completed"
					@change="toggleTask(task.id)"
				>
				<div class="task-content" @click="editTask(task)">
					<div class="task-title">{{ task.summary }}</div>
					<div v-if="task.due || task.list" class="task-meta">
						<span v-if="task.due" class="task-due">
							📅 {{ formatTaskDue(task.due) }}
						</span>
						<span v-if="task.list" class="task-list">
							📋 {{ task.list }}
						</span>
					</div>
				</div>
				<div class="task-actions">
					<div v-if="task.priority && task.priority >= 6" class="task-priority">
						{{ getPriorityIcon(task.priority) }}
					</div>
					<button 
						class="edit-task-btn" 
						@click="editTask(task)"
						:title="t('dashy', 'Edit task')"
					>
						✏️
					</button>
				</div>
			</div>
		</div>
		
		<!-- Detailed view for large widgets -->
		<div v-else-if="displayMode === 'detailed'" class="tasks-detailed">
			<div
				v-for="task in filteredTasks.slice(0, maxTasks)"
				:key="task.id"
				class="task-detailed"
				:class="{ completed: task.completed }"
			>
				<div class="task-header">
					<div class="task-checkbox-wrapper">
						<input
							type="checkbox"
							:checked="task.completed"
							@change="toggleTask(task.id)"
						>
					</div>
					<div class="task-meta">
						<div v-if="task.due" class="task-due-detailed" :class="{ overdue: isOverdue(task.due) }">
							{{ formatDetailedDue(task.due) }}
						</div>
						<div v-if="task.priority" class="task-priority-detailed">
							{{ getPriorityIcon(task.priority) }} {{ getPriorityText(task.priority) }}
						</div>
					</div>
					<button 
						class="edit-task-btn-detailed" 
						@click="editTask(task)"
						:title="t('dashy', 'Edit task')"
					>
						✏️
					</button>
				</div>
				<div class="task-content" @click="editTask(task)">
					<div class="task-title-detailed">{{ task.summary }}</div>
					<div v-if="task.description" class="task-description">
						{{ truncateDescription(task.description) }}
					</div>
					<div v-if="task.list" class="task-list-detailed">
						<span class="list-icon">📋</span>
						{{ task.list }}
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
	name: 'TodoWidget',
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
			tasks: [],
			loading: true,
			saving: false,
			refreshInterval: null,
			currentFilter: 'active',
			showTaskEditor: false,
			editingTask: null,
			modalElement: null,
			taskForm: {
				summary: '',
				description: '',
				due: '',
				priority: 0,
			},
		}
	},
	computed: {
		maxTasks() {
			return this.settings.maxTasks || 10
		},
		displayMode() {
			// Auto-determine display mode based on widget settings or size
			if (this.settings.displayMode) {
				return this.settings.displayMode
			}
			
			// Auto-detect based on widget dimensions (if available)
			const widgetWidth = this.widget.w || 4
			const widgetHeight = this.widget.h || 4
			
			if (widgetWidth >= 6 || widgetHeight >= 5) {
				return 'detailed'
			} else if (widgetWidth >= 4 && widgetHeight >= 3) {
				return 'list'
			} else {
				return 'compact'
			}
		},
		filteredTasks() {
			let filtered = this.tasks
			
			// Filter by current filter setting
			if (this.currentFilter === 'active') {
				filtered = filtered.filter(task => !task.completed)
			} else if (this.currentFilter === 'completed') {
				filtered = filtered.filter(task => task.completed)
			}
			
			// Sort by priority and due date
			return filtered.sort((a, b) => {
				// Completed tasks go to bottom
				if (a.completed !== b.completed) {
					return a.completed ? 1 : -1
				}
				
				// Sort by priority (higher first)
				if (a.priority !== b.priority) {
					return (b.priority || 0) - (a.priority || 0)
				}
				
				// Sort by due date (sooner first)
				if (a.due && b.due) {
					return new Date(a.due) - new Date(b.due)
				}
				if (a.due) return -1
				if (b.due) return 1
				
				return 0
			})
		},
		todoTasks() {
			return this.tasks.filter(task => !task.completed)
		},
		completedTasks() {
			return this.tasks.filter(task => task.completed)
		},
	},
	async mounted() {
		await this.loadTasks()
		this.setupRefreshInterval()
	},
	beforeDestroy() {
		if (this.refreshInterval) {
			clearInterval(this.refreshInterval)
		}
		document.removeEventListener('keydown', this.handleEscapeKey)
		this.removeModal()
	},
	watch: {
		// Watch for widget dimension changes to trigger display mode recalculation
		'widget.w'() {
			this.$forceUpdate()
		},
		'widget.h'() {
			this.$forceUpdate()
		},
		// Watch for settings changes
		'settings.refreshInterval'() {
			this.setupRefreshInterval()
		},
	},
	methods: {
		t,
		async loadTasks() {
			try {
				console.log('Loading tasks from:', generateUrl('/apps/dashy/api/tasks'))
				const response = await axios.get(generateUrl('/apps/dashy/api/tasks'))
				console.log('Tasks response:', response.data)
				this.tasks = response.data.tasks.map(task => ({
					...task,
					due: task.due ? new Date(task.due) : null,
					completedAt: task.completedAt ? new Date(task.completedAt) : null,
				}))
			} catch (error) {
				console.error('Failed to load tasks:', error)
				// Show user-friendly message
				this.tasks = []
			} finally {
				this.loading = false
			}
		},
		async toggleTask(taskId) {
			const task = this.tasks.find(t => t.id === taskId)
			if (!task) return

			const newCompleted = !task.completed

			try {
				await axios.put(generateUrl('/apps/dashy/api/tasks/' + taskId), {
					completed: newCompleted
				})

				task.completed = newCompleted
				if (newCompleted) {
					task.completedAt = new Date()
				} else {
					task.completedAt = null
				}
			} catch (error) {
				console.error('Failed to update task:', error)
				// Revert the change if the API call failed
				task.completed = !newCompleted
			}
		},
		formatTaskDue(date) {
			const now = new Date()
			const diffHours = Math.floor((date - now) / (1000 * 60 * 60))
			const diffDays = Math.floor(diffHours / 24)

			if (diffDays < 0) {
				return t('dashy', 'Overdue')
			} else if (diffDays === 0) {
				return t('dashy', 'Today')
			} else if (diffDays === 1) {
				return t('dashy', 'Tomorrow')
			} else if (diffDays < 7) {
				return date.toLocaleDateString([], { weekday: 'long' })
			} else {
				return date.toLocaleDateString([], { month: 'short', day: 'numeric' })
			}
		},
		formatCompactDue(date) {
			const now = new Date()
			const diffDays = Math.floor((date - now) / (1000 * 60 * 60 * 24))
			
			if (diffDays < 0) {
				return '⚠️'
			} else if (diffDays === 0) {
				return 'Today'
			} else if (diffDays === 1) {
				return 'Tom'
			} else if (diffDays < 7) {
				return date.toLocaleDateString([], { weekday: 'short' })
			} else {
				return date.toLocaleDateString([], { month: 'numeric', day: 'numeric' })
			}
		},
		formatDetailedDue(date) {
			const now = new Date()
			const diffDays = Math.floor((date - now) / (1000 * 60 * 60 * 24))
			
			if (diffDays < 0) {
				return t('dashy', 'Overdue')
			} else if (diffDays === 0) {
				return t('dashy', 'Due today')
			} else if (diffDays === 1) {
				return t('dashy', 'Due tomorrow')
			} else if (diffDays < 7) {
				return t('dashy', 'Due {day}', { day: date.toLocaleDateString([], { weekday: 'long' }) })
			} else {
				return t('dashy', 'Due {date}', { date: date.toLocaleDateString([], { weekday: 'long', month: 'short', day: 'numeric' }) })
			}
		},
		getPriorityIcon(priority) {
			if (priority >= 7) return '🔴' // High priority
			if (priority >= 4) return '🟡' // Medium priority
			return '🔵' // Low priority
		},
		getPriorityText(priority) {
			if (priority >= 7) return t('dashy', 'High')
			if (priority >= 4) return t('dashy', 'Medium')
			return t('dashy', 'Low')
		},
		truncateDescription(description) {
			if (!description) return ''
			return description.length > 100 ? description.substring(0, 100) + '...' : description
		},
		setupRefreshInterval() {
			if (this.refreshInterval) {
				clearInterval(this.refreshInterval)
			}
			
			const intervalMinutes = this.settings.refreshInterval || 5
			this.refreshInterval = setInterval(this.loadTasks, intervalMinutes * 60 * 1000)
		},
		toggleCompactFilter() {
			this.currentFilter = this.currentFilter === 'active' ? 'completed' : 'active'
		},
		isOverdue(date) {
			if (!date) return false
			return new Date(date) < new Date()
		},
		editTask(task) {
			this.editingTask = task
			this.taskForm = {
				summary: task.summary || '',
				description: task.description || '',
				due: task.due ? this.formatDateForInput(task.due) : '',
				priority: task.priority || 0,
			}
			this.showTaskEditor = true
			this.createModal()
		},
		addNewTask() {
			this.editingTask = null
			this.resetTaskForm()
			this.showTaskEditor = true
			this.createModal()
		},
		closeTaskEditor() {
			this.showTaskEditor = false
			this.editingTask = null
			this.resetTaskForm()
			this.removeModal()
		},
		resetTaskForm() {
			this.taskForm = {
				summary: '',
				description: '',
				due: '',
				priority: 0,
			}
		},
		formatDateForInput(date) {
			if (!date) return ''
			const d = new Date(date)
			const year = d.getFullYear()
			const month = String(d.getMonth() + 1).padStart(2, '0')
			const day = String(d.getDate()).padStart(2, '0')
			const hours = String(d.getHours()).padStart(2, '0')
			const minutes = String(d.getMinutes()).padStart(2, '0')
			return `${year}-${month}-${day}T${hours}:${minutes}`
		},
		async saveTask() {
			if (!this.taskForm.summary.trim()) return

			this.saving = true
			try {
				const taskData = {
					summary: this.taskForm.summary.trim(),
					description: this.taskForm.description.trim(),
					due: this.taskForm.due ? new Date(this.taskForm.due).toISOString() : null,
					priority: parseInt(this.taskForm.priority) || 0,
				}

				if (this.editingTask) {
					// Update existing task
					await axios.put(generateUrl('/apps/dashy/api/tasks/' + this.editingTask.id), taskData)
					
					// Update local task
					Object.assign(this.editingTask, {
						...taskData,
						due: taskData.due ? new Date(taskData.due) : null,
					})
				} else {
					// Create new task
					const response = await axios.post(generateUrl('/apps/dashy/api/tasks'), taskData)
					
					// Add to local tasks
					this.tasks.push({
						...response.data.task,
						due: response.data.task.due ? new Date(response.data.task.due) : null,
					})
				}

				this.closeTaskEditor()
			} catch (error) {
				console.error('Failed to save task:', error)
				// TODO: Show error message to user
			} finally {
				this.saving = false
			}
		},
		async deleteTask() {
			if (!this.editingTask) return

			this.saving = true
			try {
				await axios.delete(generateUrl('/apps/dashy/api/tasks/' + this.editingTask.id))
				
				// Remove from local tasks
				const index = this.tasks.findIndex(t => t.id === this.editingTask.id)
				if (index !== -1) {
					this.tasks.splice(index, 1)
				}

				this.closeTaskEditor()
			} catch (error) {
				console.error('Failed to delete task:', error)
				// TODO: Show error message to user
			} finally {
				this.saving = false
			}
		},
		handleEscapeKey(event) {
			if (event.key === 'Escape' && this.showTaskEditor) {
				this.closeTaskEditor()
			}
		},
		
		// Modal management methods
		createModal() {
			if (this.modalElement) return
			
			// Add escape key listener when modal opens
			document.addEventListener('keydown', this.handleEscapeKey)
			
			// Create modal overlay
			this.modalElement = document.createElement('div')
			this.modalElement.className = 'task-editor-overlay'
			this.modalElement.addEventListener('click', this.handleOverlayClick)
			
			// Create modal content
			const modalContent = document.createElement('div')
			modalContent.className = 'task-editor-modal'
			modalContent.addEventListener('click', (e) => e.stopPropagation())
			
			// Create modal HTML
			modalContent.innerHTML = this.getModalHTML()
			this.modalElement.appendChild(modalContent)
			
			// Add to body
			document.body.appendChild(this.modalElement)
			
			// Bind events
			this.bindModalEvents()
			
			// Focus title input
			setTimeout(() => {
				const titleInput = this.modalElement.querySelector('#task-title')
				if (titleInput) {
					titleInput.focus()
					titleInput.select()
				}
			}, 100)
		},
		
		removeModal() {
			if (this.modalElement) {
				// Remove escape key listener when modal closes
				document.removeEventListener('keydown', this.handleEscapeKey)
				
				this.modalElement.removeEventListener('click', this.handleOverlayClick)
				document.body.removeChild(this.modalElement)
				this.modalElement = null
			}
		},
		
		handleOverlayClick() {
			this.closeTaskEditor()
		},
		
		updateModal() {
			if (!this.modalElement) return
			
			const modalContent = this.modalElement.querySelector('.task-editor-modal')
			if (modalContent) {
				modalContent.innerHTML = this.getModalHTML()
				this.bindModalEvents()
			}
		},
		
		getModalHTML() {
			const editingTask = this.editingTask
			const taskForm = this.taskForm
			const saving = this.saving
			
			return `
				<div class="modal-header">
					<h3>${editingTask ? t('dashy', 'Edit Task') : t('dashy', 'New Task')}</h3>
					<button class="close-btn" type="button" title="Close">✕</button>
				</div>
				<form class="task-form" data-action="submit">
					<div class="form-group">
						<label for="task-title">${t('dashy', 'Title')}</label>
						<input
							id="task-title"
							type="text"
							placeholder="${t('dashy', 'Enter task title...')}"
							required
							value="${taskForm.summary}"
						>
					</div>
					<div class="form-group">
						<label for="task-description">${t('dashy', 'Description')}</label>
						<textarea
							id="task-description"
							placeholder="${t('dashy', 'Add a description...')}"
							rows="3"
						>${taskForm.description}</textarea>
					</div>
					<div class="form-row">
						<div class="form-group">
							<label for="task-due">${t('dashy', 'Due Date')}</label>
							<input
								id="task-due"
								type="datetime-local"
								value="${taskForm.due}"
							>
						</div>
						<div class="form-group">
							<label for="task-priority">${t('dashy', 'Priority')}</label>
							<select id="task-priority">
								<option value="0" ${taskForm.priority == 0 ? 'selected' : ''}>${t('dashy', 'None')}</option>
								<option value="3" ${taskForm.priority == 3 ? 'selected' : ''}>${t('dashy', 'Low')}</option>
								<option value="5" ${taskForm.priority == 5 ? 'selected' : ''}>${t('dashy', 'Medium')}</option>
								<option value="7" ${taskForm.priority == 7 ? 'selected' : ''}>${t('dashy', 'High')}</option>
								<option value="9" ${taskForm.priority == 9 ? 'selected' : ''}>${t('dashy', 'Urgent')}</option>
							</select>
						</div>
					</div>
					<div class="form-actions">
						<button type="button" data-action="cancel" class="btn-cancel">
							${t('dashy', 'Cancel')}
						</button>
						${editingTask ? `
						<button 
							type="button" 
							data-action="delete"
							class="btn-delete"
							${saving ? 'disabled' : ''}
						>
							${t('dashy', 'Delete')}
						</button>` : ''}
						<button type="submit" class="btn-save" ${saving || !taskForm.summary.trim() ? 'disabled' : ''}>
							${saving ? t('dashy', 'Saving...') : (editingTask ? t('dashy', 'Update') : t('dashy', 'Create'))}
						</button>
					</div>
				</form>
			`
		},
		
		bindModalEvents() {
			if (!this.modalElement) return
			
			// Form submission
			const form = this.modalElement.querySelector('.task-form')
			form.addEventListener('submit', (e) => {
				e.preventDefault()
				this.saveTaskFromModal()
			})
			
			// Direct event listener for close button
			const closeBtn = this.modalElement.querySelector('.close-btn')
			if (closeBtn) {
				closeBtn.addEventListener('click', (e) => {
					e.preventDefault()
					e.stopPropagation()
					console.log('Close button clicked!')
					this.closeTaskEditor()
				})
			}
			
			// Button clicks - use event delegation for other buttons
			this.modalElement.addEventListener('click', (e) => {
				const action = e.target.getAttribute('data-action')
				
				console.log('Modal click event:', e.target, 'action:', action)
				
				switch (action) {
					case 'cancel':
						e.preventDefault()
						e.stopPropagation()
						this.closeTaskEditor()
						break
					case 'delete':
						e.preventDefault()
						e.stopPropagation()
						this.deleteTask()
						break
				}
			})
			
			// Input changes
			const titleInput = this.modalElement.querySelector('#task-title')
			const descInput = this.modalElement.querySelector('#task-description')
			const dueInput = this.modalElement.querySelector('#task-due')
			const priorityInput = this.modalElement.querySelector('#task-priority')
			
			titleInput?.addEventListener('input', (e) => {
				this.taskForm.summary = e.target.value
			})
			descInput?.addEventListener('input', (e) => {
				this.taskForm.description = e.target.value
			})
			dueInput?.addEventListener('input', (e) => {
				this.taskForm.due = e.target.value
			})
			priorityInput?.addEventListener('change', (e) => {
				this.taskForm.priority = parseInt(e.target.value)
			})
		},
		
		async saveTaskFromModal() {
			// Update form data from inputs
			const titleInput = this.modalElement.querySelector('#task-title')
			const descInput = this.modalElement.querySelector('#task-description')
			const dueInput = this.modalElement.querySelector('#task-due')
			const priorityInput = this.modalElement.querySelector('#task-priority')
			
			this.taskForm.summary = titleInput.value
			this.taskForm.description = descInput.value
			this.taskForm.due = dueInput.value
			this.taskForm.priority = parseInt(priorityInput.value)
			
			await this.saveTask()
		},
	},
}
</script>

<style scoped lang="scss">
.todo-widget {
	height: 100%;
	overflow: hidden;
	display: flex;
	flex-direction: column;
}

// Widget Header
.widget-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 8px 12px;
	border-bottom: 1px solid var(--color-border);
	flex-shrink: 0;
}

.header-actions {
	display: flex;
	gap: 8px;
}

.add-task-btn,
.refresh-btn {
	background: var(--color-primary);
	border: none;
	border-radius: 6px;
	color: white;
	padding: 6px 8px;
	cursor: pointer;
	font-size: 12px;
	transition: all 0.2s ease;

	&:hover {
		background: var(--color-primary-hover);
		transform: scale(1.05);
	}

	&:disabled {
		opacity: 0.5;
		cursor: not-allowed;
		transform: none;
	}
}

.filter-tabs {
	display: flex;
	gap: 4px;

	button {
		background: none;
		border: 1px solid var(--color-border);
		border-radius: 4px;
		padding: 4px 8px;
		font-size: 11px;
		cursor: pointer;
		transition: all 0.2s ease;

		&.active {
			background: var(--color-primary);
			color: white;
			border-color: var(--color-primary);
		}

		&:hover:not(.active) {
			background: var(--color-background-hover);
		}
	}
}

.loading,
.no-tasks {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	height: 100%;
	color: var(--color-text-lighter);
	text-align: center;
	padding: 20px;
}

.no-tasks-icon {
	font-size: 48px;
	margin-bottom: 12px;
	opacity: 0.5;
}

.no-tasks-text {
	font-style: italic;
	margin-bottom: 16px;
}

.add-first-task-btn {
	background: var(--color-primary);
	color: white;
	border: none;
	border-radius: 6px;
	padding: 8px 16px;
	cursor: pointer;
	font-size: 14px;
	transition: all 0.2s ease;

	&:hover {
		background: var(--color-primary-hover);
	}
}

// Compact view (small widgets)
.view-compact .tasks-compact {
	display: flex;
	flex-direction: column;
	gap: 3px;
	overflow-y: auto;
	height: 100%;
	flex: 1;
}

.compact-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 4px 6px;
	background: var(--color-background-hover);
	border-radius: 4px;
	margin-bottom: 4px;
	flex-shrink: 0;
}

.compact-add-btn {
	background: var(--color-primary);
	border: none;
	border-radius: 3px;
	color: white;
	padding: 2px 6px;
	cursor: pointer;
	font-size: 10px;
}

.compact-filter button {
	background: none;
	border: 1px solid var(--color-border);
	border-radius: 3px;
	padding: 2px 4px;
	cursor: pointer;
	font-size: 10px;

	&.active {
		background: var(--color-primary);
		color: white;
	}
}

.task-compact {
	display: flex;
	align-items: center;
	gap: 6px;
	padding: 4px 6px;
	border-radius: 4px;
	background-color: var(--color-background-hover);
	border-left: 2px solid var(--color-primary);
	min-height: 24px;
	cursor: pointer;
	transition: all 0.2s ease;

	&:hover {
		background-color: var(--color-background-dark);
		transform: translateX(2px);
	}

	&.completed {
		opacity: 0.6;
		border-left-color: var(--color-success);
		
		.task-title-compact {
			text-decoration: line-through;
		}
	}

	input[type="checkbox"] {
		margin: 0;
		flex-shrink: 0;
	}
}

.task-content-compact {
	flex: 1;
	min-width: 0;
}

.task-title-compact {
	font-size: 11px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	margin-bottom: 1px;
}

.task-due-compact {
	font-size: 9px;
	color: var(--color-text-lighter);

	&.overdue {
		color: var(--color-error);
		font-weight: 600;
	}
}

.task-priority-compact {
	font-size: 10px;
	flex-shrink: 0;
}

// List view (medium widgets)
.view-list .tasks-list {
	display: flex;
	flex-direction: column;
	gap: 6px;
	overflow-y: auto;
	height: 100%;
	flex: 1;
	padding: 8px;
}

.task-item {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 8px 10px;
	border-radius: 6px;
	background-color: var(--color-background-hover);
	border-left: 3px solid var(--color-primary);
	transition: all 0.2s ease;

	&:hover {
		background-color: var(--color-background-dark);
		transform: translateX(2px);
		box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
	}

	&.completed {
		opacity: 0.6;
		border-left-color: var(--color-success);
		
		.task-title {
			text-decoration: line-through;
		}
	}

	&.overdue:not(.completed) {
		border-left-color: var(--color-error);
		background-color: rgba(var(--color-error-rgb), 0.05);
	}

	input[type="checkbox"] {
		margin: 0;
		flex-shrink: 0;
	}
}

.task-content {
	flex: 1;
	min-width: 0;
	cursor: pointer;
}

.task-title {
	font-weight: 500;
	margin-bottom: 4px;
	word-wrap: break-word;
	font-size: 14px;
}

.task-meta {
	display: flex;
	gap: 12px;
	flex-wrap: wrap;
}

.task-due {
	font-size: 12px;
	color: var(--color-text-lighter);
}

.task-list {
	font-size: 12px;
	color: var(--color-text-lighter);
}

.task-actions {
	display: flex;
	align-items: center;
	gap: 8px;
	flex-shrink: 0;
}

.task-priority {
	font-size: 14px;
}

.edit-task-btn {
	background: none;
	border: none;
	cursor: pointer;
	font-size: 12px;
	padding: 4px;
	border-radius: 3px;
	opacity: 0.5;
	transition: all 0.2s ease;

	&:hover {
		opacity: 1;
		background: var(--color-background-dark);
		transform: scale(1.1);
	}
}

// Detailed view (large widgets)
.view-detailed .tasks-detailed {
	display: flex;
	flex-direction: column;
	gap: 12px;
	overflow-y: auto;
	height: 100%;
}

.task-detailed {
	padding: 12px;
	border-radius: 8px;
	background-color: var(--color-background-hover);
	border-left: 4px solid var(--color-primary);

	&.completed {
		opacity: 0.6;
		border-left-color: var(--color-success);
		
		.task-title-detailed {
			text-decoration: line-through;
		}
	}
}

.task-header {
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
	margin-bottom: 8px;
}

.task-checkbox-wrapper {
	input[type="checkbox"] {
		margin: 0;
	}
}

.task-meta {
	display: flex;
	flex-direction: column;
	align-items: flex-end;
	gap: 4px;
}

.task-due-detailed {
	font-size: 12px;
	color: var(--color-text-lighter);
}

.task-priority-detailed {
	font-size: 12px;
	color: var(--color-text-lighter);
	display: flex;
	align-items: center;
	gap: 4px;
}

.task-content {
	display: flex;
	flex-direction: column;
	gap: 6px;
}

.task-title-detailed {
	font-weight: 600;
	font-size: 14px;
}

.task-description {
	font-size: 12px;
	color: var(--color-text-lighter);
	line-height: 1.4;
}

.task-list-detailed {
	display: flex;
	align-items: center;
	gap: 4px;
	font-size: 12px;
	color: var(--color-text-lighter);
}

.list-icon {
	font-size: 10px;
}
</style>

<style lang="scss">
/* Global modal styles - rendered in document.body */
.task-editor-overlay {
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

.task-editor-modal {
	background: var(--color-main-background);
	border-radius: 12px;
	box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
	max-width: 500px;
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

.task-form {
	padding: 24px;

	.form-group {
		margin-bottom: 20px;

		label {
			display: block;
			margin-bottom: 6px;
			font-weight: 500;
			color: var(--color-main-text);
			font-size: 14px;
		}

		input, textarea, select {
			width: 100%;
			padding: 10px 12px;
			border: 2px solid var(--color-border);
			border-radius: 8px;
			background: var(--color-main-background);
			color: var(--color-main-text);
			font-size: 14px;
			transition: all 0.2s ease;
			box-sizing: border-box;

			&:focus {
				outline: none;
				border-color: var(--color-primary);
				box-shadow: 0 0 0 3px rgba(var(--color-primary-rgb), 0.1);
			}

			&::placeholder {
				color: var(--color-text-maxcontrast);
			}
		}

		textarea {
			resize: vertical;
			min-height: 80px;
			font-family: inherit;
		}
	}

	.form-row {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 16px;

		@media (max-width: 480px) {
			grid-template-columns: 1fr;
		}
	}

	.form-actions {
		display: flex;
		gap: 12px;
		justify-content: flex-end;
		margin-top: 24px;
		padding-top: 20px;
		border-top: 1px solid var(--color-border);

		@media (max-width: 480px) {
			flex-direction: column-reverse;
		}
	}
}

.btn-cancel, .btn-delete, .btn-save {
	padding: 10px 20px;
	border: none;
	border-radius: 8px;
	font-size: 14px;
	font-weight: 500;
	cursor: pointer;
	transition: all 0.2s ease;
	min-width: 100px;

	&:disabled {
		opacity: 0.5;
		cursor: not-allowed;
		transform: none !important;
	}
}

.btn-cancel {
	background: var(--color-background-dark);
	color: var(--color-main-text);
	border: 2px solid var(--color-border);

	&:hover:not(:disabled) {
		background: var(--color-border);
		transform: translateY(-1px);
	}
}

.btn-delete {
	background: var(--color-error);
	color: white;

	&:hover:not(:disabled) {
		background: var(--color-error-hover);
		transform: translateY(-1px);
		box-shadow: 0 4px 12px rgba(var(--color-error-rgb), 0.3);
	}
}

.btn-save {
	background: var(--color-primary);
	color: white;

	&:hover:not(:disabled) {
		background: var(--color-primary-hover);
		transform: translateY(-1px);
		box-shadow: 0 4px 12px rgba(var(--color-primary-rgb), 0.3);
	}
}
</style>
