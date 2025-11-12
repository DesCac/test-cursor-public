<template>
  <div
    class="logic-node"
    :class="[
      `logic-node--${data.nodeType || 'default'}`,
      { 'logic-node--selected': selected },
      { 'logic-node--has-conditions': hasConditions },
    ]"
  >
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
  </div>
  <Handle type="target" position="top" :class="handleClass" />
  <Handle type="source" position="bottom" :class="handleClass" />
</template>

<script setup>
import { computed } from 'vue';
import { Handle, Position } from '@vue-flow/core';

const props = defineProps({
  data: {
    type: Object,
    default: () => ({}),
  },
  selected: {
    type: Boolean,
    default: false,
  },
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

<style scoped>
.logic-node {
  position: relative;
  width: 220px;
  min-height: 110px;
  border-radius: 18px;
  border: 1px solid rgba(255, 255, 255, 0.4);
  background: linear-gradient(165deg, rgba(255, 255, 255, 0.92), rgba(239, 244, 255, 0.86));
  box-shadow: 0 10px 30px rgba(45, 65, 132, 0.12);
  padding: 14px;
  color: #1f2a56;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.logic-node__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.logic-node__badge {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  padding: 4px 10px;
  border-radius: 999px;
  background: rgba(102, 126, 234, 0.2);
  color: #4c51bf;
}

.logic-node__status {
  font-size: 14px;
}

.logic-node__title {
  margin: 0 0 6px;
  font-size: 16px;
  font-weight: 600;
}

.logic-node__text {
  margin: 0;
  font-size: 13px;
  line-height: 1.45;
  color: rgba(31, 42, 86, 0.75);
}

.logic-node--selected {
  border-color: rgba(102, 126, 234, 0.8);
  box-shadow: 0 12px 32px rgba(102, 126, 234, 0.35);
}

.logic-node--start .logic-node__badge {
  background: rgba(56, 178, 172, 0.15);
  color: #0f766e;
}

.logic-node--dialog .logic-node__badge {
  background: rgba(102, 126, 234, 0.18);
  color: #4338ca;
}

.logic-node--choice .logic-node__badge {
  background: rgba(251, 191, 36, 0.22);
  color: #92400e;
}

.logic-node--action .logic-node__badge {
  background: rgba(129, 140, 248, 0.2);
  color: #3730a3;
}

.logic-node--condition .logic-node__badge {
  background: rgba(236, 72, 153, 0.18);
  color: #9d174d;
}

.logic-node--reward .logic-node__badge {
  background: rgba(16, 185, 129, 0.18);
  color: #047857;
}

.logic-node--end .logic-node__badge {
  background: rgba(239, 68, 68, 0.18);
  color: #b91c1c;
}

.logic-node__handle {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 2px solid white;
  background: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
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

