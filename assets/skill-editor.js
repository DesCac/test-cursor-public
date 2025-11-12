import { createApp } from 'vue';

import SkillEditor from './components/SkillEditor.vue';

import './styles/app.css';

import '@vue-flow/core/dist/style.css';
import '@vue-flow/core/dist/theme-default.css';
import '@vue-flow/controls/dist/style.css';
import '@vue-flow/minimap/dist/style.css';

const app = createApp(SkillEditor);
app.mount('#skill-editor');
