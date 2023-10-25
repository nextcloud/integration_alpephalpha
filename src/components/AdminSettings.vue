<template>
	<div id="alephalpha_prefs" class="section">
		<h2>
			<AlephAlphaIcon class="icon" />
			{{ t('integration_alephalpha', 'Aleph Alpha integration') }}
		</h2>
		<div id="alephalpha-content">
			<div class="line">
				<label for="alephalpha-api-key">
					<KeyIcon :size="20" class="icon" />
					{{ t('integration_alephalpha', 'API key') }}
				</label>
				<input id="alephalpha-api-key"
					v-model="state.api_key"
					type="password"
					:readonly="readonly"
					:placeholder="t('integration_alephalpha', 'your API key')"
					@input="onApiKeySet"
					@focus="readonly = false">
			</div>
			<p class="settings-hint">
				<InformationOutlineIcon :size="20" class="icon" />
				{{ t('integration_alephalpha', 'You can create an API key in your AlephAlpha profile settings:') }}
				&nbsp;
				<a href="https://app.aleph-alpha.com/profile" target="_blank" class="external">
					https://app.aleph-alpha.com/profile
				</a>
			</p>
			<div v-if="models"
				class="line">
				<label for="size">
					{{ t('integration_alephalpha', 'Default completion model to use') }}
				</label>
				<div class="spacer" />
				<NcSelect
					v-model="selectedModel"
					class="model-select"
					:options="formattedModels"
					:no-wrap="true"
					input-id="alephalpha-model-select"
					@input="onModelSelected" />
				<a
					:title="t('integration_alephalpha', 'More information about Aleph Alpha models')"
					href="https://docs.aleph-alpha.com/docs/introduction/luminous"
					target="_blank">
					<NcButton type="tertiary">
						<template #icon>
							<HelpCircleIcon />
						</template>
					</NcButton>
				</a>
			</div>
			<div class="line">
				<label for="alephalpha-api-timeout">
					<TimerAlertOutlineIcon :size="20" class="icon" />
					{{ t('integration_alephalpha', 'Request timeout (seconds)') }}
				</label>
				<input id="alephalpha-api-timeout"
					v-model="state.request_timeout"
					type="number"
					@input="onRequestTimeoutSet">
			</div>
		</div>
	</div>
</template>

<script>
import TimerAlertOutlineIcon from 'vue-material-design-icons/TimerAlertOutline.vue'
import InformationOutlineIcon from 'vue-material-design-icons/InformationOutline.vue'
import KeyIcon from 'vue-material-design-icons/Key.vue'
import HelpCircleIcon from 'vue-material-design-icons/HelpCircle.vue'

import AlephAlphaIcon from './icons/AlephAlphaIcon.vue'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { showSuccess, showError } from '@nextcloud/dialogs'

export default {
	name: 'AdminSettings',

	components: {
		AlephAlphaIcon,
		KeyIcon,
		InformationOutlineIcon,
		TimerAlertOutlineIcon,
		HelpCircleIcon,
		NcButton,
		NcSelect,
	},

	props: [],

	data() {
		return {
			state: loadState('integration_alephalpha', 'admin-config'),
			// to prevent some browsers to fill fields with remembered passwords
			readonly: true,
			models: null,
			selectedModel: null,
		}
	},

	computed: {
		configured() {
			return !!this.state.api_key
		},
		formattedModels() {
			if (this.models) {
				return this.models.map(m => {
					return {
						id: m.name,
						value: m.name,
						label: m.name,
					}
				})
			}
			return []
		},
	},

	watch: {},

	mounted() {
		if (this.configured) {
			this.getModels()
		}
	},

	methods: {
		getModels() {
			const url = generateUrl('/apps/integration_alephalpha/models')
			return axios.get(url)
				.then((response) => {
					this.models = response.data?.data
					const defaultModelId = this.state.completion_model ?? response.data?.completion_model
					const defaultModel = this.models.find(m => m.name === defaultModelId)
					const modelToSelect = defaultModel
						?? this.models.find(m => m.name === 'luminous-base')
						?? this.models[0]
						?? null
					if (modelToSelect) {
						this.selectedModel = {
							id: modelToSelect.name,
							value: modelToSelect.name,
							label: modelToSelect.name,
						}
					}
				})
				.catch((error) => {
					console.error(error)
				})
		},
		onModelSelected(selected) {
			if (selected === null) {
				return
			}
			this.state.completion_model = selected.id
			this.saveOptions({ completion_model: this.state.completion_model })
		},
		onApiKeySet() {
			this.saveOptions({ api_key: this.state.api_key })
			if (this.configured) {
				this.getModels()
			}
		},
		onRequestTimeoutSet() {
			this.saveOptions({ request_timeout: this.state.request_timeout })
		},
		saveOptions(values, notify = true) {
			const req = {
				values,
			}
			const url = generateUrl('/apps/integration_alephalpha/admin-config')
			return axios.put(url, req)
				.then((_) => {
					if (notify) {
						showSuccess(t('integration_alephalpha', 'Aleph Alpha admin options saved'))
					}
				})
				.catch((error) => {
					showError(
						t('integration_alephalpha', 'Failed to save Aleph Alpha admin options')
						+ ': ' + error.response?.request?.responseText,
					)
				})
		},
	},
}
</script>

<style scoped lang="scss">
#alephalpha_prefs {
	#alephalpha-content {
		margin-left: 40px;
	}

	h2,
	.line,
	.settings-hint {
		display: flex;
		align-items: center;
		margin-top: 12px;

		.icon {
			margin-right: 4px;
		}
	}

	h2 .icon {
		margin-right: 8px;
	}

	.line {
		> label {
			width: 300px;
			display: flex;
			align-items: center;
		}

		> input {
			width: 300px;
		}
	}

	.model-select {
		min-width: 350px;
	}
}
</style>
