<template>
  <div class="quest-editor">
    <div class="editor-header">
      <h3>Quest Logic Editor</h3>
      <div class="actions">
        <button @click="addNode('objective')" class="btn-add">+ Add Objective</button>
        <button @click="addNode('condition')" class="btn-add">+ Add Condition</button>
        <button @click="saveGraph" class="btn-save">💾 Save</button>
      </div>
    </div>

    <div class="editor-container">
      <VueFlow
        v-model="elements"
        :default-zoom="1"
        :min-zoom="0.2"
        :max-zoom="4"
        @node-click="onNodeClick"
        @connect="onConnect"
      >
        <Background pattern-color="#aaa" :gap="16" />
        <Controls />
        <MiniMap />
      </VueFlow>
    </div>

    <div v-if="selectedNode" class="node-properties">
      <h4>Node Properties</h4>
      <div class="property">
        <label>Type:</label>
        <select v-model="selectedNode.data.nodeType">
          <option value="start">Start</option>
          <option value="objective">Objective</option>
          <option value="condition">Condition</option>
          <option value="reward">Reward</option>
          <option value="end">End</option>
        </select>
      </div>
      <div class="property">
        <label>Data (JSON):</label>
        <textarea v-model="selectedNode.data.dataJson" rows="4" placeholder='{"objective": "Kill 10 enemies"}'></textarea>
      </div>
      <div class="property">
        <label>Conditions (JSON):</label>
        <textarea v-model="selectedNode.data.conditions" rows="3" placeholder='{"level": 5}'></textarea>
      </div>
      <button @click="deleteNode" class="btn-delete">Delete Node</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { VueFlow, useVueFlow } from '@vue-flow/core';
import { Background } from '@vue-flow/background';
import { Controls } from '@vue-flow/controls';
import { MiniMap } from '@vue-flow/minimap';
import axios from 'axios';

import '@vue-flow/core/dist/style.css';
import '@vue-flow/core/dist/theme-default.css';
import '@vue-flow/controls/dist/style.css';
import '@vue-flow/minimap/dist/style.css';

const { addNodes, addEdges, removeNodes, removeEdges } = useVueFlow();

const elements = ref([]);
const selectedNode = ref(null);
const nodeIdCounter = ref(1);

onMounted(async () => {
  if (window.questData) {
    await loadGraph();
  }
});

async function loadGraph() {
  try {
    const response = await axios.get(`/api/quests/${window.questData.id}`);
    const quest = response.data;

    const nodes = quest.nodes.map(node => ({
      id: `${node.id}`,
      type: 'default',
      position: { x: node.positionX || 0, y: node.positionY || 0 },
      data: {
        label: node.type,
        nodeType: node.type,
        dataJson: JSON.stringify(node.data || {}),
        conditions: JSON.stringify(node.conditions || {})
      }
    }));

    const edges = [];
    quest.nodes.forEach(node => {
      node.connections.forEach(conn => {
        edges.push({
          id: `e${node.id}-${conn.targetNodeId}`,
          source: `${node.id}`,
          target: `${conn.targetNodeId}`,
          data: {
            conditions: conn.conditions
          }
        });
      });
    });

    elements.value = [...nodes, ...edges];
  } catch (error) {
    console.error('Failed to load graph:', error);
  }
}

function addNode(type) {
  const newNode = {
    id: `new-${nodeIdCounter.value++}`,
    type: 'default',
    position: { x: Math.random() * 400, y: Math.random() * 400 },
    data: {
      label: type,
      nodeType: type,
      dataJson: '{}',
      conditions: '{}'
    }
  };

  elements.value.push(newNode);
}

function onNodeClick(event) {
  selectedNode.value = event.node;
}

function onConnect(params) {
  const newEdge = {
    ...params,
    id: `e${params.source}-${params.target}`,
    data: { conditions: null }
  };
  elements.value.push(newEdge);
}

function deleteNode() {
  if (selectedNode.value) {
    elements.value = elements.value.filter(el => el.id !== selectedNode.value.id);
    selectedNode.value = null;
  }
}

async function saveGraph() {
  try {
    alert('Quest graph save functionality will be implemented');
  } catch (error) {
    console.error('Failed to save graph:', error);
    alert('Failed to save graph');
  }
}
</script>

<style scoped>
.quest-editor {
  display: flex;
  flex-direction: column;
  height: 600px;
  border: 1px solid #ddd;
  border-radius: 8px;
  overflow: hidden;
}

.editor-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  background: #f8f9fa;
  border-bottom: 1px solid #ddd;
}

.editor-header h3 {
  margin: 0;
  color: #764ba2;
}

.actions {
  display: flex;
  gap: 10px;
}

.btn-add, .btn-save {
  padding: 8px 16px;
  background: #764ba2;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 14px;
}

.btn-add:hover, .btn-save:hover {
  background: #5d3a7f;
}

.editor-container {
  flex: 1;
  position: relative;
}

.node-properties {
  padding: 15px;
  background: #f8f9fa;
  border-top: 1px solid #ddd;
  max-height: 300px;
  overflow-y: auto;
}

.node-properties h4 {
  margin-top: 0;
  color: #764ba2;
}

.property {
  margin-bottom: 15px;
}

.property label {
  display: block;
  font-weight: 600;
  margin-bottom: 5px;
  color: #333;
}

.property select,
.property textarea {
  width: 100%;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-family: inherit;
}

.btn-delete {
  padding: 8px 16px;
  background: #dc3545;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 14px;
}

.btn-delete:hover {
  background: #c82333;
}
</style>
