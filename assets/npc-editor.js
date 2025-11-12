import { createApp } from 'vue';

import NPCEditor from './components/NPCEditor.vue';

import './styles/app.css';

// Import VueFlow styles
import '@vue-flow/core/dist/style.css';
import '@vue-flow/core/dist/theme-default.css';
import '@vue-flow/controls/dist/style.css';
import '@vue-flow/minimap/dist/style.css';

const app = createApp(NPCEditor);
app.mount('#dialog-editor');
