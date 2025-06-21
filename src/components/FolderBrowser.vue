<template>
  <div class="folder-browser">
    <div class="folder-browser-header">
      <div class="current-path">
        <span v-if="currentPath" class="path-text">{{ '/' + currentPath }}</span>
        <span v-else class="path-text">/</span>
      </div>
      <div class="navigation-buttons">
        <button 
          v-if="parentPath !== null" 
          @click="navigateUp" 
          class="nav-btn up-btn"
          :disabled="loading"
        >
          ↑ Back
        </button>
        <button 
          @click="selectCurrentFolder" 
          class="nav-btn select-btn"
          :disabled="loading"
        >
          Select this folder
        </button>
      </div>
    </div>
    
    <div class="folder-list" v-if="!loading">
      <div 
        v-for="folder in folders" 
        :key="folder.path"
        @click="navigateToFolder(folder.path)"
        class="folder-item"
      >
        <span class="folder-icon">📁</span>
        <span class="folder-name">{{ folder.name }}</span>
      </div>
      <div v-if="folders.length === 0" class="no-folders">
        No subfolders found
      </div>
    </div>
    
    <div v-if="loading" class="loading">
      Loading folders...
    </div>
    
    <div v-if="error" class="error">
      {{ error }}
    </div>
  </div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

export default {
  name: 'FolderBrowser',
  emits: ['folderSelected'],
  data() {
    return {
      currentPath: '',
      parentPath: null,
      folders: [],
      loading: false,
      error: null
    }
  },
  mounted() {
    this.loadFolders('')
  },
  methods: {
    async loadFolders(path) {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.get(generateUrl('/apps/dashy/api/folders/browse'), {
          params: { path }
        })
        
        this.folders = response.data.folders || []
        this.currentPath = response.data.currentPath || ''
        this.parentPath = response.data.parentPath
        
        if (response.data.error) {
          this.error = response.data.error
        }
      } catch (error) {
        console.error('Error loading folders:', error)
        this.error = 'Failed to load folders'
      } finally {
        this.loading = false
      }
    },
    
    navigateToFolder(folderPath) {
      this.loadFolders(folderPath)
    },
    
    navigateUp() {
      if (this.parentPath !== null) {
        this.loadFolders(this.parentPath)
      }
    },
    
    selectCurrentFolder() {
      const selectedPath = this.currentPath || ''
      this.$emit('folderSelected', selectedPath)
    }
  }
}
</script>

<style scoped>
.folder-browser {
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  max-height: 400px;
  background: var(--color-main-background);
}

.folder-browser-header {
  padding: 12px;
  border-bottom: 1px solid var(--color-border);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--color-background-dark);
}

.current-path {
  flex: 1;
}

.path-text {
  font-family: monospace;
  font-size: 13px;
  color: var(--color-text-maxcontrast);
  background: var(--color-background-hover);
  padding: 4px 8px;
  border-radius: 4px;
}

.navigation-buttons {
  display: flex;
  gap: 8px;
}

.nav-btn {
  padding: 6px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  color: var(--color-text-lighter);
  cursor: pointer;
  font-size: 12px;
  transition: all 0.2s;
}

.nav-btn:hover:not(:disabled) {
  background: var(--color-background-hover);
  color: var(--color-main-text);
}

.nav-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.select-btn {
  background: var(--color-primary);
  color: white;
  border-color: var(--color-primary);
}

.select-btn:hover:not(:disabled) {
  background: var(--color-primary-hover);
}

.folder-list {
  max-height: 280px;
  overflow-y: auto;
}

.folder-item {
  display: flex;
  align-items: center;
  padding: 10px 12px;
  cursor: pointer;
  transition: background-color 0.2s;
  border-bottom: 1px solid var(--color-border-dark);
}

.folder-item:hover {
  background: var(--color-background-hover);
}

.folder-item:last-child {
  border-bottom: none;
}

.folder-icon {
  margin-right: 8px;
  font-size: 16px;
}

.folder-name {
  font-size: 14px;
  color: var(--color-main-text);
}

.no-folders {
  padding: 20px;
  text-align: center;
  color: var(--color-text-maxcontrast);
  font-style: italic;
}

.loading {
  padding: 20px;
  text-align: center;
  color: var(--color-text-maxcontrast);
}

.error {
  padding: 12px;
  background: var(--color-error);
  color: white;
  text-align: center;
  margin: 8px;
  border-radius: var(--border-radius);
}
</style>
