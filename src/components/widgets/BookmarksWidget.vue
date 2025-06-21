<template>
	<div class="bookmarks-widget">
		<div v-if="loading" class="loading">
			<NcLoadingIcon :size="24" />
			<p>{{ t('dashy', 'Loading bookmarks...') }}</p>
		</div>
		<div v-else-if="error" class="error">
			<AlertCircleIcon :size="24" />
			<p>{{ error }}</p>
		</div>
		<div v-else>
			<div v-if="displayMode === 'compact'" class="bookmarks-compact">
				<div class="bookmarks-count">
					<BookmarkIcon :size="20" />
					<span>{{ bookmarks.length }} {{ t('dashy', 'bookmarks') }}</span>
				</div>
				<div v-if="recentBookmarks.length > 0" class="recent-bookmarks">
					<div 
						v-for="bookmark in recentBookmarks.slice(0, 3)" 
						:key="bookmark.id"
						class="bookmark-item compact"
						@click="openBookmark(bookmark)"
					>
						<img v-if="bookmark.favicon" :src="bookmark.favicon" :alt="bookmark.title" class="bookmark-favicon">
						<BookmarkIcon v-else :size="16" />
						<span class="bookmark-title">{{ bookmark.title }}</span>
					</div>
				</div>
			</div>
			<div v-else class="bookmarks-list">
				<div class="bookmarks-header">
					<h4>{{ t('dashy', 'Bookmarks') }}</h4>
					<div class="header-actions">
						<NcButton 
							type="tertiary" 
							:aria-label="t('dashy', 'Add bookmark')"
							@click="showAddBookmark = true"
						>
							<template #icon>
								<PlusIcon :size="16" />
							</template>
						</NcButton>
						<NcButton 
							type="tertiary" 
							:aria-label="t('dashy', 'Refresh')"
							@click="loadBookmarks"
						>
							<template #icon>
								<RefreshIcon :size="16" />
							</template>
						</NcButton>
						<NcButton 
							type="tertiary" 
							:aria-label="t('dashy', 'Open Bookmarks app')"
							@click="openBookmarksApp"
						>
							<template #icon>
								<OpenInNewIcon :size="16" />
							</template>
						</NcButton>
					</div>
				</div>
				<div class="bookmarks-search" v-if="bookmarks.length > 5">
					<input 
						v-model="searchQuery"
						type="text" 
						:placeholder="t('dashy', 'Search bookmarks...')"
						class="search-input"
					>
				</div>
				<div class="bookmark-list">
					<div 
						v-for="bookmark in filteredBookmarks.slice(0, settings.maxBookmarks || 15)" 
						:key="bookmark.id"
						class="bookmark-item"
						@click="openBookmark(bookmark)"
					>
						<div class="bookmark-icon">
							<img v-if="bookmark.favicon" :src="bookmark.favicon" :alt="bookmark.title" class="bookmark-favicon">
							<BookmarkIcon v-else :size="20" />
						</div>
						<div class="bookmark-info">
							<div class="bookmark-title">{{ bookmark.title }}</div>
							<div class="bookmark-url">{{ bookmark.url }}</div>
							<div v-if="bookmark.description" class="bookmark-description">{{ bookmark.description }}</div>
						</div>
						<div class="bookmark-actions">
							<NcButton 
								type="tertiary" 
								:aria-label="t('dashy', 'Copy link')"
								@click.stop="copyBookmark(bookmark)"
							>
								<template #icon>
									<ContentCopyIcon :size="16" />
								</template>
							</NcButton>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Add Bookmark Modal -->
		<NcDialog
			v-if="showAddBookmark"
			:name="t('dashy', 'Add Bookmark')"
			@closing="closeAddBookmark"
		>
			<div class="add-bookmark-content">
				<input
					v-model="newBookmark.title"
					type="text"
					:placeholder="t('dashy', 'Bookmark title...')"
					class="bookmark-input"
				>
				<input
					v-model="newBookmark.url"
					type="url"
					:placeholder="t('dashy', 'https://example.com')"
					class="bookmark-input"
				>
				<textarea
					v-model="newBookmark.description"
					:placeholder="t('dashy', 'Description (optional)...')"
					class="bookmark-textarea"
					rows="3"
				></textarea>
			</div>

			<template #actions>
				<NcButton @click="closeAddBookmark">
					{{ t('dashy', 'Cancel') }}
				</NcButton>
				<NcButton type="primary" @click="saveBookmark">
					{{ t('dashy', 'Add Bookmark') }}
				</NcButton>
			</template>
		</NcDialog>
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import BookmarkIcon from 'vue-material-design-icons/Bookmark.vue'
import AlertCircleIcon from 'vue-material-design-icons/AlertCircle.vue'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'
import RefreshIcon from 'vue-material-design-icons/Refresh.vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import ContentCopyIcon from 'vue-material-design-icons/ContentCopy.vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'BookmarksWidget',
	components: {
		NcButton,
		NcDialog,
		NcLoadingIcon,
		BookmarkIcon,
		AlertCircleIcon,
		OpenInNewIcon,
		RefreshIcon,
		PlusIcon,
		ContentCopyIcon,
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
			bookmarks: [],
			loading: true,
			error: null,
			searchQuery: '',
			showAddBookmark: false,
			newBookmark: {
				title: '',
				url: '',
				description: '',
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
		recentBookmarks() {
			return [...this.bookmarks].sort((a, b) => {
				if (a.lastAccessed && b.lastAccessed) {
					return b.lastAccessed - a.lastAccessed
				}
				return b.added - a.added
			})
		},
		filteredBookmarks() {
			if (!this.searchQuery) {
				return this.recentBookmarks
			}
			const query = this.searchQuery.toLowerCase()
			return this.bookmarks.filter(bookmark => 
				bookmark.title.toLowerCase().includes(query) ||
				bookmark.url.toLowerCase().includes(query) ||
				(bookmark.description && bookmark.description.toLowerCase().includes(query))
			)
		},
	},
	async mounted() {
		await this.loadBookmarks()
	},
	methods: {
		t,
		async loadBookmarks() {
			try {
				this.loading = true
				this.error = null
				
				// Load bookmarks from Nextcloud Bookmarks app or simple API
				const response = await axios.get(generateUrl('/apps/dashy/api/bookmarks'))
				this.bookmarks = response.data.bookmarks || []
				
			} catch (error) {
				console.error('Failed to load bookmarks:', error)
				if (error.response?.status === 404) {
					this.error = t('dashy', 'Bookmarks app not found or not accessible')
				} else {
					this.error = t('dashy', 'Failed to load bookmarks')
				}
			} finally {
				this.loading = false
			}
		},
		openBookmark(bookmark) {
			// Track access and open bookmark
			this.trackBookmarkAccess(bookmark.id)
			window.open(bookmark.url, '_blank')
		},
		async trackBookmarkAccess(bookmarkId) {
			try {
				await axios.post(generateUrl(`/apps/dashy/api/bookmarks/${bookmarkId}/access`))
			} catch (error) {
				// Silently fail - this is just for tracking
				console.debug('Failed to track bookmark access:', error)
			}
		},
		openBookmarksApp() {
			if (this.isBookmarksAppAvailable()) {
				window.open(generateUrl('/apps/bookmarks'), '_blank')
			} else {
				// Fallback to add bookmark
				this.showAddBookmark = true
			}
		},
		copyBookmark(bookmark) {
			navigator.clipboard.writeText(bookmark.url).then(() => {
				// Could show a toast notification here
			}).catch(err => {
				console.error('Failed to copy bookmark URL:', err)
			})
		},
		closeAddBookmark() {
			this.showAddBookmark = false
			this.newBookmark = { title: '', url: '', description: '' }
		},
		async saveBookmark() {
			try {
				if (!this.newBookmark.url) return
				
				const bookmarkData = {
					title: this.newBookmark.title || this.newBookmark.url,
					url: this.newBookmark.url,
					description: this.newBookmark.description,
				}
				
				await axios.post(generateUrl('/apps/dashy/api/bookmarks'), bookmarkData)
				await this.loadBookmarks()
				this.closeAddBookmark()
				
			} catch (error) {
				console.error('Failed to save bookmark:', error)
			}
		},
		isBookmarksAppAvailable() {
			// Check if Bookmarks app is available (simplified check)
			return document.querySelector('a[href*="/apps/bookmarks"]') !== null
		},
	},
}
</script>

<style scoped lang="scss">
.bookmarks-widget {
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

.bookmarks-compact {
	.bookmarks-count {
		display: flex;
		align-items: center;
		gap: 8px;
		margin-bottom: 12px;
		font-weight: 500;
		color: var(--color-text-dark);
	}
	
	.recent-bookmarks {
		display: flex;
		flex-direction: column;
		gap: 6px;
	}
	
	.bookmark-item.compact {
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
		
		.bookmark-favicon {
			width: 16px;
			height: 16px;
			object-fit: cover;
			border-radius: 2px;
		}
		
		.bookmark-title {
			font-size: 12px;
			font-weight: 500;
			color: var(--color-text-dark);
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}
	}
}

.bookmarks-list {
	height: 100%;
	display: flex;
	flex-direction: column;
}

.bookmarks-header {
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

.bookmarks-search {
	margin-bottom: 12px;
	
	.search-input {
		width: 100%;
		padding: 8px 12px;
		border: 1px solid var(--color-border);
		border-radius: 6px;
		background-color: var(--color-main-background);
		color: var(--color-main-text);
		font-size: 14px;
		
		&:focus {
			outline: none;
			border-color: var(--color-primary);
		}
		
		&::placeholder {
			color: var(--color-text-maxcontrast);
		}
	}
}

.bookmark-list {
	flex: 1;
	overflow-y: auto;
	
	.bookmark-item {
		display: flex;
		align-items: flex-start;
		gap: 12px;
		padding: 10px;
		border-radius: 8px;
		cursor: pointer;
		transition: background-color 0.2s;
		
		&:hover {
			background-color: var(--color-background-hover);
		}
		
		.bookmark-icon {
			display: flex;
			align-items: center;
			justify-content: center;
			width: 24px;
			height: 24px;
			flex-shrink: 0;
			margin-top: 2px;
			
			.bookmark-favicon {
				width: 20px;
				height: 20px;
				object-fit: cover;
				border-radius: 3px;
			}
		}
		
		.bookmark-info {
			flex: 1;
			min-width: 0;
			
			.bookmark-title {
				font-weight: 500;
				color: var(--color-text-dark);
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
				margin-bottom: 2px;
			}
			
			.bookmark-url {
				font-size: 12px;
				color: var(--color-text-maxcontrast);
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
				margin-bottom: 4px;
			}
			
			.bookmark-description {
				font-size: 12px;
				color: var(--color-text-light);
				line-height: 1.3;
				display: -webkit-box;
				-webkit-line-clamp: 2;
				line-clamp: 2;
				-webkit-box-orient: vertical;
				overflow: hidden;
			}
		}
		
		.bookmark-actions {
			display: flex;
			gap: 4px;
			opacity: 0;
			transition: opacity 0.2s;
		}
		
		&:hover .bookmark-actions {
			opacity: 1;
		}
	}
}

.add-bookmark-content {
	padding: 20px;
	
	.bookmark-input {
		width: 100%;
		padding: 8px 12px;
		border: 1px solid var(--color-border);
		border-radius: 6px;
		background-color: var(--color-main-background);
		color: var(--color-main-text);
		font-size: 14px;
		margin-bottom: 12px;
		
		&:focus {
			outline: none;
			border-color: var(--color-primary);
		}
		
		&::placeholder {
			color: var(--color-text-maxcontrast);
		}
	}
	
	.bookmark-textarea {
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
