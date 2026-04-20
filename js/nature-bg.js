/* ============================================================
   AgroSenseIoT — Three.js Nature Background
   Floating leaves, firefly particles, and gentle wind
   ============================================================ */

(function () {
  'use strict';

  // Only run if THREE is available and canvas element exists
  if (typeof THREE === 'undefined') {
    console.warn('Three.js not loaded, skipping nature background.');
    return;
  }

  const canvas = document.getElementById('nature-canvas');
  if (!canvas) return;

  // ---------- Scene Setup ----------
  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
  camera.position.z = 50;

  const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
  renderer.setSize(window.innerWidth, window.innerHeight);
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  renderer.setClearColor(0x000000, 0);

  // ---------- Leaf Particles ----------
  const leafCount = 60;
  const leafGeometry = new THREE.BufferGeometry();
  const leafPositions = new Float32Array(leafCount * 3);
  const leafColors = new Float32Array(leafCount * 3);
  const leafSizes = new Float32Array(leafCount);
  const leafSpeeds = [];
  const leafPhases = [];

  const leafColorPalette = [
    [0.13, 0.64, 0.29],  // green
    [0.18, 0.75, 0.35],  // light green
    [0.52, 0.80, 0.09],  // lime
    [0.42, 0.70, 0.15],  // olive green
    [0.92, 0.72, 0.05],  // gold
    [0.85, 0.55, 0.08],  // amber
  ];

  for (let i = 0; i < leafCount; i++) {
    const i3 = i * 3;
    leafPositions[i3] = (Math.random() - 0.5) * 120;
    leafPositions[i3 + 1] = (Math.random() - 0.5) * 80;
    leafPositions[i3 + 2] = (Math.random() - 0.5) * 40 - 10;

    const color = leafColorPalette[Math.floor(Math.random() * leafColorPalette.length)];
    leafColors[i3] = color[0];
    leafColors[i3 + 1] = color[1];
    leafColors[i3 + 2] = color[2];

    leafSizes[i] = Math.random() * 3 + 1.5;
    leafSpeeds.push({
      x: (Math.random() - 0.3) * 0.015,
      y: -(Math.random() * 0.01 + 0.005),
      rotX: (Math.random() - 0.5) * 0.02,
      rotZ: (Math.random() - 0.5) * 0.015,
    });
    leafPhases.push(Math.random() * Math.PI * 2);
  }

  leafGeometry.setAttribute('position', new THREE.BufferAttribute(leafPositions, 3));
  leafGeometry.setAttribute('color', new THREE.BufferAttribute(leafColors, 3));
  leafGeometry.setAttribute('size', new THREE.BufferAttribute(leafSizes, 1));

  // Custom leaf shader for soft rounded shapes
  const leafMaterial = new THREE.ShaderMaterial({
    transparent: true,
    depthWrite: false,
    vertexColors: true,
    vertexShader: `
      attribute float size;
      varying vec3 vColor;
      void main() {
        vColor = color;
        vec4 mvPosition = modelViewMatrix * vec4(position, 1.0);
        gl_PointSize = size * (300.0 / -mvPosition.z);
        gl_Position = projectionMatrix * mvPosition;
      }
    `,
    fragmentShader: `
      varying vec3 vColor;
      void main() {
        vec2 center = gl_PointCoord - vec2(0.5);
        float dist = length(center);
        
        // Leaf-like shape: elliptical with soft edge
        vec2 stretched = vec2(center.x * 1.8, center.y);
        float leafShape = length(stretched);
        
        if (leafShape > 0.45) discard;
        
        float alpha = smoothstep(0.45, 0.2, leafShape) * 0.65;
        gl_FragColor = vec4(vColor, alpha);
      }
    `
  });

  const leafParticles = new THREE.Points(leafGeometry, leafMaterial);
  scene.add(leafParticles);

  // ---------- Firefly Particles ----------
  const fireflyCount = 40;
  const fireflyGeometry = new THREE.BufferGeometry();
  const fireflyPositions = new Float32Array(fireflyCount * 3);
  const fireflySizes = new Float32Array(fireflyCount);
  const fireflyPhases = [];

  for (let i = 0; i < fireflyCount; i++) {
    const i3 = i * 3;
    fireflyPositions[i3] = (Math.random() - 0.5) * 100;
    fireflyPositions[i3 + 1] = (Math.random() - 0.5) * 70;
    fireflyPositions[i3 + 2] = (Math.random() - 0.5) * 30 - 5;
    fireflySizes[i] = Math.random() * 2 + 0.5;
    fireflyPhases.push(Math.random() * Math.PI * 2);
  }

  fireflyGeometry.setAttribute('position', new THREE.BufferAttribute(fireflyPositions, 3));
  fireflyGeometry.setAttribute('size', new THREE.BufferAttribute(fireflySizes, 1));

  const fireflyMaterial = new THREE.ShaderMaterial({
    transparent: true,
    depthWrite: false,
    uniforms: {
      uTime: { value: 0 },
    },
    vertexShader: `
      attribute float size;
      uniform float uTime;
      varying float vAlpha;
      void main() {
        vec3 pos = position;
        float phase = pos.x * 0.1 + pos.y * 0.1;
        vAlpha = (sin(uTime * 1.5 + phase) + 1.0) * 0.35 + 0.1;
        vec4 mvPosition = modelViewMatrix * vec4(pos, 1.0);
        gl_PointSize = size * (200.0 / -mvPosition.z);
        gl_Position = projectionMatrix * mvPosition;
      }
    `,
    fragmentShader: `
      varying float vAlpha;
      void main() {
        float dist = length(gl_PointCoord - vec2(0.5));
        if (dist > 0.5) discard;
        float glow = smoothstep(0.5, 0.0, dist);
        vec3 warmYellow = vec3(1.0, 0.92, 0.5);
        gl_FragColor = vec4(warmYellow, glow * vAlpha);
      }
    `
  });

  const fireflyParticles = new THREE.Points(fireflyGeometry, fireflyMaterial);
  scene.add(fireflyParticles);

  // ---------- Soft ambient light fog ----------
  scene.fog = new THREE.FogExp2(0x0a2f1a, 0.008);

  // ---------- Animation Loop ----------
  const clock = new THREE.Clock();

  function animate() {
    requestAnimationFrame(animate);

    const elapsed = clock.getElapsedTime();
    const positions = leafGeometry.attributes.position.array;

    // Animate leaves
    for (let i = 0; i < leafCount; i++) {
      const i3 = i * 3;
      const speed = leafSpeeds[i];
      const phase = leafPhases[i];

      // Wind sway (sinusoidal)
      positions[i3] += speed.x + Math.sin(elapsed * 0.5 + phase) * 0.008;
      positions[i3 + 1] += speed.y;
      positions[i3 + 2] += Math.sin(elapsed * 0.3 + phase) * 0.003;

      // Wrap around when leaves go off screen
      if (positions[i3 + 1] < -45) {
        positions[i3 + 1] = 45;
        positions[i3] = (Math.random() - 0.5) * 120;
      }
      if (positions[i3] > 65) positions[i3] = -65;
      if (positions[i3] < -65) positions[i3] = 65;
    }
    leafGeometry.attributes.position.needsUpdate = true;

    // Animate fireflies
    const ffPos = fireflyGeometry.attributes.position.array;
    for (let i = 0; i < fireflyCount; i++) {
      const i3 = i * 3;
      const phase = fireflyPhases[i];
      ffPos[i3] += Math.sin(elapsed * 0.3 + phase) * 0.02;
      ffPos[i3 + 1] += Math.cos(elapsed * 0.4 + phase) * 0.015;
      ffPos[i3 + 2] += Math.sin(elapsed * 0.2 + phase * 2) * 0.01;
    }
    fireflyGeometry.attributes.position.needsUpdate = true;

    // Update firefly time
    fireflyMaterial.uniforms.uTime.value = elapsed;

    // Gentle camera breathing
    camera.position.x = Math.sin(elapsed * 0.1) * 0.5;
    camera.position.y = Math.cos(elapsed * 0.08) * 0.3;

    renderer.render(scene, camera);
  }

  animate();

  // ---------- Resize Handler ----------
  window.addEventListener('resize', () => {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
  });
})();
