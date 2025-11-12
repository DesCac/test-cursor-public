<template>
  <div class="flow-shell">
    <Palette
      :items="paletteItems"
      @add-node="handleAddFromPalette"
      @drag-node="handleDragFromPalette"
    />

    <section class="flow-main">
      <header class="flow-toolbar">
        <div class="flow-toolbar__left">
          <h3 class="flow-toolbar__title">Редактор логики квеста</h3>
          <span class="flow-toolbar__subtitle">{{ questTitle }}</span>
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
        @dragover.prevent="handleDragOver"
        @drop="handleDrop"
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
          <p>Загружаем логику квеста…</p>
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
      context="quest"
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

import Palette from './flow/Palette.vue';
import PropertiesPanel from './flow/PropertiesPanel.vue';
import LogicNode from './flow/LogicNode.vue';
import {
  normalizeQuestGraph,
  serializeQuestGraph,
  GraphValidationError,
} from './flow/utils/serialization';

const nodeTypes = {
  'logic-node': LogicNode,
};

const defaultEdgeOptions = {
  type: 'smoothstep',
  markerEnd: 'arrowclosed',
};

const NODE_META = {
  start: { label: 'Старт', icon: '🚀', description: 'Начало квеста' },
  objective: { label: 'Цель', icon: '🎯', description: 'Задача, которую необходимо выполнить' },
  condition: { label: 'Условие', icon: '🧠', description: 'Проверка состояния игрока/мира' },
  reward: { label: 'Награда', icon: '💎', description: 'Выдача наград' },
  end: { label: 'Завершение', icon: '🏁', description: 'Финал квеста' },
};

const paletteItems = Object.entries(NODE_META).map(([type, meta]) => ({
  type,
  label: meta.label,
  icon: meta.icon,
  description: meta.description,
}));

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

const draggedType = ref(null);
const canvasRef = ref(null);

const tempNodeCounter = ref(1);
const tempEdgeCounter = ref(1);

const questTitle = computed(() => window.questData?.name || 'Quest');

const { project, fitView, setViewport, getViewport } = useVueFlow();

const selectedNode = computed(
  () => nodes.value.find((node) => node.id === selectedNodeId.value) || null
);
const selectedEdge = computed(
  () => edges.value.find((edge) => edge.id === selectedEdgeId.value) || null
);

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
    if (hydrating) return;
    isDirty.value = true;
  },
  { deep: true }
);

watch(
  () => nodes.value.map((node) => node.data?.payload),
  (payloads, oldPayloads) => {
    payloads.forEach((payload, index) => {
      if (payload !== oldPayloads?.[index]) {
        updateQuestNodePreview(nodes.value[index]);
      }
    });
  },
  { deep: true }
);

watch(
  edges,
  () => {
    edges.value.forEach((edge) => {
      edge.label = edge.data?.label || '';
    });
  },
  { deep: true }
);

async function loadGraph() {
  isLoading.value = true;
  hydrating = true;
  try {
    const response = await axios.get(`/api/quests/${window.questData.id}`);
    const { nodes: loadedNodes, edges: loadedEdges } = normalizeQuestGraph(response.data);
    nodes.value = loadedNodes.map(enhanceNode);
    edges.value = loadedEdges.map(enhanceEdge);
    nodes.value.forEach(updateQuestNodePreview);
    bumpCounters();
    await nextTickFitView();
    isDirty.value = false;
  } catch (error) {
    console.error('Не удалось загрузить логику квеста:', error);
    notify('error', 'Не удалось загрузить логику квеста');
  } finally {
    hydrating = false;
    isLoading.value = false;
  }
}

function enhanceNode(node) {
  return {
    ...node,
    errorState: {},
      data: {
      nodeType: node.data?.nodeType || 'objective',
      title: node.data?.title || NODE_META[node.data?.nodeType || 'objective']?.label || 'Узел',
      body: node.data?.body || '',
      payload: node.data?.payload || '',
      conditions: node.data?.conditions || '',
    },
  };
}

function enhanceEdge(edge) {
  return {
    ...edge,
    errorState: {},
          data: {
      label: edge.data?.label || '',
      conditions: edge.data?.conditions || '',
    },
  };
}

function updateQuestNodePreview(node) {
  if (!node) return;
  const payload = node.data?.payload;
  if (!payload) {
    node.data.body = '';
    return;
  }

  try {
    const parsed = JSON.parse(payload);
    if (parsed.objective) {
      node.data.body = parsed.objective;
    } else if (parsed.description) {
      node.data.body = parsed.description;
    } else {
      const firstKey = Object.keys(parsed)[0];
      node.data.body = firstKey ? `${firstKey}: ${String(parsed[firstKey])}` : '';
    }
  } catch {
    node.data.body = payload.slice(0, 80);
  }
}

function bumpCounters() {
  const numericNodeIds = nodes.value
    .map((node) => Number(node.id))
    .filter((value) => Number.isFinite(value));
  const numericEdgeIds = edges.value
    .map((edge) => Number(edge.id))
    .filter((value) => Number.isFinite(value));

  const maxNodeId = numericNodeIds.length ? Math.max(...numericNodeIds) : 0;
  const maxEdgeId = numericEdgeIds.length ? Math.max(...numericEdgeIds) : 0;

  tempNodeCounter.value = Math.max(tempNodeCounter.value, maxNodeId + 1);
  tempEdgeCounter.value = Math.max(tempEdgeCounter.value, maxEdgeId + 1);
}

function handleAddFromPalette(item) {
  addNode(item.type);
}

function handleDragFromPalette({ event, item }) {
  draggedType.value = item.type;
  event.dataTransfer.setData('application/x-flow-node', item.type);
  event.dataTransfer.effectAllowed = 'move';
}

function handleDragOver(event) {
  event.preventDefault();
  event.dataTransfer.dropEffect = 'copy';
}

function handleDrop(event) {
  event.preventDefault();
  const type =
    event.dataTransfer.getData('application/x-flow-node') ||
    draggedType.value ||
    'objective';
  const position = projectPosition(event.clientX, event.clientY);
  addNode(type, position);
  draggedType.value = null;
}

function projectPosition(x, y) {
  if (typeof project === 'function') {
    return project({ x, y });
  }
  return { x: 0, y: 0 };
}

function addNode(type, position = null) {
  const meta = NODE_META[type] || NODE_META.objective;
  const id = generateNodeId();
  const defaultPosition = position || getDefaultPosition();

  const defaultPayload =
    type === 'objective'
      ? JSON.stringify({ objective: 'Новая цель' }, null, 2)
      : type === 'condition'
      ? JSON.stringify({ check: 'custom_condition' }, null, 2)
      : '{}';

  const node = {
    id,
    type: 'logic-node',
    position: defaultPosition,
    data: {
      nodeType: type,
      title: meta.label,
      body: '',
      payload: defaultPayload,
      conditions: '',
    },
    errorState: {},
  };

  updateQuestNodePreview(node);
  nodes.value = [...nodes.value, node];
  selectedNodeId.value = id;
  selectedEdgeId.value = null;
}

function getDefaultPosition() {
  if (typeof getViewport === 'function') {
    const viewport = getViewport();
    const centerX = viewport ? -viewport.x / viewport.zoom + 200 : 200;
    const centerY = viewport ? -viewport.y / viewport.zoom + 200 : 200;
    return { x: centerX + Math.random() * 40, y: centerY + Math.random() * 40 };
  }

  return { x: Math.random() * 400, y: Math.random() * 400 };
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
    data: {
      label: '',
      conditions: '',
    },
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
      nodeType: node.data?.nodeType || 'objective',
      title: `${node.data?.title || 'Узел'} (копия)`,
      body: node.data?.body || '',
      payload: node.data?.payload || '',
      conditions: node.data?.conditions || '',
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
  clearErrors();
  isSaving.value = true;

  try {
    const payload = serializeQuestGraph(nodes.value, edges.value);
    await axios.put(`/api/quests/${window.questData.id}/nodes`, {
      nodes: payload.nodes.map((node) => ({
        id: node.id,
        clientId: node.clientId,
        type: node.type,
        data: node.data,
        positionX: node.positionX,
        positionY: node.positionY,
        conditions: node.conditions,
      })),
      connections: payload.edges.map((edge) => ({
        id: edge.id,
        clientId: edge.clientId,
        sourceId: edge.sourceId,
        targetId: edge.targetId,
        conditions: edge.conditions,
      })),
    });

    await loadGraph();
    isDirty.value = false;
    notify('success', 'Логика квеста сохранена');
  } catch (error) {
    handleSaveError(error);
  } finally {
    isSaving.value = false;
  }
}

function handleSaveError(error) {
  if (error instanceof GraphValidationError) {
    applyValidationError(error.meta);
    notify('error', error.message);
    return;
  }

  if (error?.response?.data?.error) {
    notify('error', error.response.data.error);
    return;
  }

  notify('error', error.message || 'Не удалось сохранить логику квеста');
}

function applyValidationError(meta = {}) {
  if (meta.entity === 'node') {
    const node = nodes.value.find((item) => item.id === meta.id);
    if (node) {
      node.errorState = { ...node.errorState, [meta.field]: meta.message };
      selectedNodeId.value = node.id;
    }
  }

  if (meta.entity === 'edge') {
    const edge = edges.value.find((item) => item.id === meta.id);
    if (edge) {
      edge.errorState = { ...edge.errorState, [meta.field]: meta.message };
      selectedEdgeId.value = edge.id;
    }
  }
}

function clearErrors() {
  nodes.value = nodes.value.map((node) => ({
    ...node,
    errorState: {},
  }));
  edges.value = edges.value.map((edge) => ({
    ...edge,
    errorState: {},
  }));
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

  // Создаём граф зависимостей
  const nodeMap = new Map(nodes.value.map(n => [n.id, { ...n, level: -1, index: 0 }]));
  const incomingCount = new Map(nodes.value.map(n => [n.id, 0]));
  const outgoingMap = new Map();

  // Подсчитываем входящие связи
  edges.value.forEach(edge => {
    incomingCount.set(edge.target, (incomingCount.get(edge.target) || 0) + 1);
    if (!outgoingMap.has(edge.source)) {
      outgoingMap.set(edge.source, []);
    }
    outgoingMap.get(edge.source).push(edge.target);
  });

  // Находим корневые узлы (без входящих связей)
  const rootNodes = Array.from(nodeMap.keys()).filter(id => incomingCount.get(id) === 0);
  
  // Если нет корневых узлов, берём первый узел или узел типа 'start'
  if (rootNodes.length === 0) {
    const startNode = nodes.value.find(n => n.data?.nodeType === 'start');
    rootNodes.push(startNode ? startNode.id : nodes.value[0].id);
  }

  // Распределяем узлы по уровням (BFS)
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

  // Добавляем неподключённые узлы в конец
  const unvisited = nodes.value.filter(n => !visited.has(n.id));
  if (unvisited.length > 0) {
    const lastLevel = levels.length;
    levels[lastLevel] = unvisited.map(n => ({ ...n, level: lastLevel }));
  }

  // Параметры расстановки
  const nodeWidth = 220;
  const nodeHeight = 110;
  const horizontalGap = 150; // Расстояние между узлами по горизонтали
  const verticalGap = 200;   // Расстояние между уровнями (больше для лейблов)
  const startX = 200;
  const startY = 150;

  // Располагаем узлы
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

  // Применяем изменения и подгоняем вид
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
  return `temp-${tempNodeCounter.value++}`;
}

function generateEdgeId() {
  return `temp-edge-${tempEdgeCounter.value++}`;
}
</script>

<style scoped>
.flow-shell {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 0;
  height: 680px;
  border-radius: 18px;
  overflow: hidden;
  border: 1px solid rgba(102, 126, 234, 0.25);
  background: rgba(255, 255, 255, 0.85);
  box-shadow: 0 24px 60px rgba(45, 65, 132, 0.2);
}

.flow-main {
  display: flex;
  flex-direction: column;
  background: linear-gradient(180deg, rgba(242, 246, 255, 0.95), rgba(235, 239, 255, 0.9));
}

.flow-toolbar {
  display: grid;
  grid-template-columns: 1fr auto auto;
  align-items: center;
  padding: 18px 24px;
  border-bottom: 1px solid rgba(102, 126, 234, 0.2);
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
  color: #28346d;
}

.flow-toolbar__subtitle {
  font-size: 13px;
  color: rgba(40, 52, 109, 0.6);
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
  border: 1px solid rgba(102, 126, 234, 0.35);
  background: rgba(255, 255, 255, 0.8);
  color: #404b8c;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.2s ease;
}

.flow-toolbar__btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 18px rgba(102, 126, 234, 0.25);
}

.flow-toolbar__btn--primary {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border-color: transparent;
  box-shadow: 0 12px 24px rgba(118, 75, 162, 0.25);
}

.flow-toolbar__btn--primary:disabled {
  opacity: 0.65;
  cursor: progress;
  box-shadow: none;
}

.flow-toolbar__status {
  font-size: 12px;
  color: rgba(40, 52, 109, 0.6);
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
  color: #404b8c;
  z-index: 10;
}

.flow-overlay__spinner {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 4px solid rgba(102, 126, 234, 0.2);
  border-top-color: #667eea;
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
  border: 1px solid rgba(102, 126, 234, 0.18);
  box-shadow: 0 16px 28px rgba(45, 65, 132, 0.22);
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
  color: rgba(31, 42, 86, 0.8);
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

/* Стили для лейблов на связях */
:deep(.vue-flow__edge-text) {
  font-size: 12px;
  font-weight: 600;
}

:deep(.vue-flow__edge-textbg) {
  fill: rgba(255, 255, 255, 0.95);
}

:deep(.vue-flow__edge-label) {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(242, 246, 255, 0.95));
  padding: 7px 14px;
  margin: 8px;
  border-radius: 10px;
  border: 2px solid rgba(102, 126, 234, 0.5);
  box-shadow: 0 6px 20px rgba(45, 65, 132, 0.25), 
              0 2px 8px rgba(102, 126, 234, 0.2),
              0 0 0 1px rgba(255, 255, 255, 0.8) inset;
  font-size: 12px;
  font-weight: 600;
  color: #404b8c;
  backdrop-filter: blur(10px);
  outline: 1px solid rgba(102, 126, 234, 0.15);
  outline-offset: 2px;
  white-space: nowrap;
  display: inline-block;
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>

