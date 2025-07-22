<template>
  <div class="ai-chat-settings">
    <!-- Modal wird dynamisch in den Body eingefügt -->
  </div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
export default {
  name: 'AiChatSettings',
  props: {
    settings: Object,
  },
  data() {
    return {
      apiKey: this.settings.geminiApiKey || '',
      showModal: false,
      modalElement: null,
    }
  },
  methods: {
    t,
    openSettings() {
      this.showModal = true
      this.createModal()
    },
    closeSettings() {
      this.showModal = false
      this.removeModal()
    },
    handleEscapeKey(event) {
      if (event.key === 'Escape' && this.showModal) {
        this.closeSettings()
      }
    },
    createModal() {
      if (this.modalElement) return
      document.addEventListener('keydown', this.handleEscapeKey)
      this.modalElement = document.createElement('div')
      this.modalElement.className = 'ai-chat-settings-overlay'
      this.modalElement.addEventListener('click', this.handleOverlayClick)
      const modalContent = document.createElement('div')
      modalContent.className = 'ai-chat-settings-modal'
      modalContent.addEventListener('click', (e) => e.stopPropagation())
      modalContent.innerHTML = this.getModalHTML()
      this.modalElement.appendChild(modalContent)
      document.body.appendChild(this.modalElement)
      this.bindModalEvents()
    },
    removeModal() {
      if (this.modalElement) {
        document.removeEventListener('keydown', this.handleEscapeKey)
        this.modalElement.removeEventListener('click', this.handleOverlayClick)
        document.body.removeChild(this.modalElement)
        this.modalElement = null
      }
    },
    handleOverlayClick() {
      this.closeSettings()
    },
    getModalHTML() {
      return `
        <div class="modal-header">
          <h3>${t('dashy', 'AI Chat Einstellungen')}</h3>
          <button class="close-btn" type="button" title="Close">✕</button>
        </div>
        <form class="settings-form">
          <div class="form-group">
            <label for="apiKey">Gemini API-Key</label>
            <input id="apiKey" type="password" placeholder="GEMINI_API_KEY" value="${this.apiKey}">
            <small>${t('dashy', 'Dein API-Key wird nur lokal im Widget gespeichert.')}</small>
          </div>
          <div class="form-actions">
            <button type="button" class="btn-cancel">${t('dashy', 'Abbrechen')}</button>
            <button type="submit" class="btn-save">${t('dashy', 'Speichern')}</button>
          </div>
        </form>
      `
    },
    bindModalEvents() {
      if (!this.modalElement) return
      const form = this.modalElement.querySelector('.settings-form')
      form.addEventListener('submit', (e) => {
        e.preventDefault()
        const input = this.modalElement.querySelector('#apiKey')
        this.apiKey = input.value
        this.saveSettings()
      })
      const closeBtn = this.modalElement.querySelector('.close-btn')
      if (closeBtn) {
        closeBtn.addEventListener('click', (e) => {
          e.preventDefault()
          e.stopPropagation()
          this.closeSettings()
        })
      }
      const cancelBtn = this.modalElement.querySelector('.btn-cancel')
      if (cancelBtn) {
        cancelBtn.addEventListener('click', (e) => {
          e.preventDefault()
          this.closeSettings()
        })
      }
    },
    saveSettings() {
      this.$emit('update', { ...this.settings, geminiApiKey: this.apiKey })
      this.closeSettings()
    },
  },
  beforeDestroy() {
    this.removeModal()
  },
}
</script>

<style scoped>
.ai-chat-settings { display: none; }
</style>
<style>
.ai-chat-settings-overlay {
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
.ai-chat-settings-modal {
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
}
.modal-header h3 {
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
  min-width: 32px;
  min-height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}
.close-btn:hover {
  background: var(--color-background-hover);
  color: var(--color-main-text);
}
.settings-form {
  padding: 24px;
}
.form-group {
  margin-bottom: 20px;
}
.form-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  color: var(--color-main-text);
  font-size: 14px;
}
.form-group input {
  width: 100%;
  padding: 10px 12px;
  border: 2px solid var(--color-border);
  border-radius: 8px;
  background: var(--color-main-background);
  color: var(--color-main-text);
  font-size: 14px;
  transition: all 0.2s ease;
  box-sizing: border-box;
}
.form-group input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(var(--color-primary-rgb), 0.1);
}
.form-group small {
  display: block;
  font-size: 12px;
  color: var(--color-text-lighter);
  margin-top: 4px;
}
.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid var(--color-border);
}
@media (max-width: 480px) {
  .form-actions {
    flex-direction: column-reverse;
  }
}
.btn-cancel, .btn-save {
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  min-width: 100px;
}
.btn-cancel {
  background: var(--color-background-dark);
  color: var(--color-main-text);
  border: 2px solid var(--color-border);
}
.btn-cancel:hover:not(:disabled) {
  background: var(--color-border);
  transform: translateY(-1px);
}
.btn-save {
  background: var(--color-primary);
  color: white;
}
.btn-save:hover:not(:disabled) {
  background: var(--color-primary-hover);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(var(--color-primary-rgb), 0.3);
}
</style>
