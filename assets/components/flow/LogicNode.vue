<template>
  <div
    class="logic-node"
    :class="[
      `logic-node--${data.nodeType || 'default'}`,
      { 'logic-node--selected': selected },
      { 'logic-node--has-conditions': hasConditions },
    ]"
  >
    <Handle type="target" :position="Position.Top" :class="handleClass" />
    
    <header class="logic-node__header">
      <span class="logic-node__badge">{{ typeLabel }}</span>
      <span class="logic-node__status" v-if="hasConditions" title="Применяются условия">
        ⛓
      </span>
    </header>
    <main class="logic-node__body">
      <h5 class="logic-node__title">{{ data.title || placeholderTitle }}</h5>
      <p class="logic-node__text" v-if="data.body">
        {{ previewText }}
      </p>
    </main>
    
    <Handle type="source" :position="Position.Bottom" :class="handleClass" />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Handle, Position } from '@vue-flow/core';

// Отключаем наследование атрибутов чтобы избежать [object Object] в DOM
defineOptions({
  inheritAttrs: false
});

const props = defineProps({
  id: String,
  type: String,
  data: {
    type: Object,
    default: () => ({}),
  },
  selected: {
    type: Boolean,
    default: false,
  },
  dragging: Boolean,
  resizing: Boolean,
  connectable: Boolean,
  position: Object,
  dimensions: Object,
  zIndex: [Number, String],
});

const NODE_LABELS = {
  start: 'Старт',
  dialog: 'Диалог',
  choice: 'Выбор',
  action: 'Действие',
  end: 'Завершение',
  condition: 'Условие',
  objective: 'Цель',
  reward: 'Награда',
};

const typeLabel = computed(() => NODE_LABELS[props.data.nodeType] || 'Узел');
const placeholderTitle = computed(() => NODE_LABELS[props.data.nodeType] || 'Новый узел');
const previewText = computed(() => {
  if (!props.data.body) return '';
  if (props.data.body.length <= 80) return props.data.body;
  return `${props.data.body.slice(0, 77)}...`;
});

const hasConditions = computed(() => {
  if (!props.data.conditions) {
    return false;
  }
  try {
    const value = JSON.parse(props.data.conditions);
    return value && typeof value === 'object' && Object.keys(value).length > 0;
  } catch {
    return !!props.data.conditions?.trim();
  }
});

const handleClass = computed(() => [
  'logic-node__handle',
  `logic-node__handle--${props.data.nodeType || 'default'}`,
]);
</script>

<style>
.logic-node {
  position: relative;
  width: 240px;
  min-height: 100px;
  border-radius: 12px;
  border: 2px solid rgba(102, 126, 234, 0.3);
  background: #ffffff;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(0, 0, 0, 0.03);
  padding: 0;
  color: #1f2a56;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
  cursor: pointer;
  overflow: hidden;
}

.logic-node__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 14px;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.12), rgba(118, 75, 162, 0.08));
  border-bottom: 1px solid rgba(102, 126, 234, 0.15);
}

.logic-node__badge {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: #4c51bf;
}

.logic-node__status {
  font-size: 14px;
}

.logic-node__body {
  padding: 12px 14px;
}

.logic-node__title {
  margin: 0 0 6px;
  font-size: 14px;
  font-weight: 600;
  color: #1a202c;
}

.logic-node__text {
  margin: 0;
  font-size: 12px;
  line-height: 1.5;
  color: #4a5568;
}

.logic-node:hover {
  border-color: rgba(102, 126, 234, 0.5);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12), 0 0 0 1px rgba(102, 126, 234, 0.2);
}

.logic-node--selected {
  border-color: #667eea;
  box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3), 0 0 0 3px rgba(102, 126, 234, 0.2);
}

.logic-node--start .logic-node__header {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.12), rgba(5, 150, 105, 0.08));
}

.logic-node--start .logic-node__badge {
  color: #047857;
}

.logic-node--start {
  border-color: rgba(16, 185, 129, 0.4);
}

.logic-node--dialog .logic-node__header {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.12), rgba(37, 99, 235, 0.08));
}

.logic-node--dialog .logic-node__badge {
  color: #1e40af;
}

.logic-node--dialog {
  border-color: rgba(59, 130, 246, 0.4);
}

.logic-node--choice .logic-node__header {
  background: linear-gradient(135deg, rgba(251, 191, 36, 0.12), rgba(245, 158, 11, 0.08));
}

.logic-node--choice .logic-node__badge {
  color: #92400e;
}

.logic-node--choice {
  border-color: rgba(251, 191, 36, 0.4);
}

.logic-node--action .logic-node__header {
  background: linear-gradient(135deg, rgba(139, 92, 246, 0.12), rgba(124, 58, 237, 0.08));
}

.logic-node--action .logic-node__badge {
  color: #6d28d9;
}

.logic-node--action {
  border-color: rgba(139, 92, 246, 0.4);
}

.logic-node--condition .logic-node__header {
  background: linear-gradient(135deg, rgba(236, 72, 153, 0.12), rgba(219, 39, 119, 0.08));
}

.logic-node--condition .logic-node__badge {
  color: #9d174d;
}

.logic-node--condition {
  border-color: rgba(236, 72, 153, 0.4);
}

.logic-node--reward .logic-node__header {
  background: linear-gradient(135deg, rgba(34, 197, 94, 0.12), rgba(22, 163, 74, 0.08));
}

.logic-node--reward .logic-node__badge {
  color: #15803d;
}

.logic-node--reward {
  border-color: rgba(34, 197, 94, 0.4);
}

.logic-node--end .logic-node__header {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.12), rgba(220, 38, 38, 0.08));
}

.logic-node--end .logic-node__badge {
  color: #b91c1c;
}

.logic-node--end {
  border-color: rgba(239, 68, 68, 0.4);
}

.logic-node__handle {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  border: 2px solid white;
  background: #667eea;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
  transition: all 0.2s ease;
}

.logic-node__handle:hover {
  width: 14px;
  height: 14px;
  box-shadow: 0 0 0 6px rgba(102, 126, 234, 0.25);
}

.logic-node__handle--start {
  background: #0f766e;
  box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.2);
}

.logic-node__handle--dialog {
  background: #4338ca;
  box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.2);
}

.logic-node__handle--choice {
  background: #92400e;
  box-shadow: 0 0 0 3px rgba(146, 64, 14, 0.2);
}

.logic-node__handle--action {
  background: #3730a3;
  box-shadow: 0 0 0 3px rgba(55, 48, 163, 0.2);
}

.logic-node__handle--condition {
  background: #9d174d;
  box-shadow: 0 0 0 3px rgba(157, 23, 77, 0.2);
}

.logic-node__handle--reward {
  background: #047857;
  box-shadow: 0 0 0 3px rgba(4, 120, 87, 0.2);
}

.logic-node__handle--end {
  background: #b91c1c;
  box-shadow: 0 0 0 3px rgba(185, 28, 28, 0.2);
}
</style>

