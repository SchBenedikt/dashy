<template>
	<div class="clock-settings">
		<!-- Settings are triggered from widget header, no UI needed here -->
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'ClockSettings',
	props: {
		settings: {
			type: Object,
			default: () => ({}),
		},
	},
	data() {
		return {
			localSettings: {
				format24h: false,
				showSeconds: false,
				showTimezone: false,
				...this.settings,
			},
			showModal: false,
			modalElement: null,
		}
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
			this.modalElement.className = 'clock-settings-overlay'
			this.modalElement.addEventListener('click', this.handleOverlayClick)
			
			const modalContent = document.createElement('div')
			modalContent.className = 'clock-settings-modal'
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
					<h3>${t('dashy', 'Clock Settings')}</h3>
					<button class="close-btn" type="button" title="Close">✕</button>
				</div>
				<form class="settings-form">
					<div class="form-group">
						<label class="checkbox-label">
							<input type="checkbox" id="format24h" ${this.localSettings.format24h ? 'checked' : ''}>
							<span>${t('dashy', 'Use 24-hour format')}</span>
						</label>
					</div>
					<div class="form-group">
						<label class="checkbox-label">
							<input type="checkbox" id="showSeconds" ${this.localSettings.showSeconds ? 'checked' : ''}>
							<span>${t('dashy', 'Show seconds')}</span>
						</label>
					</div>
					<div class="form-group">
						<label class="checkbox-label">
							<input type="checkbox" id="showTimezone" ${this.localSettings.showTimezone ? 'checked' : ''}>
							<span>${t('dashy', 'Show timezone')}</span>
						</label>
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
			const format24hInput = this.modalElement.querySelector('#format24h')
			const showSecondsInput = this.modalElement.querySelector('#showSeconds')
			const showTimezoneInput = this.modalElement.querySelector('#showTimezone')
			
			format24hInput?.addEventListener('change', (e) => {
				this.localSettings.format24h = e.target.checked
			})
			showSecondsInput?.addEventListener('change', (e) => {
				this.localSettings.showSeconds = e.target.checked
			})
			showTimezoneInput?.addEventListener('change', (e) => {
				this.localSettings.showTimezone = e.target.checked
			})
		},
		
		saveSettings() {
			// Update settings from form inputs
			const format24hInput = this.modalElement.querySelector('#format24h')
			const showSecondsInput = this.modalElement.querySelector('#showSeconds')
			const showTimezoneInput = this.modalElement.querySelector('#showTimezone')
			
			this.localSettings.format24h = format24hInput.checked
			this.localSettings.showSeconds = showSecondsInput.checked
			this.localSettings.showTimezone = showTimezoneInput.checked
			
			this.updateSettings()
			this.closeSettings()
		},
	},
}
</script>

<style scoped lang="scss">
.clock-settings {
	display: none; /* Hidden, triggered from widget header */
}
</style>

<style lang="scss">
/* Global modal styles - rendered in document.body */
.clock-settings-overlay {
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

.clock-settings-modal {
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
