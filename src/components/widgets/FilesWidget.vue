<template>
	<div class="files-widget">
		<div v-if="loading" class="loading">
			<NcLoadingIcon :size="24" />
			<p>{{ t('dashy', 'Loading files...') }}</p>
		</div>
		<div v-else-if="error" class="error">
			<AlertCircleIcon :size="24" />
			<p>{{ error }}</p>
		</div>
		<div v-else>
			<div v-if="displayMode === 'compact'" class="files-compact">
				<div class="files-count">
					<FolderIcon :size="20" />
					<span>{{ files.length }} {{ t('dashy', 'recent files') }}</span>
				</div>
				<div v-if="recentFiles.length > 0" class="recent-files">
					<div 
						v-for="file in recentFiles.slice(0, 3)" 
						:key="file.id"
						class="file-item compact"
						@click="openFile(file)"
					>
						<component :is="getFileIcon(file)" :size="16" />
						<span class="file-name">{{ file.name }}</span>
					</div>
				</div>
			</div>
			<div v-else class="files-list">
				<div class="files-header">
					<h4>{{ t('dashy', 'Recent Files') }}</h4>
					<div class="header-actions">
						<NcButton 
							type="tertiary" 
							:aria-label="t('dashy', 'Refresh')"
							@click="loadFiles"
						>
							<template #icon>
								<RefreshIcon :size="16" />
							</template>
						</NcButton>
						<NcButton 
							type="tertiary" 
							:aria-label="t('dashy', 'Open Files app')"
							@click="openFilesApp"
						>
							<template #icon>
								<OpenInNewIcon :size="16" />
							</template>
						</NcButton>
					</div>
				</div>
				<div class="view-toggle" v-if="files.length > 0">
					<NcButton 
						:type="viewMode === 'recent' ? 'primary' : 'tertiary'"
						@click="viewMode = 'recent'"
					>
						{{ t('dashy', 'Recent') }}
					</NcButton>
					<NcButton 
						:type="viewMode === 'favorites' ? 'primary' : 'tertiary'"
						@click="viewMode = 'favorites'"
					>
						{{ t('dashy', 'Favorites') }}
					</NcButton>
				</div>
				<div class="file-list">
					<div 
						v-for="file in displayedFiles.slice(0, settings.maxFiles || 15)" 
						:key="file.id"
						class="file-item"
						@click="openFile(file)"
					>
						<div class="file-icon">
							<component :is="getFileIcon(file)" :size="24" />
						</div>
						<div class="file-info">
							<div class="file-name">{{ file.name }}</div>
							<div class="file-details">
								<span class="file-size">{{ formatFileSize(file.size) }}</span>
								<span class="file-modified">{{ formatDate(file.mtime) }}</span>
							</div>
						</div>
						<div class="file-actions">
							<NcButton 
								type="tertiary" 
								:aria-label="t('dashy', 'Share file')"
								@click.stop="shareFile(file)"
							>
								<template #icon>
									<ShareIcon :size="16" />
								</template>
							</NcButton>
							<NcButton 
								type="tertiary" 
								:aria-label="t('dashy', 'Download file')"
								@click.stop="downloadFile(file)"
							>
								<template #icon>
									<DownloadIcon :size="16" />
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
import FolderIcon from 'vue-material-design-icons/Folder.vue'
import FileIcon from 'vue-material-design-icons/File.vue'
import FileDocumentIcon from 'vue-material-design-icons/FileDocument.vue'
import FileImageIcon from 'vue-material-design-icons/FileImage.vue'
import FileMusicIcon from 'vue-material-design-icons/FileMusic.vue'
import FileVideoIcon from 'vue-material-design-icons/FileVideo.vue'
import FilePresentationIcon from 'vue-material-design-icons/FilePresentationBox.vue'
import FileSpreadsheetIcon from 'vue-material-design-icons/GoogleSpreadsheet.vue'
import FilePdfIcon from 'vue-material-design-icons/FilePdfBox.vue'
import AlertCircleIcon from 'vue-material-design-icons/AlertCircle.vue'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'
import RefreshIcon from 'vue-material-design-icons/Refresh.vue'
import ShareIcon from 'vue-material-design-icons/Share.vue'
import DownloadIcon from 'vue-material-design-icons/Download.vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'FilesWidget',
	components: {
		NcButton,
		NcLoadingIcon,
		FolderIcon,
		FileIcon,
		FileDocumentIcon,
		FileImageIcon,
		FileMusicIcon,
		FileVideoIcon,
		FilePresentationIcon,
		FileSpreadsheetIcon,
		FilePdfIcon,
		AlertCircleIcon,
		OpenInNewIcon,
		RefreshIcon,
		ShareIcon,
		DownloadIcon,
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
			files: [],
			loading: true,
			error: null,
			viewMode: 'recent', // 'recent' or 'favorites'
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
		recentFiles() {
			return [...this.files]
				.filter(file => file.type === 'file')
				.sort((a, b) => b.mtime - a.mtime)
		},
		favoriteFiles() {
			return this.files.filter(file => file.favorite && file.type === 'file')
		},
		displayedFiles() {
			return this.viewMode === 'favorites' ? this.favoriteFiles : this.recentFiles
		},
	},
	async mounted() {
		await this.loadFiles()
	},
	methods: {
		t,
		async loadFiles() {
			try {
				this.loading = true
				this.error = null
				
				// Load recent files from Nextcloud Files API
				const response = await axios.get(generateUrl('/apps/dashy/api/files'))
				this.files = response.data.files || []
				
			} catch (error) {
				console.error('Failed to load files:', error)
				if (error.response?.status === 404) {
					this.error = t('dashy', 'Files API not accessible')
				} else {
					this.error = t('dashy', 'Failed to load files')
				}
			} finally {
				this.loading = false
			}
		},
		getFileIcon(file) {
			if (file.type === 'dir') return FolderIcon
			
			const extension = file.name.split('.').pop()?.toLowerCase() || ''
			const mimeType = file.mimetype || ''
			
			// Images
			if (mimeType.startsWith('image/') || ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'].includes(extension)) {
				return FileImageIcon
			}
			
			// Documents
			if (mimeType.includes('document') || ['doc', 'docx', 'odt', 'rtf'].includes(extension)) {
				return FileDocumentIcon
			}
			
			// Spreadsheets
			if (mimeType.includes('spreadsheet') || ['xls', 'xlsx', 'ods', 'csv'].includes(extension)) {
				return FileSpreadsheetIcon
			}
			
			// Presentations
			if (mimeType.includes('presentation') || ['ppt', 'pptx', 'odp'].includes(extension)) {
				return FilePresentationIcon
			}
			
			// PDFs
			if (mimeType === 'application/pdf' || extension === 'pdf') {
				return FilePdfIcon
			}
			
			// Audio
			if (mimeType.startsWith('audio/') || ['mp3', 'wav', 'ogg', 'flac', 'm4a'].includes(extension)) {
				return FileMusicIcon
			}
			
			// Video
			if (mimeType.startsWith('video/') || ['mp4', 'avi', 'mkv', 'mov', 'wmv'].includes(extension)) {
				return FileVideoIcon
			}
			
			return FileIcon
		},
		formatFileSize(bytes) {
			if (!bytes) return '0 B'
			
			const units = ['B', 'KB', 'MB', 'GB']
			let size = bytes
			let unitIndex = 0
			
			while (size >= 1024 && unitIndex < units.length - 1) {
				size /= 1024
				unitIndex++
			}
			
			return `${Math.round(size * 10) / 10} ${units[unitIndex]}`
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
		openFile(file) {
			// Open file in appropriate viewer/editor
			const fileUrl = generateUrl(`/apps/files/?dir=${encodeURIComponent(file.path)}&openfile=${encodeURIComponent(file.id)}`)
			window.open(fileUrl, '_blank')
		},
		openFilesApp() {
			window.open(generateUrl('/apps/files'), '_blank')
		},
		shareFile(file) {
			// Open sharing dialog - simplified implementation
			window.open(generateUrl(`/apps/files/?dir=${encodeURIComponent(file.path)}&openfile=${encodeURIComponent(file.id)}&view=sharing`), '_blank')
		},
		downloadFile(file) {
			// Download file
			const downloadUrl = generateUrl(`/remote.php/webdav${file.path}/${file.name}`)
			const link = document.createElement('a')
			link.href = downloadUrl
			link.download = file.name
			document.body.appendChild(link)
			link.click()
			document.body.removeChild(link)
		},
	},
}
</script>

<style scoped lang="scss">
.files-widget {
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

.files-compact {
	.files-count {
		display: flex;
		align-items: center;
		gap: 8px;
		margin-bottom: 12px;
		font-weight: 500;
		color: var(--color-text-dark);
	}
	
	.recent-files {
		display: flex;
		flex-direction: column;
		gap: 6px;
	}
	
	.file-item.compact {
		display: flex;
		align-items: center;
		gap: 8px;
		padding: 6px;
		border-radius: 6px;
		cursor: pointer;
		transition: background-color 0.2s;
		
		&:hover {
			background-color: var(--color-background-hover);
		}
		
		.file-name {
			font-size: 12px;
			font-weight: 500;
			color: var(--color-text-dark);
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}
	}
}

.files-list {
	height: 100%;
	display: flex;
	flex-direction: column;
}

.files-header {
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

.view-toggle {
	display: flex;
	gap: 4px;
	margin-bottom: 12px;
}

.file-list {
	flex: 1;
	overflow-y: auto;
	
	.file-item {
		display: flex;
		align-items: center;
		gap: 12px;
		padding: 10px;
		border-radius: 8px;
		cursor: pointer;
		transition: background-color 0.2s;
		
		&:hover {
			background-color: var(--color-background-hover);
		}
		
		.file-icon {
			display: flex;
			align-items: center;
			justify-content: center;
			width: 32px;
			height: 32px;
			flex-shrink: 0;
			color: var(--color-text-maxcontrast);
		}
		
		.file-info {
			flex: 1;
			min-width: 0;
			
			.file-name {
				font-weight: 500;
				color: var(--color-text-dark);
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
			}
			
			.file-details {
				display: flex;
				gap: 8px;
				font-size: 12px;
				color: var(--color-text-maxcontrast);
				margin-top: 2px;
				
				.file-size::after {
					content: '•';
					margin-left: 8px;
				}
			}
		}
		
		.file-actions {
			display: flex;
			gap: 4px;
			opacity: 0;
			transition: opacity 0.2s;
		}
		
		&:hover .file-actions {
			opacity: 1;
		}
	}
}
</style>
