<template>
	<div class="notes-settings">
		<!-- Settings triggered from widget header -->
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import FolderBrowser from '../FolderBrowser.vue'

export default {
	name: 'NotesSettings',
	components: {
		FolderBrowser,
	},
	props: {
		settings: {
			type: Object,
			default: () => ({}),
		},
	},
	data() {
		return {
			showModal: false,
			showFolderBrowser: false,
			modalElement: null,
			folderBrowserElement: null,
			localSettings: {
				maxNotes: this.settings.maxNotes || 10,
				showFavorites: this.settings.showFavorites || false,
				sortBy: this.settings.sortBy || 'modified',
				notesFolder: this.settings.notesFolder || '',
				...this.settings,
			},
			availableFolders: [],
		}
	},
	watch: {
		settings: {
			handler(newSettings) {
				this.localSettings = {
					maxNotes: newSettings.maxNotes || 10,
					showFavorites: newSettings.showFavorites || false,
					sortBy: newSettings.sortBy || 'modified',
					notesFolder: newSettings.notesFolder || '',
					...newSettings,
				}
			},
			deep: true,
		},
	},
	mounted() {
		// Listen for show event from parent widget
		this.$el.addEventListener('show-settings', this.showSettings)
		this.loadAvailableFolders()
	},
	beforeDestroy() {
		this.$el.removeEventListener('show-settings', this.showSettings)
		this.removeModal()
		this.closeFolderBrowser()
	},
	methods: {
		t,
		openSettings() {
			// Method called by WidgetContainer - delegates to showSettings
			console.log('NotesSettings openSettings called')
			this.showSettings()
		},
		showSettings() {
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
			this.modalElement.className = 'notes-settings-overlay'
			this.modalElement.addEventListener('click', this.handleOverlayClick)
			
			const modalContent = document.createElement('div')
			modalContent.className = 'notes-settings-modal'
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
					<h3>${t('dashy', 'Notes Settings')}</h3>
					<button class="close-btn" type="button" title="Close">✕</button>
				</div>
				<form class="settings-form">
					<div class="form-group">
						<label for="maxNotes">${t('dashy', 'Maximum notes to display')}</label>
						<input 
							type="number" 
							id="maxNotes" 
							min="1" 
							max="50" 
							value="${this.localSettings.maxNotes}"
						>
						<small class="setting-description">${t('dashy', 'Maximum number of notes to show in the widget (1-50)')}</small>
					</div>
					<div class="form-group">
						<label for="sortBy">${t('dashy', 'Sort notes by')}</label>
						<select id="sortBy">
							<option value="modified" ${this.localSettings.sortBy === 'modified' ? 'selected' : ''}>${t('dashy', 'Last modified')}</option>
							<option value="created" ${this.localSettings.sortBy === 'created' ? 'selected' : ''}>${t('dashy', 'Date created')}</option>
							<option value="title" ${this.localSettings.sortBy === 'title' ? 'selected' : ''}>${t('dashy', 'Title (A-Z)')}</option>
						</select>
					</div>
					<div class="form-group">
						<label for="notesFolder">${t('dashy', 'Notes storage folder')}</label>
						<div class="folder-selection">
							<div class="current-folder">
								<input 
									type="text" 
									id="notesFolderDisplay" 
									readonly
									value="${this.localSettings.notesFolder || t('dashy', 'Default folder')}"
									class="folder-display"
								>
								<button type="button" class="browse-btn" id="browseFolderBtn">
									${t('dashy', 'Browse...')}
								</button>
							</div>
							<input type="hidden" id="notesFolder" value="${this.localSettings.notesFolder}">
						</div>
						<small class="setting-description">${t('dashy', 'Choose the folder where new notes will be saved')}</small>
					</div>
					<div class="form-group">
						<label class="checkbox-label">
							<input type="checkbox" id="showFavorites" ${this.localSettings.showFavorites ? 'checked' : ''}>
							<span>${t('dashy', 'Show favorites first')}</span>
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

			// Browse folder button
			const browseFolderBtn = this.modalElement.querySelector('#browseFolderBtn')
			if (browseFolderBtn) {
				browseFolderBtn.addEventListener('click', (e) => {
					e.preventDefault()
					this.openFolderBrowser()
				})
			}
			
			// Input change handlers
			const maxNotesInput = this.modalElement.querySelector('#maxNotes')
			const sortBySelect = this.modalElement.querySelector('#sortBy')
			const showFavoritesInput = this.modalElement.querySelector('#showFavorites')
			
			maxNotesInput?.addEventListener('input', (e) => {
				this.localSettings.maxNotes = parseInt(e.target.value) || 10
			})
			sortBySelect?.addEventListener('change', (e) => {
				this.localSettings.sortBy = e.target.value
			})
			showFavoritesInput?.addEventListener('change', (e) => {
				this.localSettings.showFavorites = e.target.checked
			})
		},

		openFolderBrowser() {
			this.showFolderBrowser = true
			this.createFolderBrowser()
		},

		createFolderBrowser() {
			if (this.folderBrowserElement) return

			// Add escape key handler
			document.addEventListener('keydown', this.handleFolderBrowserEscapeKey)

			// Create overlay for folder browser
			this.folderBrowserElement = document.createElement('div')
			this.folderBrowserElement.className = 'folder-browser-overlay'
			this.folderBrowserElement.addEventListener('click', this.handleFolderBrowserOverlayClick)

			const browserContent = document.createElement('div')
			browserContent.className = 'folder-browser-modal'
			browserContent.addEventListener('click', (e) => e.stopPropagation())

			browserContent.innerHTML = `
				<div class="modal-header">
					<h3>${t('dashy', 'Select Folder')}</h3>
					<button class="close-btn" type="button" title="Close">✕</button>
				</div>
				<div class="modal-content">
					<div class="browser-content" id="folderBrowserContent">
						<!-- Folder browser will be mounted here -->
					</div>
				</div>
			`

			this.folderBrowserElement.appendChild(browserContent)
			document.body.appendChild(this.folderBrowserElement)

			// Mount Vue component
			this.mountFolderBrowser()

			// Bind events
			const closeBtn = this.folderBrowserElement.querySelector('.close-btn')
			if (closeBtn) {
				closeBtn.addEventListener('click', () => {
					this.closeFolderBrowser()
				})
			}
		},

		mountFolderBrowser() {
			const container = this.folderBrowserElement.querySelector('#folderBrowserContent')
			if (!container) return

			// Create folder browser component instance
			const FolderBrowserComponent = this.$options.components.FolderBrowser
			const browserInstance = new (this.$options._base.extend(FolderBrowserComponent))({
				propsData: {
					initialPath: '', // Start at root folder
				}
			})

			// Listen for events
			browserInstance.$on('folderSelected', this.onFolderSelected)
			browserInstance.$on('cancel', this.closeFolderBrowser)

			// Mount the component
			browserInstance.$mount()
			container.appendChild(browserInstance.$el)

			// Store reference for cleanup
			this.folderBrowserInstance = browserInstance
		},

		onFolderSelected(folder) {
			console.log('Folder selected:', folder)
			this.localSettings.notesFolder = folder.path
			
			// Update the display field in the modal
			const folderDisplay = this.modalElement.querySelector('#notesFolderDisplay')
			const folderHidden = this.modalElement.querySelector('#notesFolder')
			if (folderDisplay && folderHidden) {
				folderDisplay.value = folder.path || t('dashy', 'Default folder')
				folderHidden.value = folder.path
			}
			
			this.closeFolderBrowser()
		},

		closeFolderBrowser() {
			this.showFolderBrowser = false
			if (this.folderBrowserInstance) {
				this.folderBrowserInstance.$destroy()
				this.folderBrowserInstance = null
			}
			if (this.folderBrowserElement) {
				document.removeEventListener('keydown', this.handleFolderBrowserEscapeKey)
				this.folderBrowserElement.removeEventListener('click', this.handleFolderBrowserOverlayClick)
				document.body.removeChild(this.folderBrowserElement)
				this.folderBrowserElement = null
			}
		},

		handleFolderBrowserOverlayClick() {
			this.closeFolderBrowser()
		},

		handleFolderBrowserEscapeKey(event) {
			if (event.key === 'Escape' && this.showFolderBrowser) {
				this.closeFolderBrowser()
			}
		},
		
		updateSettings() {
			// Emit settings update to parent WidgetContainer
			this.$emit('update', { ...this.localSettings })
		},
		
		async loadAvailableFolders() {
			try {
				// Load available folders from Nextcloud Notes API
				const response = await axios.get(generateUrl('/apps/dashy/api/notes/folders'))
				
				if (response.data && response.data.folders) {
					this.availableFolders = response.data.folders
				} else {
					// Fallback: common folder names
					this.availableFolders = [
						{ path: '', name: t('dashy', 'Root folder') },
						{ path: 'Notes', name: 'Notes' },
						{ path: 'Documents', name: 'Documents' },
						{ path: 'Documents/Notes', name: 'Documents/Notes' },
					]
				}
			} catch (error) {
				console.warn('Failed to load folders:', error)
				// Fallback folders
				this.availableFolders = [
					{ path: '', name: t('dashy', 'Root folder') },
					{ path: 'Notes', name: 'Notes' },
					{ path: 'Documents', name: 'Documents' },
					{ path: 'Documents/Notes', name: 'Documents/Notes' },
				]
			}
		},
		
		saveSettings() {
			// Update settings from form inputs
			const maxNotesInput = this.modalElement.querySelector('#maxNotes')
			const sortBySelect = this.modalElement.querySelector('#sortBy')
			const showFavoritesInput = this.modalElement.querySelector('#showFavorites')
			const notesFolderHidden = this.modalElement.querySelector('#notesFolder')
			
			this.localSettings.maxNotes = parseInt(maxNotesInput.value) || 10
			this.localSettings.sortBy = sortBySelect.value
			this.localSettings.showFavorites = showFavoritesInput.checked
			this.localSettings.notesFolder = notesFolderHidden.value
			
			this.updateSettings()
			this.closeSettings()
		},
	},
}
</script>

<style scoped lang="scss">
.notes-settings {
	display: none; /* Hidden, triggered from widget header */
}
</style>

<style lang="scss">
/* Global modal styles - rendered in document.body */
.notes-settings-overlay {
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

.notes-settings-modal {
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

		.setting-description {
			display: block;
			font-size: 12px;
			color: var(--color-text-lighter);
			margin-top: 4px;
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

/* Folder Browser Overlay Styles */
.folder-browser-overlay {
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
	z-index: 99999 !important;
	padding: 20px;
}

.folder-browser-modal {
	background: var(--color-main-background);
	border-radius: 12px;
	box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
	max-width: 700px;
	width: 100%;
	max-height: 90vh;
	overflow: hidden;
	border: 1px solid var(--color-border);
	display: flex;
	flex-direction: column;
}

.folder-browser-modal .modal-header {
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

.folder-browser-modal .modal-content {
	padding: 24px;
	flex: 1;
	overflow: hidden;
	display: flex;
	flex-direction: column;
}

.folder-browser-modal .browser-content {
	flex: 1;
	display: flex;
	flex-direction: column;
	min-height: 0;
}

/* Adjust the folder browser component inside the modal */
.folder-browser-modal .folder-browser {
	border: none;
	border-radius: 0;
	background: transparent;
	height: 100%;
	display: flex;
	flex-direction: column;
}

.folder-browser-modal .folder-browser-header {
	padding: 16px;
	border-bottom: 1px solid var(--color-border);
	background: var(--color-background-dark);
	border-radius: 8px 8px 0 0;
}

.folder-browser-modal .folder-list {
	flex: 1;
	min-height: 300px;
	overflow-y: auto;
	padding: 12px;
	background: var(--color-main-background);
}

.folder-browser-modal .folder-browser-footer {
	padding: 16px;
	border-top: 1px solid var(--color-border);
	background: var(--color-background-dark);
	border-radius: 0 0 8px 8px;
}
</style>
