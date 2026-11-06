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
