<template>
	<div class="contacts-widget">
		<div v-if="loading" class="loading">
			<NcLoadingIcon :size="24" />
			<p>{{ t('dashy', 'Loading contacts...') }}</p>
		</div>
		<div v-else-if="error" class="error">
			<AlertCircleIcon :size="24" />
			<p>{{ error }}</p>
		</div>
		<div v-else>
			<div v-if="displayMode === 'compact'" class="contacts-compact">
				<div class="contact-count">
					<AccountMultipleIcon :size="20" />
					<span>{{ contacts.length }} {{ t('dashy', 'contacts') }}</span>
				</div>
				<div v-if="recentContacts.length > 0" class="recent-contacts">
					<div 
						v-for="contact in recentContacts.slice(0, 3)" 
						:key="contact.id"
						class="contact-item compact"
						@click="openContact(contact)"
					>
						<div class="contact-avatar">
							<img v-if="contact.photo" :src="contact.photo" :alt="contact.displayName">
							<AccountIcon v-else :size="16" />
						</div>
						<span class="contact-name">{{ contact.displayName }}</span>
					</div>
				</div>
			</div>
			<div v-else class="contacts-list">
				<div class="contacts-header">
					<h4>{{ t('dashy', 'Recent Contacts') }}</h4>
					<NcButton 
						type="tertiary" 
						:aria-label="t('dashy', 'Open Contacts app')"
						@click="openContactsApp"
					>
						<template #icon>
							<OpenInNewIcon :size="16" />
						</template>
					</NcButton>
				</div>
				<div class="contacts-search" v-if="contacts.length > 5">
					<input 
						v-model="searchQuery"
						type="text" 
						:placeholder="t('dashy', 'Search contacts...')"
						class="search-input"
					>
				</div>
				<div class="contact-list">
					<div 
						v-for="contact in filteredContacts.slice(0, settings.maxContacts || 10)" 
						:key="contact.id"
						class="contact-item"
						@click="openContact(contact)"
					>
						<div class="contact-avatar">
							<img v-if="contact.photo" :src="contact.photo" :alt="contact.displayName">
							<AccountIcon v-else :size="24" />
						</div>
						<div class="contact-info">
							<div class="contact-name">{{ contact.displayName }}</div>
							<div v-if="contact.email" class="contact-detail">{{ contact.email }}</div>
							<div v-else-if="contact.phone" class="contact-detail">{{ contact.phone }}</div>
						</div>
						<div class="contact-actions">
							<NcButton 
								v-if="contact.email"
								type="tertiary" 
								:aria-label="t('dashy', 'Send email')"
								@click.stop="sendEmail(contact.email)"
							>
								<template #icon>
									<EmailIcon :size="16" />
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
import AccountIcon from 'vue-material-design-icons/Account.vue'
import AccountMultipleIcon from 'vue-material-design-icons/AccountMultiple.vue'
import AlertCircleIcon from 'vue-material-design-icons/AlertCircle.vue'
import EmailIcon from 'vue-material-design-icons/Email.vue'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'ContactsWidget',
	components: {
		NcButton,
		NcLoadingIcon,
		AccountIcon,
		AccountMultipleIcon,
		AlertCircleIcon,
		EmailIcon,
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
			contacts: [],
			loading: true,
			error: null,
			searchQuery: '',
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
		recentContacts() {
			// Sort by last contacted or alphabetically
			return [...this.contacts].sort((a, b) => {
				if (a.lastContacted && b.lastContacted) {
					return new Date(b.lastContacted) - new Date(a.lastContacted)
				}
				return a.displayName.localeCompare(b.displayName)
			})
		},
		filteredContacts() {
			if (!this.searchQuery) {
				return this.recentContacts
			}
			const query = this.searchQuery.toLowerCase()
			return this.contacts.filter(contact => 
				contact.displayName.toLowerCase().includes(query) ||
				(contact.email && contact.email.toLowerCase().includes(query)) ||
				(contact.phone && contact.phone.includes(query))
			)
		},
	},
	async mounted() {
		await this.loadContacts()
	},
	methods: {
		t,
		async loadContacts() {
			try {
				this.loading = true
				this.error = null
				
				// Try to load contacts from Nextcloud Contacts app
				const response = await axios.get(generateUrl('/apps/dashy/api/contacts'))
				this.contacts = response.data.contacts || []
				
			} catch (error) {
				console.error('Failed to load contacts:', error)
				if (error.response?.status === 404) {
					this.error = t('dashy', 'Contacts app not found or not accessible')
				} else {
					this.error = t('dashy', 'Failed to load contacts')
				}
			} finally {
				this.loading = false
			}
		},
		openContact(contact) {
			// Open contact in Contacts app
			window.open(generateUrl(`/apps/contacts/${contact.id}`), '_blank')
		},
		openContactsApp() {
			window.open(generateUrl('/apps/contacts'), '_blank')
		},
		sendEmail(email) {
			// Open default email client or Nextcloud Mail app
			if (this.isNextcloudMailAvailable()) {
				window.open(generateUrl(`/apps/mail/compose?to=${encodeURIComponent(email)}`), '_blank')
			} else {
				window.location.href = `mailto:${email}`
			}
		},
		isNextcloudMailAvailable() {
			// Check if Mail app is available (simplified check)
			return document.querySelector('a[href*="/apps/mail"]') !== null
		},
	},
}
</script>

<style scoped lang="scss">
.contacts-widget {
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

.contacts-compact {
	.contact-count {
		display: flex;
		align-items: center;
		gap: 8px;
		margin-bottom: 12px;
		font-weight: 500;
		color: var(--color-text-dark);
	}
	
	.recent-contacts {
		display: flex;
		flex-direction: column;
		gap: 6px;
	}
	
	.contact-item.compact {
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
		
		.contact-avatar {
			width: 20px;
			height: 20px;
			border-radius: 50%;
			overflow: hidden;
			background-color: var(--color-background-dark);
			display: flex;
			align-items: center;
			justify-content: center;
			
			img {
				width: 100%;
				height: 100%;
				object-fit: cover;
			}
		}
		
		.contact-name {
			font-size: 12px;
			font-weight: 500;
			color: var(--color-text-dark);
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}
	}
}

.contacts-list {
	height: 100%;
	display: flex;
	flex-direction: column;
}

.contacts-header {
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
}

.contacts-search {
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

.contact-list {
	flex: 1;
	overflow-y: auto;
	
	.contact-item {
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
		
		.contact-avatar {
			width: 32px;
			height: 32px;
			border-radius: 50%;
			overflow: hidden;
			background-color: var(--color-background-dark);
			display: flex;
			align-items: center;
			justify-content: center;
			flex-shrink: 0;
			
			img {
				width: 100%;
				height: 100%;
				object-fit: cover;
			}
		}
		
		.contact-info {
			flex: 1;
			min-width: 0;
			
			.contact-name {
				font-weight: 500;
				color: var(--color-text-dark);
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
			}
			
			.contact-detail {
				font-size: 12px;
				color: var(--color-text-maxcontrast);
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
				margin-top: 2px;
			}
		}
		
		.contact-actions {
			display: flex;
			gap: 4px;
			opacity: 0;
			transition: opacity 0.2s;
		}
		
		&:hover .contact-actions {
			opacity: 1;
		}
	}
}
</style>
