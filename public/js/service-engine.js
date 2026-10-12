/* ============================================================================
 * SUPPORTFLOW · Precision Service Engine — bespoke WebGL hero sculpture
 * Asymmetric architectural composition: machined core, intersecting service
 * channels, translucent membranes, illuminated nodes, travelling signals.
 * Safety: lazy load, IntersectionObserver pause, prefers-reduced-motion
 * static frame, WebGL-failure fallback, DPR cap, full disposal.
 * The scene function below is fully self-contained so it can be injected
 * as an ES module (import map → three.module.js) at runtime.
 * ========================================================================== */
(function () {
  'use strict';

  function bootScene(THREE) {
    var mount = document.getElementById('service-engine');
    if (!mount) return;
    var fallback = document.getElementById('service-engine-fallback');
    var loader = document.getElementById('service-engine-loader');
    function showFallback() {
      if (loader) loader.style.display = 'none';
      if (fallback) fallback.style.display = 'grid';
      mount.setAttribute('data-engine-state', 'fallback');
    }
    var reduceMotion = false;
    try { reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch (e) {}

    var W = 0, H = 0, raf = 0, running = false, disposed = false, drawnOnce = false;
    var pointer = { x: 0, y: 0, tx: 0, ty: 0 };
    var clock = new THREE.Clock();

    var renderer;
    try {
      renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'low-power' });
    } catch (e) { showFallback(); return; }
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.75));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.05;
    mount.appendChild(renderer.domElement);
    renderer.domElement.setAttribute('aria-hidden', 'true');

    var scene = new THREE.Scene();
    scene.fog = new THREE.FogExp2(0x04070f, 0.055);
    var camera = new THREE.PerspectiveCamera(38, 1, 0.1, 100);
    camera.position.set(0.4, 1.15, 8.4);

    /* Cinematic but restrained lighting */
    scene.add(new THREE.AmbientLight(0x8fb4dd, 0.5));
    var key = new THREE.DirectionalLight(0xdff4ff, 1.5);
    key.position.set(4, 6, 5);
    scene.add(key);
    var rim = new THREE.PointLight(0x22d3ee, 22, 30);
    rim.position.set(-5, 1.5, 2.5);
    scene.add(rim);
    var fill = new THREE.PointLight(0x6366f1, 12, 26);
    fill.position.set(4.5, -3, -2);
    scene.add(fill);

    var engine = new THREE.Group();
    scene.add(engine);
    engine.rotation.y = -0.35;

    var steel = new THREE.MeshStandardMaterial({ color: 0x9fb2cc, metalness: 0.92, roughness: 0.32 });
    var darkSteel = new THREE.MeshStandardMaterial({ color: 0x2b3a5c, metalness: 0.85, roughness: 0.45 });
    var cyanGlow = new THREE.MeshStandardMaterial({ color: 0x0b2b33, emissive: 0x22d3ee, emissiveIntensity: 2.4, roughness: 0.4 });
    var violetGlow = new THREE.MeshStandardMaterial({ color: 0x1c1440, emissive: 0x6366f1, emissiveIntensity: 1.8, roughness: 0.4 });
    var membrane = new THREE.MeshStandardMaterial({ color: 0x67e8f9, metalness: 0.1, roughness: 0.15, transparent: true, opacity: 0.16, side: THREE.DoubleSide, depthWrite: false });

    /* Central machined core: stacked discs + octagonal column + crown */
    var core = new THREE.Group();
    var discSizes = [[1.55, 0.16, -0.85], [1.28, 0.14, -0.28], [1.02, 0.14, 0.3], [0.74, 0.12, 0.84]];
    discSizes.forEach(function (d, i) {
      var disc = new THREE.Mesh(new THREE.CylinderGeometry(d[0], d[0] * 0.96, d[1], 56), i % 2 ? darkSteel : steel);
      disc.position.y = d[2];
      core.add(disc);
      var lip = new THREE.Mesh(new THREE.TorusGeometry(d[0], 0.022, 12, 72), i === 1 ? cyanGlow : steel);
      lip.rotation.x = Math.PI / 2;
      lip.position.y = d[2] + d[1] / 2;
      core.add(lip);
    });
    var column = new THREE.Mesh(new THREE.CylinderGeometry(0.34, 0.42, 2.5, 8), darkSteel);
    column.position.y = 0.1;
    core.add(column);
    var crown = new THREE.Mesh(new THREE.OctahedronGeometry(0.42), steel);
    crown.position.y = 1.62;
    core.add(crown);
    var crownLight = new THREE.Mesh(new THREE.OctahedronGeometry(0.2), cyanGlow);
    crownLight.position.y = 1.62;
    core.add(crownLight);
    engine.add(core);

    /* Instrument dial: faint tick ring on the floor */
    var dial = new THREE.Group();
    var dialRing = new THREE.Mesh(new THREE.TorusGeometry(3.1, 0.014, 8, 128),
      new THREE.MeshBasicMaterial({ color: 0x2b6f84, transparent: true, opacity: 0.55 }));
    dialRing.rotation.x = Math.PI / 2;
    dial.add(dialRing);
    for (var t = 0; t < 48; t++) {
      var major = t % 4 === 0;
      var tick = new THREE.Mesh(new THREE.BoxGeometry(major ? 0.05 : 0.025, 0.006, major ? 0.22 : 0.12),
        new THREE.MeshBasicMaterial({ color: major ? 0x67e8f9 : 0x2b4a66, transparent: true, opacity: major ? 0.9 : 0.55 }));
      var a = (t / 48) * Math.PI * 2;
      tick.position.set(Math.cos(a) * 3.1, -1.35, Math.sin(a) * 3.1);
      tick.rotation.y = -a;
      dial.add(tick);
    }
    engine.add(dial);

    /* Intersecting service channels */
    var channelDefs = [
      { pts: [[-3.4, -0.9, 0.6], [-1.9, -0.1, 1.4], [-0.7, 0.7, 0.4], [0.7, 0.5, -0.9], [2.2, 1.3, -0.4], [3.4, 0.6, 0.8]], r: 0.075, mat: 'steel' },
      { pts: [[-3.1, 1.2, -1.1], [-1.6, 0.5, -0.6], [-0.2, 1.15, 0.5], [1.4, 0.35, 1.1], [3.2, -0.5, -0.7]], r: 0.06, mat: 'dark' },
      { pts: [[-2.6, -1.3, -0.9], [-1.2, -0.9, 0.2], [0.4, -0.55, 0.9], [2.0, -1.0, 0.1], [3.3, -0.2, 1.0]], r: 0.05, mat: 'dark' }
    ];
    var curves = [];
    channelDefs.forEach(function (c) {
      var v = c.pts.map(function (p) { return new THREE.Vector3(p[0], p[1], p[2]); });
      var curve = new THREE.CatmullRomCurve3(v, false, 'centripetal', 0.6);
      curves.push(curve);
      engine.add(new THREE.Mesh(new THREE.TubeGeometry(curve, 72, c.r, 12, false), c.mat === 'steel' ? steel : darkSteel));
    });
    [[-1.7, 0.35, 0.9, 0.5], [1.5, -0.35, -0.5, -0.45]].forEach(function (m) {
      var mem = new THREE.Mesh(new THREE.PlaneGeometry(1.5, 1.0, 1, 1), membrane);
      mem.position.set(m[0], m[1], m[2]);
      mem.rotation.set(0.4, m[3], 0.15);
      engine.add(mem);
    });

    /* Illuminated connection nodes */
    var nodeSpots = [[-3.4, -0.9, 0.6], [3.4, 0.6, 0.8], [-3.1, 1.2, -1.1], [3.2, -0.5, -0.7], [-2.6, -1.3, -0.9], [2.0, -1.0, 0.1]];
    nodeSpots.forEach(function (p, i) {
      var n = new THREE.Mesh(new THREE.SphereGeometry(i % 3 === 0 ? 0.11 : 0.075, 20, 20), i % 2 ? violetGlow : cyanGlow);
      n.position.set(p[0], p[1], p[2]);
      engine.add(n);
      var halo = new THREE.Mesh(new THREE.SphereGeometry(0.2, 16, 16),
        new THREE.MeshBasicMaterial({ color: i % 2 ? 0x6366f1 : 0x22d3ee, transparent: true, opacity: 0.14, depthWrite: false }));
      halo.position.copy(n.position);
      engine.add(halo);
    });

    /* Travelling signal pulses */
    var pulses = [];
    curves.forEach(function (curve, ci) {
      for (var k = 0; k < 2; k++) {
        var mat = cyanGlow.clone();
        mat.emissiveIntensity = 3.2;
        var p = new THREE.Mesh(new THREE.SphereGeometry(0.055, 14, 14), mat);
        engine.add(p);
        pulses.push({ mesh: p, curve: curve, t: (k * 0.5 + ci * 0.23) % 1, speed: 0.10 + ci * 0.028 + k * 0.012 });
      }
    });

    function resize() {
      var r = mount.getBoundingClientRect();
      W = Math.max(280, r.width); H = Math.max(320, r.height);
      var mobile = W < 640;
      camera.aspect = W / H;
      camera.position.z = mobile ? 10.6 : 8.4;
      camera.updateProjectionMatrix();
      renderer.setSize(W, H, false);
    }
    resize();
    window.addEventListener('resize', resize);

    var fine = false;
    try { fine = window.matchMedia('(pointer: fine)').matches; } catch (e) {}
    if (fine && !reduceMotion) {
      window.addEventListener('pointermove', function (e) {
        pointer.tx = (e.clientX / window.innerWidth - 0.5);
        pointer.ty = (e.clientY / window.innerHeight - 0.5);
      }, { passive: true });
    }

    function frame() {
      if (disposed) return;
      raf = requestAnimationFrame(frame);
      var dt = Math.min(clock.getDelta(), 0.05);
      var t = clock.elapsedTime;
      if (!reduceMotion) {
        engine.rotation.y += dt * 0.12;
        dial.rotation.y -= dt * 0.02;
        crown.rotation.y += dt * 0.5;
        pointer.x += (pointer.tx - pointer.x) * 0.04;
        pointer.y += (pointer.ty - pointer.y) * 0.04;
        camera.position.x = 0.4 + pointer.x * 1.1;
        camera.position.y = 1.15 - pointer.y * 0.7;
        camera.lookAt(0, 0.1, 0);
        rim.intensity = 22 + Math.sin(t * 0.8) * 4;
        for (var i = 0; i < pulses.length; i++) {
          var pu = pulses[i];
          pu.t = (pu.t + dt * pu.speed) % 1;
          pu.mesh.position.copy(pu.curve.getPointAt(pu.t));
          var s = 0.85 + 0.35 * Math.sin(t * 3 + i * 1.7);
          pu.mesh.scale.set(s, s, s);
        }
      } else if (!drawnOnce) {
        engine.rotation.y = -0.35;
        for (var j = 0; j < pulses.length; j++) {
          pulses[j].mesh.position.copy(pulses[j].curve.getPointAt(pulses[j].t));
        }
        drawnOnce = true;
      }
      renderer.render(scene, camera);
    }

    var visible = true;
    function onVis() {
      var hidden = false;
      try { hidden = document.hidden; } catch (e) {}
      if (hidden || reduceMotion) { if (running) { cancelAnimationFrame(raf); running = false; } }
      else if (visible && !running) { running = true; clock.getDelta(); frame(); }
    }
    var io = null;
    if ('IntersectionObserver' in window) {
      io = new IntersectionObserver(function (entries) {
        visible = entries[0] && entries[0].isIntersecting !== false;
        onVis();
      }, { threshold: 0.05 });
      io.observe(mount);
    }
    document.addEventListener('visibilitychange', onVis);
    window.addEventListener('pagehide', function () {
      disposed = true;
      try { cancelAnimationFrame(raf); } catch (e) {}
      try { if (io) io.disconnect(); } catch (e) {}
      scene.traverse(function (o) {
        if (o.geometry) o.geometry.dispose();
        if (o.material) (Array.isArray(o.material) ? o.material : [o.material]).forEach(function (m) { m.dispose(); });
      });
      try { renderer.dispose(); } catch (e) {}
      try { if (renderer.domElement.parentNode === mount) mount.removeChild(renderer.domElement); } catch (e) {}
    });

    mount.setAttribute('data-engine-state', 'live');
    if (loader) loader.style.display = 'none';
    running = true;
    frame();
  }

  /* ---------- loader: checks, import map, lazy module injection ---------- */
  var mount = document.getElementById('service-engine');
  if (!mount) return;
  mount.setAttribute('data-engine-state', 'loading');

  function showFallback() {
    var loader = document.getElementById('service-engine-loader');
    var fallback = document.getElementById('service-engine-fallback');
    if (loader) loader.style.display = 'none';
    if (fallback) fallback.style.display = 'grid';
    mount.setAttribute('data-engine-state', 'fallback');
  }

  try {
    if (!window.WebGLRenderingContext) { showFallback(); return; }
    var probe = document.createElement('canvas');
    if (!(probe.getContext('webgl2') || probe.getContext('webgl'))) { showFallback(); return; }
  } catch (e) { showFallback(); return; }

  var started = false;
  function start() {
    if (started) return; started = true;
    var loader = document.getElementById('service-engine-loader');
    var fallback = document.getElementById('service-engine-fallback');
    if (fallback) fallback.style.display = 'none';
    if (loader) loader.style.display = 'flex';
    var s = document.createElement('script');
    s.type = 'importmap';
    try {
      s.textContent = JSON.stringify({ imports: { three: 'https://unpkg.com/three@0.160.0/build/three.module.js' } });
    } catch (e) { showFallback(); return; }
    document.head.appendChild(s);
    var m = document.createElement('script');
    m.type = 'module';
    m.textContent = "import * as THREE from 'three';\n(" + bootScene.toString() + ")(THREE);";
    m.onerror = showFallback;
    document.head.appendChild(m);
    setTimeout(function () {
      if (mount.getAttribute('data-engine-state') === 'loading') showFallback();
    }, 9000);
  }

  if ('IntersectionObserver' in window) {
    var io2 = new IntersectionObserver(function (entries, obs) {
      if (entries[0] && entries[0].isIntersecting) { obs.disconnect(); start(); }
    }, { rootMargin: '240px' });
    io2.observe(mount);
  } else {
    start();
  }
  setTimeout(function () {
    if (mount.getAttribute('data-engine-state') === 'loading' && !started) start();
  }, 2500);
})();
