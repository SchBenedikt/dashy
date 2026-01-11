<template>
	<div class="contacts-settings">
		<div class="setting-group">
			<label>{{ t('dashy', 'Maximum contacts') }}</label>
			<input 
				type="number" 
				min="1" 
				max="50" 
				v-model.number="localSettings.maxContacts"
				@change="save"
			>
		</div>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'ContactsSettings',
	props: {
		settings: {
			type: Object,
			default: () => ({}),
		},
	},
	data() {
		return {
			localSettings: {
				maxContacts: this.settings.maxContacts || 10,
			},
		}
	},
	methods: {
		t,
		save() {
			this.$emit('update', this.localSettings)
		},
	},
}
</script>

<style scoped>
.setting-group {
	display: flex;
	flex-direction: column;
	gap: 6px;
	margin-bottom: 12px;
}
input {
	padding: 8px;
	border: 1px solid var(--color-border);
	border-radius: 4px;
	background: var(--color-main-background);
	color: var(--color-main-text);
}
</style>
