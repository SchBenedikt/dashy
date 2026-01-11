<template>
	<div class="talk-widget">
		<div v-if="loading" class="loading">
			<NcLoadingIcon :size="24" />
			<p>{{ t('dashy', 'Loading conversations...') }}</p>
		</div>
		<div v-else-if="error" class="error">
			<AlertCircleIcon :size="24" />
			<p>{{ error }}</p>
		</div>
		<div v-else>
			<div class="talk-header" v-if="displayMode !== 'compact'">
				<h4>{{ t('dashy', 'Conversations') }}</h4>
				<NcButton 
					type="tertiary" 
					:aria-label="t('dashy', 'Open Talk')"
					@click="openTalkApp"
				>
					<template #icon>
						<OpenInNewIcon :size="16" />
					</template>
				</NcButton>
			</div>
			
			<div v-if="rooms.length === 0" class="empty-state">
				<p>{{ t('dashy', 'No recent conversations') }}</p>
			</div>

			<div class="room-list" :class="{ compact: displayMode === 'compact' }">
				<div 
					v-for="room in displayedRooms" 
					:key="room.token"
					class="room-item"
					@click="openRoom(room)"
				>
					<div class="room-icon">
						<div class="avatar-placeholder" :style="{ backgroundColor: getAvatarColor(room.name) }">
							{{ getInitials(room.name) }}
						</div>
						<div v-if="room.unreadMessages > 0" class="unread-badge">
							{{ room.unreadMessages > 99 ? '99+' : room.unreadMessages }}
						</div>
					</div>
					<div class="room-info">
						<div class="room-name">{{ room.displayName || room.name }}</div>
						<div class="room-message" v-if="room.lastMessage">
							<span class="sender" v-if="room.lastMessage.actorId !== currentUserId">
								{{ room.lastMessage.actorDisplayName }}:
							</span>
							<span class="preview">{{ room.lastMessage.message }}</span>
						</div>
					</div>
					<div class="room-time" v-if="room.lastMessage">
						{{ formatTime(room.lastMessage.timestamp) }}
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import AlertCircleIcon from 'vue-material-design-icons/AlertCircle.vue'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'
import { getCurrentUser } from '@nextcloud/auth'

export default {
	name: 'TalkWidget',
	components: {
		NcButton,
		NcLoadingIcon,
		AlertCircleIcon,
		OpenInNewIcon,
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
			rooms: [],
			loading: true,
			error: null,
			currentUserId: getCurrentUser()?.uid,
		}
	},
	computed: {
		displayMode() {
			if (this.widget.w <= 2 || this.widget.h <= 2) {
				return 'compact'
			}
			return 'full'
		},
		displayedRooms() {
			const max = this.settings.maxRooms || (this.displayMode === 'compact' ? 3 : 10)
			return this.rooms.slice(0, max)
		}
	},
	async mounted() {
		await this.loadRooms()
	},
	methods: {
		t,
		async loadRooms() {
			try {
				this.loading = true
				// Fetch rooms from Spreed API
				const response = await axios.get(generateUrl('/ocs/v2.php/apps/spreed/api/v4/room'))
				this.rooms = response.data.ocs.data || []
				
				// Sort by last activity
				this.rooms.sort((a, b) => b.lastMessage.timestamp - a.lastMessage.timestamp)
			} catch (error) {
				console.error('Failed to load Talk rooms:', error)
				// Don't show error if it's just 404 (Spreed/Talk not installed)
				if (error.response?.status === 404) {
					this.error = t('dashy', 'Talk app not installed')
				} else {
					this.error = t('dashy', 'Failed to load conversations')
				}
			} finally {
				this.loading = false
			}
		},
		openTalkApp() {
			window.location.href = generateUrl('/apps/spreed')
		},
		openRoom(room) {
			window.location.href = generateUrl(`/call/${room.token}`)
		},
		getInitials(name) {
			if (!name) return '?'
			return name.substring(0, 2).toUpperCase()
		},
		getAvatarColor(name) {
			if (!name) return '#ccc'
			let hash = 0
			for (let i = 0; i < name.length; i++) {
				hash = name.charCodeAt(i) + ((hash << 5) - hash)
			}
			const c = (hash & 0x00FFFFFF).toString(16).toUpperCase()
			return '#' + '00000'.substring(0, 6 - c.length) + c
		},
		formatTime(timestamp) {
			if (!timestamp) return ''
			const date = new Date(timestamp * 1000)
			const now = new Date()
			if (now - date < 24 * 60 * 60 * 1000) {
				return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
			}
			return date.toLocaleDateString([], { month: 'short', day: 'numeric' })
		}
	},
}
</script>

<style scoped lang="scss">
.talk-widget {
	height: 100%;
	display: flex;
	flex-direction: column;
}

.loading, .error, .empty-state {
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

.talk-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 8px;
	
	h4 {
		margin: 0;
		font-size: 14px;
		font-weight: 600;
		color: var(--color-text-dark);
	}
}

.room-list {
	flex: 1;
	overflow-y: auto;
	display: flex;
	flex-direction: column;
	gap: 4px;
	
	&.compact {
		gap: 2px;
	}
}

.room-item {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 8px;
	border-radius: 8px;
	cursor: pointer;
	transition: background-color 0.2s;
	
	&:hover {
		background-color: var(--color-background-hover);
	}
	
	.room-icon {
		position: relative;
		width: 40px;
		height: 40px;
		flex-shrink: 0;
		
		.avatar-placeholder {
			width: 100%;
			height: 100%;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			color: #fff;
			font-weight: 600;
			font-size: 14px;
		}
		
		.unread-badge {
			position: absolute;
			top: -4px;
			right: -4px;
			background: var(--color-error);
			color: white;
			font-size: 10px;
			padding: 2px 4px;
			border-radius: 10px;
			font-weight: bold;
			border: 2px solid var(--color-main-background);
		}
	}
	
	.room-info {
		flex: 1;
		min-width: 0;
		
		.room-name {
			font-weight: 600;
			color: var(--color-text-dark);
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
			font-size: 14px;
		}
		
		.room-message {
			font-size: 12px;
			color: var(--color-text-lighter);
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
			
			.sender {
				font-weight: 500;
				margin-right: 4px;
			}
		}
	}
	
	.room-time {
		font-size: 11px;
		color: var(--color-text-lighter);
		white-space: nowrap;
	}
}
</style>
