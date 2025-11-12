const DEFAULT_CONFIG = {
  horizontalSpacing: 320,
  verticalSpacing: 200,
  horizontalMargin: 80,
  verticalMargin: 80,
  gridSize: 10,
};

function clampToGrid(value, gridSize) {
  if (!Number.isFinite(value)) {
    return 0;
  }
  if (gridSize <= 1) {
    return Math.round(value);
  }
  return Math.round(value / gridSize) * gridSize;
}

export function beautifyGraphLayout(nodes = [], edges = [], config = {}) {
  if (!Array.isArray(nodes) || nodes.length === 0) {
    return Array.isArray(nodes) ? [...nodes] : [];
  }

  const settings = {
    ...DEFAULT_CONFIG,
    ...config,
  };

  const nodeMap = new Map();
  const adjacency = new Map();
  const incoming = new Map();

  nodes.forEach((node) => {
    const safeNode = {
      ...node,
      position: {
        x: clampToGrid(node.position?.x ?? 0, settings.gridSize),
        y: clampToGrid(node.position?.y ?? 0, settings.gridSize),
      },
    };

    nodeMap.set(node.id, safeNode);
    adjacency.set(node.id, new Set());
    incoming.set(node.id, new Set());
  });

  edges.forEach((edge) => {
    const { source, target } = edge;
    if (!nodeMap.has(source) || !nodeMap.has(target) || source === target) {
      return;
    }
    adjacency.get(source).add(target);
    incoming.get(target).add(source);
  });

  const indegree = new Map();
  nodeMap.forEach((_, id) => {
    indegree.set(id, incoming.get(id)?.size ?? 0);
  });

  const levels = new Map();
  const processed = new Set();
  const queue = [];

  const startNodes = nodes.filter((node) => node.data?.nodeType === 'start');
  const seedNodes = startNodes.length
    ? startNodes
    : nodes.filter((node) => (indegree.get(node.id) ?? 0) === 0);

  seedNodes.forEach((node) => {
    if (!levels.has(node.id)) {
      levels.set(node.id, 0);
      queue.push(node.id);
    }
  });

  if (!queue.length && nodes[0]) {
    levels.set(nodes[0].id, 0);
    queue.push(nodes[0].id);
  }

  while (queue.length) {
    const currentId = queue.shift();
    processed.add(currentId);
    const currentLevel = levels.get(currentId) ?? 0;

    adjacency.get(currentId)?.forEach((targetId) => {
      const proposedLevel = currentLevel + 1;
      const existingLevel = levels.get(targetId);
      if (existingLevel == null || proposedLevel > existingLevel) {
        levels.set(targetId, proposedLevel);
      }

      const remaining = (indegree.get(targetId) ?? 0) - 1;
      indegree.set(targetId, remaining);
      if (remaining <= 0 && !processed.has(targetId)) {
        queue.push(targetId);
      }
    });
  }

  let maxLevel = 0;
  levels.forEach((level) => {
    if (level > maxLevel) {
      maxLevel = level;
    }
  });

  nodeMap.forEach((_, id) => {
    if (!levels.has(id)) {
      maxLevel += 1;
      levels.set(id, maxLevel);
    }
  });

  const levelBuckets = new Map();
  levels.forEach((level, id) => {
    if (!levelBuckets.has(level)) {
      levelBuckets.set(level, []);
    }
    levelBuckets.get(level).push(nodeMap.get(id));
  });

  Array.from(levelBuckets.entries())
    .sort((a, b) => a[0] - b[0])
    .forEach(([level, bucket]) => {
      const nodesWithAnchors = bucket.map((node) => {
        const parentIds = Array.from(incoming.get(node.id) ?? []);
        if (!parentIds.length) {
          return {
            node,
            anchor: node.position.y,
          };
        }

        const sum = parentIds.reduce((acc, parentId) => {
          const parent = nodeMap.get(parentId);
          return acc + (parent?.position.y ?? node.position.y);
        }, 0);

        return {
          node,
          anchor: sum / parentIds.length,
        };
      });

      nodesWithAnchors.sort((a, b) => a.anchor - b.anchor);

      let lastY = settings.verticalMargin - settings.verticalSpacing;
      nodesWithAnchors.forEach(({ node, anchor }, index) => {
        const baseY = settings.verticalMargin + index * settings.verticalSpacing;
        const proposedY = Number.isFinite(anchor) ? anchor : baseY;
        const nextY = Math.max(baseY, lastY + settings.verticalSpacing, proposedY);
        node.position = {
          x: clampToGrid(
            settings.horizontalMargin + level * settings.horizontalSpacing,
            settings.gridSize
          ),
          y: clampToGrid(nextY, settings.gridSize),
        };
        lastY = nextY;
      });
    });

  return nodes.map((original) => {
    const updated = nodeMap.get(original.id);
    if (!updated) {
      return original;
    }
    return {
      ...original,
      position: {
        x: updated.position.x,
        y: updated.position.y,
      },
    };
  });
}
