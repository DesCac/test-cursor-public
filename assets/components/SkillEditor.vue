<template>
  <div class="flow-shell">
    <section class="flow-sidebar">
      <h3 class="flow-sidebar__title">Дерево навыков</h3>
      <p class="flow-sidebar__hint">
        Выберите навык на графе, чтобы увидеть его свойства.
        Связи между навыками показывают зависимости.
      </p>
      
      <div class="flow-sidebar__info" v-if="selectedNode">
        <h4>{{ selectedNode.data?.title || 'Навык' }}</h4>
        <p>{{ selectedNode.data?.body || 'Нет описания' }}</p>
        
        <div class="skill-section">
          <strong>Требования:</strong>
          <pre v-if="selectedNode.data?.conditions">{{ formatJSON(selectedNode.data.conditions) }}</pre>
          <span v-else class="text-muted">Нет требований</span>
        </div>
        
        <div class="skill-section">
          <strong>Эффекты:</strong>
          <pre v-if="selectedNode.data?.effects">{{ formatJSON(selectedNode.data.effects) }}</pre>
          <span v-else class="text-muted">Нет эффектов</span>
        </div>
      </div>
    </section>

    <section class="flow-main">
      <header class="flow-toolbar">
        <div class="flow-toolbar__left">
          <h3 class="flow-toolbar__title">Редактор навыков</h3>
          <span class="flow-toolbar__subtitle">
            {{ skillName }}
          </span>
        </div>
        <div class="flow-toolbar__center">
          <button type="button" class="flow-toolbar__btn" @click="fitToView">
            ◉ Fit
          </button>
          <button type="button" class="flow-toolbar__btn" @click="beautifyLayout">
            ✨ Beautify
          </button>
          <button type="button" class="flow-toolbar__btn" @click="resetPosition">
            ↺ Reset
          </button>
        </div>
        <div class="flow-toolbar__actions">
          <span class="flow-toolbar__status" :class="{ 'flow-toolbar__status--dirty': isDirty }">
            {{ isDirty ? 'Есть несохранённые изменения' : 'Все изменения сохранены' }}
          </span>
          <button
            type="button"
            class="flow-toolbar__btn flow-toolbar__btn--primary"
            :disabled="isSaving"
            @click="saveGraph"
          >
            {{ isSaving ? 'Сохранение…' : 'Сохранить' }}
          </button>
        </div>
      </header>

      <div
        ref="canvasRef"
        class="flow-canvas"
      >
        <VueFlow
          class="flow-canvas__inner"
          v-model:nodes="nodes"
          v-model:edges="edges"
          :node-types="nodeTypes"
          :default-edge-options="defaultEdgeOptions"
          :min-zoom="0.2"
          :max-zoom="2"
          @pane-click="clearSelection"
          @node-click="handleNodeClick"
          @edge-click="handleEdgeClick"
          @connect="handleConnect"
          @pane-ready="handlePaneReady"
        >
          <Background pattern-color="#bfc4ff" :gap="24" />
          <Controls position="top-left" />
          <MiniMap pannable zoomable />
        </VueFlow>

        <div v-if="isLoading" class="flow-overlay">
          <div class="flow-overlay__spinner" />
          <p>Загружаем дерево навыков…</p>
        </div>
      </div>

      <transition-group name="toast" tag="div" class="flow-toast-list">
        <article
          v-for="notification in notifications"
          :key="notification.id"
          class="flow-toast"
          :class="`flow-toast--${notification.type}`"
        >
          <strong class="flow-toast__title">
            {{ notification.type === 'error' ? 'Ошибка' : 'Готово' }}
          </strong>
          <p class="flow-toast__message">{{ notification.message }}</p>
        </article>
      </transition-group>
    </section>

    <PropertiesPanel
      :node="selectedNode"
      :edge="selectedEdge"
      :outgoing-edges="outgoingEdges"
      :node-type-options="nodeTypeOptions"
      :node-errors="selectedNode?.errorState || {}"
      :edge-errors="selectedEdge?.errorState || {}"
      context="skill"
      @delete-node="deleteNode"
      @delete-edge="deleteEdge"
      @duplicate-node="duplicateNode"
      @select-edge="selectEdge"
    />
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { VueFlow, useVueFlow } from '@vue-flow/core';
import { Background } from '@vue-flow/background';
import { Controls } from '@vue-flow/controls';
import { MiniMap } from '@vue-flow/minimap';
import axios from 'axios';

import '@vue-flow/core/dist/style.css';
import '@vue-flow/core/dist/theme-default.css';
import '@vue-flow/controls/dist/style.css';
import '@vue-flow/minimap/dist/style.css';

import PropertiesPanel from './flow/PropertiesPanel.vue';
import LogicNode from './flow/LogicNode.vue';

const nodeTypes = {
  'logic-node': LogicNode,
};

const defaultEdgeOptions = {
  type: 'smoothstep',
  markerEnd: 'arrowclosed',
};

const NODE_META = {
  skill: { label: 'Навык', icon: '⚔️', description: 'Игровой навык' },
};

const nodeTypeOptions = Object.entries(NODE_META).map(([value, meta]) => ({
  value,
  label: meta.label,
}));

const nodes = ref([]);
const edges = ref([]);
const notifications = ref([]);
const isLoading = ref(false);
const isSaving = ref(false);
const isDirty = ref(false);

const selectedNodeId = ref(null);
const selectedEdgeId = ref(null);

const canvasRef = ref(null);

const tempNodeCounter = ref(1);
const tempEdgeCounter = ref(1);

const skillName = computed(() => window.skillData?.name || 'Skill');

const { project, fitView, setViewport, getViewport } = useVueFlow();

const selectedNode = computed(() => nodes.value.find((node) => node.id === selectedNodeId.value) || null);
const selectedEdge = computed(() => edges.value.find((edge) => edge.id === selectedEdgeId.value) || null);

const outgoingEdges = computed(() =>
  selectedNode.value
    ? edges.value.filter((edge) => edge.source === selectedNode.value.id)
    : []
);

const notificationsTimers = new Map();

let hydrating = false;

onMounted(async () => {
  window.addEventListener('keydown', handleShortcuts);
  await loadGraph();
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleShortcuts);
  notificationsTimers.forEach((timer) => clearTimeout(timer));
  notificationsTimers.clear();
});

watch(
  [nodes, edges],
  () => {
    if (hydrating) {
      return;
    }
    isDirty.value = true;
  },
  { deep: true }
);

async function loadGraph() {
  isLoading.value = true;
  hydrating = true;
  try {
    const response = await axios.get(`/api/skills/${window.skillData.id}`);
    const skillData = response.data;
    
    // Create node for current skill
    const currentSkill = {
      id: skillData.id.toString(),
      type: 'logic-node',
      position: { x: 400, y: 300 },
      data: {
        nodeType: 'skill',
        title: skillData.name,
        body: skillData.description || '',
        conditions: skillData.unlockConditions ? JSON.stringify(skillData.unlockConditions, null, 2) : '',
        effects: skillData.effects ? JSON.stringify(skillData.effects, null, 2) : '',
      },
      errorState: {},
    };
    
    nodes.value = [currentSkill];
    
    // Add parent skills
    if (skillData.parents && skillData.parents.length > 0) {
      skillData.parents.forEach((parent, index) => {
        const parentNode = {
          id: parent.id.toString(),
          type: 'logic-node',
          position: { x: 200 + index * 200, y: 100 },
          data: {
            nodeType: 'skill',
            title: parent.name,
            body: '',
            conditions: '',
          },
          errorState: {},
        };
        nodes.value.push(parentNode);
        
        // Add edge from parent to current
        edges.value.push({
          id: `edge-${parent.id}-${skillData.id}`,
          source: parent.id.toString(),
          target: skillData.id.toString(),
          type: 'smoothstep',
          data: { label: '' },
          errorState: {},
        });
      });
    }
    
    // Add child skills
    if (skillData.children && skillData.children.length > 0) {
      skillData.children.forEach((child, index) => {
        const childNode = {
          id: child.id.toString(),
          type: 'logic-node',
          position: { x: 200 + index * 200, y: 500 },
          data: {
            nodeType: 'skill',
            title: child.name,
            body: '',
            conditions: '',
          },
          errorState: {},
        };
        nodes.value.push(childNode);
        
        // Add edge from current to child
        edges.value.push({
          id: `edge-${skillData.id}-${child.id}`,
          source: skillData.id.toString(),
          target: child.id.toString(),
          type: 'smoothstep',
          data: { label: '' },
          errorState: {},
        });
      });
    }
    
    bumpCounters();
    await nextTickFitView();
    isDirty.value = false;
  } catch (error) {
    console.error('Не удалось загрузить граф навыков:', error);
    notify('error', 'Не удалось загрузить дерево навыков');
  } finally {
    hydrating = false;
    isLoading.value = false;
  }
}

function bumpCounters() {
  const numericNodeIds = nodes.value
    .map((node) => Number(node.id))
    .filter((value) => Number.isFinite(value));
  const numericEdgeIds = edges.value
    .map((edge) => Number(edge.id.replace(/^edge-/, '').split('-')[0]))
    .filter((value) => Number.isFinite(value));

  const maxNodeId = numericNodeIds.length ? Math.max(...numericNodeIds) : 0;
  const maxEdgeId = numericEdgeIds.length ? Math.max(...numericEdgeIds) : 0;

  tempNodeCounter.value = Math.max(tempNodeCounter.value, maxNodeId + 1);
  tempEdgeCounter.value = Math.max(tempEdgeCounter.value, maxEdgeId + 1);
}

function handleNodeClick({ node }) {
  selectedNodeId.value = node.id;
  selectedEdgeId.value = null;
}

function handleEdgeClick({ edge }) {
  selectedEdgeId.value = edge.id;
  selectedNodeId.value = null;
}

function handleConnect(connection) {
  const edge = {
    id: generateEdgeId(),
    type: 'logic-edge',
    source: connection.source,
    target: connection.target,
    data: { label: '' },
    errorState: {},
  };

  edges.value = [...edges.value, edge];
  selectedEdgeId.value = edge.id;
}

function deleteNode(node) {
  const nodeId = typeof node === 'string' ? node : node?.id;
  if (!nodeId) return;

  nodes.value = nodes.value.filter((item) => item.id !== nodeId);
  edges.value = edges.value.filter((edge) => edge.source !== nodeId && edge.target !== nodeId);

  if (selectedNodeId.value === nodeId) {
    selectedNodeId.value = null;
  }
  selectedEdgeId.value = null;
}

function deleteEdge(edge) {
  const edgeId = typeof edge === 'string' ? edge : edge?.id;
  if (!edgeId) return;

  edges.value = edges.value.filter((item) => item.id !== edgeId);
  if (selectedEdgeId.value === edgeId) {
    selectedEdgeId.value = null;
  }
}

function duplicateNode(node) {
  if (!node) return;

  const id = generateNodeId();
  const offset = { x: node.position.x + 60, y: node.position.y + 60 };
  const clone = {
    id,
    type: 'logic-node',
    position: offset,
    data: {
      nodeType: node.data?.nodeType || 'skill',
      title: `${node.data?.title || 'Навык'} (копия)`,
      body: node.data?.body || '',
      conditions: node.data?.conditions || '',
      effects: node.data?.effects || '',
    },
    errorState: {},
  };

  nodes.value = [...nodes.value, clone];
  selectedNodeId.value = id;
  selectedEdgeId.value = null;
}

function selectEdge(edge) {
  selectedEdgeId.value = edge?.id || null;
  selectedNodeId.value = null;
}

function clearSelection() {
  selectedNodeId.value = null;
  selectedEdgeId.value = null;
}

async function saveGraph() {
  isSaving.value = true;

  try {
    // For skills, we just update the current skill's properties
    await axios.put(`/api/skills/${window.skillData.id}`, {
      name: selectedNode.value?.data?.title || window.skillData.name,
      description: selectedNode.value?.data?.body || '',
      unlockConditions: parseJSON(selectedNode.value?.data?.conditions),
      effects: parseJSON(selectedNode.value?.data?.effects),
    });

    isDirty.value = false;
    notify('success', 'Навык успешно сохранён');
  } catch (error) {
    console.error('Error saving skill:', error);
    notify('error', error?.response?.data?.error || 'Не удалось сохранить навык');
  } finally {
    isSaving.value = false;
  }
}

function parseJSON(str) {
  if (!str) return null;
  try {
    return JSON.parse(str);
  } catch (e) {
    return null;
  }
}

function formatJSON(str) {
  if (!str) return '';
  try {
    const obj = typeof str === 'string' ? JSON.parse(str) : str;
    return JSON.stringify(obj, null, 2);
  } catch (e) {
    return str;
  }
}

function fitToView() {
  if (typeof fitView === 'function') {
    fitView({ padding: 0.18, includeHiddenNodes: true });
  }
}

function resetPosition() {
  if (typeof setViewport === 'function') {
    setViewport({ x: 0, y: 0, zoom: 1 });
  }
}

function beautifyLayout() {
  if (nodes.value.length === 0) return;

  const nodeMap = new Map(nodes.value.map(n => [n.id, { ...n, level: -1, index: 0 }]));
  const incomingCount = new Map(nodes.value.map(n => [n.id, 0]));
  const outgoingMap = new Map();

  edges.value.forEach(edge => {
    incomingCount.set(edge.target, (incomingCount.get(edge.target) || 0) + 1);
    if (!outgoingMap.has(edge.source)) {
      outgoingMap.set(edge.source, []);
    }
    outgoingMap.get(edge.source).push(edge.target);
  });

  const rootNodes = Array.from(nodeMap.keys()).filter(id => incomingCount.get(id) === 0);
  if (rootNodes.length === 0 && nodes.value.length > 0) {
    rootNodes.push(nodes.value[0].id);
  }

  const levels = [];
  const visited = new Set();
  const queue = rootNodes.map(id => ({ id, level: 0 }));

  while (queue.length > 0) {
    const { id, level } = queue.shift();
    if (visited.has(id)) continue;
    
    visited.add(id);
    const node = nodeMap.get(id);
    if (node) {
      node.level = level;
      if (!levels[level]) levels[level] = [];
      levels[level].push(node);
    }

    const children = outgoingMap.get(id) || [];
    children.forEach(childId => {
      if (!visited.has(childId)) {
        queue.push({ id: childId, level: level + 1 });
      }
    });
  }

  const unvisited = nodes.value.filter(n => !visited.has(n.id));
  if (unvisited.length > 0) {
    const lastLevel = levels.length;
    levels[lastLevel] = unvisited.map(n => ({ ...n, level: lastLevel }));
  }

  const nodeWidth = 220;
  const nodeHeight = 110;
  const horizontalGap = 150;
  const verticalGap = 200;
  const startX = 200;
  const startY = 150;

  levels.forEach((levelNodes, levelIndex) => {
    const levelWidth = levelNodes.length * (nodeWidth + horizontalGap) - horizontalGap;
    const offsetX = startX - levelWidth / 2;
    
    levelNodes.forEach((node, index) => {
      const x = offsetX + index * (nodeWidth + horizontalGap) + levelWidth / 2;
      const y = startY + levelIndex * (nodeHeight + verticalGap);
      
      const originalNode = nodes.value.find(n => n.id === node.id);
      if (originalNode) {
        originalNode.position = { x, y };
      }
    });
  });

  nodes.value = [...nodes.value];
  setTimeout(() => {
    fitToView();
    notify('success', 'Граф упорядочен');
  }, 100);
}

function handlePaneReady() {
  nextTickFitView();
}

async function nextTickFitView() {
  await new Promise((resolve) => setTimeout(resolve, 50));
  fitToView();
}

function handleShortcuts(event) {
  if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 's') {
    event.preventDefault();
    saveGraph();
    return;
  }

  if (event.key === 'Delete') {
    if (selectedNode.value) {
      deleteNode(selectedNode.value);
      event.preventDefault();
    } else if (selectedEdge.value) {
      deleteEdge(selectedEdge.value);
      event.preventDefault();
    }
  }

  if ((event.metaKey || event.ctrlKey) && event.key === '0') {
    event.preventDefault();
    fitToView();
  }
}

function notify(type, message) {
  const id = `${Date.now()}-${Math.random().toString(16).slice(2)}`;
  notifications.value.push({ id, type, message });
  const timer = setTimeout(() => removeNotification(id), 4200);
  notificationsTimers.set(id, timer);
}

function removeNotification(id) {
  notifications.value = notifications.value.filter((item) => item.id !== id);
  if (notificationsTimers.has(id)) {
    clearTimeout(notificationsTimers.get(id));
    notificationsTimers.delete(id);
  }
}

function generateNodeId() {
  const id = `temp-${tempNodeCounter.value++}`;
  return id;
}

function generateEdgeId() {
  const id = `temp-edge-${tempEdgeCounter.value++}`;
  return id;
}
</script>

<style scoped>
.flow-shell {
  display: grid;
  grid-template-columns: 280px 1fr auto;
  gap: 0;
  height: 680px;
  border-radius: 18px;
  overflow: hidden;
  border: 1px solid rgba(245, 158, 11, 0.25);
  background: rgba(255, 255, 255, 0.85);
  box-shadow: 0 24px 60px rgba(245, 158, 11, 0.2);
}

.flow-sidebar {
  background: linear-gradient(180deg, rgba(255, 251, 235, 0.95), rgba(254, 249, 231, 0.9));
  border-right: 1px solid rgba(245, 158, 11, 0.2);
  padding: 20px;
  overflow-y: auto;
}

.flow-sidebar__title {
  margin: 0 0 12px;
  font-size: 18px;
  font-weight: 700;
  color: #78350f;
}

.flow-sidebar__hint {
  font-size: 13px;
  color: rgba(120, 53, 15, 0.7);
  margin-bottom: 20px;
}

.flow-sidebar__info {
  background: rgba(255, 255, 255, 0.9);
  padding: 16px;
  border-radius: 12px;
  border: 1px solid rgba(245, 158, 11, 0.2);
}

.flow-sidebar__info h4 {
  margin: 0 0 8px;
  color: #78350f;
  font-size: 16px;
}

.flow-sidebar__info p {
  margin: 0 0 12px;
  font-size: 13px;
  color: rgba(120, 53, 15, 0.8);
}

.skill-section {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid rgba(245, 158, 11, 0.1);
}

.skill-section strong {
  display: block;
  margin-bottom: 6px;
  font-size: 12px;
  color: #78350f;
}

.skill-section pre {
  font-size: 11px;
  background: rgba(255, 251, 235, 0.5);
  padding: 8px;
  border-radius: 6px;
  overflow-x: auto;
}

.text-muted {
  font-size: 12px;
  color: rgba(120, 53, 15, 0.5);
}

.flow-main {
  display: flex;
  flex-direction: column;
  background: linear-gradient(180deg, rgba(254, 249, 231, 0.95), rgba(254, 243, 199, 0.9));
}

.flow-toolbar {
  display: grid;
  grid-template-columns: 1fr auto auto;
  align-items: center;
  padding: 18px 24px;
  border-bottom: 1px solid rgba(245, 158, 11, 0.2);
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(12px);
  gap: 18px;
}

.flow-toolbar__left {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.flow-toolbar__title {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: #78350f;
}

.flow-toolbar__subtitle {
  font-size: 13px;
  color: rgba(120, 53, 15, 0.6);
}

.flow-toolbar__center {
  display: flex;
  align-items: center;
  gap: 10px;
}

.flow-toolbar__actions {
  display: flex;
  align-items: center;
  gap: 14px;
}

.flow-toolbar__btn {
  padding: 8px 16px;
  border-radius: 12px;
  border: 1px solid rgba(245, 158, 11, 0.35);
  background: rgba(255, 255, 255, 0.8);
  color: #78350f;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.2s ease;
}

.flow-toolbar__btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 18px rgba(245, 158, 11, 0.25);
}

.flow-toolbar__btn--primary {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: white;
  border-color: transparent;
  box-shadow: 0 12px 24px rgba(217, 119, 6, 0.25);
}

.flow-toolbar__btn--primary:disabled {
  opacity: 0.65;
  cursor: progress;
  box-shadow: none;
}

.flow-toolbar__status {
  font-size: 12px;
  color: rgba(120, 53, 15, 0.6);
}

.flow-toolbar__status--dirty {
  color: #c53030;
  font-weight: 600;
}

.flow-canvas {
  position: relative;
  flex: 1;
}

.flow-canvas__inner {
  width: 100%;
  height: 100%;
}

.flow-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  background: rgba(255, 255, 255, 0.85);
  color: #78350f;
  z-index: 10;
}

.flow-overlay__spinner {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 4px solid rgba(245, 158, 11, 0.2);
  border-top-color: #f59e0b;
  animation: spin 1s linear infinite;
}

.flow-toast-list {
  position: absolute;
  right: 24px;
  bottom: 24px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  pointer-events: none;
}

.flow-toast {
  min-width: 260px;
  padding: 12px 16px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.95);
  border: 1px solid rgba(245, 158, 11, 0.18);
  box-shadow: 0 16px 28px rgba(120, 53, 15, 0.22);
}

.flow-toast__title {
  display: block;
  margin-bottom: 4px;
  font-size: 13px;
  font-weight: 700;
}

.flow-toast__message {
  margin: 0;
  font-size: 13px;
  color: rgba(120, 53, 15, 0.8);
}

.flow-toast--error {
  border-color: rgba(220, 38, 38, 0.2);
  background: rgba(254, 242, 242, 0.95);
}

.flow-toast--success {
  border-color: rgba(56, 161, 105, 0.18);
  background: rgba(237, 247, 243, 0.95);
}

.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(12px);
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
