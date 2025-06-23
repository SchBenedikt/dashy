^<template>
  <div class="notes-settings">
    <!-- Settings triggered from widget header -->
    <div class="notes-settings-modal" v-if="showModal" @click.self="closeModal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Notes Widget Settings</h3>
          <button @click="closeModal" class="close-btn">&times;</button>
        </div>
        
        <div class="modal-body">
          <div class="setting-group">
            <label for="notes-folder">Storage Folder:</label>
            <div class="folder-selection">
              <div class="current-selection" v-if="!showFolderBrowser">
                <span class="selected-folder">
                  {{ localSettings.notesFolder || 'Default (Notes)' }}
                </span>
                <button @click="openFolderBrowser" class="browse-btn">
                  Browse...
                </button>
              </div>
              
              <div v-if="showFolderBrowser" class="folder-browser-container">
                <FolderBrowser @folderSelected="onFolderSelected" />
                <div class="browser-actions">
                  <button @click="closeFolderBrowser" class="cancel-btn">
                    Cancel
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          <div class="setting-group">
            <label for="default-category">Default Category:</label>
            <select 
              id="default-category" 
              v-model="localSettings.defaultCategory"
              class="category-select"
            >
              <option value="">No category</option>
              <option value="personal">Personal</option>
              <option value="work">Work</option>
              <option value="ideas">Ideas</option>
              <option value="todo">To Do</option>
            </select>
          </div>
          
          <div class="setting-group">
            <label for="max-notes">Max Notes to Display:</label>
            <select 
              id="max-notes" 
              v-model="localSettings.maxNotes"
              class="max-notes-select"
            >
              <option value="5">5</option>
              <option value="10">10</option>
              <option value="15">15</option>
              <option value="20">20</option>
            </select>
          </div>
          
          <div class="setting-group">
            <label for="sort-by">Sort By:</label>
            <select 
              id="sort-by" 
              v-model="localSettings.sortBy"
              class="sort-select"
            >
              <option value="modified">Last Modified</option>
              <option value="created">Date Created</option>
              <option value="title">Title</option>
            </select>
          </div>
        </div>
        
        <div class="modal-footer">
          <button @click="saveSettings" class="save-btn" :disabled="loading">
            {{ loading ? 'Saving...' : 'Save' }}
          </button>
          <button @click="closeModal" class="cancel-btn">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import FolderBrowser from '../FolderBrowser.vue'

export default {
  name: 'NotesSettings',
  components: {
    FolderBrowser
  },
  props: {
    settings: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      showModal: false,
      showFolderBrowser: false,
      loading: false,
      localSettings: {
        maxNotes: this.settings.maxNotes || 10,
        showFavorites: this.settings.showFavorites || false,
        sortBy: this.settings.sortBy || 'modified',
        notesFolder: this.settings.notesFolder || '',
        defaultCategory: this.settings.defaultCategory || '',
        ...this.settings,
      },
    }
  },
  watch: {
    settings: {
      handler(newSettings) {
        this.localSettings = {
          maxNotes: newSettings.maxNotes || 10,
          showFavorites: newSettings.showFavorites || false,
          sortBy: newSettings.sortBy || 'modified',
          notesFolder: newSettings.notesFolder || '',
          defaultCategory: newSettings.defaultCategory || '',
          ...newSettings,
        }
      },
      deep: true,
    },
  },
  methods: {
    t,
    openSettings() {
      // Method called by WidgetContainer
      this.showModal = true
    },
    
    closeModal() {
      this.showModal = false
      this.showFolderBrowser = false
    },
    
    openFolderBrowser() {
      this.showFolderBrowser = true
    },
    
    closeFolderBrowser() {
      this.showFolderBrowser = false
    },
    
    onFolderSelected(folderPath) {
      this.localSettings.notesFolder = folderPath
      this.showFolderBrowser = false
    },
    
    async saveSettings() {
      this.loading = true
      try {
        // Emit the settings update to parent component
        this.$emit('update', this.localSettings)
        this.closeModal()
      } catch (error) {
        console.error('Error saving settings:', error)
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>
.notes-settings-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

.modal-content {
  background: var(--color-main-background);
  border-radius: var(--border-radius-large);
  padding: 0;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
  max-width: 600px;
  width: 90%;
  max-height: 80vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid var(--color-border);
  background: var(--color-background-dark);
}

.modal-header h3 {
  margin: 0;
  color: var(--color-main-text);
  font-size: 18px;
  font-weight: 600;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: var(--color-text-maxcontrast);
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--border-radius);
}

.close-btn:hover {
  background: var(--color-background-hover);
  color: var(--color-main-text);
}

.modal-body {
  padding: 20px;
  overflow-y: auto;
  flex: 1;
}

.setting-group {
  margin-bottom: 20px;
}

.setting-group:last-child {
  margin-bottom: 0;
}

.setting-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: var(--color-main-text);
}

.folder-selection {
  width: 100%;
}

.current-selection {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
}

.selected-folder {
  flex: 1;
  color: var(--color-main-text);
  font-family: monospace;
  font-size: 13px;
}

.browse-btn {
  padding: 6px 12px;
  border: 1px solid var(--color-primary);
  border-radius: var(--border-radius);
  background: var(--color-primary);
  color: white;
  cursor: pointer;
  font-size: 12px;
  transition: all 0.2s;
}

.browse-btn:hover {
  background: var(--color-primary-hover);
}

.folder-browser-container {
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  overflow: hidden;
}

.browser-actions {
  padding: 12px;
  border-top: 1px solid var(--color-border);
  background: var(--color-background-dark);
  text-align: right;
}

select {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  color: var(--color-main-text);
  font-size: 14px;
}

select:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 2px var(--color-primary-light);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px;
  border-top: 1px solid var(--color-border);
  background: var(--color-background-dark);
}

.save-btn {
  padding: 8px 16px;
  border: 1px solid var(--color-primary);
  border-radius: var(--border-radius);
  background: var(--color-primary);
  color: white;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.2s;
}

.save-btn:hover:not(:disabled) {
  background: var(--color-primary-hover);
}

.save-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.cancel-btn {
  padding: 8px 16px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  color: var(--color-text-lighter);
  cursor: pointer;
  transition: all 0.2s;
}

.cancel-btn:hover {
  background: var(--color-background-hover);
  color: var(--color-main-text);
}
</style>
