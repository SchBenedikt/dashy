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
						@click="openNote(note)"
					>
						<div class="note-icon">
							<NoteTextIcon :size="24" />
						</div>
						<div class="note-info">
							<div class="note-title">{{ note.title || t('dashy', 'Untitled') }}</div>
							<div class="note-preview">{{ getPreview(note.content) }}</div>
							<div class="note-meta">
								<span class="note-category" v-if="note.category">{{ note.category }}</span>
								<span class="note-modified">{{ formatDate(note.modified) }}</span>
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

		<!-- Quick Note Modal -->
		<NcDialog
			v-if="showQuickNote"
			:name="t('dashy', 'Quick Note')"
			@closing="closeQuickNote"
		>
			<div class="quick-note-content">
				<input
					v-model="quickNote.title"
					type="text"
					:placeholder="t('dashy', 'Note title...')"
					class="note-title-input"
				>
				<textarea
					v-model="quickNote.content"
					:placeholder="t('dashy', 'Write your note here...')"
					class="note-content-input"
					rows="6"
				></textarea>
			</div>

			<template #actions>
				<NcButton @click="closeQuickNote">
					{{ t('dashy', 'Cancel') }}
				</NcButton>
				<NcButton type="primary" @click="saveQuickNote">
					{{ t('dashy', 'Save Note') }}
				</NcButton>
			</template>
		</NcDialog>
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
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
		NcDialog,
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
			quickNote: {
				title: '',
				content: '',
			},
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
			const now = new Date()
			const diff = now - date
			
			// Less than 24 hours ago
			if (diff < 24 * 60 * 60 * 1000) {
				return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
			}
			
			// Less than a week ago
			if (diff < 7 * 24 * 60 * 60 * 1000) {
				return date.toLocaleDateString([], { weekday: 'short' })
			}
			
			// Older
			return date.toLocaleDateString([], { month: 'short', day: 'numeric' })
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
		},
		createNote() {
			this.quickNote = {
				title: '',
				content: '',
			}
			this.showQuickNote = true
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
			this.quickNote = { title: '', content: '' }
		},
		async saveQuickNote() {
			try {
				const noteData = {
					title: this.quickNote.title || t('dashy', 'Untitled'),
					content: this.quickNote.content,
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

.quick-note-content {
	padding: 20px;
	
	.note-title-input {
		width: 100%;
		padding: 8px 12px;
		border: 1px solid var(--color-border);
		border-radius: 6px;
		background-color: var(--color-main-background);
		color: var(--color-main-text);
		font-size: 16px;
		font-weight: 500;
		margin-bottom: 12px;
		
		&:focus {
			outline: none;
			border-color: var(--color-primary);
		}
		
		&::placeholder {
			color: var(--color-text-maxcontrast);
		}
	}
	
	.note-content-input {
		width: 100%;
		padding: 12px;
		border: 1px solid var(--color-border);
		border-radius: 6px;
		background-color: var(--color-main-background);
		color: var(--color-main-text);
		font-family: inherit;
		font-size: 14px;
		line-height: 1.5;
		resize: vertical;
		
		&:focus {
			outline: none;
			border-color: var(--color-primary);
		}
		
		&::placeholder {
			color: var(--color-text-maxcontrast);
		}
	}
}
</style>
