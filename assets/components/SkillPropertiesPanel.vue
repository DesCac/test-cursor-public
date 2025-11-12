<template>
  <aside class="properties-panel" v-if="hasSelection">
    <header class="properties-panel__header">
      <h4 class="properties-panel__title">
        {{ headerTitle }}
      </h4>
      <div class="properties-panel__actions">
        <button
          type="button"
          class="properties-panel__icon-btn properties-panel__icon-btn--danger"
          :title="node ? 'Удалить навык' : 'Удалить связь'"
          @click="handleDelete"
        >
          ✕
        </button>
      </div>
    </header>

    <section class="properties-panel__content" v-if="node">
      <fieldset class="properties-panel__group">
        <label class="properties-panel__label">Название навыка</label>
        <input
          v-model="node.data.title"
          type="text"
          class="properties-panel__input"
          placeholder="Название навыка"
        />
      </fieldset>

      <fieldset class="properties-panel__group">
        <label class="properties-panel__label">Slug</label>
        <input
          v-model="node.data.slug"
          type="text"
          class="properties-panel__input"
          placeholder="slug-название-навика"
        />
      </fieldset>

      <fieldset class="properties-panel__group">
        <label class="properties-panel__label">Описание</label>
        <textarea
          v-model="node.data.body"
          rows="4"
          class="properties-panel__textarea"
          placeholder="Краткое описание эффекта навыка"
        ></textarea>
      </fieldset>

      <fieldset class="properties-panel__group">
        <label class="properties-panel__label">Требуемый уровень</label>
        <input
          v-model="node.data.requiredLevel"
          type="number"
          min="0"
          class="properties-panel__input"
          placeholder="Например, 5"
        />
      </fieldset>

      <fieldset class="properties-panel__group">
        <label class="properties-panel__label">
          Необходимые классы
          <span class="properties-panel__hint">Выберите классы, для которых доступен навык</span>
        </label>
        <select
          v-model="node.data.requiredClasses"
          class="properties-panel__select"
          multiple
          size="5"
        >
          <option
            v-for="classOption in classOptions"
            :key="classOption.id"
            :value="String(classOption.id)"
          >
            {{ classOption.name }}
          </option>
        </select>
      </fieldset>

      <fieldset class="properties-panel__group">
        <label class="properties-panel__label">
          Требуемые квесты
          <span class="properties-panel__hint">Навык откроется после завершения выбранных квестов</span>
        </label>
        <select
          v-model="node.data.requiredQuests"
          class="properties-panel__select"
          multiple
          size="5"
        >
          <option
            v-for="quest in questOptions"
            :key="quest.id"
            :value="String(quest.id)"
          >
            {{ quest.name }}
          </option>
        </select>
      </fieldset>

      <fieldset class="properties-panel__group">
        <label class="properties-panel__label">
          Дополнительные условия (JSON)
          <span class="properties-panel__hint">Например: {"attributes":{"agility":{"min":5}}}</span>
        </label>
        <textarea
          v-model="node.data.availabilityRules"
          rows="8"
          class="properties-panel__textarea properties-panel__textarea--mono"
          :class="{ 'properties-panel__textarea--error': node.errorState?.availabilityRules }"
        ></textarea>
        <p v-if="node.errorState?.availabilityRules" class="properties-panel__error">
          {{ node.errorState.availabilityRules }}
        </p>
      </fieldset>
    </section>

    <section class="properties-panel__content" v-else-if="edge">
      <fieldset class="properties-panel__group">
        <label class="properties-panel__label">
          Требовать все родительские навыки
          <span class="properties-panel__hint">
            Если выключено, достаточно одного из родителей, чтобы открыть навык
          </span>
        </label>
        <label class="properties-panel__toggle">
          <input
            v-model="edge.data.requiresAllParents"
            type="checkbox"
          />
          <span>Включено</span>
        </label>
      </fieldset>

      <fieldset class="properties-panel__group">
        <label class="properties-panel__label">
          Дополнительные данные (JSON)
          <span class="properties-panel__hint">Произвольные метаданные для связи</span>
        </label>
        <textarea
          v-model="edge.data.metadata"
          rows="6"
          class="properties-panel__textarea properties-panel__textarea--mono"
          :class="{ 'properties-panel__textarea--error': edge.errorState?.metadata }"
        ></textarea>
        <p v-if="edge.errorState?.metadata" class="properties-panel__error">
          {{ edge.errorState.metadata }}
        </p>
      </fieldset>
    </section>
  </aside>
</template>

<script setup>
import { computed, watch } from 'vue';

const props = defineProps({
  node: {
    type: Object,
    default: null,
  },
  edge: {
    type: Object,
    default: null,
  },
  classOptions: {
    type: Array,
    default: () => [],
  },
  questOptions: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['delete-node', 'delete-edge']);

const hasSelection = computed(() => Boolean(props.node) || Boolean(props.edge));

const headerTitle = computed(() => {
  if (props.node) {
    return props.node.data?.title || 'Навык';
  }
  if (props.edge) {
    return 'Связь навыков';
  }
  return 'Свойства';
});

watch(
  () => props.node,
  (node) => {
    if (!node) {
      return;
    }
    if (!Array.isArray(node.data.requiredClasses)) {
      node.data.requiredClasses = [];
    } else {
      node.data.requiredClasses = node.data.requiredClasses.map((value) => String(value));
    }

    if (!Array.isArray(node.data.requiredQuests)) {
      node.data.requiredQuests = [];
    } else {
      node.data.requiredQuests = node.data.requiredQuests.map((value) => String(value));
    }

    node.data.requiredLevel =
      node.data.requiredLevel === null || node.data.requiredLevel === undefined
        ? ''
        : node.data.requiredLevel;
  },
  { immediate: true }
);

watch(
  () => props.edge,
  (edge) => {
    if (!edge) {
      return;
    }
    edge.data = edge.data || {};
    edge.data.requiresAllParents = edge.data.requiresAllParents !== false;
    if (typeof edge.data.metadata !== 'string') {
      edge.data.metadata = edge.data.metadata ? JSON.stringify(edge.data.metadata, null, 2) : '';
    }
  },
  { immediate: true }
);

function handleDelete() {
  if (props.node) {
    emit('delete-node', props.node);
  } else if (props.edge) {
    emit('delete-edge', props.edge);
  }
}
</script>

<style scoped>
.properties-panel {
  display: flex;
  flex-direction: column;
  width: 320px;
  min-width: 300px;
  max-width: 360px;
  background: #fdfdff;
  border-left: 1px solid rgba(102, 126, 234, 0.2);
  color: #1f2a56;
}

.properties-panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 20px 14px;
  border-bottom: 1px solid rgba(102, 126, 234, 0.2);
}

.properties-panel__title {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
}

.properties-panel__actions {
  display: flex;
  gap: 8px;
}

.properties-panel__icon-btn {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  border: none;
  background: rgba(220, 53, 69, 0.12);
  color: #c53030;
  cursor: pointer;
  font-size: 14px;
  display: grid;
  place-items: center;
}

.properties-panel__content {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.properties-panel__group {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin: 0;
  border: none;
}

.properties-panel__label {
  font-size: 13px;
  font-weight: 600;
  color: rgba(31, 42, 86, 0.85);
  display: flex;
  flex-direction: column;
}

.properties-panel__hint {
  font-weight: 400;
  font-size: 11px;
  color: rgba(31, 42, 86, 0.55);
}

.properties-panel__input,
.properties-panel__select,
.properties-panel__textarea {
  width: 100%;
  padding: 10px 12px;
  font-size: 14px;
  border-radius: 10px;
  border: 1px solid rgba(31, 42, 86, 0.12);
  background: white;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.properties-panel__textarea {
  min-height: 80px;
  resize: vertical;
  line-height: 1.45;
}

.properties-panel__textarea--mono {
  font-family: ui-monospace, "SFMono-Regular", Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
  font-size: 13px;
}

.properties-panel__textarea--error {
  border-color: #e53e3e;
  background: rgba(229, 62, 62, 0.05);
}

.properties-panel__input:focus,
.properties-panel__select:focus,
.properties-panel__textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.18);
}

.properties-panel__error {
  margin: 0;
  font-size: 12px;
  color: #c53030;
}

.properties-panel__toggle {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  color: rgba(31, 42, 86, 0.8);
}
</style>
