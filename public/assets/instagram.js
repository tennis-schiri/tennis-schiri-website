/* First-party feed. Reads only a same-origin JSON snapshot; Instagram tokens
   and Instagram scripts are never included in the browser. */
(() => {
  "use strict";
  const containers = document.querySelectorAll('[data-instagram-account]');
  if (!containers.length) return;

  const host = "https://www.instagram.com/";
  const safeInstagramUrl = (value) => {
    try {
      const url = new URL(value);
      if (url.protocol !== "https:" || !["www.instagram.com", "instagram.com"].includes(url.hostname)) return null;
      return url.href;
    } catch { return null; }
  };
  const safeImagePath = (value) =>
    typeof value === "string" && /^assets\/instagram\/[a-z0-9-]+-[123]-[0-9]+\.(jpg|png|webp)$/.test(value)
      ? value : null;

  const element = (name, className, content) => {
    const node = document.createElement(name);
    if (className) node.className = className;
    if (content !== undefined) node.textContent = content;
    return node;
  };

  function render(account, posts, username) {
    const grid = account.querySelector('[data-ig-posts]');
    if (!grid) return;
    grid.replaceChildren();
    const valid = Array.isArray(posts) ? posts.slice(0, 3) : [];
    let count = 0;
    for (const item of valid) {
      if (!item || typeof item !== "object") continue;
      const href = safeInstagramUrl(item.permalink);
      const src = safeImagePath(item.image);
      if (!href || !src) continue;
      const card = element("a", "ig-post");
      card.href = href;
      card.target = "_blank";
      card.rel = "noopener noreferrer";
      card.setAttribute("aria-label", `Beitrag von @${username} auf Instagram öffnen`);
      const media = element("div", "ig-post-media");
      const image = element("img", "ig-post-image");
      image.src = src;
      image.loading = "lazy";
      image.decoding = "async";
      image.alt = `Instagram-Beitrag von @${username}`;
      media.appendChild(image);
      media.appendChild(element("span", "ig-post-icon", "↗"));
      card.appendChild(media);
      const caption = typeof item.caption === "string" ? item.caption.trim() : "";
      card.appendChild(element("span", "ig-post-caption", caption ? caption.slice(0, 100) : "Auf Instagram ansehen"));
      grid.appendChild(card);
      count++;
    }
    if (!count) {
      const empty = element("div", "ig-empty");
      empty.appendChild(element("p", "", "Die Beitragsvorschau ist noch nicht verbunden. Aktuelle Beiträge findest du direkt auf Instagram."));
      const link = element("a", "ig-empty-link", `@${username} auf Instagram ↗`);
      link.href = host + username + "/";
      link.target = "_blank";
      link.rel = "noopener noreferrer";
      empty.appendChild(link);
      grid.appendChild(empty);
    }
  }

  // The initial markup contains the direct Instagram profile links as a no-JS fallback.
  (async () => {
    let accounts = [];
    try {
      const response = await fetch("data/instagram.json", {cache: "no-store"});
      if (!response.ok) throw new Error("Kein Instagram-Datensatz verfügbar");
      const data = await response.json();
      if (!data || !Array.isArray(data.accounts)) throw new Error("Ungültiger Datensatz");
      accounts = data.accounts;
    } catch {
      // Intentionally silent for visitors: the direct account links remain usable.
    }
    for (const container of containers) {
      const key = container.dataset.instagramAccount;
      const username = container.dataset.instagramUsername;
      const source = accounts.find(a => a && a.key === key && a.username === username);
      render(container, source?.posts, username);
    }
  })();
})();