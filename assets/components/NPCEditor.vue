<template>
  <div class="flow-shell">
    <div class="flow-shell__palette">
      <Palette
        :items="paletteItems"
        @add-node="handleAddFromPalette"
        @drag-node="handleDragFromPalette"
      />
    </div>

    <section class="flow-main">
      <header class="flow-toolbar">
        <div class="flow-toolbar__left">
          <h3 class="flow-toolbar__title">Редактор диалога</h3>
          <span class="flow-toolbar__subtitle">
            {{ npcName }}
          </span>
        </div>
        <div class="flow-toolbar__center">
          <div class="flow-toolbar__group">
            <button
              type="button"
              class="flow-toolbar__btn flow-toolbar__btn--ghost"
              aria-label="Уменьшить масштаб"
              @click="handleZoomOut"
            >
              −
            </button>
            <span class="flow-toolbar__zoom">{{ zoomLabel }}</span>
            <button
              type="button"
              class="flow-toolbar__btn flow-toolbar__btn--ghost"
              aria-label="Увеличить масштаб"
              @click="handleZoomIn"
            >
              +
            </button>
          </div>
          <button type="button" class="flow-toolbar__btn flow-toolbar__btn--ghost" @click="fitToView">
            ⤢ Обзор
          </button>
          <button type="button" class="flow-toolbar__btn flow-toolbar__btn--ghost" @click="resetPosition">
            ⟲ Сбросить
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
          :connection-mode="connectionMode"
          :connection-line-style="connectionLineStyle"
          :snap-to-grid="true"
          :snap-grid="snapGrid"
          :pan-on-scroll="true"
          :pan-on-drag="true"
          :min-zoom="0.35"
          :max-zoom="2.5"
          :fit-view-on-init="false"
          @pane-click="clearSelection"
          @node-click="handleNodeClick"
          @edge-click="handleEdgeClick"
          @connect="handleConnect"
          @pane-ready="handlePaneReady"
        >
          <Background pattern-color="#c7cdfb" :gap="28" />
          <Controls
            class="flow-controls"
            position="bottom-left"
            :show-fit-view="false"
            :show-interactive="false"
          />
          <MiniMap class="flow-minimap" pannable zoomable />
        </VueFlow>

        <div v-if="!nodes.length && !isLoading" class="flow-empty">
          <h4>Добавьте первый узел</h4>
          <p>Перетащите тип из библиотеки слева или воспользуйтесь кнопками быстрого создания.</p>
          <div class="flow-empty__actions">
            <button type="button" class="flow-empty__cta" @click="addNode('start')">
              Стартовый узел
            </button>
            <button type="button" class="flow-empty__cta flow-empty__cta--ghost" @click="addNode('dialog')">
              Диалог
            </button>
          </div>
        </div>

        <div v-if="isLoading" class="flow-overlay">
          <div class="flow-overlay__spinner" />
          <p>Загружаем диалог…</p>
        </div>

        <div class="flow-hud">
          <button type="button" class="flow-hud__btn" @click="addNode('dialog')">
            + Диалог
          </button>
          <button type="button" class="flow-hud__btn" @click="addNode('choice')">
            + Выбор
          </button>
          <button type="button" class="flow-hud__btn" @click="addNode('condition')">
            + Условие
          </button>
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

    <aside class="flow-shell__properties">
      <PropertiesPanel
        v-if="hasSelection"
        :node="selectedNode"
        :edge="selectedEdge"
        :outgoing-edges="outgoingEdges"
        :node-type-options="nodeTypeOptions"
        :node-errors="selectedNode?.errorState || {}"
        :edge-errors="selectedEdge?.errorState || {}"
        context="dialog"
        @delete-node="deleteNode"
        @delete-edge="deleteEdge"
        @duplicate-node="duplicateNode"
        @select-edge="selectEdge"
      />
      <div v-else class="flow-shell__properties-placeholder">
        <h4>Нет выбранного элемента</h4>
        <p>Выберите узел или связь, чтобы изменить содержание и условия переходов.</p>
      </div>
    </aside>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { VueFlow, useVueFlow, ConnectionMode } from '@vue-flow/core';
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
  normalizeDialogGraph,
  serializeDialogGraph,
  GraphValidationError,
} from './flow/utils/serialization';

const nodeTypes = {
  'logic-node': LogicNode,
};

const defaultEdgeOptions = {
  type: 'smoothstep',
  markerEnd: 'arrowclosed',
  style: {
    stroke: '#6b7bff',
    strokeWidth: 2,
  },
};

const connectionMode = ConnectionMode.Loose;
const connectionLineStyle = {
  stroke: '#7b6bff',
  strokeWidth: 2,
  strokeLinecap: 'round',
};

const snapGrid = [28, 28];

const NODE_META = {
  start: { label: 'Старт', icon: '🚀', description: 'Начальное сообщение NPC' },
  dialog: { label: 'Диалог', icon: '💬', description: 'Фраза NPC или реплика игрока' },
  choice: { label: 'Выбор', icon: '🔀', description: 'Ветка выбора игрока' },
  action: { label: 'Действие', icon: '⚙️', description: 'Запуск действий/скриптов' },
  condition: { label: 'Условие', icon: '🛡', description: 'Проверка требований' },
  end: { label: 'Завершение', icon: '🏁', description: 'Конец диалога' },
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
const zoomLevel = ref(100);

let stopViewportListener = null;

const npcName = computed(() => window.npcData?.name || 'NPC');

const { project, fitView, setViewport, getViewport, zoomIn, zoomOut, onViewportChange } = useVueFlow();

const selectedNode = computed(() => nodes.value.find((node) => node.id === selectedNodeId.value) || null);
const selectedEdge = computed(() => edges.value.find((edge) => edge.id === selectedEdgeId.value) || null);

const outgoingEdges = computed(() =>
  selectedNode.value
    ? edges.value.filter((edge) => edge.source === selectedNode.value.id)
    : []
);

const notificationsTimers = new Map();

let hydrating = false;

const hasSelection = computed(() => Boolean(selectedNode.value || selectedEdge.value));
const zoomLabel = computed(() => `${zoomLevel.value}%`);

onMounted(async () => {
  window.addEventListener('keydown', handleShortcuts);
  if (typeof onViewportChange === 'function') {
    stopViewportListener = onViewportChange(({ zoom }) => {
      if (typeof zoom === 'number' && Number.isFinite(zoom)) {
        zoomLevel.value = Math.round(zoom * 100);
      }
    });
  }
    await loadGraph();
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleShortcuts);
  notificationsTimers.forEach((timer) => clearTimeout(timer));
  notificationsTimers.clear();
  if (typeof stopViewportListener === 'function') {
    stopViewportListener();
    stopViewportListener = null;
  }
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
    const response = await axios.get(`/api/npcs/${window.npcData.id}`);
    const { nodes: loadedNodes, edges: loadedEdges } = normalizeDialogGraph(response.data);
    nodes.value = loadedNodes.map(enhanceNode);
    edges.value = loadedEdges.map(enhanceEdge);
    bumpCounters();
    await nextTickFitView();
    isDirty.value = false;
  } catch (error) {
    console.error('Не удалось загрузить граф NPC:', error);
    notify('error', 'Не удалось загрузить диалог NPC');
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
      nodeType: node.data?.nodeType || 'dialog',
      title: node.data?.title || NODE_META[node.data?.nodeType || 'dialog']?.label || 'Узел',
      body: node.data?.body || '',
      conditions: node.data?.conditions || '',
    },
  };
}

function enhanceEdge(edge) {
  return {
    ...edge,
    errorState: {},
    data: {
      label: edge.data?.label || edge.label || '',
      conditions: edge.data?.conditions || '',
    },
  };
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
    'dialog';
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
  if (type === 'start' && nodes.value.some((node) => node.data?.nodeType === 'start')) {
    notify('error', 'Стартовый узел уже существует');
    return;
  }

  const meta = NODE_META[type] || NODE_META.dialog;
  const id = generateNodeId();
  const defaultPosition = position || getDefaultPosition();

  const node = {
    id,
    type: 'logic-node',
    position: defaultPosition,
    data: {
      nodeType: type,
      title: meta.label,
      body: '',
      conditions: '',
    },
    errorState: {},
  };

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
      label: 'Новый выбор',
      conditions: '',
    },
    errorState: {},
  };

  edges.value = [...edges.value, edge];
  selectedEdgeId.value = edge.id;
}

function handleZoomIn() {
  if (typeof zoomIn === 'function') {
    zoomIn();
  }
}

function handleZoomOut() {
  if (typeof zoomOut === 'function') {
    zoomOut();
  }
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
      nodeType: node.data?.nodeType || 'dialog',
      title: `${node.data?.title || 'Узел'} (копия)`,
      body: node.data?.body || '',
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
    const payload = serializeDialogGraph(nodes.value, edges.value);
    await axios.put(`/api/npcs/${window.npcData.id}/nodes`, {
      nodes: payload.nodes.map((node) => ({
        id: node.id,
        clientId: node.clientId,
        type: node.type,
        text: node.text,
        positionX: node.positionX,
        positionY: node.positionY,
        conditions: node.conditions,
      })),
      connections: payload.edges.map((edge) => ({
        id: edge.id,
        clientId: edge.clientId,
        sourceId: edge.sourceId,
        targetId: edge.targetId,
        choiceText: edge.choiceText,
        conditions: edge.conditions,
      })),
    });

    await loadGraph();
    isDirty.value = false;
    notify('success', 'Диалог успешно сохранён');
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

  notify('error', error.message || 'Не удалось сохранить диалог');
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

function handlePaneReady() {
  nextTickFitView();
}

async function nextTickFitView() {
  await new Promise((resolve) => setTimeout(resolve, 50));
  if (nodes.value.length && typeof fitView === 'function') {
    fitToView();
  }
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
  position: relative;
  display: grid;
  grid-template-columns: 280px minmax(0, 1fr) 340px;
  gap: 0;
  width: 100%;
  border-radius: 20px;
  overflow: hidden;
  border: 1px solid rgba(102, 126, 234, 0.25);
  background: rgba(249, 250, 255, 0.96);
  box-shadow: 0 28px 64px rgba(45, 65, 132, 0.18);
  min-height: 680px;
}

.flow-shell__palette {
  position: relative;
  border-right: 1px solid rgba(102, 126, 234, 0.12);
}

.flow-shell__properties {
  position: relative;
  display: flex;
  flex-direction: column;
  background: linear-gradient(180deg, rgba(248, 249, 255, 0.92), rgba(243, 245, 255, 0.88));
  border-left: 1px solid rgba(102, 126, 234, 0.12);
}

.flow-shell__properties-placeholder {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: flex-start;
  gap: 8px;
  padding: 32px;
  color: rgba(31, 42, 86, 0.7);
  font-size: 14px;
}

.flow-shell__properties-placeholder h4 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #28346d;
}

.flow-main {
  display: flex;
  flex-direction: column;
  background: linear-gradient(180deg, rgba(242, 246, 255, 0.95), rgba(228, 235, 255, 0.88));
}

.flow-toolbar {
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  padding: 18px 28px;
  border-bottom: 1px solid rgba(102, 126, 234, 0.16);
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(12px);
  gap: 24px;
  position: sticky;
  top: 0;
  z-index: 5;
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
  font-size: 14px;
  color: rgba(40, 52, 109, 0.65);
}

.flow-toolbar__center {
  display: flex;
  align-items: center;
  gap: 12px;
  justify-self: center;
}

.flow-toolbar__group {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  border-radius: 999px;
  background: rgba(102, 126, 234, 0.12);
  border: 1px solid rgba(102, 126, 234, 0.18);
}

.flow-toolbar__zoom {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 58px;
  font-size: 13px;
  font-weight: 600;
  color: #404b8c;
}

.flow-toolbar__actions {
  display: flex;
  align-items: center;
  gap: 14px;
}

.flow-toolbar__btn {
  padding: 8px 16px;
  border-radius: 12px;
  border: 1px solid rgba(102, 126, 234, 0.25);
  background: rgba(255, 255, 255, 0.88);
  color: #3a478d;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.2s ease, background 0.2s ease;
}

.flow-toolbar__btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 24px rgba(102, 126, 234, 0.25);
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

.flow-toolbar__btn--ghost {
  background: rgba(255, 255, 255, 0.6);
  border-color: rgba(102, 126, 234, 0.15);
  color: #404b8c;
}

.flow-toolbar__btn--ghost:hover {
  border-color: rgba(102, 126, 234, 0.4);
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
  overflow: hidden;
}

.flow-canvas__inner {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, rgba(236, 240, 255, 0.75), rgba(225, 229, 255, 0.9));
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

.flow-empty {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  color: rgba(31, 42, 86, 0.75);
  text-align: center;
  pointer-events: none;
}

.flow-empty h4 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #28346d;
}

.flow-empty p {
  margin: 0;
  font-size: 14px;
  max-width: 320px;
}

.flow-empty__actions {
  display: flex;
  gap: 12px;
  pointer-events: all;
}

.flow-empty__cta {
  padding: 10px 18px;
  border-radius: 12px;
  border: none;
  background: linear-gradient(135deg, #667eea, #7f9cf5);
  color: white;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 16px 30px rgba(102, 126, 234, 0.32);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.flow-empty__cta:hover {
  transform: translateY(-2px);
  box-shadow: 0 18px 36px rgba(102, 126, 234, 0.35);
}

.flow-empty__cta--ghost {
  background: rgba(255, 255, 255, 0.85);
  color: #404b8c;
  border: 1px solid rgba(102, 126, 234, 0.25);
  box-shadow: none;
}

.flow-empty__cta--ghost:hover {
  box-shadow: 0 10px 18px rgba(102, 126, 234, 0.25);
}

.flow-hud {
  position: absolute;
  bottom: 28px;
  right: 28px;
  display: flex;
  gap: 10px;
  pointer-events: all;
}

.flow-hud__btn {
  padding: 10px 14px;
  border-radius: 12px;
  border: none;
  background: rgba(33, 43, 95, 0.85);
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  backdrop-filter: blur(8px);
  box-shadow: 0 12px 24px rgba(17, 23, 48, 0.35);
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.flow-hud__btn:hover {
  background: rgba(33, 43, 95, 0.95);
  transform: translateY(-2px);
}

.flow-controls {
  padding: 10px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.85);
  box-shadow: 0 14px 28px rgba(45, 65, 132, 0.2);
}

.flow-minimap {
  border-radius: 14px;
  overflow: hidden;
  border: 1px solid rgba(102, 126, 234, 0.25);
  background: rgba(255, 255, 255, 0.85);
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

@media (max-width: 1460px) {
  .flow-shell {
    grid-template-columns: 260px minmax(0, 1fr) 320px;
  }
}

@media (max-width: 1280px) {
  .flow-shell {
    grid-template-columns: 240px minmax(0, 1fr);
  }

  .flow-shell__properties {
    display: none;
  }
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
