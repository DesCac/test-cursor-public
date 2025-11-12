import { createApp } from 'vue';
import QuestEditor from './components/QuestEditor.vue';

// Import CSS
import './styles/app.css';

// Import VueFlow styles
import '@vue-flow/core/dist/style.css';
import '@vue-flow/core/dist/theme-default.css';
import '@vue-flow/controls/dist/style.css';
import '@vue-flow/minimap/dist/style.css';

const app = createApp(QuestEditor);
app.mount('#quest-editor');
