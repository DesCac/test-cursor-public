import { createApp } from 'vue';

// Import VueFlow styles FIRST
import '@vue-flow/core/dist/style.css';
import '@vue-flow/core/dist/theme-default.css';
import '@vue-flow/controls/dist/style.css';
import '@vue-flow/minimap/dist/style.css';

// Import our custom CSS LAST (to override VueFlow)
import './styles/app.css';

import NPCEditor from './components/NPCEditor.vue';

const app = createApp(NPCEditor);
app.mount('#dialog-editor');
