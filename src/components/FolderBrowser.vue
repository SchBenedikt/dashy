<template>
	<div class="folder-browser">
		<div class="folder-browser-header">
			<div class="navigation-path">
				<span v-for="(segment, index) in pathSegments" :key="index" class="path-segment">
					<button 
						v-if="index < pathSegments.length - 1"
						@click="navigateToPath(getPathUpTo(index))"
						class="path-button"
					>
						{{ segment.name }}
					</button>
					<span v-else class="current-segment">{{ segment.name }}</span>
					<span v-if="index < pathSegments.length - 1" class="path-separator">/</span>
				</span>
			</div>
			<div class="folder-actions">
				<NcButton 
					v-if="currentPath !== ''"
					type="tertiary" 
					:aria-label="t('dashy', 'Go up one level')"
					@click="goUp"
				>
					<template #icon>
						<ChevronUpIcon :size="16" />
					</template>
				</NcButton>
				<NcButton 
					type="tertiary" 
					:aria-label="t('dashy', 'Refresh')"
					@click="loadFolders"
				>
					<template #icon>
						<RefreshIcon :size="16" />
					</template>
				</NcButton>
			</div>
		</div>

		<div v-if="loading" class="loading">
			<NcLoadingIcon :size="24" />
			<p>{{ t('dashy', 'Loading folders...') }}</p>
		</div>

		<div v-else-if="error" class="error">
			<AlertCircleIcon :size="24" />
			<p>{{ error }}</p>
		</div>

		<div v-else class="folder-list">
			<div 
				v-for="folder in folders" 
				:key="folder.path"
				class="folder-item"
				@click="selectFolder(folder)"
				@dblclick="navigateToFolder(folder)"
				:class="{ 'selected': selectedFolder && selectedFolder.path === folder.path }"
			>
				<div class="folder-icon">
					<FolderIcon :size="20" />
				</div>
				<div class="folder-info">
					<div class="folder-name">{{ folder.name }}</div>
					<div class="folder-path">{{ folder.relativePath || '/' }}</div>
				</div>
				<div class="folder-actions">
					<NcButton 
						type="tertiary" 
						:aria-label="t('dashy', 'Open folder')"
						@click.stop="navigateToFolder(folder)"
					>
						<template #icon>
							<ChevronRightIcon :size="16" />
						</template>
					</NcButton>
				</div>
			</div>

			<div v-if="folders.length === 0" class="empty-folder">
				<FolderIcon :size="48" />
				<p>{{ t('dashy', 'No folders found') }}</p>
			</div>
		</div>

		<div class="folder-browser-footer">
			<div class="current-selection">
				<strong>{{ t('dashy', 'Selected:') }}</strong>
				<span>{{ selectedFolder ? selectedFolder.path : t('dashy', 'No folder selected') }}</span>
			</div>
			<div class="browser-actions">
				<NcButton @click="cancelSelection">
					{{ t('dashy', 'Cancel') }}
				</NcButton>
				<NcButton 
					type="primary" 
					:disabled="!selectedFolder"
					@click="confirmSelection"
				>
					{{ t('dashy', 'Select Folder') }}
				</NcButton>
			</div>
		</div>
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import FolderIcon from 'vue-material-design-icons/Folder.vue'
import AlertCircleIcon from 'vue-material-design-icons/AlertCircle.vue'
import ChevronUpIcon from 'vue-material-design-icons/ChevronUp.vue'
import ChevronRightIcon from 'vue-material-design-icons/ChevronRight.vue'
import RefreshIcon from 'vue-material-design-icons/Refresh.vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'FolderBrowser',
	components: {
		NcButton,
		NcLoadingIcon,
		FolderIcon,
		AlertCircleIcon,
		ChevronUpIcon,
		ChevronRightIcon,
		RefreshIcon,
	},
	props: {
		initialPath: {
			type: String,
			default: '',
		},
		allowCreateFolder: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			folders: [],
			loading: true,
			error: null,
			currentPath: this.initialPath,
			selectedFolder: null,
		}
	},
	computed: {
		pathSegments() {
			const segments = []
			
			// Add root segment
			segments.push({ name: t('dashy', 'Root'), path: '' })
			
			if (this.currentPath) {
				const pathParts = this.currentPath.split('/').filter(part => part.length > 0)
				let currentPath = ''
				
				pathParts.forEach(part => {
					currentPath += '/' + part
					segments.push({ 
						name: part, 
						path: currentPath.substring(1) // Remove leading slash
					})
				})
			}
			
			return segments
		},
	},
	async mounted() {
		await this.loadFolders()
	},
	methods: {
		t,
		async loadFolders() {
			try {
				this.loading = true
				this.error = null
				
				// Load folders from Nextcloud Files API
				const response = await axios.get(generateUrl('/apps/dashy/api/folders'), {
					params: {
						path: this.currentPath
					}
				})
				
				this.folders = response.data.folders || []
				
			} catch (error) {
				console.error('Failed to load folders:', error)
				this.error = t('dashy', 'Failed to load folders')
			} finally {
				this.loading = false
			}
		},
		selectFolder(folder) {
			this.selectedFolder = folder
		},
		async navigateToFolder(folder) {
			this.currentPath = folder.path
			this.selectedFolder = null
			await this.loadFolders()
		},
		async navigateToPath(path) {
			this.currentPath = path
			this.selectedFolder = null
			await this.loadFolders()
		},
		async goUp() {
			const pathParts = this.currentPath.split('/').filter(part => part.length > 0)
			pathParts.pop()
			this.currentPath = pathParts.join('/')
			this.selectedFolder = null
			await this.loadFolders()
		},
		getPathUpTo(index) {
			if (index === 0) return ''
			
			const segments = this.pathSegments.slice(1, index + 1)
			return segments.map(s => s.name).join('/')
		},
		confirmSelection() {
			if (this.selectedFolder) {
				this.$emit('folderSelected', this.selectedFolder)
			}
		},
		cancelSelection() {
			this.$emit('cancel')
		},
	},
}
</script>

<style scoped lang="scss">
.folder-browser {
	width: 100%;
	max-width: 600px;
	height: 400px;
	display: flex;
	flex-direction: column;
	border: 1px solid var(--color-border);
	border-radius: 8px;
	background: var(--color-main-background);
}

.folder-browser-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 12px 16px;
	border-bottom: 1px solid var(--color-border);
	background: var(--color-background-dark);
	border-radius: 8px 8px 0 0;
}

.navigation-path {
	display: flex;
	align-items: center;
	flex: 1;
	min-width: 0;
	
	.path-segment {
		display: flex;
		align-items: center;
		
		.path-button {
			background: none;
			border: none;
			color: var(--color-primary);
			cursor: pointer;
			padding: 4px 8px;
			border-radius: 4px;
			font-size: 14px;
			
			&:hover {
				background: var(--color-background-hover);
			}
		}
		
		.current-segment {
			font-weight: 500;
			color: var(--color-main-text);
			padding: 4px 8px;
		}
		
		.path-separator {
			margin: 0 4px;
			color: var(--color-text-maxcontrast);
		}
	}
}

.folder-actions {
	display: flex;
	gap: 4px;
}

.loading, .error {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	flex: 1;
	text-align: center;
	color: var(--color-text-maxcontrast);
	
	p {
		margin: 8px 0 0 0;
		font-size: 14px;
	}
}

.folder-list {
	flex: 1;
	overflow-y: auto;
	padding: 8px;
}

.folder-item {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 10px 12px;
	border-radius: 6px;
	cursor: pointer;
	transition: background-color 0.2s;
	
	&:hover {
		background-color: var(--color-background-hover);
	}
	
	&.selected {
		background-color: var(--color-primary-light);
		border: 2px solid var(--color-primary);
	}
	
	.folder-icon {
		display: flex;
		align-items: center;
		justify-content: center;
		color: var(--color-primary);
		flex-shrink: 0;
	}
	
	.folder-info {
		flex: 1;
		min-width: 0;
		
		.folder-name {
			font-weight: 500;
			color: var(--color-main-text);
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}
		
		.folder-path {
			font-size: 12px;
			color: var(--color-text-maxcontrast);
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}
	}
	
	.folder-actions {
		opacity: 0;
		transition: opacity 0.2s;
	}
	
	&:hover .folder-actions {
		opacity: 1;
	}
}

.empty-folder {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	height: 200px;
	color: var(--color-text-maxcontrast);
	
	p {
		margin: 16px 0 0 0;
		font-size: 14px;
	}
}

.folder-browser-footer {
	padding: 12px 16px;
	border-top: 1px solid var(--color-border);
	background: var(--color-background-dark);
	border-radius: 0 0 8px 8px;
}

.current-selection {
	margin-bottom: 12px;
	font-size: 14px;
	
	strong {
		color: var(--color-main-text);
		margin-right: 8px;
	}
	
	span {
		color: var(--color-text-maxcontrast);
		font-family: monospace;
		font-size: 13px;
	}
}

.browser-actions {
	display: flex;
	gap: 8px;
	justify-content: flex-end;
}
</style>
