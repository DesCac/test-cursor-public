export class GraphValidationError extends Error {
  constructor(message, meta = {}) {
    super(message);
    this.name = 'GraphValidationError';
    this.meta = meta;
  }
}

export function normalizeDialogGraph(npc) {
  const nodes = (npc.nodes || []).map((node) => ({
    id: String(node.id),
    type: 'logic-node',
    position: {
      x: typeof node.positionX === 'number' ? node.positionX : 0,
      y: typeof node.positionY === 'number' ? node.positionY : 0,
    },
    data: {
      nodeType: node.type || 'dialog',
      title: node.text ? node.text.split('\n')[0] : formatType(node.type),
      body: node.text || '',
      conditions: formatJson(node.conditions),
    },
  }));

  const edges = [];
  (npc.nodes || []).forEach((node) => {
    (node.connections || []).forEach((connection, index) => {
      edges.push({
        id: connection.id ? String(connection.id) : buildEdgeId(node.id, connection.targetNodeId, index),
        type: 'logic-edge',
        source: String(node.id),
        target: String(connection.targetNodeId),
        label: connection.choiceText || '',
        data: {
          label: connection.choiceText || '',
          conditions: formatJson(connection.conditions),
        },
      });
    });
  });

  return { nodes, edges };
}

export function serializeDialogGraph(nodes, edges) {
  const formattedNodes = nodes.map((node) => {
    const nodeTitle = node.data?.title || node.id;
    const conditions = safeParseJson(node.data?.conditions || '', {
      entity: 'node',
      field: 'conditions',
      id: node.id,
      message: `Узел "${nodeTitle}" содержит некорректный JSON условий`,
    });

    return {
      id: node.id.startsWith('temp-') ? null : Number(node.id) || node.id,
      clientId: node.id,
      type: node.data?.nodeType || 'dialog',
      text: node.data?.body || '',
      positionX: node.position?.x ?? 0,
      positionY: node.position?.y ?? 0,
      conditions,
    };
  });

  const formattedEdges = edges.map((edge) => {
    const edgeLabel = edge.data?.label || edge.id;
    const conditions = safeParseJson(edge.data?.conditions || '', {
      entity: 'edge',
      field: 'conditions',
      id: edge.id,
      message: `Связь "${edgeLabel}" содержит некорректный JSON условий`,
    });

    return {
      id: edge.id.startsWith('temp-edge-') ? null : Number(edge.id) || edge.id,
      clientId: edge.id,
      sourceId: edge.source,
      targetId: edge.target,
      choiceText: edge.data?.label || '',
      conditions,
    };
  });

  return { nodes: formattedNodes, edges: formattedEdges };
}

export function normalizeQuestGraph(quest) {
  const nodes = (quest.nodes || []).map((node) => ({
    id: String(node.id),
    type: 'logic-node',
    position: {
      x: typeof node.positionX === 'number' ? node.positionX : 0,
      y: typeof node.positionY === 'number' ? node.positionY : 0,
    },
    data: {
      nodeType: node.type || 'objective',
      title: formatType(node.type),
      body: formatQuestBody(node.data),
      payload: formatJson(node.data),
      conditions: formatJson(node.conditions),
    },
  }));

  const edges = [];
  (quest.nodes || []).forEach((node) => {
    (node.connections || []).forEach((connection, index) => {
      edges.push({
        id: connection.id ? String(connection.id) : buildEdgeId(node.id, connection.targetNodeId, index),
        type: 'logic-edge',
        source: String(node.id),
        target: String(connection.targetNodeId),
        data: {
          conditions: formatJson(connection.conditions),
          label: '',
        },
      });
    });
  });

  return { nodes, edges };
}

export function serializeQuestGraph(nodes, edges) {
  const formattedNodes = nodes.map((node) => {
    const nodeTitle = node.data?.title || node.id;
    const payload = safeParseJson(node.data?.payload || '{}', {
      entity: 'node',
      field: 'payload',
      id: node.id,
      message: `Узел "${nodeTitle}" содержит некорректный JSON данных`,
    });
    const conditions = safeParseJson(node.data?.conditions || '{}', {
      entity: 'node',
      field: 'conditions',
      id: node.id,
      message: `Узел "${nodeTitle}" содержит некорректный JSON условий`,
    });

    return {
      id: node.id.startsWith('temp-') ? null : Number(node.id) || node.id,
      clientId: node.id,
      type: node.data?.nodeType || 'objective',
      data: payload,
      conditions,
      positionX: node.position?.x ?? 0,
      positionY: node.position?.y ?? 0,
    };
  });

  const formattedEdges = edges.map((edge) => {
    const conditions = safeParseJson(edge.data?.conditions || '{}', {
      entity: 'edge',
      field: 'conditions',
      id: edge.id,
      message: `Связь ${edge.id} содержит некорректный JSON условий`,
    });

    return {
      id: edge.id.startsWith('temp-edge-') ? null : Number(edge.id) || edge.id,
      clientId: edge.id,
      sourceId: edge.source,
      targetId: edge.target,
      conditions,
    };
  });

  return { nodes: formattedNodes, edges: formattedEdges };
}

export function normalizeSkillGraph(graph) {
  const focusId = graph.focusSkillId ? String(graph.focusSkillId) : null;
  const nodes = (graph.nodes || []).map((skill) => ({
    id: String(skill.id),
    type: 'logic-node',
    position: {
      x: typeof skill.positionX === 'number' ? skill.positionX : 0,
      y: typeof skill.positionY === 'number' ? skill.positionY : 0,
    },
    data: {
      nodeType: 'skill',
      title: skill.name || 'Навык',
      body: skill.description || '',
      slug: skill.slug || '',
      requiredLevel: typeof skill.requiredLevel === 'number' ? skill.requiredLevel : '',
      availabilityRules: formatJson(skill.availabilityRules),
      conditions: formatJson(skill.availabilityRules),
      requiredClasses: Array.isArray(skill.requiredClasses) ? skill.requiredClasses.map(String) : [],
      requiredQuests: Array.isArray(skill.requiredQuests) ? skill.requiredQuests.map(String) : [],
    },
    errorState: {},
    meta: {
      isFocus: focusId ? String(skill.id) === focusId : false,
    },
  }));

  const edges = (graph.links || []).map((link) => ({
    id: link.id ? String(link.id) : buildEdgeId(link.parentId, link.childId, Math.random().toString(16).slice(2)),
    type: 'logic-edge',
    source: String(link.parentId),
    target: String(link.childId),
    data: {
      requiresAllParents: link.requiresAllParents !== false,
      metadata: formatJson(link.metadata),
    },
    errorState: {},
  }));

  return { nodes, edges, focusId };
}

export function serializeSkillGraph(nodes, edges, options = {}) {
  const formattedNodes = nodes.map((node) => {
    const nodeTitle = node.data?.title || node.id;
    const availabilityRules = safeParseJson(node.data?.availabilityRules || '', {
      entity: 'node',
      field: 'availabilityRules',
      id: node.id,
      message: `Навык "${nodeTitle}" содержит некорректный JSON правил доступности`,
    });

    return {
      id: node.id.startsWith('temp-') ? null : Number(node.id) || node.id,
      clientId: node.id,
      name: node.data?.title || 'Навык',
      slug: node.data?.slug || '',
      description: node.data?.body || '',
      requiredLevel: node.data?.requiredLevel === '' || node.data?.requiredLevel === null
        ? null
        : Number(node.data.requiredLevel),
      availabilityRules: isEmptyObject(availabilityRules) ? null : availabilityRules,
      positionX: node.position?.x ?? 0,
      positionY: node.position?.y ?? 0,
      requiredClasses: (node.data?.requiredClasses || []).map((value) => Number(value)).filter(Number.isFinite),
      requiredQuests: (node.data?.requiredQuests || []).map((value) => Number(value)).filter(Number.isFinite),
    };
  });

  const formattedEdges = edges.map((edge) => {
    const metadata = safeParseJson(edge.data?.metadata || '', {
      entity: 'edge',
      field: 'metadata',
      id: edge.id,
      message: `Связь ${edge.id} содержит некорректный JSON`,
    });

    return {
      id: edge.id.startsWith('temp-edge-') ? null : Number(edge.id) || edge.id,
      clientId: edge.id,
      parentId: edge.source,
      childId: edge.target,
      requiresAllParents: edge.data?.requiresAllParents !== false,
      metadata: isEmptyObject(metadata) ? null : metadata,
    };
  });

  const payload = {
    nodes: formattedNodes,
    links: formattedEdges,
  };

  if (options.focusSkillId) {
    payload.focusSkillId = options.focusSkillId;
  }

  if (Array.isArray(options.deletedSkillIds)) {
    payload.deletedSkillIds = options.deletedSkillIds;
  }

  return payload;
}

function formatType(type) {
  const map = {
    start: 'Старт',
    dialog: 'Диалог',
    choice: 'Выбор',
    action: 'Действие',
    end: 'Завершение',
    condition: 'Условие',
    objective: 'Цель',
    reward: 'Награда',
  };
  return map[type] || 'Узел';
}

function formatJson(value) {
  if (!value || (typeof value === 'object' && Object.keys(value).length === 0)) {
    return '';
  }

  try {
    const parsed = typeof value === 'string' ? JSON.parse(value) : value;
    return JSON.stringify(parsed, null, 2);
  } catch {
    return typeof value === 'string' ? value : JSON.stringify(value);
  }
}

function formatQuestBody(data) {
  if (!data) {
    return '';
  }

  try {
    const parsed = typeof data === 'string' ? JSON.parse(data) : data;
    if (parsed.objective) {
      return parsed.objective;
    }
    if (parsed.description) {
      return parsed.description;
    }
    if (parsed.check) {
      return `Проверка: ${parsed.check}`;
    }
    const firstKey = Object.keys(parsed)[0];
    if (!firstKey) {
      return '';
    }
    return `${firstKey}: ${String(parsed[firstKey])}`;
  } catch {
    return typeof data === 'string' ? data.slice(0, 80) : '';
  }
}

function buildEdgeId(source, target, index) {
  return `edge-${source}-${target}-${index}`;
}

function safeParseJson(value, meta) {
  if (value === '' || value === null || value === undefined) {
    return {};
  }

  if (typeof value === 'object') {
    return value;
  }

  const trimmed = value.trim();
  if (trimmed === '') {
    return {};
  }

  try {
    return JSON.parse(trimmed);
  } catch {
    throw new GraphValidationError(meta?.message || 'Некорректный JSON', meta);
  }
}

function isEmptyObject(value) {
  return !value || (typeof value === 'object' && Object.keys(value).length === 0);
}

