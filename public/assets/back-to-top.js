/* Gemeinsamer, barrierearmer Zurueck-nach-oben-Button fuer alle Seiten. */
(() => {
  "use strict";

  function initBackToTop() {
    if (document.querySelector(".back-to-top")) return;

    const button = document.createElement("button");
    button.type = "button";
    button.className = "back-to-top";
    button.setAttribute("aria-label", "Zurück zum Seitenanfang");
    button.setAttribute("title", "Nach oben");
    button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 14 6-6 6 6"/><path d="M12 8v12"/></svg><span>Nach oben</span>';
    button.tabIndex = -1;
    document.body.appendChild(button);

    let scrollScheduled = false;

    function updateVisibility() {
      const threshold = Math.max(420, Math.round(window.innerHeight * 0.55));
      const visible = window.scrollY > threshold;
      button.classList.toggle("is-visible", visible);
      button.tabIndex = visible ? 0 : -1;
      scrollScheduled = false;
    }

    window.addEventListener("scroll", () => {
      if (scrollScheduled) return;
      scrollScheduled = true;
      window.requestAnimationFrame(updateVisibility);
    }, { passive: true });
    window.addEventListener("resize", updateVisibility, { passive: true });

    button.addEventListener("click", () => {
      const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
      window.scrollTo({ top: 0, behavior: reducedMotion ? "instant" : "smooth" });
    });

    updateVisibility();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initBackToTop, { once: true });
  } else {
    initBackToTop();
  }
})();


/* Seitenweiter Schalter: hell als Standard, dunkel auf ausdruecklichen Wunsch. */
(() => {
  "use strict";
  const key = "tennis-schiri-theme";
  const root = document.documentElement;
  let saved = null;
  try { saved = window.localStorage.getItem(key); } catch (_) { /* private mode */ }
  root.setAttribute("data-theme", saved === "dark" ? "dark" : "light");

  function initThemeToggle() {
    const header = document.querySelector(".site-head .header-inner");
    if (!header || header.querySelector(".theme-toggle")) return;
    const toggle = document.createElement("button");
    toggle.type = "button";
    toggle.className = "theme-toggle";
    const symbol = document.createElement("span");
    symbol.className = "theme-toggle-icon";
    symbol.setAttribute("aria-hidden", "true");
    const label = document.createElement("span");
    label.className = "theme-toggle-label";
    toggle.append(symbol, label);

    function paint() {
      const dark = root.getAttribute("data-theme") === "dark";
      symbol.textContent = dark ? "☀" : "☾";
      label.textContent = dark ? "Hell" : "Dunkel";
      toggle.setAttribute("aria-pressed", String(dark));
      toggle.setAttribute("aria-label", dark ? "Helles Design aktivieren" : "Dunkles Design aktivieren");
      toggle.title = dark ? "Zum hellen Design wechseln" : "Zum dunklen Design wechseln";
    }
    toggle.addEventListener("click", () => {
      const next = root.getAttribute("data-theme") === "dark" ? "light" : "dark";
      root.setAttribute("data-theme", next);
      try { window.localStorage.setItem(key, next); } catch (_) { /* private mode */ }
      paint();
    });
    window.addEventListener("storage", (event) => {
      if (event.key !== key) return;
      root.setAttribute("data-theme", event.newValue === "dark" ? "dark" : "light");
      paint();
    });

    const mobileToggle = header.querySelector(".mobile-toggle");
    if (mobileToggle) header.insertBefore(toggle, mobileToggle);
    else header.appendChild(toggle);
    paint();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initThemeToggle, { once: true });
  } else {
    initThemeToggle();
  }
})();
