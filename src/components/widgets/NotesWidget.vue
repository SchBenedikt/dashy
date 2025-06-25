<template>
	<div class="notes-widget">
		<div v-if="loading" class="loading">
			<NcLoadingIcon :size="24" />
			<p>{{ t('dashy', 'Loading notes...') }}</p>
		</div>
		<div v-else-if="error" class="error">
			<AlertCircleIcon :size="24" />
			<p>{{ error }}</p>
		</div>
		<div v-else>
			<div v-if="displayMode === 'compact'" class="notes-compact">
				<div class="notes-count">
					<NoteTextIcon :size="20" />
					<span>{{ notes.length }} {{ t('dashy', 'notes') }}</span>
				</div>
				<div v-if="recentNotes.length > 0" class="recent-notes">
					<div 
						v-for="note in recentNotes.slice(0, 3)" 
						:key="note.id"
						class="note-item compact"
						@click="openNote(note)"
					>
						<div class="note-content">
							<div class="note-title">{{ note.title || t('dashy', 'Untitled') }}</div>
							<div class="note-preview">{{ getPreview(note.content) }}</div>
						</div>
					</div>
				</div>
			</div>
			<div v-else class="notes-list">
				<div class="notes-header">
					<h4>{{ t('dashy', 'Recent Notes') }}</h4>
					<div class="header-actions">
						<NcButton 
							type="tertiary" 
							:aria-label="t('dashy', 'Create new note')"
							@click="createNote"
						>
							<template #icon>
								<PlusIcon :size="16" />
							</template>
						</NcButton>
						<NcButton 
							type="tertiary" 
							:aria-label="t('dashy', 'Refresh')"
							@click="loadNotes"
						>
							<template #icon>
								<RefreshIcon :size="16" />
							</template>
						</NcButton>
						<NcButton 
							type="tertiary" 
							:aria-label="t('dashy', 'Open Notes app')"
							@click="openNotesApp"
						>
							<template #icon>
								<OpenInNewIcon :size="16" />
							</template>
						</NcButton>
					</div>
				</div>
				<div class="note-list">
					<div 
						v-for="note in displayedNotes.slice(0, settings.maxNotes || 10)" 
						:key="note.id"
						class="note-item"
						@click="showNotePreview(note)"
					>
						<div class="note-icon">
							<NoteTextIcon :size="24" />
						</div>
						<div class="note-info">
							<div class="note-title">{{ note.title || t('dashy', 'Untitled') }}</div>
							<div class="note-preview">{{ getPreview(note.content) }}</div>
							<div class="note-meta">
								<span class="note-category" v-if="note.category">{{ note.category }}</span>
								<span class="note-created">{{ formatDate(note.created || note.modified) }}</span>
							</div>
						</div>
						<div class="note-actions">
							<NcButton 
								type="tertiary" 
								:aria-label="t('dashy', 'Edit note')"
								@click.stop="editNote(note)"
							>
								<template #icon>
									<PencilIcon :size="16" />
								</template>
							</NcButton>
						</div>
					</div>
				</div>
			</div>
		</div>


	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NoteTextIcon from 'vue-material-design-icons/NoteText.vue'
import AlertCircleIcon from 'vue-material-design-icons/AlertCircle.vue'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'
import RefreshIcon from 'vue-material-design-icons/Refresh.vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import PencilIcon from 'vue-material-design-icons/Pencil.vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'NotesWidget',
	components: {
		NcButton,
		NcLoadingIcon,
		NoteTextIcon,
		AlertCircleIcon,
		OpenInNewIcon,
		RefreshIcon,
		PlusIcon,
		PencilIcon,
	},
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
			notes: [],
			loading: true,
			error: null,
			showQuickNote: false,
			showPreview: false,
			quickNote: {
				title: '',
				content: '',
				id: null,
			},
			previewNote: {},
			noteModalElement: null,
			previewModalElement: null,
		}
	},
	computed: {
		displayMode() {
			// Automatic display mode based on widget size
			if (this.widget.w <= 2 || this.widget.h <= 2) {
				return 'compact'
			}
			return 'full'
		},
		recentNotes() {
			return [...this.notes].sort((a, b) => b.modified - a.modified)
		},
		displayedNotes() {
			return this.recentNotes
		},
	},
	async mounted() {
		await this.loadNotes()
	},
	beforeDestroy() {
		this.removeNoteModal()
		this.removePreviewModal()
	},
	methods: {
		t,
		async loadNotes() {
			console.log('Loading notes with settings:', this.settings)
			try {
				this.loading = true
				this.error = null
				
				// Build API URL with folder parameter if specified
				let apiUrl = generateUrl('/apps/dashy/api/notes')
				if (this.settings.notesFolder) {
					apiUrl += `?folder=${encodeURIComponent(this.settings.notesFolder)}`
				}
				
				console.log('Notes API URL:', apiUrl)
				
				// Load notes from Nextcloud Notes app or simple API
				const response = await axios.get(apiUrl)
				console.log('Notes API response:', response.data)
				this.notes = response.data.notes || []
				
			} catch (error) {
				console.error('Failed to load notes:', error)
				console.error('Error response:', error.response)
				if (error.response?.status === 404) {
					this.error = t('dashy', 'Notes API not accessible')
				} else {
					this.error = t('dashy', 'Failed to load notes')
				}
			} finally {
				this.loading = false
			}
		},
		getPreview(content) {
			if (!content) return t('dashy', 'No content')
			
			// Strip HTML tags and get first 100 characters
			const stripped = content.replace(/<[^>]*>/g, '').trim()
			return stripped.length > 100 ? stripped.substring(0, 100) + '...' : stripped
		},
		formatDate(timestamp) {
			if (!timestamp) return ''
			
			const date = new Date(timestamp * 1000)
			
			// Format as DD.MM.YY, HH:MM
			const day = String(date.getDate()).padStart(2, '0')
			const month = String(date.getMonth() + 1).padStart(2, '0')
			const year = String(date.getFullYear()).substring(2)
			const hours = String(date.getHours()).padStart(2, '0')
			const minutes = String(date.getMinutes()).padStart(2, '0')
			
			return `${day}.${month}.${year}, ${hours}:${minutes}`
		},
		openNote(note) {
			// Open note in Notes app or text editor
			if (this.isNotesAppAvailable()) {
				window.open(generateUrl(`/apps/notes/${note.id}`), '_blank')
			} else {
				// Fallback to edit mode
				this.editNote(note)
			}
		},
		editNote(note) {
			this.quickNote = {
				id: note.id,
				title: note.title || '',
				content: note.content || '',
			}
			this.showQuickNote = true
			this.createNoteModal()
		},
		createNote() {
			this.quickNote = {
				title: '',
				content: '',
				id: null,
			}
			this.showQuickNote = true
			this.createNoteModal()
		},
		openNotesApp() {
			if (this.isNotesAppAvailable()) {
				window.open(generateUrl('/apps/notes'), '_blank')
			} else {
				// Fallback to create new note
				this.createNote()
			}
		},
		closeQuickNote() {
			this.showQuickNote = false
			this.removeNoteModal()
			this.quickNote = { title: '', content: '', id: null }
		},
		
		handleNoteModalEscapeKey(event) {
			if (event.key === 'Escape' && this.showQuickNote) {
				this.closeQuickNote()
			}
		},
		
		createNoteModal() {
			if (this.noteModalElement) return
			
			document.addEventListener('keydown', this.handleNoteModalEscapeKey)
			
			this.noteModalElement = document.createElement('div')
			this.noteModalElement.className = 'note-editor-overlay'
			this.noteModalElement.addEventListener('click', this.handleNoteModalOverlayClick)
			
			const modalContent = document.createElement('div')
			modalContent.className = 'note-editor-modal'
			modalContent.addEventListener('click', (e) => e.stopPropagation())
			
			modalContent.innerHTML = this.getNoteModalHTML()
			this.noteModalElement.appendChild(modalContent)
			
			document.body.appendChild(this.noteModalElement)
			this.bindNoteModalEvents()
			
			// Focus title input
			setTimeout(() => {
				const titleInput = this.noteModalElement.querySelector('#note-title')
				if (titleInput) {
					titleInput.focus()
					if (this.quickNote.title) {
						titleInput.select()
					}
				}
			}, 100)
		},
		
		removeNoteModal() {
			if (this.noteModalElement) {
				document.removeEventListener('keydown', this.handleNoteModalEscapeKey)
				this.noteModalElement.removeEventListener('click', this.handleNoteModalOverlayClick)
				document.body.removeChild(this.noteModalElement)
				this.noteModalElement = null
			}
		},
		
		handleNoteModalOverlayClick() {
			this.closeQuickNote()
		},
		
		getNoteModalHTML() {
			return `
				<div class="modal-header">
					<h3>${this.quickNote.id ? this.t('dashy', 'Edit Note') : this.t('dashy', 'Create Note')}</h3>
					<button class="close-btn" type="button" title="Close">✕</button>
				</div>
				<div class="modal-content">
					<form class="note-form">
						<div class="form-group">
							<label for="note-title">${this.t('dashy', 'Title')}</label>
							<input
								id="note-title"
								type="text"
								placeholder="${this.t('dashy', 'Note title...')}"
								value="${this.quickNote.title}"
								class="note-title-input"
							>
						</div>
						<div class="form-group">
							<label for="note-content">${this.t('dashy', 'Content')}</label>
							<textarea
								id="note-content"
								placeholder="${this.t('dashy', 'Write your note here...')}"
								rows="12"
								class="note-content-input"
							>${this.quickNote.content}</textarea>
						</div>
						<div class="form-actions">
							<button type="button" class="btn-cancel">${this.t('dashy', 'Cancel')}</button>
							<button type="submit" class="btn-save">${this.t('dashy', 'Save Note')}</button>
						</div>
					</form>
				</div>
			`
		},
		
		bindNoteModalEvents() {
			if (!this.noteModalElement) return
			
			const form = this.noteModalElement.querySelector('.note-form')
			form.addEventListener('submit', (e) => {
				e.preventDefault()
				this.saveQuickNote()
			})
			
			const closeBtn = this.noteModalElement.querySelector('.close-btn')
			if (closeBtn) {
				closeBtn.addEventListener('click', (e) => {
					e.preventDefault()
					e.stopPropagation()
					this.closeQuickNote()
				})
			}
			
			const cancelBtn = this.noteModalElement.querySelector('.btn-cancel')
			if (cancelBtn) {
				cancelBtn.addEventListener('click', (e) => {
					e.preventDefault()
					this.closeQuickNote()
				})
			}
			
			// Input change handlers
			const titleInput = this.noteModalElement.querySelector('#note-title')
			const contentTextarea = this.noteModalElement.querySelector('#note-content')
			
			titleInput?.addEventListener('input', (e) => {
				this.quickNote.title = e.target.value
			})
			contentTextarea?.addEventListener('input', (e) => {
				this.quickNote.content = e.target.value
			})
		},
		async saveQuickNote() {
			try {
				// Get current values from form
				const titleInput = this.noteModalElement.querySelector('#note-title')
				const contentTextarea = this.noteModalElement.querySelector('#note-content')
				
				const noteData = {
					title: titleInput.value || this.t('dashy', 'Untitled'),
					content: contentTextarea.value,
					folder: this.settings.notesFolder || '',
				}
				
				if (this.quickNote.id) {
					// Update existing note
					await axios.put(generateUrl(`/apps/dashy/api/notes/${this.quickNote.id}`), noteData)
				} else {
					// Create new note
					await axios.post(generateUrl('/apps/dashy/api/notes'), noteData)
				}
				
				await this.loadNotes()
				this.closeQuickNote()
				
			} catch (error) {
				console.error('Failed to save note:', error)
			}
		},
		isNotesAppAvailable() {
			// Check if Notes app is available (simplified check)
			return document.querySelector('a[href*="/apps/notes"]') !== null
		},
		showNotePreview(note) {
			this.previewNote = { ...note }
			this.showPreview = true
			this.createPreviewModal()
		},
		closeNotePreview() {
			this.showPreview = false
			this.removePreviewModal()
			this.previewNote = {}
		},
		editPreviewNote() {
			this.editNote(this.previewNote)
			this.closeNotePreview()
		},
		
		handlePreviewModalEscapeKey(event) {
			if (event.key === 'Escape' && this.showPreview) {
				this.closeNotePreview()
			}
		},
		
		createPreviewModal() {
			if (this.previewModalElement) return
			
			document.addEventListener('keydown', this.handlePreviewModalEscapeKey)
			
			this.previewModalElement = document.createElement('div')
			this.previewModalElement.className = 'note-preview-overlay'
			this.previewModalElement.addEventListener('click', this.handlePreviewModalOverlayClick)
			
			const modalContent = document.createElement('div')
			modalContent.className = 'note-preview-modal'
			modalContent.addEventListener('click', (e) => e.stopPropagation())
			
			modalContent.innerHTML = this.getPreviewModalHTML()
			this.previewModalElement.appendChild(modalContent)
			
			document.body.appendChild(this.previewModalElement)
			this.bindPreviewModalEvents()
		},
		
		removePreviewModal() {
			if (this.previewModalElement) {
				document.removeEventListener('keydown', this.handlePreviewModalEscapeKey)
				this.previewModalElement.removeEventListener('click', this.handlePreviewModalOverlayClick)
				document.body.removeChild(this.previewModalElement)
				this.previewModalElement = null
			}
		},
		
		handlePreviewModalOverlayClick() {
			this.closeNotePreview()
		},
		
		getPreviewModalHTML() {
			return `
				<div class="modal-header">
					<h3>${this.previewNote.title || this.t('dashy', 'Untitled')}</h3>
					<button class="close-btn" type="button" title="Close">✕</button>
				</div>
				<div class="modal-content">
					<div class="note-preview-content">
						<div class="note-meta-info">
							<span class="note-created-date">${this.t('dashy', 'Created')}: ${this.formatDate(this.previewNote.created || this.previewNote.modified)}</span>
							${this.previewNote.category ? `<span class="note-category-badge">${this.previewNote.category}</span>` : ''}
						</div>
						<div class="note-content-display">
							${this.previewNote.content}
						</div>
					</div>
					<div class="modal-actions">
						<button type="button" class="btn-cancel">${this.t('dashy', 'Close')}</button>
						<button type="button" class="btn-save">${this.t('dashy', 'Edit')}</button>
					</div>
				</div>
			`
		},
		
		bindPreviewModalEvents() {
			if (!this.previewModalElement) return
			
			const closeBtn = this.previewModalElement.querySelector('.close-btn')
			if (closeBtn) {
				closeBtn.addEventListener('click', (e) => {
					e.preventDefault()
					e.stopPropagation()
					this.closeNotePreview()
				})
			}
			
			const cancelBtn = this.previewModalElement.querySelector('.btn-cancel')
			if (cancelBtn) {
				cancelBtn.addEventListener('click', (e) => {
					e.preventDefault()
					this.closeNotePreview()
				})
			}
			
			const editBtn = this.previewModalElement.querySelector('.btn-save')
			if (editBtn) {
				editBtn.addEventListener('click', (e) => {
					e.preventDefault()
					this.editPreviewNote()
				})
			}
		},
	},
}
</script>

<style scoped lang="scss">
.notes-widget {
	height: 100%;
	display: flex;
	flex-direction: column;
}

.loading, .error {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	height: 100%;
	text-align: center;
	color: var(--color-text-maxcontrast);
	
	p {
		margin: 8px 0 0 0;
		font-size: 14px;
	}
}

.notes-compact {
	.notes-count {
		display: flex;
		align-items: center;
		gap: 8px;
		margin-bottom: 12px;
		font-weight: 500;
		color: var(--color-text-dark);
	}
	
	.recent-notes {
		display: flex;
		flex-direction: column;
		gap: 6px;
	}
	
	.note-item.compact {
		padding: 8px;
		border-radius: 6px;
		cursor: pointer;
		transition: background-color 0.2s;
		border: 1px solid var(--color-border);
		
		&:hover {
			background-color: var(--color-background-hover);
		}
		
		.note-content {
			.note-title {
				font-size: 12px;
				font-weight: 500;
				color: var(--color-text-dark);
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
				margin-bottom: 2px;
			}
			
			.note-preview {
				font-size: 10px;
				color: var(--color-text-maxcontrast);
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
			}
		}
	}
}

.notes-list {
	height: 100%;
	display: flex;
	flex-direction: column;
}

.notes-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 12px;
	
	h4 {
		margin: 0;
		font-size: 14px;
		font-weight: 600;
		color: var(--color-text-dark);
	}
	
	.header-actions {
		display: flex;
		gap: 4px;
	}
}

.note-list {
	flex: 1;
	overflow-y: auto;
	
	.note-item {
		display: flex;
		align-items: flex-start;
		gap: 12px;
		padding: 12px;
		border-radius: 8px;
		cursor: pointer;
		transition: background-color 0.2s;
		border: 1px solid var(--color-border);
		margin-bottom: 8px;
		
		&:hover {
			background-color: var(--color-background-hover);
		}
		
		.note-icon {
			display: flex;
			align-items: center;
			justify-content: center;
			width: 32px;
			height: 32px;
			flex-shrink: 0;
			color: var(--color-text-maxcontrast);
		}
		
		.note-info {
			flex: 1;
			min-width: 0;
			
			.note-title {
				font-weight: 500;
				color: var(--color-text-dark);
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
				margin-bottom: 4px;
			}
			
			.note-preview {
				font-size: 12px;
				color: var(--color-text-light);
				line-height: 1.4;
				margin-bottom: 6px;
			}
			
			.note-meta {
				display: flex;
				gap: 8px;
				font-size: 11px;
				color: var(--color-text-maxcontrast);
				
				.note-category {
					background-color: var(--color-background-dark);
					padding: 2px 6px;
					border-radius: 3px;
				}
			}
		}
		
		.note-actions {
			display: flex;
			gap: 4px;
			opacity: 0;
			transition: opacity 0.2s;
		}
		
		&:hover .note-actions {
			opacity: 1;
		}
	}
}
</style>

<style lang="scss">
/* Global modal styles - rendered in document.body */
.note-editor-overlay {
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

.note-editor-modal {
	background: var(--color-main-background);
	border-radius: 12px;
	box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
	max-width: 500px;
	width: 100%;
	max-height: 90vh;
	overflow: auto;
	border: 1px solid var(--color-border);
}

.note-preview-overlay {
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

.note-preview-modal {
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

.modal-content {
	padding: 24px;
}

.note-form {
	margin: 0;
	padding: 0;
}

.form-group {
	margin-bottom: 20px;

	label {
		display: block;
		margin-bottom: 8px;
		font-weight: 500;
		color: var(--color-main-text);
		font-size: 14px;
	}

	.note-title-input {
		width: 100%;
		padding: 12px 16px;
		border: 1px solid var(--color-border);
		border-radius: 8px;
		background-color: var(--color-main-background);
		color: var(--color-main-text);
		font-size: 16px;
		font-weight: 500;
		
		&:focus {
			outline: none;
			border-color: var(--color-primary);
			box-shadow: 0 0 0 2px var(--color-primary-light);
		}
		
		&::placeholder {
			color: var(--color-text-maxcontrast);
		}
	}
	
	.note-content-input {
		width: 100%;
		padding: 16px;
		border: 1px solid var(--color-border);
		border-radius: 8px;
		background-color: var(--color-main-background);
		color: var(--color-main-text);
		font-family: inherit;
		font-size: 14px;
		line-height: 1.6;
		resize: vertical;
		min-height: 300px;
		
		&:focus {
			outline: none;
			border-color: var(--color-primary);
			box-shadow: 0 0 0 2px var(--color-primary-light);
		}
		
		&::placeholder {
			color: var(--color-text-maxcontrast);
		}
	}
}

.form-actions {
	display: flex;
	gap: 12px;
	justify-content: flex-end;
	margin-top: 0;
	padding-top: 16px;
	border-top: 1px solid var(--color-border);
}

.modal-actions {
	display: flex;
	gap: 12px;
	justify-content: flex-end;
	margin-top: 20px;
	padding-top: 16px;
	border-top: 1px solid var(--color-border);
}

.btn-cancel, .btn-save {
	padding: 8px 16px;
	border: 1px solid var(--color-border);
	border-radius: 6px;
	background: var(--color-main-background);
	color: var(--color-main-text);
	cursor: pointer;
	font-size: 14px;
	font-weight: 500;
	transition: all 0.2s ease;

	&:hover {
		background: var(--color-background-hover);
		border-color: var(--color-primary);
	}
}

.btn-save {
	background: var(--color-primary);
	border-color: var(--color-primary);
	color: var(--color-primary-text);

	&:hover {
		background: var(--color-primary-hover);
		border-color: var(--color-primary-hover);
	}
}

/* Note Preview Specific Styles */
.note-preview-content {
	.note-meta-info {
		display: flex;
		gap: 12px;
		margin-bottom: 16px;
		font-size: 14px;
		
		.note-created-date {
			color: var(--color-text-maxcontrast);
		}
		
		.note-category-badge {
			background-color: var(--color-primary-light);
			color: var(--color-primary-text);
			padding: 2px 8px;
			border-radius: 12px;
			font-size: 12px;
			font-weight: 500;
		}
	}
	
	.note-content-display {
		background: var(--color-background-dark);
		border: 1px solid var(--color-border);
		border-radius: 8px;
		padding: 16px;
		white-space: pre-wrap;
		font-family: inherit;
		font-size: 14px;
		line-height: 1.6;
		color: var(--color-main-text);
		max-height: 400px;
		overflow-y: auto;
	}
}
</style>

