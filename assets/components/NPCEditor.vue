<template>
  <div class="npc-editor">
    <div class="editor-header">
      <h3>Dialog Tree Editor</h3>
      <div class="actions">
        <button @click="addNode('dialog')" class="btn-add">+ Add Dialog</button>
        <button @click="addNode('choice')" class="btn-add">+ Add Choice</button>
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
        @edge-click="onEdgeClick"
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
          <option value="dialog">Dialog</option>
          <option value="choice">Choice</option>
          <option value="action">Action</option>
          <option value="end">End</option>
        </select>
      </div>
      <div class="property">
        <label>Text:</label>
        <textarea v-model="selectedNode.data.text" rows="4"></textarea>
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
  if (window.npcData) {
    await loadGraph();
  }
});

async function loadGraph() {
  try {
    const response = await axios.get(`/api/npcs/${window.npcData.id}`);
    const npc = response.data;

    const nodes = npc.nodes.map(node => ({
      id: `${node.id}`,
      type: 'default',
      position: { x: node.positionX || 0, y: node.positionY || 0 },
      data: {
        label: node.text || node.type,
        nodeType: node.type,
        text: node.text,
        conditions: JSON.stringify(node.conditions || {})
      }
    }));

    const edges = [];
    npc.nodes.forEach(node => {
      node.connections.forEach(conn => {
        edges.push({
          id: `e${node.id}-${conn.targetNodeId}`,
          source: `${node.id}`,
          target: `${conn.targetNodeId}`,
          label: conn.choiceText,
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
      text: '',
      conditions: '{}'
    }
  };

  elements.value.push(newNode);
}

function onNodeClick(event) {
  selectedNode.value = event.node;
}

function onEdgeClick(event) {
  // Handle edge selection if needed
}

function onConnect(params) {
  const newEdge = {
    ...params,
    id: `e${params.source}-${params.target}`,
    label: 'Choice',
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
    const nodes = elements.value.filter(el => !el.source);
    const edges = elements.value.filter(el => el.source);

    await axios.put(`/api/npcs/${window.npcData.id}/nodes`, {
      nodes: nodes.map(n => ({
        id: n.id,
        type: n.data.nodeType,
        text: n.data.text,
        conditions: JSON.parse(n.data.conditions || '{}'),
        positionX: n.position.x,
        positionY: n.position.y
      })),
      connections: edges.map(e => ({
        sourceId: e.source,
        targetId: e.target,
        choiceText: e.label,
        conditions: e.data.conditions
      }))
    });

    alert('Graph saved successfully!');
  } catch (error) {
    console.error('Failed to save graph:', error);
    alert('Failed to save graph');
  }
}
</script>

<style scoped>
.npc-editor {
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
  color: #667eea;
}

.actions {
  display: flex;
  gap: 10px;
}

.btn-add, .btn-save {
  padding: 8px 16px;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 14px;
}

.btn-add:hover, .btn-save:hover {
  background: #5568d3;
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
  color: #667eea;
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
