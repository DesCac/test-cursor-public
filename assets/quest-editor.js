import { createApp } from 'vue';

// Import VueFlow styles FIRST
import '@vue-flow/core/dist/style.css';
import '@vue-flow/core/dist/theme-default.css';
import '@vue-flow/controls/dist/style.css';
import '@vue-flow/minimap/dist/style.css';

// Import our custom CSS LAST (to override VueFlow)
import './styles/app.css';

import QuestEditor from './components/QuestEditor.vue';

const app = createApp(QuestEditor);
app.mount('#quest-editor');
