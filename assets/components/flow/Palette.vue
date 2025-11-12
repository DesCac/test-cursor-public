<template>
  <aside class="flow-palette">
    <header class="flow-palette__header">
      <h4 class="flow-palette__title">Библиотека узлов</h4>
    </header>

    <div class="flow-palette__search" v-if="filterable">
      <input
        v-model="search"
        type="search"
        class="flow-palette__input"
        placeholder="Поиск типов..."
      />
    </div>

    <section class="flow-palette__items">
      <article
        v-for="item in filteredItems"
        :key="item.type"
        class="flow-palette__item"
        draggable="true"
        @dragstart="onDragStart($event, item)"
        @dblclick.prevent="handleAdd(item)"
        @keydown.enter.prevent="handleAdd(item)"
        tabindex="0"
        role="button"
        :aria-label="`Добавить узел типа ${item.label}`"
      >
        <div class="flow-palette__icon">{{ item.icon }}</div>
        <div class="flow-palette__meta">
          <div class="flow-palette__label">{{ item.label }}</div>
          <p class="flow-palette__description">{{ item.description }}</p>
        </div>
        <button
          type="button"
          class="flow-palette__add"
          @click.stop="handleAdd(item)"
        >
          +
        </button>
      </article>
    </section>
  </aside>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  filterable: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['add-node', 'drag-node']);

const search = ref('');

const filteredItems = computed(() => {
  if (!search.value.trim()) {
    return props.items;
  }

  const query = search.value.trim().toLowerCase();
  return props.items.filter((item) => {
    return (
      item.label.toLowerCase().includes(query) ||
      (item.type && item.type.toLowerCase().includes(query)) ||
      (item.description && item.description.toLowerCase().includes(query))
    );
  });
});

function onDragStart(event, item) {
  emit('drag-node', { event, item });
}

function handleAdd(item) {
  emit('add-node', item);
}
</script>

<style scoped>
.flow-palette {
  display: flex;
  flex-direction: column;
  width: 260px;
  min-width: 240px;
  max-width: 300px;
  background: #f5f7fb;
  border-right: 1px solid rgba(102, 126, 234, 0.2);
  color: #1f2a56;
}

.flow-palette__header {
  padding: 16px 20px 12px;
}

.flow-palette__title {
  margin: 0;
  font-size: 15px;
  font-weight: 600;
  letter-spacing: 0.02em;
}

.flow-palette__search {
  padding: 0 20px 12px;
}

.flow-palette__input {
  width: 100%;
  padding: 8px 10px;
  border-radius: 8px;
  border: 1px solid rgba(31, 42, 86, 0.15);
  font-size: 14px;
  background: white;
  transition: border-color 0.2s ease;
}

.flow-palette__input:focus {
  border-color: #667eea;
  outline: none;
  box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.15);
}

.flow-palette__items {
  padding: 0 12px 16px;
  overflow-y: auto;
  flex: 1;
}

.flow-palette__item {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 12px;
  align-items: center;
  padding: 12px;
  margin-bottom: 10px;
  border-radius: 12px;
  background: white;
  border: 1px solid rgba(103, 126, 234, 0.1);
  box-shadow: 0 4px 10px rgba(17, 23, 48, 0.05);
  cursor: grab;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.flow-palette__item:active {
  cursor: grabbing;
}

.flow-palette__item:focus-visible {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.18);
}

.flow-palette__item:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(17, 23, 48, 0.1);
}

.flow-palette__icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.15));
  color: #4c51bf;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.flow-palette__meta {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.flow-palette__label {
  font-weight: 600;
  font-size: 14px;
}

.flow-palette__description {
  margin: 0;
  font-size: 12px;
  color: rgba(31, 42, 86, 0.6);
}

.flow-palette__add {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: none;
  background: #667eea;
  color: white;
  cursor: pointer;
  font-size: 18px;
  line-height: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s ease;
}

.flow-palette__add:hover {
  background: #5568d3;
}
</style>

