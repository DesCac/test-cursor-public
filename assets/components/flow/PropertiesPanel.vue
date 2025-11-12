<template>
  <aside class="properties-panel" v-if="hasSelection">
    <header class="properties-panel__header">
      <h4 class="properties-panel__title">
        {{ headerTitle }}
      </h4>
      <div class="properties-panel__actions">
        <button
          v-if="node"
          type="button"
          class="properties-panel__icon-btn"
          title="Дублировать узел"
          @click="$emit('duplicate-node', node)"
        >
          ⎘
        </button>
        <button
          type="button"
          class="properties-panel__icon-btn properties-panel__icon-btn--danger"
          :title="node ? 'Удалить узел' : 'Удалить связь'"
          @click="handleDelete"
        >
          ✕
        </button>
      </div>
    </header>

    <nav class="properties-panel__tabs" v-if="node">
      <button
        v-for="tab in nodeTabs"
        :key="tab.id"
        type="button"
        class="properties-panel__tab"
        :class="{ 'properties-panel__tab--active': currentTab === tab.id }"
        @click="currentTab = tab.id"
      >
        {{ tab.label }}
      </button>
    </nav>

      <section class="properties-panel__content" v-if="node">
        <template v-if="currentTab === 'general'">
          <template v-if="context === 'skill'">
            <fieldset class="properties-panel__group">
              <label class="properties-panel__label">Тип навыка</label>
              <select v-model="node.data.nodeType" class="properties-panel__select">
                <option v-for="option in nodeTypeOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </fieldset>

            <fieldset class="properties-panel__group">
              <label class="properties-panel__label">Название</label>
              <input
                v-model="node.data.title"
                type="text"
                class="properties-panel__input"
                placeholder="Например, «Пламенный всплеск»"
              />
            </fieldset>

            <fieldset class="properties-panel__group">
              <label class="properties-panel__label">Описание</label>
              <textarea
                v-model="node.data.body"
                rows="5"
                class="properties-panel__textarea"
                placeholder="Кратко опишите эффект навыка"
              ></textarea>
            </fieldset>

            <fieldset class="properties-panel__group">
              <label class="properties-panel__label">Минимальный уровень героя</label>
              <input
                v-model.number="node.data.requiredLevel"
                type="number"
                min="0"
                class="properties-panel__input"
                placeholder="0"
              />
            </fieldset>

            <fieldset class="properties-panel__group">
              <label class="properties-panel__label">
                Требуемые классы
                <span class="properties-panel__hint">выберите все классы, для которых доступен навык</span>
              </label>
              <select
                v-model="node.data.requiredClassIds"
                class="properties-panel__select properties-panel__select--multi"
                multiple
              >
                <option
                  v-for="option in classOptions"
                  :key="option.value"
                  :value="String(option.value)"
                >
                  {{ option.label }}
                </option>
              </select>
            </fieldset>

            <fieldset class="properties-panel__group">
              <label class="properties-panel__label">
                Требуемые квесты
                <span class="properties-panel__hint">навык станет доступен после выполнения квестов</span>
              </label>
              <select
                v-model="node.data.requiredQuestIds"
                class="properties-panel__select properties-panel__select--multi"
                multiple
              >
                <option
                  v-for="option in questOptions"
                  :key="option.value"
                  :value="String(option.value)"
                >
                  {{ option.label }}
                </option>
              </select>
            </fieldset>

            <fieldset class="properties-panel__group">
              <label class="properties-panel__label">
                Метаданные навыка (JSON)
                <span class="properties-panel__hint">опишите эффекты или числовые параметры</span>
              </label>
              <textarea
                v-model="node.data.payload"
                rows="6"
                class="properties-panel__textarea properties-panel__textarea--mono"
                :class="{ 'properties-panel__textarea--error': nodeErrors.payload }"
                placeholder='{"effects":{"damage":40}}'
              ></textarea>
              <p v-if="nodeErrors.payload" class="properties-panel__error">
                {{ nodeErrors.payload }}
              </p>
            </fieldset>
          </template>

          <template v-else-if="context === 'dialog'">
            <fieldset class="properties-panel__group">
              <label class="properties-panel__label">Тип узла</label>
              <select v-model="node.data.nodeType" class="properties-panel__select">
                <option v-for="option in nodeTypeOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </fieldset>

            <fieldset class="properties-panel__group">
              <label class="properties-panel__label">Заголовок</label>
              <input
                v-model="node.data.title"
                type="text"
                class="properties-panel__input"
                placeholder="Краткий заголовок"
              />
            </fieldset>

            <fieldset class="properties-panel__group">
              <label class="properties-panel__label">Описание / текст</label>
              <textarea
                v-model="node.data.body"
                rows="5"
                class="properties-panel__textarea"
                placeholder="Что произносит NPC или что должно произойти"
              ></textarea>
            </fieldset>
          </template>

          <template v-else>
            <fieldset class="properties-panel__group">
              <label class="properties-panel__label">Тип узла</label>
              <select v-model="node.data.nodeType" class="properties-panel__select">
                <option v-for="option in nodeTypeOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </fieldset>

            <fieldset class="properties-panel__group">
              <label class="properties-panel__label">
                Данные узла (JSON)
                <span class="properties-panel__hint">объект с параметрами цели/условия</span>
              </label>
              <textarea
                v-model="node.data.payload"
                rows="8"
                class="properties-panel__textarea properties-panel__textarea--mono"
                :class="{ 'properties-panel__textarea--error': nodeErrors.payload }"
                placeholder='{"objective": "Найти вход"}'
              ></textarea>
              <p v-if="nodeErrors.payload" class="properties-panel__error">
                {{ nodeErrors.payload }}
              </p>
            </fieldset>
          </template>
        </template>

        <template v-else-if="currentTab === 'conditions'">
          <fieldset class="properties-panel__group">
            <label class="properties-panel__label">
              {{ context === 'skill' ? 'Дополнительные условия (JSON)' : 'Условия (JSON)' }}
              <span class="properties-panel__hint">
                {{ context === 'skill'
                  ? 'опишите специфические требования: экипировка, атрибуты, теги'
                  : 'поддерживается произвольная структура' }}
              </span>
            </label>
            <textarea
              v-model="node.data.conditions"
              rows="8"
              class="properties-panel__textarea properties-panel__textarea--mono"
              :class="{ 'properties-panel__textarea--error': hasNodeConditionsError }"
              :placeholder="context === 'skill' ? '{\"requiresDualDaggers\": true}' : '{\"level\": {\"min\": 5}}'"
            ></textarea>
            <p v-if="hasNodeConditionsError" class="properties-panel__error">
              {{ nodeErrors.conditions }}
            </p>
          </fieldset>
        </template>

      <template v-else-if="currentTab === 'transitions'">
        <div class="properties-panel__transitions">
          <header class="properties-panel__transitions-header">
            <span>Исходящие переходы</span>
            <small>{{ outgoingEdges.length }}</small>
          </header>
          <div v-if="!outgoingEdges.length" class="properties-panel__empty">
            Перетащите связь к другому узлу, чтобы добавить переход.
          </div>
          <article
            v-for="edge in outgoingEdges"
            :key="edge.id"
            class="properties-panel__transition"
            @click="$emit('select-edge', edge)"
          >
            <header class="properties-panel__transition-header">
              <strong>{{ edge.data.label || 'Без названия' }}</strong>
              <button
                type="button"
                class="properties-panel__icon-btn properties-panel__icon-btn--danger"
                title="Удалить связь"
                @click.stop="$emit('delete-edge', edge)"
              >
                ✕
              </button>
            </header>
            <textarea
              v-model="edge.data.label"
              rows="2"
              class="properties-panel__textarea"
              placeholder="Текст выбора игрока"
            ></textarea>
            <textarea
              v-model="edge.data.conditions"
              rows="3"
              class="properties-panel__textarea properties-panel__textarea--mono"
              :class="{ 'properties-panel__textarea--error': edge.errorState?.conditions }"
              placeholder='{"has_item": "ancient_key"}'
            ></textarea>
            <p v-if="edge.errorState?.conditions" class="properties-panel__error">
              {{ edge.errorState.conditions }}
            </p>
          </article>
        </div>
      </template>
    </section>

    <section class="properties-panel__content" v-else-if="edge">
      <fieldset class="properties-panel__group">
        <label class="properties-panel__label">Текст выбора</label>
        <input
          v-model="edge.data.label"
          type="text"
          class="properties-panel__input"
          placeholder="Что выбирает игрок"
        />
      </fieldset>

      <fieldset class="properties-panel__group">
        <label class="properties-panel__label">Условия (JSON)</label>
        <textarea
          v-model="edge.data.conditions"
          rows="6"
          class="properties-panel__textarea properties-panel__textarea--mono"
          :class="{ 'properties-panel__textarea--error': edgeErrors.conditions }"
          placeholder='{"reputation": {"faction": "mages", "min": 10}}'
        ></textarea>
        <p v-if="edgeErrors.conditions" class="properties-panel__error">
          {{ edgeErrors.conditions }}
        </p>
      </fieldset>
    </section>
  </aside>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  node: {
    type: Object,
    default: null,
  },
  edge: {
    type: Object,
    default: null,
  },
  outgoingEdges: {
    type: Array,
    default: () => [],
  },
  nodeTypeOptions: {
    type: Array,
    default: () => [],
  },
  nodeErrors: {
    type: Object,
    default: () => ({}),
  },
  edgeErrors: {
    type: Object,
    default: () => ({}),
  },
  context: {
    type: String,
    default: 'dialog',
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

const emit = defineEmits(['delete-node', 'delete-edge', 'duplicate-node', 'select-edge']);

const nodeTabs = [
  { id: 'general', label: 'Основное' },
  { id: 'conditions', label: 'Условия' },
  { id: 'transitions', label: 'Переходы' },
];

const currentTab = ref('general');

const hasSelection = computed(() => !!props.node || !!props.edge);
const headerTitle = computed(() => {
  if (props.node) {
    return props.node.data?.title || 'Без названия';
  }
  if (props.edge) {
    return props.edge.data?.label || 'Связь';
  }
  return 'Свойства';
});

const hasNodeConditionsError = computed(() => Boolean(props.nodeErrors.conditions));

watch(
  () => props.node,
  () => {
    currentTab.value = 'general';
    if (props.context === 'skill' && props.node) {
      if (!Array.isArray(props.node.data?.requiredClassIds)) {
        props.node.data.requiredClassIds = [];
      }
      if (!Array.isArray(props.node.data?.requiredQuestIds)) {
        props.node.data.requiredQuestIds = [];
      }
      if (
        props.node.data &&
        props.node.data.requiredLevel !== null &&
        props.node.data.requiredLevel !== undefined &&
        Number.isNaN(Number(props.node.data.requiredLevel))
      ) {
        props.node.data.requiredLevel = null;
      }
    }
  }
);

function handleDelete() {
  if (props.node) {
    emit('delete-node', props.node);
  } else if (props.edge) {
    emit('delete-edge', props.edge);
  }
}
</script>

<style>
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
  background: rgba(102, 126, 234, 0.15);
  color: #4c51bf;
  cursor: pointer;
  font-size: 14px;
  display: grid;
  place-items: center;
}

.properties-panel__icon-btn--danger {
  background: rgba(220, 53, 69, 0.12);
  color: #c53030;
}

.properties-panel__icon-btn:hover {
  filter: brightness(0.9);
}

.properties-panel__tabs {
  display: flex;
  gap: 6px;
  padding: 10px 20px;
  border-bottom: 1px solid rgba(102, 126, 234, 0.15);
  background: rgba(102, 126, 234, 0.05);
}

.properties-panel__tab {
  padding: 6px 12px;
  border-radius: 999px;
  border: none;
  background: transparent;
  color: rgba(31, 42, 86, 0.7);
  font-size: 13px;
  cursor: pointer;
}

.properties-panel__tab--active {
  background: #667eea;
  color: white;
  box-shadow: 0 4px 10px rgba(102, 126, 234, 0.25);
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
  padding: 0;
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

.properties-panel__select--multi {
  min-height: 120px;
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

.properties-panel__transitions {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.properties-panel__transitions-header {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  font-weight: 600;
  color: rgba(31, 42, 86, 0.75);
}

.properties-panel__empty {
  padding: 16px;
  border-radius: 12px;
  background: rgba(102, 126, 234, 0.08);
  color: rgba(31, 42, 86, 0.7);
  font-size: 13px;
  text-align: center;
}

.properties-panel__transition {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 14px;
  border-radius: 12px;
  background: white;
  border: 1px solid rgba(102, 126, 234, 0.12);
  box-shadow: 0 4px 12px rgba(17, 23, 48, 0.05);
  cursor: pointer;
}

.properties-panel__transition-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 13px;
  color: rgba(31, 42, 86, 0.75);
}
</style>

