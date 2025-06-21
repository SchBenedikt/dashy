<template>
	<div class="todo-settings">
		<!-- Settings are triggered from widget header, no UI needed here -->
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'TodoSettings',
	props: {
		settings: {
			type: Object,
			default: () => ({}),
		},
	},
	data() {
		return {
			localSettings: {
				maxTasks: 10,
				displayMode: '',
				showCompleted: true,
				showHighPriority: true,
				showMediumPriority: true,
				showLowPriority: true,
				refreshInterval: 5,
				...this.settings,
			},
			showModal: false,
			modalElement: null,
		}
	},
	mounted() {
		console.log('TodoSettings component mounted', this)
	},
	beforeDestroy() {
		document.removeEventListener('keydown', this.handleEscapeKey)
		this.removeModal()
	},
	methods: {
		t,
		updateSettings() {
			this.$emit('update', this.localSettings)
		},
		
		openSettings() {
			this.showModal = true
			this.createModal()
		},
		
		closeSettings() {
			this.showModal = false
			this.removeModal()
		},
		
		handleEscapeKey(event) {
			if (event.key === 'Escape' && this.showModal) {
				this.closeSettings()
			}
		},
		
		createModal() {
			if (this.modalElement) return
			
			document.addEventListener('keydown', this.handleEscapeKey)
			
			this.modalElement = document.createElement('div')
			this.modalElement.className = 'todo-settings-overlay'
			this.modalElement.addEventListener('click', this.handleOverlayClick)
			
			const modalContent = document.createElement('div')
			modalContent.className = 'todo-settings-modal'
			modalContent.addEventListener('click', (e) => e.stopPropagation())
			
			modalContent.innerHTML = this.getModalHTML()
			this.modalElement.appendChild(modalContent)
			
			document.body.appendChild(this.modalElement)
			this.bindModalEvents()
		},
		
		removeModal() {
			if (this.modalElement) {
				document.removeEventListener('keydown', this.handleEscapeKey)
				this.modalElement.removeEventListener('click', this.handleOverlayClick)
				document.body.removeChild(this.modalElement)
				this.modalElement = null
			}
		},
		
		handleOverlayClick() {
			this.closeSettings()
		},
		
		getModalHTML() {
			return `
				<div class="modal-header">
					<h3>${t('dashy', 'Todo Settings')}</h3>
					<button class="close-btn" type="button" title="Close">✕</button>
				</div>
				<form class="settings-form">
					<div class="form-group">
						<label for="maxTasks">${t('dashy', 'Maximum tasks to show')}</label>
						<input 
							type="number" 
							id="maxTasks" 
							min="1" 
							max="50" 
							value="${this.localSettings.maxTasks}"
						>
					</div>
					<div class="form-group">
						<label for="displayMode">${t('dashy', 'Display mode')}</label>
						<select id="displayMode">
							<option value="" ${this.localSettings.displayMode === '' ? 'selected' : ''}>${t('dashy', 'Auto (based on widget size)')}</option>
							<option value="compact" ${this.localSettings.displayMode === 'compact' ? 'selected' : ''}>${t('dashy', 'Compact')}</option>
							<option value="list" ${this.localSettings.displayMode === 'list' ? 'selected' : ''}>${t('dashy', 'List')}</option>
							<option value="detailed" ${this.localSettings.displayMode === 'detailed' ? 'selected' : ''}>${t('dashy', 'Detailed')}</option>
						</select>
					</div>
					<div class="form-group">
						<label for="refreshInterval">${t('dashy', 'Refresh interval (minutes)')}</label>
						<select id="refreshInterval">
							<option value="1" ${this.localSettings.refreshInterval === 1 ? 'selected' : ''}>${t('dashy', '1 minute')}</option>
							<option value="5" ${this.localSettings.refreshInterval === 5 ? 'selected' : ''}>${t('dashy', '5 minutes')}</option>
							<option value="10" ${this.localSettings.refreshInterval === 10 ? 'selected' : ''}>${t('dashy', '10 minutes')}</option>
							<option value="30" ${this.localSettings.refreshInterval === 30 ? 'selected' : ''}>${t('dashy', '30 minutes')}</option>
							<option value="60" ${this.localSettings.refreshInterval === 60 ? 'selected' : ''}>${t('dashy', '1 hour')}</option>
						</select>
					</div>
					<div class="form-group">
						<label class="checkbox-label">
							<input type="checkbox" id="showCompleted" ${this.localSettings.showCompleted ? 'checked' : ''}>
							<span>${t('dashy', 'Include completed tasks in the list')}</span>
						</label>
					</div>
					<div class="form-group">
						<label>${t('dashy', 'Priority filter')}</label>
						<div class="priority-filters">
							<label class="checkbox-label priority-option">
								<input type="checkbox" id="showHighPriority" ${this.localSettings.showHighPriority ? 'checked' : ''}>
								<span>🔴 ${t('dashy', 'High priority')}</span>
							</label>
							<label class="checkbox-label priority-option">
								<input type="checkbox" id="showMediumPriority" ${this.localSettings.showMediumPriority ? 'checked' : ''}>
								<span>🟡 ${t('dashy', 'Medium priority')}</span>
							</label>
							<label class="checkbox-label priority-option">
								<input type="checkbox" id="showLowPriority" ${this.localSettings.showLowPriority ? 'checked' : ''}>
								<span>🔵 ${t('dashy', 'Low priority')}</span>
							</label>
						</div>
					</div>
					<div class="form-actions">
						<button type="button" class="btn-cancel">${t('dashy', 'Cancel')}</button>
						<button type="submit" class="btn-save">${t('dashy', 'Save')}</button>
					</div>
				</form>
			`
		},
		
		bindModalEvents() {
			if (!this.modalElement) return
			
			const form = this.modalElement.querySelector('.settings-form')
			form.addEventListener('submit', (e) => {
				e.preventDefault()
				this.saveSettings()
			})
			
			const closeBtn = this.modalElement.querySelector('.close-btn')
			if (closeBtn) {
				closeBtn.addEventListener('click', (e) => {
					e.preventDefault()
					e.stopPropagation()
					this.closeSettings()
				})
			}
			
			const cancelBtn = this.modalElement.querySelector('.btn-cancel')
			if (cancelBtn) {
				cancelBtn.addEventListener('click', (e) => {
					e.preventDefault()
					this.closeSettings()
				})
			}
			
			// Input change handlers
			const maxTasksInput = this.modalElement.querySelector('#maxTasks')
			const displayModeSelect = this.modalElement.querySelector('#displayMode')
			const refreshIntervalSelect = this.modalElement.querySelector('#refreshInterval')
			const showCompletedInput = this.modalElement.querySelector('#showCompleted')
			const showHighPriorityInput = this.modalElement.querySelector('#showHighPriority')
			const showMediumPriorityInput = this.modalElement.querySelector('#showMediumPriority')
			const showLowPriorityInput = this.modalElement.querySelector('#showLowPriority')
			
			maxTasksInput?.addEventListener('input', (e) => {
				this.localSettings.maxTasks = parseInt(e.target.value) || 10
			})
			displayModeSelect?.addEventListener('change', (e) => {
				this.localSettings.displayMode = e.target.value
			})
			refreshIntervalSelect?.addEventListener('change', (e) => {
				this.localSettings.refreshInterval = parseInt(e.target.value) || 5
			})
			showCompletedInput?.addEventListener('change', (e) => {
				this.localSettings.showCompleted = e.target.checked
			})
			showHighPriorityInput?.addEventListener('change', (e) => {
				this.localSettings.showHighPriority = e.target.checked
			})
			showMediumPriorityInput?.addEventListener('change', (e) => {
				this.localSettings.showMediumPriority = e.target.checked
			})
			showLowPriorityInput?.addEventListener('change', (e) => {
				this.localSettings.showLowPriority = e.target.checked
			})
		},
		
		saveSettings() {
			// Update settings from form inputs
			const maxTasksInput = this.modalElement.querySelector('#maxTasks')
			const displayModeSelect = this.modalElement.querySelector('#displayMode')
			const refreshIntervalSelect = this.modalElement.querySelector('#refreshInterval')
			const showCompletedInput = this.modalElement.querySelector('#showCompleted')
			const showHighPriorityInput = this.modalElement.querySelector('#showHighPriority')
			const showMediumPriorityInput = this.modalElement.querySelector('#showMediumPriority')
			const showLowPriorityInput = this.modalElement.querySelector('#showLowPriority')
			
			this.localSettings.maxTasks = parseInt(maxTasksInput.value) || 10
			this.localSettings.displayMode = displayModeSelect.value
			this.localSettings.refreshInterval = parseInt(refreshIntervalSelect.value) || 5
			this.localSettings.showCompleted = showCompletedInput.checked
			this.localSettings.showHighPriority = showHighPriorityInput.checked
			this.localSettings.showMediumPriority = showMediumPriorityInput.checked
			this.localSettings.showLowPriority = showLowPriorityInput.checked
			
			this.updateSettings()
			this.closeSettings()
		},
	},
}
</script>

<style scoped lang="scss">
.todo-settings {
	display: none; /* Hidden, triggered from widget header */
}
</style>

<style lang="scss">
/* Global modal styles - rendered in document.body */
.todo-settings-overlay {
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

.todo-settings-modal {
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

.settings-form {
	padding: 24px;

	.form-group {
		margin-bottom: 20px;

		label {
			display: block;
			margin-bottom: 6px;
			font-weight: 500;
			color: var(--color-main-text);
			font-size: 14px;

			&.checkbox-label {
				display: flex;
				align-items: center;
				gap: 8px;
				font-weight: normal;

				input[type="checkbox"] {
					width: auto;
					margin: 0;
				}
			}
		}

		input, select {
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
		}

		.priority-filters {
			margin-top: 8px;
			display: flex;
			flex-direction: column;
			gap: 8px;
		}

		.checkbox-label {
			display: flex;
			align-items: center;
			gap: 8px;
			font-weight: normal;
			color: var(--color-text-lighter);

			input[type="checkbox"] {
				width: auto;
				margin: 0;
			}
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

.btn-cancel, .btn-save {
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
