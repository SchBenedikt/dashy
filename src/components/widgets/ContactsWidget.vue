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
			<div class="header" v-if="displayMode !== 'compact'">
				<h4>{{ t('dashy', 'Contacts') }}</h4>
				<NcButton 
					type="tertiary" 
					:aria-label="t('dashy', 'Open Contacts')"
					@click="openContactsApp"
				>
					<template #icon>
						<OpenInNewIcon :size="16" />
					</template>
				</NcButton>
			</div>
			
			<div v-if="contacts.length === 0" class="empty-state">
				<p>{{ t('dashy', 'No contacts found') }}</p>
			</div>

			<div class="contact-list" :class="{ compact: displayMode === 'compact' }">
				<div 
					v-for="(contact, index) in displayedContacts" 
					:key="index"
					class="contact-item"
					@click="openContact(contact)"
				>
					<div class="contact-avatar" :style="{ backgroundColor: getAvatarColor(contact.fn) }">
						{{ contact.initials }}
					</div>
					<div class="contact-info">
						<div class="contact-name">{{ contact.fn }}</div>
						<div class="contact-email" v-if="contact.email && displayMode !== 'compact'">
							{{ contact.email }}
						</div>
					</div>
					<div class="contact-actions" v-if="displayMode !== 'compact'">
						<NcButton 
							v-if="contact.email"
							type="tertiary" 
							:aria-label="t('dashy', 'Send email')"
							@click.stop="mailto(contact.email)"
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
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import AlertCircleIcon from 'vue-material-design-icons/AlertCircle.vue'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'
import EmailIcon from 'vue-material-design-icons/Email.vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'ContactsWidget',
	components: {
		NcButton,
		NcLoadingIcon,
		AlertCircleIcon,
		OpenInNewIcon,
		EmailIcon,
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
		}
	},
	computed: {
		displayMode() {
			if (this.widget.w <= 2 || this.widget.h <= 2) {
				return 'compact'
			}
			return 'full'
		},
		displayedContacts() {
			const max = this.settings.maxContacts || (this.displayMode === 'compact' ? 4 : 10)
			return this.contacts.slice(0, max)
		}
	},
	async mounted() {
		await this.loadContacts()
	},
	methods: {
		t,
		async loadContacts() {
			try {
				this.loading = true
				const response = await axios.get(generateUrl('/apps/dashy/api/contacts'))
				this.contacts = response.data.contacts || []
			} catch (error) {
				console.error('Failed to load contacts:', error)
				this.error = t('dashy', 'Failed to load contacts')
			} finally {
				this.loading = false
			}
		},
		openContactsApp() {
			window.location.href = generateUrl('/apps/contacts')
		},
		openContact(contact) {
			// Link to contacts app if possible, or just mailto
			if (contact.email) {
				this.mailto(contact.email)
			}
		},
		mailto(email) {
			window.location.href = `mailto:${email}`
		},
		getAvatarColor(name) {
			if (!name) return '#ccc'
			let hash = 0
			for (let i = 0; i < name.length; i++) {
				hash = name.charCodeAt(i) + ((hash << 5) - hash)
			}
			const c = (hash & 0x00FFFFFF).toString(16).toUpperCase()
			return '#' + '00000'.substring(0, 6 - c.length) + c
		}
	},
}
</script>

<style scoped lang="scss">
.contacts-widget {
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

.header {
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

.contact-list {
	flex: 1;
	overflow-y: auto;
	display: flex;
	flex-direction: column;
	gap: 4px;
	
	&.compact {
		flex-direction: row;
		flex-wrap: wrap;
		gap: 8px;
		align-content: flex-start;
		
		.contact-item {
			width: 48px;
			flex-direction: column;
			padding: 4px;
			text-align: center;
			
			.contact-info {
				display: none;
			}
		}
	}
}

.contact-item {
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
	
	.contact-avatar {
		width: 36px;
		height: 36px;
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		color: #fff;
		font-weight: 600;
		font-size: 13px;
		flex-shrink: 0;
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
			font-size: 14px;
		}
		
		.contact-email {
			font-size: 12px;
			color: var(--color-text-lighter);
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}
	}
}
</style>
