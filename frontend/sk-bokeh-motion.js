(function () {
  // 1. Ensure ambient bokeh layer exists on first execution
  if (document.body && !document.querySelector(".sk-bokeh-layer")) {
    const layer = document.createElement("div");
    layer.className = "sk-bokeh-layer";
    layer.setAttribute("aria-hidden", "true");
    layer.innerHTML =
      '<div class="sk-bokeh-horizon"></div>' +
      '<div class="sk-bokeh-orb sk-bokeh-orb--emerald"></div>' +
      '<div class="sk-bokeh-orb sk-bokeh-orb--magenta"></div>' +
      '<div class="sk-bokeh-orb sk-bokeh-orb--gold"></div>' +
      '<div class="sk-bokeh-orb sk-bokeh-orb--archipelago"></div>' +
      '<div class="sk-bokeh-beacon sk-bokeh-beacon--seal"></div>' +
      '<div class="sk-bokeh-beacon sk-bokeh-beacon--coin"></div>';
    document.body.insertBefore(layer, document.body.firstChild);
  }

  // 2. Apply subtle shimmer & buoyancy to key hero containers and MSK coin badges
  document
    .querySelectorAll(".sk-hero, .sk-hero-anim, .sk-banner, .sk-top, .sk-prefooter-card")
    .forEach((el) => el.classList.add("sk-seal-shimmer"));

  document
    .querySelectorAll(".sk-coin-box img, .sk-coin-img, .sk-seal-badge img, .sk-logo")
    .forEach((img) => img.classList.add("sk-coin-float"));

  // 3. Collect and reveal content blocks cleanly (zero-latency fallback + reduced-motion support)
  const autoTargets = document.querySelectorAll(
    ".sk-main > section, .sk-main > .sk-panel, .sk-main > .sk-section, #sk-public-portal > section, #sk-explorer > section"
  );
  autoTargets.forEach((el) => {
    if (!el.classList.contains("sk-reveal")) {
      el.classList.add("sk-reveal");
    }
  });

  const nodes = Array.from(document.querySelectorAll(".sk-reveal"));
  if (!nodes.length) return;

  nodes.forEach((el, idx) => {
    const delay = Math.min(idx * 45, 180);
    el.style.setProperty("--sk-delay", delay + "ms");
  });

  const reducedMotion =
    window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  if (reducedMotion || !("IntersectionObserver" in window)) {
    nodes.forEach((el) => el.classList.add("is-visible"));
    return;
  }

  const vh = window.innerHeight || 800;
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.05, rootMargin: "0px 0px -20px 0px" }
  );

  nodes.forEach((el) => {
    const rect = el.getBoundingClientRect();
    if (rect.top < vh * 0.96) {
      el.classList.add("is-visible");
    } else {
      observer.observe(el);
    }
  });

  // Safety reveal so no element ever remains hidden after fast scroll
  setTimeout(() => {
    nodes.forEach((el) => el.classList.add("is-visible"));
  }, 450);
})();
