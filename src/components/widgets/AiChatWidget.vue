<template>
  <div class="ai-chat-widget">
    <div class="chat-history">
      <div v-for="(msg, idx) in messages" :key="idx" :class="['chat-row', msg.role]">
        <div class="bubble">
          <span class="text">{{ msg.text }}</span>
        </div>
      </div>
    </div>
    <form @submit.prevent="sendMessage">
      <input v-model="userInput" :placeholder="t('dashy', 'Nachricht an die KI...')" />
      <button :disabled="loading || !userInput">Senden</button>
    </form>
    <div v-if="error" class="error">{{ error }}</div>
  </div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
export default {
  name: 'AiChatWidget',
  props: {
    widget: Object,
    settings: Object,
  },
  data() {
    return {
      userInput: '',
      messages: [],
      loading: false,
      error: '',
    }
  },
  methods: {
    t,
    async sendMessage() {
      if (!this.settings.geminiApiKey) {
        this.error = 'API-Key fehlt!';
        return;
      }
      const userText = this.userInput.trim();
      if (!userText) return;
      this.messages.push({ role: 'user', text: userText });
      this.userInput = '';
      this.loading = true;
      this.error = '';
      try {
        const res = await fetch('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-goog-api-key': this.settings.geminiApiKey,
          },
          body: JSON.stringify({
            contents: [
              { parts: [ { text: userText } ] }
            ]
          })
        });
        const data = await res.json();
        const aiText = data?.candidates?.[0]?.content?.parts?.[0]?.text || 'Keine Antwort.';
        this.messages.push({ role: 'ai', text: aiText });
      } catch (e) {
        this.error = 'Fehler bei der Anfrage.';
      } finally {
        this.loading = false;
      }
    },
  },
}
</script>

<style scoped>
.ai-chat-widget {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: var(--color-main-background);
}
.chat-history {
  flex: 1;
  overflow-y: auto;
  margin-bottom: 8px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 8px 0;
}
.chat-row {
  display: flex;
  width: 100%;
}
.chat-row.user {
  justify-content: flex-end;
}
.chat-row.ai {
  justify-content: flex-start;
}
.bubble {
  max-width: 80%;
  padding: 7px 12px;
  border-radius: 10px;
  font-size: 14px;
  line-height: 1.5;
  border: 1px solid var(--color-border);
  background: #fafbfc;
  color: #222;
  display: flex;
  flex-direction: column;
  position: relative;
  box-shadow: none !important;
  text-shadow: none !important;
}

.chat-row.user .bubble {
  background: #f0f4fa;
  color: #222;
  align-items: flex-end;
}

.chat-row.ai .bubble {
  background: #f7f7f7;
  color: #222;
  align-items: flex-start;
}

.text {
  white-space: pre-line;
  font-size: 14px;
}
form {
  display: flex;
  gap: 6px;
  padding: 6px 0 0 0;
}
input {
  flex: 1;
  padding: 7px 10px;
  border-radius: 10px;
  border: 1px solid var(--color-border);
  font-size: 14px;
  background: var(--color-main-background);
}
input:focus {
  outline: none;
  border-color: var(--color-primary);
}
button {
  padding: 7px 14px;
  border-radius: 10px;
  border: none;
  background: var(--color-primary);
  color: #fff;
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: background 0.2s;
}
button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.error {
  color: #c00;
  margin-top: 8px;
}
</style>