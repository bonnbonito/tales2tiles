/**
 * Tales to Tiles app: order wizard, order tracking and the shop-owner dashboard.
 * Catalog, shop settings, image URLs and REST details come from PHP (inc/config.php) via window.TT.
 * The home page itself is rendered by front-page.php; this file only decorates it.
 */
const { config: CONFIG, products: PRODUCTS, groups: GROUPS, swatch: SWATCH, img: IMG } = window.TT;

/* ---------- helpers ---------- */
const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];
const app = $("#app");
const peso = n => "₱" + Number(n).toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const esc = s => String(s ?? "").replace(/[&<>"']/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
const clamp = (v, a, b) => Math.min(b, Math.max(a, v));
const uid = () => Math.random().toString(36).slice(2, 10);
function toast(msg) { const t = $("#toast"); t.textContent = msg; t.classList.add("show"); clearTimeout(toast._t); toast._t = setTimeout(() => t.classList.remove("show"), 2800); }
function ls(k, v) { try { if (v === undefined) return localStorage.getItem(k); localStorage.setItem(k, v); } catch (e) { return null; } }
async function copy(text) { try { await navigator.clipboard.writeText(text); toast("Copied " + text); } catch (e) { toast(text); } }
const ICON = {
  check: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l4 4 10-10"/></svg>',
  cloud: '<svg width="46" height="46" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 36h-3a9 9 0 0 1-1-17.9A12 12 0 0 1 34 15a9 9 0 0 1 3 17.5"/><path d="M24 40V24m-6 6 6-6 6 6"/></svg>',
  lock: '<svg width="30" height="30" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="7" y="14" width="18" height="14" rx="3"/><path d="M11 14v-3a5 5 0 0 1 10 0v3M16 20v3"/></svg>',
  heart: '<svg width="30" height="30" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><path d="M16 27S4 19.6 4 11.8A6 6 0 0 1 16 9a6 6 0 0 1 12 2.8C28 19.6 16 27 16 27z"/></svg>',
  truck: '<svg width="32" height="30" viewBox="0 0 34 32" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><path d="M3 8h17v14H3zM20 13h6l4 5v4H20z"/><circle cx="9" cy="24" r="3" fill="var(--mint)"/><circle cx="25" cy="24" r="3" fill="var(--mint)"/></svg>',
  camera: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/></svg>',
};

/* ---------- product art ---------- */
let _sid = 0;
function keepsakeArt(kind, strap = "Pink") {
  const id = "k" + (++_sid), photo = { pearl: IMG.tBday, leather: IMG.tBeach, pin: IMG.tLion }[kind];
  const gloss = `<radialGradient id="${id}g" cx=".32" cy=".28" r=".75"><stop offset="0" stop-color="#fff" stop-opacity=".6"/><stop offset=".5" stop-color="#fff" stop-opacity="0"/></radialGradient>`;
  const charm = (cx, cy, r) => `<circle cx="${cx + 2}" cy="${cy + 4}" r="${r + 6}" fill="#000" opacity=".12"/><circle cx="${cx}" cy="${cy}" r="${r + 5.5}" fill="#D3D6DB" stroke="#A4A9B0" stroke-width="1.4"/><image href="${photo}" x="${cx - r}" y="${cy - r}" width="${2 * r}" height="${2 * r}" preserveAspectRatio="xMidYMid slice" clip-path="url(#${id}c)"/><circle cx="${cx}" cy="${cy}" r="${r}" fill="url(#${id}g)"/>`;
  if (kind === "pin") return `<svg viewBox="0 0 160 120" role="img" aria-label="Button pin"><defs>${gloss}<clipPath id="${id}c"><circle cx="80" cy="60" r="46"/></clipPath></defs><circle cx="84" cy="66" r="47" fill="#000" opacity=".13"/><image href="${photo}" x="34" y="14" width="92" height="92" preserveAspectRatio="xMidYMid slice" clip-path="url(#${id}c)"/><circle cx="80" cy="60" r="46" fill="url(#${id}g)"/><circle cx="80" cy="60" r="45.5" fill="none" stroke="#fff" stroke-opacity=".5"/></svg>`;
  if (kind === "pearl") {
    let pearls = ""; for (let i = 0; i < 20; i++) { const a = i / 20 * Math.PI * 2; const x = 56 + Math.cos(a) * 36, y = 44 + Math.sin(a) * 27; pearls += `<circle cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" r="6" fill="url(#${id}p)" stroke="#E4D9C5" stroke-width=".6"/>`; }
    return `<svg viewBox="0 0 160 120" role="img" aria-label="Pearl wristlet keychain"><defs>${gloss}<radialGradient id="${id}p" cx=".35" cy=".3"><stop offset="0" stop-color="#fff"/><stop offset="1" stop-color="#E8DDCB"/></radialGradient><clipPath id="${id}c"><circle cx="112" cy="84" r="23"/></clipPath></defs>${pearls}<path d="M88 58 L100 60 L108 58" stroke="#C9A24B" stroke-width="3" fill="none" stroke-linecap="round"/><rect x="104" y="54" width="7" height="12" rx="3" fill="#C9A24B"/>${charm(112, 84, 23)}</svg>`;
  }
  return `<svg viewBox="0 0 160 120" role="img" aria-label="Leather keychain"><defs>${gloss}<clipPath id="${id}c"><circle cx="66" cy="80" r="23"/></clipPath></defs><circle cx="66" cy="26" r="17" fill="none" stroke="#C9A24B" stroke-width="4.5"/><rect x="78" y="22" width="22" height="84" rx="10" fill="${SWATCH[strap]}" stroke="rgba(0,0,0,.12)" transform="rotate(-24 89 64)"/><circle cx="84" cy="36" r="4" fill="#B8903D" transform="rotate(-24 89 64)"/>${charm(66, 80, 23)}</svg>`;
}
function productArt(k) {
  const p = PRODUCTS[k];
  return p.photo ? `<img src="${esc(p.photo)}" alt="${esc(p.name)}" loading="lazy">` : keepsakeArt(p.art);
}
const peso0 = n => "₱" + Number(n).toLocaleString("en-PH");
function tierRange(p, i) { const t = p.tiers[i], n = p.tiers[i + 1]; return n ? `${t[0]}–${n[0] - 1} pcs` : `${t[0]}+ pcs`; }

/* ---------- storage (WordPress REST API, see inc/orders.php) ---------- */
async function api(path, opts = {}) {
  const r = await fetch(TT.api + path, { credentials: "same-origin", ...opts, headers: { "X-WP-Nonce": TT.nonce, ...(opts.headers || {}) } });
  const data = await r.json().catch(() => ({}));
  if (!r.ok) throw new Error(data.message || "Something went wrong. Please try again.");
  return data;
}
const allOrders = () => api("orders");
const getOrder = id => api("orders/" + encodeURIComponent(id)).catch(() => null);
const trackOrder = id => api("track/" + encodeURIComponent(id)).catch(() => null);

/* ---------- page wiring ---------- */
$("#menuBtn")?.addEventListener("click", () => { const n = $("#nav"); n.classList.toggle("open"); $("#menuBtn").setAttribute("aria-expanded", n.classList.contains("open")); });
$$("#nav a").forEach(a => a.addEventListener("click", () => { $("#nav").classList.remove("open"); $("#menuBtn")?.setAttribute("aria-expanded", "false"); }));
$$("[data-art]").forEach(el => { el.innerHTML = keepsakeArt(el.dataset.art); });
// Full-size photo viewer (like heartprinted.com / PhotoSwipe): the photo grows out of the one clicked
// and shrinks back into the current one's thumbnail on close, with the background fading in/out.
// ‹ × › buttons at the bottom, arrow keys and swipe move between the [data-zoom] photos of one
// .pdp-gallery; click/tap zooms 2.5× towards the pointer and moving looks around.
document.addEventListener("click", e => {
  const z = e.target.closest("[data-zoom]"); if (!z) return;
  const group = z.closest(".pdp-gallery"), items = group ? $$("[data-zoom]", group) : [z];
  openViewer(items, items.indexOf(z));
});
const EASE = "cubic-bezier(.4,0,.22,1)";
function openViewer(items, index) {
  const many = items.length > 1;
  const dlg = document.createElement("dialog"); dlg.className = "viewer";
  dlg.innerHTML = `<div class="vw-bg"></div><div class="vw-stage"><div class="lb-frame"><img alt="" draggable="false"></div></div>
    <div class="vw-ui"><div class="vw-bar">${many ? `<button class="vw-btn" data-go="-1" aria-label="Previous">${VW.prev}</button>` : ""}<button class="vw-btn vw-close" aria-label="Close gallery">${VW.close}</button>${many ? `<button class="vw-btn" data-go="1" aria-label="Next">${VW.next}</button>` : ""}</div>
    ${many ? `<p class="vw-count" aria-live="polite"></p>` : ""}</div>`;
  document.body.appendChild(dlg); dlg.showModal();
  const frame = $(".lb-frame", dlg), img = $("img", frame), fade = $$(".vw-bg, .vw-ui", dlg);
  const reduce = matchMedia("(prefers-reduced-motion: reduce)").matches;
  let zoomed = false, closing = false;
  const setZoom = on => { zoomed = on; frame.classList.toggle("zoomed", on); };
  // Keyframe that puts the frame exactly over the thumbnail: scaled to cover it, clipped to its shape.
  const atThumb = el => {
    const f = el.getBoundingClientRect(), t = frame.getBoundingClientRect();
    if (!t.width || f.bottom < 0 || f.top > innerHeight || !f.width) return null; // thumbnail off screen: just fade
    const sc = Math.max(f.width / t.width, f.height / t.height);
    const dx = f.left + f.width / 2 - (t.left + t.width / 2), dy = f.top + f.height / 2 - (t.top + t.height / 2);
    const ix = (t.width - f.width / sc) / 2, iy = (t.height - f.height / sc) / 2;
    return { transform: `translate(${dx}px, ${dy}px) scale(${sc})`, clipPath: `inset(${iy}px ${ix}px round ${parseFloat(getComputedStyle(el).borderRadius) / sc || 0}px)` };
  };
  const fadeUI = show => fade.forEach(el => el.animate([{ opacity: show ? 0 : 1 }, { opacity: show ? 1 : 0 }], { duration: 333, easing: EASE, fill: "forwards" }));
  const show = (i, dir = 0) => {
    index = (i + items.length) % items.length;
    setZoom(false);
    img.src = items[index].dataset.zoom;
    if (many) $(".vw-count", dlg).textContent = `${index + 1} / ${items.length}`;
    if (dir && !reduce) frame.animate([{ transform: `translateX(${dir * 60}px)`, opacity: 0 }, { transform: "none", opacity: 1 }], { duration: 300, easing: EASE });
  };
  if (!reduce) {
    fadeUI(true);
    frame.style.visibility = "hidden"; // until the photo has a size to grow to
    img.addEventListener("load", () => {
      frame.style.visibility = "";
      const from = atThumb(items[index]);
      frame.animate(from ? [from, { transform: "none", clipPath: "inset(0 round 10px)" }] : [{ opacity: 0 }, { opacity: 1 }], { duration: 333, easing: EASE });
    }, { once: true });
  }
  show(index);
  const shut = () => {
    if (closing) return; closing = true;
    if (reduce) return dlg.close();
    img.style.transition = "none"; setZoom(false);
    const to = atThumb(items[index]);
    fadeUI(false);
    frame.animate(to ? [{ transform: "none", clipPath: "inset(0 round 10px)" }, to] : [{ opacity: 1 }, { opacity: 0 }], { duration: 333, easing: EASE, fill: "forwards" }).onfinish = () => dlg.close();
  };
  const aim = ev => { // Zoom towards the pointer / look around while zoomed.
    const r = frame.getBoundingClientRect();
    img.style.transformOrigin = `${clamp((ev.clientX - r.left) / r.width, 0, 1) * 100}% ${clamp((ev.clientY - r.top) / r.height, 0, 1) * 100}%`;
  };
  let down = null, swiped = false; // Swipe left/right (when not zoomed) to change photo.
  frame.onpointerdown = ev => { down = { x: ev.clientX, y: ev.clientY }; swiped = false; };
  frame.onpointerup = ev => {
    if (!down || zoomed) return;
    const dx = ev.clientX - down.x;
    if (many && Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(ev.clientY - down.y)) { swiped = true; show(index + (dx < 0 ? 1 : -1), dx < 0 ? 1 : -1); }
    down = null;
  };
  frame.onclick = ev => { if (swiped || closing) return; aim(ev); setZoom(!zoomed); };
  frame.onpointermove = ev => { if (zoomed) aim(ev); };
  dlg.onclick = ev => {
    const go = ev.target.closest("[data-go]");
    if (go) show(index + +go.dataset.go, +go.dataset.go);
    else if (ev.target.closest(".vw-close") || ev.target === dlg || ev.target.matches(".vw-stage, .vw-bg")) shut();
  };
  dlg.oncancel = ev => { ev.preventDefault(); shut(); }; // Esc
  dlg.onkeydown = ev => { if (many && (ev.key === "ArrowLeft" || ev.key === "ArrowRight")) { ev.preventDefault(); const d = ev.key === "ArrowRight" ? 1 : -1; show(index + d, d); } };
  dlg.onclose = () => dlg.remove();
}
const VW = {
  prev: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg>',
  next: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg>',
  close: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>'
};


/* ---------- cart: saved in IndexedDB, so it survives page changes and reloads ----------
   Item: { id, added, product, color, qty, photos: [{ id, file, crop, seen }] }.
   Photos are kept as the customer's original files; previews and print crops are made from them. */
const MAX_ITEMS = TT.limits.items, MAX_PHOTOS = TT.limits.photos;
const cartStore = (() => {
  let db;
  const open = () => db ??= new Promise((res, rej) => {
    const r = indexedDB.open("tt-cart", 1);
    r.onupgradeneeded = () => r.result.createObjectStore("items", { keyPath: "id" });
    r.onsuccess = () => res(r.result); r.onerror = () => rej(r.error);
  });
  const run = async (mode, fn) => {
    const t = (await open()).transaction("items", mode), req = fn(t.objectStore("items"));
    return new Promise((res, rej) => { t.oncomplete = () => res(req.result); t.onerror = t.onabort = () => rej(t.error); });
  };
  return { all: () => run("readonly", s => s.getAll()), put: it => run("readwrite", s => s.put(it)), remove: id => run("readwrite", s => s.delete(id)), clear: () => run("readwrite", s => s.clear()) };
})();
let CART = [], cartOK = true;
function showCartCount() { const n = +(ls("tt-cart-count") || 0), b = $("#cartCount"); if (b) { b.textContent = n; b.hidden = !n; } }
function setCartCount() { ls("tt-cart-count", String(CART.length)); showCartCount(); }
async function loadCart() {
  try { CART = (await cartStore.all()).sort((a, b) => a.added - b.added); }
  catch (e) { cartOK = false; CART = []; }
  const gone = CART.filter(it => !Object.hasOwn(PRODUCTS, it.product));
  if (gone.length) { gone.forEach(it => cartStore.remove(it.id)); CART = CART.filter(it => !gone.includes(it)); toast("A product in your cart is no longer sold, so we removed it."); }
  setCartCount();
}

/* Prices: price breaks use all pieces of that product in the cart (server does the same).
   An item is qty designs (its photos) printed `copies` times, so it counts as qty × copies pieces. */
const shipping = () => CONFIG.shippingFee == null ? null : Number(CONFIG.shippingFee);
const pieces = it => it.qty * (it.copies || 1);
const productTotal = (k, items = CART) => items.filter(it => it.product === k).reduce((a, it) => a + pieces(it), 0);
const unitFor = (k, n) => { const p = PRODUCTS[k]; return p.tiers ? (p.tiers.filter(t => n >= t[0]).pop() || p.tiers[0])[1] : p.price; };
const itemUnit = it => unitFor(it.product, productTotal(it.product));
const itemName = it => PRODUCTS[it.product].name + (it.color ? ` (${it.color})` : "");
const cartSubtotal = () => CART.reduce((a, it) => a + itemUnit(it) * pieces(it), 0);
const itemLine = it => `${qtyText(it)}${(it.copies || 1) > 1 ? ` × ${it.copies} copies` : ""}`;
/* Discount code: checked with the server (GET coupon/{code}), remembered for this browser. Same formula as tales2tiles_coupon_discount(). */
let COUPON = (() => { try { return JSON.parse(ls("tt-coupon") || "null"); } catch (e) { return null; } })();
const couponDiscount = () => !COUPON || cartSubtotal() < COUPON.min ? 0 : Math.min(cartSubtotal(), Math.round((cartSubtotal() * COUPON.percent / 100 + COUPON.amount) * 100) / 100);
const cartTotal = () => cartSubtotal() - couponDiscount() + (shipping() || 0);
const cartPhotos = () => CART.reduce((a, it) => a + it.photos.length, 0);
function cartProblems() {
  const out = [];
  CART.forEach(it => {
    const d = PRODUCTS[it.product];
    if (d.colors && !d.colors.includes(it.color)) out.push(`${d.name}: choose a ${d.colorLabel.toLowerCase()} (edit the item).`);
    if (it.qty > d.max) out.push(`${d.name}: up to ${d.max} per item.`);
    if (it.photos.length !== d.per * it.qty) out.push(`${d.name} needs ${d.per * it.qty} photos. Edit the item to update them.`);
  });
  new Set(CART.map(it => it.product)).forEach(k => { const d = PRODUCTS[k], n = productTotal(k); if (n < d.min) out.push(`${d.name}: the minimum order is ${d.min} pieces in total (you have ${n}).`); });
  if (cartPhotos() > MAX_PHOTOS) out.push(`One order can have up to ${MAX_PHOTOS} photos. Please split it into two orders.`);
  if (CART.length > MAX_ITEMS) out.push(`One order can have up to ${MAX_ITEMS} items.`);
  return out;
}

/* Photo helpers (d = product). */
function photoLabel(i, d, n) {
  if (d.sides) return `${d.group === "keepsakes" ? "Keychain" : "Piece"} ${Math.floor(i / 2) + 1} · ${i % 2 ? "back" : "front"}`;
  return `${d.per === 9 ? "Magnet" : "Photo"} ${i + 1} of ${n}`;
}
// A photo can fill several slots ("Set of 3" from 1 photo): the same object sits in IT.photos n times, kept together.
const isFirst = (list, i) => list.findIndex(q => q.id === list[i].id) === i;
const timesUsed = (list, id) => list.filter(q => q.id === id).length;
function photoAspect(ph, d) { if (d.circle) return 1; const a = d.mm[0] / d.mm[1]; return d.orient && ph.crop.land ? 1 / a : a; }
const mmPx = mm => Math.round(mm / 25.4 * 300);
function outSize(ph, d) { let w = mmPx(d.mm[0]), h = mmPx(d.mm[1]); if (d.orient && ph.crop.land) [w, h] = [h, w]; return [w, h]; }
function readAsDataURL(file) { return new Promise((res, rej) => { const r = new FileReader(); r.onload = () => res(r.result); r.onerror = rej; r.readAsDataURL(file); }); }
function loadImg(src) { return new Promise((res, rej) => { const i = new Image(); i.onload = () => res(i); i.onerror = rej; i.src = src; }); }
async function shrink(file, max = 1600, q = .88) {
  const im = await loadImg(await readAsDataURL(file));
  const k = Math.min(1, max / Math.max(im.width, im.height));
  const c = document.createElement("canvas"); c.width = Math.round(im.width * k); c.height = Math.round(im.height * k);
  c.getContext("2d").drawImage(im, 0, 0, c.width, c.height);
  const src = c.toDataURL("image/jpeg", q);
  return { src, img: await loadImg(src), w: im.width, h: im.height }; // w/h: the original's pixels (low-resolution check)
}
/* Photo edits live on each photo's crop object and are drawn by drawCrop, which makes the previews,
   the cart thumbnails and the 300 dpi print files, so the print always matches the preview:
   { x, y, zoom, rot (90° steps), land, tilt (straighten ±15°), flip, flipV, fit (whole photo on a background), bg,
     look, b, c, s (brightness/contrast/saturation %), warm (−100…100), texts: [{ t, font, size, x, y, color, bold, italic, shadow, outline, op }] }.
   Filters use the canvas `filter` property (Chrome, Firefox, Safari 18+); where it's missing the filter controls are hidden.
   Warmth and text use plain canvas drawing, so they work everywhere. */
const FILTER_OK = typeof document.createElement("canvas").getContext("2d").filter === "string";
const LOOKS = { // name, canvas filter steps, extra warmth
  none: ["Original", []],
  vivid: ["Vivid", [["saturate", 1.35], ["contrast", 1.08]]],
  bright: ["Bright", [["brightness", 1.12], ["contrast", 1.04]]],
  warm: ["Warm", [["saturate", 1.1]], 45],
  cool: ["Cool", [["saturate", 1.05]], -45],
  rosy: ["Rosy", [["brightness", 1.04], ["saturate", 1.1]], 25],
  dramatic: ["Dramatic", [["contrast", 1.3], ["saturate", 1.1], ["brightness", .95]]],
  matte: ["Matte", [["contrast", .85], ["brightness", 1.08], ["saturate", .9]]],
  fade: ["Fade", [["contrast", .88], ["brightness", 1.06], ["saturate", .8]]],
  vintage: ["Vintage", [["sepia", .35], ["contrast", .92], ["saturate", .85], ["brightness", 1.05]]],
  bw: ["B&W", [["grayscale", 1], ["contrast", 1.1]]],
  noir: ["Noir", [["grayscale", 1], ["contrast", 1.4], ["brightness", .92]]],
  sepia: ["Sepia", [["sepia", .7]]]
};
const fxCSS = ops => ops.map(([f, v]) => `${f}(${v})`).join(" ") || "none";
const fxOps = c => [...(LOOKS[c.look] || LOOKS.none)[1], ["brightness", (c.b ?? 100) / 100], ["contrast", (c.c ?? 100) / 100], ["saturate", (c.s ?? 100) / 100]].filter(([f, v]) => v !== (f === "grayscale" || f === "sepia" ? 0 : 1)); // Drop no-op steps.
const warmth = c => clamp((c.warm || 0) + ((LOOKS[c.look] || LOOKS.none)[2] || 0), -100, 100);
const freshCrop = (land = false) => ({ x: 0, y: 0, zoom: 1, rot: 0, land, tilt: 0, flip: false, flipV: false, fit: false, bg: "#ffffff", look: "none", b: 100, c: 100, s: 100, warm: 0, texts: [] });
const FONTS = { // key: [family, fallback]; web fonts are loaded on the order and cart pages (functions.php)
  jost: ["Jost", "sans-serif"], arial: ["Arial", "sans-serif"], georgia: ["Georgia", "serif"], courier: ["Courier New", "monospace"],
  pacifico: ["Pacifico", "cursive"], lobster: ["Lobster", "cursive"], caveat: ["Caveat", "cursive"], oswald: ["Oswald", "sans-serif"],
  playfair: ["Playfair Display", "serif"], cormorant: ["Cormorant Garamond", "serif"]
};
const fontCSS = (t, H) => `${t.italic ? "italic " : ""}${t.bold ? 700 : 400} ${Math.max(1, t.size * H)}px "${(FONTS[t.font] || FONTS.jost)[0]}", ${(FONTS[t.font] || FONTS.jost)[1]}`;
const newText = () => ({ t: "Your text", font: "pacifico", size: .12, x: .5, y: .82, color: "#ffffff", bold: false, italic: false, shadow: true, outline: false, op: 1 });
function textBox(ctx, t, W, H) { // Text block bounds in frame pixels (for dragging).
  ctx.save(); ctx.font = fontCSS(t, H);
  const lines = t.t.split("\n"), lh = t.size * H * 1.15, w = Math.max(...lines.map(l => ctx.measureText(l).width));
  ctx.restore();
  return { x: t.x * W - w / 2, y: t.y * H - lines.length * lh / 2, w, h: lines.length * lh };
}
function drawTexts(ctx, texts, W, H) {
  for (const t of texts || []) {
    if (!t.t.trim()) continue;
    const px = t.size * H, lines = t.t.split("\n"), lh = px * 1.15, y0 = t.y * H - (lines.length - 1) * lh / 2;
    ctx.save();
    ctx.font = fontCSS(t, H); ctx.textAlign = "center"; ctx.textBaseline = "middle"; ctx.globalAlpha = t.op ?? 1;
    if (t.shadow) { ctx.shadowColor = "rgba(0,0,0,.45)"; ctx.shadowBlur = px * .15; ctx.shadowOffsetY = px * .06; }
    lines.forEach((ln, i) => {
      const y = y0 + i * lh;
      if (t.outline) { ctx.lineWidth = Math.max(1, px * .09); ctx.lineJoin = "round"; ctx.strokeStyle = /^#(000|000000)$/i.test(t.color) ? "#fff" : "#000"; ctx.strokeText(ln, t.x * W, y); }
      ctx.fillStyle = t.color; ctx.fillText(ln, t.x * W, y);
    });
    ctx.restore();
  }
}
const loadTextFonts = photos => Promise.all(photos.flatMap(ph => (ph.crop.texts || []).map(t => document.fonts?.load(fontCSS(t, 40)).catch(() => {}))));
function drawCrop(ctx, img, c, W, H) {
  const fx = c.flip ? -1 : 1, fy = c.flipV ? -1 : 1, a = ((c.rot || 0) + (c.tilt || 0)) * Math.PI / 180, ca = Math.cos(a), sa = Math.sin(a);
  const A = Math.abs(ca), B = Math.abs(sa);
  let s, px = c.x * W, py = c.y * H;
  if (c.fit) {
    // Whole photo inside the frame, the rest filled with the background colour; pan in frame axes.
    const rw = img.width * A + img.height * B, rh = img.width * B + img.height * A;
    s = Math.min(W / rw, H / rh) * c.zoom;
    const lx = Math.abs(W - rw * s) / 2, ly = Math.abs(H - rh * s) / 2;
    px = clamp(px, -lx, lx); py = clamp(py, -ly, ly);
  } else {
    // The frame's bounding box measured along the photo's own axes; the scaled photo must cover it,
    // and the pan is clamped along those axes so no white corner shows at any angle.
    const bw = W * A + H * B, bh = W * B + H * A;
    s = Math.max(bw / img.width, bh / img.height) * c.zoom;
    const lx = Math.max(0, (img.width * s - bw) / 2), ly = Math.max(0, (img.height * s - bh) / 2);
    const qx = clamp(px * ca + py * sa, -lx, lx), qy = clamp(-px * sa + py * ca, -ly, ly);
    px = qx * ca - qy * sa; py = qx * sa + qy * ca;
  }
  c.x = px / W; c.y = py / H;
  const iw = img.width * s, ih = img.height * s, warm = warmth(c);
  ctx.save(); ctx.fillStyle = c.fit ? (c.bg || "#ffffff") : "#fff"; ctx.fillRect(0, 0, W, H);
  ctx.save();
  ctx.translate(W / 2 + px, H / 2 + py); ctx.scale(fx, fy); ctx.rotate(fx * fy * a); // Mirror, keeping the rotation direction.
  ctx.imageSmoothingQuality = "high";
  if (FILTER_OK) ctx.filter = fxCSS(fxOps(c));
  ctx.drawImage(img, -iw / 2, -ih / 2, iw, ih);
  if (warm) { // Warm/cool tint over the photo only.
    if (FILTER_OK) ctx.filter = "none";
    ctx.globalCompositeOperation = "soft-light";
    ctx.fillStyle = warm > 0 ? `rgba(255,140,40,${warm / 100 * .55})` : `rgba(40,120,255,${-warm / 100 * .55})`;
    ctx.fillRect(-iw / 2, -ih / 2, iw, ih);
  }
  ctx.restore();
  drawTexts(ctx, c.texts, W, H);
  ctx.restore();
}
function paint(canvas, ph, W, H, img = ph.img) {
  const dpr = window.devicePixelRatio || 1;
  canvas.width = Math.round(W * dpr); canvas.height = Math.round(H * dpr); canvas.style.width = W + "px"; canvas.style.height = H + "px";
  const ctx = canvas.getContext("2d"); ctx.setTransform(dpr, 0, 0, dpr, 0, 0); drawCrop(ctx, img, ph.crop, W, H);
}
/* Print sharpness: dots per inch the original photo gives at the chosen crop (prints are 300 dpi). */
const LOW_DPI = 150;
function photoDPI(ph, d) {
  if (!ph.w) return Infinity;
  const [W, H] = outSize(ph, d), c = ph.crop, a = ((c.rot || 0) + (c.tilt || 0)) * Math.PI / 180, A = Math.abs(Math.cos(a)), B = Math.abs(Math.sin(a));
  const s = c.fit ? Math.min(W / (ph.w * A + ph.h * B), H / (ph.w * B + ph.h * A)) * c.zoom : Math.max((W * A + H * B) / ph.w, (W * B + H * A) / ph.h) * c.zoom;
  return 300 / s; // s = print pixels per original pixel
}
const lowRes = (ph, d) => photoDPI(ph, d) < LOW_DPI;
function thumbBox(a, m) { return a >= 1 ? [m, Math.round(m / a)] : [Math.round(m * a), m]; }

/* ---------- shared wizard frame (one wizard per page: builder on /order/, checkout on /cart/) ---------- */
const S = {
  step: 0,
  d: { name: "", phone: "", email: "", address: "", city: "", province: "", postal: "", notes: ls("tt-note") || "" }, // notes: also typed in the slide-in cart
  receipt: null, payMethod: "GCash", payRef: "",
  hq: true // Also send the untouched original files after the order (uploadOriginals).
};
let render = () => {};
const STEP_LABEL = { options: "Quantity", preview: "Photos", cart: "Cart", details: "Details", pay: "Payment", receipt: "Receipt", review: "Review" };
function stepperHTML(keys) {
  const s = S.step;
  return `<ol class="stepper">${keys.map((k, i) => `<li class="${i < s ? "done" : i === s ? "cur" : ""}"${i === s ? ' aria-current="step"' : ""}><span>${STEP_LABEL[k]}</span></li>`).join("")}</ol>
    <div class="mstep"><b>Step ${s + 1} of ${keys.length}</b> <span>· ${STEP_LABEL[keys[s]]}</span><div class="bar"><i style="width:${((s + 1) / keys.length) * 100}%"></i></div></div>`;
}
/* first = [href, label] shown instead of "Back" on the first step. */
function navHTML(nextLabel = "Next", first = null) {
  const back = S.step > 0 ? `<button class="btn ghost" id="back">Back</button>` : first ? `<a class="btn ghost" href="${esc(first[0])}">${esc(first[1])}</a>` : "<span></span>";
  return `<div class="wiz-nav">${back}<button class="btn" id="next">${nextLabel}</button></div>`;
}
function go(step) { S.step = step; render(); window.scrollTo({ top: 0 }); }
function bindNav(validate, onNext) {
  $("#back")?.addEventListener("click", () => go(S.step - 1));
  $("#next").addEventListener("click", async () => {
    const err = validate ? validate() : "";
    if (err) return toast(err);
    if (onNext) return onNext();
    go(S.step + 1);
  });
}

/* How an item's quantity reads: the product's own dropdown label (Products → "Pieces dropdown"), else "Set of 6" / "6 magnets". */
const qtyText = it => {
  const d = PRODUCTS[it.product], opt = (d.options || []).find(o => o[0] === it.qty);
  return opt && opt[1] ? opt[1] : d.tiers ? `Set of ${it.qty}` : `${it.qty} ${it.qty === 1 ? d.unitWord.replace(/s$/, "") : d.unitWord}`;
};
/* "Upload images" modal: drop zone + "Upload From PC", or Google Drive / Camera / Facebook. Calls onFiles(File[]). */
const SRC_ICON = {
  photos: '<svg width="84" height="72" viewBox="0 0 84 72" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="10" y="22" width="34" height="34" rx="5" transform="rotate(-12 27 39)"/><rect x="38" y="10" width="32" height="32" rx="5" transform="rotate(14 54 26)"/><circle cx="22" cy="36" r="3"/><circle cx="56" cy="22" r="3"/><path d="M14 52l9-9 8 7 7-6M8 12l3 0M9.5 10.5v3M74 54h4M76 52v4"/><circle cx="76" cy="44" r="2"/><circle cx="12" cy="20" r="2"/></svg>',
  drive: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M8.5 3h7l6 10.5-3.5 6h-12L2.5 13.5z"/><path d="M8.5 3l6.5 11M15.5 3L9 14M2.5 13.5h19M6 19.5l3-5.5M18 19.5l-3-5.5"/></svg>',
  facebook: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><path d="M13.2 21.4V13h2.6l.4-3h-3V8.3c0-.9.3-1.5 1.5-1.5h1.6V4.2a20 20 0 0 0-2.3-.1c-2.3 0-3.8 1.4-3.8 3.9V10H7.6v3h2.6v8.3" stroke-linejoin="round"/></svg>'
};
function openUploader(onFiles, multiple = true) {
  const m = modal(`<div class="mhead"><h2>Upload images</h2><button class="close" aria-label="Close">×</button></div>
    <label class="up-drop" id="upDrop"><input type="file" accept="image/*"${multiple ? " multiple" : ""} class="sr" id="upFile">
      <span class="up-hint">Drag &amp; drop images</span>${SRC_ICON.photos}<span class="btn dark">+ Upload From PC</span></label>
    <p class="up-or">or choose from</p>
    <div class="up-sources">
      <button type="button" class="up-src" data-src="Google Drive" aria-label="Choose from Google Drive" title="Google Drive">${SRC_ICON.drive}</button>
      <label class="up-src" aria-label="Take a photo" title="Camera"><input type="file" accept="image/*" capture="environment" class="sr" id="upCam">${ICON.camera}</label>
      <button type="button" class="up-src" data-src="Facebook" aria-label="Choose from Facebook" title="Facebook">${SRC_ICON.facebook}</button>
    </div>
    <button type="button" class="btn ghost block" id="upCancel">Cancel</button>`);
  m.classList.add("upload-modal");
  const take = files => { const list = [...files]; if (!list.length) return; closeModal(); onFiles(list); };
  $("#upFile", m).onchange = e => take(e.target.files);
  $("#upCam", m).onchange = e => take(e.target.files);
  const dz = $("#upDrop", m);
  ["dragenter", "dragover"].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.add("over"); }));
  ["dragleave", "drop"].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.remove("over"); }));
  dz.addEventListener("drop", e => take(e.dataTransfer.files));
  $("#upCancel", m).onclick = closeModal;
  // ponytail: Google Drive / Facebook pickers are placeholders; wire Google Picker (API key + OAuth client) and Facebook (app review needed) here.
  $$("[data-src]", m).forEach(b => b.onclick = () => toast(`${b.dataset.src} import is coming soon. For now, save the photos to your device and use Upload From PC.`));
}

/* Start a cart item from the product page: upload photos, or add straight away. */
function startItem(k, qty, color, feature = false) {
  if (CART.length >= MAX_ITEMS) return toast(`Your cart is full (${MAX_ITEMS} items). Check out first, then start a new order.`);
  IT = { ...newItem(k), qty, color, feature };
  if (!PRODUCTS[k].per) return saveItem(); // Nothing to upload.
  openUploader(files => { render = renderBuilder; S.step = 1; renderBuilder(); window.scrollTo({ top: 0 }); addPhotos(files); });
}

/* ---------- Product page (single-tt_product.php): pieces dropdown, live price, Upload pictures ---------- */
let PRODUCT_HTML = ""; // The server-built page, restored after "Add to cart".
function initProductPage() {
  const k = TT.product, d = PRODUCTS[k]; if (!d) return;
  const qty = $("#pdpQty"), other = $("#pdpOther"), color = $("#pdpColor"), consent = $("#pdpConsent");
  // Pieces: the dropdown choice, or the typed number when "Other amount…" is picked (kept within min–max).
  const pieces = () => qty.value === "other" ? clamp(parseInt(other.value, 10) || d.min, d.min, d.max) : +qty.value || d.min;
  const price = () => {
    const q = pieces(), unit = unitFor(k, productTotal(k) + q); // Price breaks count what's already in the cart.
    $("#pdpPrice").textContent = peso(unit * q);
    $("#pdpNote").textContent = `for ${q} ${q === 1 ? d.unitWord.replace(/s$/, "") : d.unitWord}`;
  };
  qty.onchange = () => {
    const isOther = qty.value === "other";
    if (other) { other.closest("label").hidden = !isOther; if (isOther) other.focus(); }
    price();
  };
  if (other) { other.oninput = price; other.onchange = () => { other.value = pieces(); price(); }; }
  if (color && d.art === "leather") { color.onchange = () => { $(".pdp-main").innerHTML = keepsakeArt("leather", color.value); }; } // Strap drawn in the chosen color.
  $("#pdpGo").onclick = () => { if (other) other.value = pieces(); startItem(k, pieces(), color ? color.value : null, !!consent?.checked); };
  $$(".help[data-help]").forEach(b => b.onclick = () => toast(b.dataset.help)); // Phones have no hover tooltip.
  $$("[data-art]", app).forEach(el => { el.innerHTML = keepsakeArt(el.dataset.art); });
  color?.onchange?.();
  price();
}

/* ---------- /order/?product=… (custom amount) or ?edit=…: one cart item ---------- */
let IT = null; // Item being built; photos also carry { src, img } previews.
const BP = () => PRODUCTS[IT.product];
const builderSteps = () => ["options", ...(BP().per > 0 ? ["preview"] : [])];
const photosNeeded = () => BP().per * IT.qty;
const editing = () => CART.some(it => it.id === IT.id);
function newItem(k) {
  const p = PRODUCTS[k];
  return { id: Date.now().toString(36) + uid(), added: Date.now(), product: k, color: p.colors ? p.colors[0] : null, qty: clamp(p.min - productTotal(k), 1, p.max), copies: 1, photos: [], active: 0 };
}
function renderBuilder() {
  const keys = builderSteps(); S.step = clamp(S.step, 0, keys.length - 1);
  app.innerHTML = `<div class="wiz"><div class="wrap">${keys.length > 1 ? stepperHTML(keys) : ""}<div class="panel" id="panel"></div></div></div>`;
  ({ options: stepOptions, preview: stepPreview })[keys[S.step]]($("#panel"));
}
const addLabel = () => editing() ? "Save changes" : "Add to cart";
async function saveItem() {
  const btn = $("#next") || document.activeElement; if (btn) btn.disabled = true;
  try {
    await cartStore.put({ id: IT.id, added: IT.added, product: IT.product, color: IT.color, qty: IT.qty, copies: IT.copies || 1, feature: !!IT.feature, photos: IT.photos.map(({ id, file, crop, w, h }) => ({ id, file, crop, w, h })) });
    IT = null;
    await loadCart();
    if (TT.view === "product") { // Back to the product page, with the cart sliding in.
      app.innerHTML = PRODUCT_HTML; initProductPage(); window.scrollTo({ top: 0 }); openDrawer();
    } else { // Builder on /order/?product= or ?edit=: show the Order page with the cart open.
      ls("tt-open-cart", "1"); location.href = TT.urls.order;
    }
  } catch (e) {
    if (btn) btn.disabled = false;
    toast(cartOK ? "Couldn't save to your cart. Free up some space on your device and try again." : "Your browser is blocking storage (private browsing?). Use a normal window to order.");
  }
}
window.addEventListener("beforeunload", e => { if (IT && IT.photos.length) { e.preventDefault(); e.returnValue = ""; } });

/* Add picked files to the item, keeping the original files for printing. */
async function addPhotos(files) {
  const need = photosNeeded(), room = need - IT.photos.length;
  const list = [...files].filter(f => /^image\//.test(f.type) || /\.(heic|heif)$/i.test(f.name));
  if (room <= 0) return toast(`This item already has ${need} photo${need > 1 ? "s" : ""}. Remove one to swap it.`);
  if (list.length > room) toast(`Added the first ${room}. This item needs ${need}.`);
  for (const f of list.slice(0, room)) {
    try {
      const r = await shrink(f);
      IT.photos.push({ id: uid(), ...r, file: f, crop: freshCrop() });
      drawTiles?.();
    } catch (e) { toast(`Couldn't open ${f.name}. Save it as JPG or PNG and try again.`); }
  }
}
let drawTiles = null; // Set by stepPreview; redraws the preview grid.

/* Step — options (custom amount, or "Back" from the preview) */
function stepOptions(p) {
  const d = BP(), last = builderSteps().length === 1, others = productTotal(IT.product, CART.filter(it => it.id !== IT.id));
  p.innerHTML = `<div class="opt-grid">
      ${d.photo ? `<button type="button" class="opt-art art photo" data-zoom="${esc(d.photo)}" aria-label="View larger">${productArt(IT.product)}</button>` : `<div class="opt-art art">${productArt(IT.product)}</div>`}
      <div class="opt-body">
        <h2>${esc(d.name)}</h2><p class="sub">${esc(d.blurb)}</p>
        ${d.colors ? `<p class="qty-label">${esc(d.colorLabel)}: <b id="cname">${esc(IT.color)}</b></p><div class="swatches">${d.colors.map(c => `<button class="sw" data-c="${esc(c)}" aria-pressed="${IT.color === c}" aria-label="${esc(c)}" title="${esc(c)}" style="--c:${esc(SWATCH[c])}"></button>`).join("")}</div>` : ""}
        <p class="qty-label"><label for="qv">Quantity</label></p>
        <div class="qstep"><button class="iconbtn" id="qm" aria-label="Fewer">−</button><input class="input qty" id="qv" type="number" min="1" max="${d.max}" value="${IT.qty}" inputmode="numeric"><button class="iconbtn" id="qp" aria-label="More">+</button></div>
        ${d.tiers ? `<div class="chips quick" aria-label="Quick pick">${[4, 6, 9, 12, 24].filter(q => q <= d.max).map(q => `<button class="chip" data-quick="${q}">Set of ${q}</button>`).join("")}</div>` : ""}
        ${d.tiers ? `<table class="tiers wide" id="tiers"><tbody>${d.tiers.map((t, i) => `<tr><td>${tierRange(d, i)}</td><td>${peso0(t[1])} each</td></tr>`).join("")}</tbody></table>` : ""}
        <p class="line" id="line" aria-live="polite"></p>
      </div>
    </div>${navHTML(last ? addLabel() : "Next: upload pictures", [BP().url || TT.urls.order, "Back to product"])}`;
  const update = () => {
    const n = others + IT.qty, unit = unitFor(IT.product, n), need = photosNeeded();
    $("#qv").value = IT.qty;
    $$("[data-quick]").forEach(b => b.setAttribute("aria-pressed", String(+b.dataset.quick === IT.qty)));
    $$("#tiers tr").forEach((tr, i) => tr.classList.toggle("on", d.tiers[i][1] === unit));
    $("#line").innerHTML = `${IT.qty} × ${peso(unit)} = <b>${peso(unit * IT.qty)}</b>`
      + (need ? ` · you'll add ${need} photo${need > 1 ? "s" : ""}${d.sides ? " (front and back)" : ""}` : "")
      + (others ? `<br><span class="hint">The price counts the ${others} already in your cart.</span>` : "")
      + (n < d.min ? `<br><span class="hint">Minimum order is ${d.min} pieces in total. You can split them across several designs.</span>` : "");
  };
  const setQ = q => { IT.qty = clamp(Number.isFinite(q) ? q : 1, 1, d.max); update(); };
  $$("[data-c]", p).forEach(b => b.onclick = () => { IT.color = b.dataset.c; $$("[data-c]", p).forEach(x => x.setAttribute("aria-pressed", x === b)); $("#cname").textContent = IT.color; });
  $("#qm").onclick = () => setQ(IT.qty - 1);
  $("#qp").onclick = () => setQ(IT.qty + 1);
  $("#qv").onchange = e => setQ(parseInt(e.target.value, 10));
  $$("[data-quick]", p).forEach(b => b.onclick = () => setQ(+b.dataset.quick));
  update();
  bindNav(null, () => {
    setQ(parseInt($("#qv").value, 10));
    if (IT.photos.length > photosNeeded()) { IT.photos = IT.photos.slice(0, photosNeeded()); toast(`Kept your first ${photosNeeded()} photos.`); }
    last ? saveItem() : go(1);
  });
}

/* Step — product preview: every photo as a magnet, each with remove / replace / edit / text (like the old store). */
const TOOL = {
  trash: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>',
  edit: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h10M18 7h2M4 17h4M12 17h8"/><circle cx="16" cy="7" r="2"/><circle cx="10" cy="17" r="2"/></svg>',
  text: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 6V4h14v2M12 4v16M9 20h6"/></svg>',
  copy: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/></svg>',
  pencil: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h4L19 9l-4-4L4 16z"/></svg>'
};
function stepPreview(p) {
  const d = BP(), need = photosNeeded();
  p.innerHTML = `<div class="pv">
      <div class="pv-main">
        <h2>Product preview</h2>
        <p class="sub">${esc(d.name)} · ${qtyText(IT)}${IT.color ? ` · ${esc(IT.color)}` : ""}</p>
        <div class="pv-bar"><span class="pv-count" id="count"></span><button type="button" class="btn ghost sm" id="addMore">+ Add photos</button></div>
        <div class="pv-grid" id="pvgrid"></div>
      </div>
      <aside class="pv-side">
        <ul class="pv-help">
          <li>${TOOL.edit} Crop, rotate, filters and fit</li><li>${TOOL.text} Add text</li><li>${TOOL.trash} Remove a photo</li>${d.sides ? "" : `<li>${TOOL.copy} Duplicate, to design it differently</li>`}
        </ul>
        ${d.sides ? `<p class="hint">Photos go in pairs: front, then back of each piece.</p>` : ""}
        ${d.pin ? `<p class="hint">The dashed ring is the front of the pin. Anything outside it wraps around the edge.</p>` : ""}
        <p class="note warn" id="lowNote" hidden>Some photos are low resolution and may print blurry. Use a larger original, or zoom out.</p>
        <p class="line" id="pvline"></p>
        ${navHTML(addLabel(), editing() ? [TT.urls.cart, "Back to cart"] : [BP().url || TT.urls.order, "Back to product"])}
      </aside>
    </div>`;
  const grid = $("#pvgrid");
  drawTiles = () => {
    if (!document.body.contains(grid)) return;
    $("#count").textContent = `${IT.photos.length} of ${need} photos`;
    $("#addMore").hidden = IT.photos.length >= need;
    $("#next").disabled = IT.photos.length !== need; // Add to cart only when the set is complete.
    $("#lowNote").hidden = !IT.photos.some(ph => lowRes(ph, d));
    const unit = unitFor(IT.product, productTotal(IT.product, CART.filter(it => it.id !== IT.id)) + IT.qty);
    $("#pvline").innerHTML = `${qtyText(IT)}: <b>${peso(unit * IT.qty)}</b>`;
    const full = IT.photos.length >= need, slots = IT.photos.map((_, i) => i).filter(i => d.sides || isFirst(IT.photos, i));
    grid.innerHTML = [...slots, ...Array.from({ length: need - IT.photos.length }, (_, k) => IT.photos.length + k)].map(i => {
      const ph = IT.photos[i], label = photoLabel(i, d, need), n = ph && !d.sides ? timesUsed(IT.photos, ph.id) : 1;
      const qty = ph && !d.sides && need > 1 ? `<span class="pv-qty">Prints <button data-less="${i}" aria-label="One less of ${label}"${n < 2 ? " disabled" : ""}>−</button><b aria-label="Prints">${n}</b><button data-more="${i}" aria-label="One more of ${label}"${full ? " disabled" : ""}>+</button></span>` : "";
      return ph
        ? `<figure class="pv-tile"><div class="pv-mag ${d.circle ? "round" : ""}"><canvas></canvas>${lowRes(ph, d) ? `<span class="pv-low" title="About ${Math.round(photoDPI(ph, d))} dpi; 150 or more prints sharp">⚠ Low resolution</span>` : ""}</div>
            <div class="pv-tools"><button data-del="${i}" aria-label="Remove ${label}" title="Remove">${TOOL.trash}</button>${d.sides ? "" : `<button data-dup="${i}" aria-label="Duplicate ${label}" title="${full ? "The set is full. Remove a photo or lower its prints first." : "Duplicate, to design it differently"}"${full ? " disabled" : ""}>${TOOL.copy}</button>`}<button data-edit="${i}" aria-label="Edit ${label}" title="Crop, rotate, filters">${TOOL.edit}</button><button data-text="${i}" aria-label="Add text to ${label}" title="Add text">${TOOL.text}</button></div>${qty}
            <figcaption>${label}</figcaption></figure>`
        : `<button class="pv-tile empty" data-add="${i}"><span>+</span>Add photo<small>${label}</small></button>`;
    }).join("");
    $$(".pv-tile canvas", grid).forEach((cv, k) => { const ph = IT.photos[slots[k]], [w, h] = thumbBox(photoAspect(ph, d), 170); loadTextFonts([ph]).then(() => paint(cv, ph, w, h)); });
  };
  grid.onclick = e => {
    const b = e.target.closest("button"); if (!b) return;
    const at = +(b.dataset.del ?? b.dataset.less ?? b.dataset.more ?? b.dataset.dup), id = IT.photos[at]?.id;
    if (b.dataset.del) { IT.photos = d.sides ? IT.photos.filter((_, i) => i !== at) : IT.photos.filter(q => q.id !== id); drawTiles(); }
    else if (b.dataset.dup && IT.photos.length < need) { // Same picture, its own design: a separate photo with a copy of the edits.
      IT.photos.splice(at + timesUsed(IT.photos, id), 0, { ...IT.photos[at], id: uid(), crop: structuredClone(IT.photos[at].crop), seen: false });
      drawTiles();
    }
    else if (b.dataset.more && IT.photos.length < need) { IT.photos.splice(at + timesUsed(IT.photos, id), 0, IT.photos[at]); drawTiles(); }
    else if (b.dataset.less && timesUsed(IT.photos, id) > 1) { IT.photos.splice(at, 1); drawTiles(); }
    else if (b.dataset.add) openUploader(files => addPhotos(files));
    else if (b.dataset.edit || b.dataset.text) openEditor(+(b.dataset.edit || b.dataset.text), b.dataset.text ? "text" : "crop");
  };
  $("#addMore").onclick = () => openUploader(files => addPhotos(files));
  const zone = $(".pv-main", p); // Dropping photos on the preview adds them too.
  ["dragenter", "dragover"].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.add("over"); }));
  ["dragleave", "drop"].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.remove("over"); }));
  zone.addEventListener("drop", e => addPhotos(e.dataTransfer.files));
  drawTiles();
  bindNav(() => IT.photos.length === need ? "" : `Add ${need - IT.photos.length} more photo${need - IT.photos.length > 1 ? "s" : ""} to continue.`, saveItem);
}
/* The photo editor (stepAdjust) in a pop-up, for one photo; "Done" returns to the preview. */
function openEditor(i, tab) {
  IT.active = i;
  const m = modal(`<div class="editor" id="edpanel"></div>`, true);
  m.classList.add("edit-modal");
  stepAdjust($("#edpanel", m), { tab, onDone: closeModal });
}

/* Photo editor for IT.photos[IT.active] (opened by openEditor). opts: { tab, onDone } */
function stepAdjust(p, opts = {}) {
  const d = BP(), need = photosNeeded();
  const slider = (id, label, min, max, step) => `<div class="ctrl"><label for="${id}">${label} <output id="${id}v"></output></label><input type="range" id="${id}" min="${min}" max="${max}" step="${step}"></div>`;
  const colors = (attr, list) => list.map(c => `<button class="sw sm" data-${attr}="${c}" aria-label="${c}" style="--c:${c}"></button>`).join("");
  const TEXT_COLORS = ["#ffffff", "#000000", "#e0261c", "#ffd60a", "#dc6c77", "#2f6fdc"], BG_COLORS = ["#ffffff", "#000000", "#fcedee", "#e4f2ed", "#f4efec"];
  p.innerHTML = `<h2>Edit photo</h2><p class="sub">Drag to move, zoom to fill the ${d.circle ? "circle" : "frame"}. Add a filter or text if you like. What you see is what we print.</p>
    <div class="crop-wrap">
      <div><div class="crop-stage" id="stage"><div class="crop-frame ${d.circle ? "circle" : ""} ${d.pin ? "pin" : ""}" id="frame"><canvas id="cv"></canvas><div class="grid3"></div></div></div>
        <p class="hint" id="which"></p><p class="note warn" id="lowres" hidden></p></div>
      <div class="ctrls">
        <div class="seg adj-tabs" id="tabs"><button data-tab="crop">Crop</button>${FILTER_OK ? `<button data-tab="look">Filters</button>` : ""}<button data-tab="light">Adjust</button><button data-tab="text">Text</button></div>
        <div class="adj-pane" data-pane="crop">
          ${d.orient ? `<div class="ctrl"><label>Orientation</label><div class="seg" id="orient"><button data-o="0">Portrait</button><button data-o="1">Landscape</button></div></div>` : ""}
          <div class="ctrl"><label for="zoom">Zoom</label><div class="zoomrow"><button class="iconbtn" id="zout" aria-label="Zoom out">−</button><input type="range" id="zoom" min="1" max="3" step="0.01"><button class="iconbtn" id="zin" aria-label="Zoom in">+</button></div></div>
          <div class="ctrl"><label class="check"><input type="checkbox" id="fit"> Fit whole photo (no cropping)</label>
            <div class="swatches" id="bgs" hidden>${colors("bg", BG_COLORS)}<input type="color" id="bgc" aria-label="Custom background color"></div></div>
        </div>
        <div class="adj-pane" data-pane="look" hidden><div class="looks">${Object.entries(LOOKS).map(([k, [name, ops, w]]) => `<button class="look" data-look="${k}"><span class="look-img"><img alt="" style="filter:${fxCSS(ops)}">${w ? `<i style="background:${w > 0 ? "rgba(255,140,40," : "rgba(40,120,255,"}${Math.abs(w) / 100 * .55})"></i>` : ""}</span><span>${name}</span></button>`).join("")}</div></div>
        <div class="adj-pane" data-pane="light" hidden>${FILTER_OK ? slider("b", "Brightness", 50, 150, 1) + slider("c", "Contrast", 50, 150, 1) + slider("s", "Saturation", 0, 200, 1) : ""}${slider("warm", "Warmth", -100, 100, 1)}</div>
        <div class="adj-pane" data-pane="text" hidden>
          <button class="btn ghost sm block" id="addText">+ Add text</button>
          <div id="textEdit" hidden>
            <div class="ctrl"><label for="tt">Text <output id="tcount"></output></label><textarea class="input" id="tt" rows="2" maxlength="80"></textarea></div>
            <div class="ctrl"><label for="tfont">Font</label><select class="input" id="tfont">${Object.entries(FONTS).map(([k, [f]]) => `<option value="${k}" style="font-family:'${f}'">${f}</option>`).join("")}</select></div>
            <div class="ctrl"><label>Style</label><div class="btnrow">
              <button class="iconbtn" data-ts="bold" aria-label="Bold"><b>B</b></button><button class="iconbtn" data-ts="italic" aria-label="Italic"><i>I</i></button>
              <button class="iconbtn" data-ts="shadow" aria-label="Shadow"><span style="text-shadow:1px 2px 2px currentColor">S</span></button><button class="iconbtn" data-ts="outline" aria-label="Outline"><span style="-webkit-text-stroke:1.3px var(--ink);color:transparent;font-weight:700;font-size:17px">O</span></button></div></div>
            <div class="ctrl"><label>Color</label><div class="swatches">${colors("tc", TEXT_COLORS)}<input type="color" id="tcc" aria-label="Custom text color"></div></div>
            ${slider("tsize", "Size", 4, 30, 1)}${slider("top", "Opacity", 20, 100, 1)}
            <div class="btnrow"><button class="btn ghost sm" id="nextText" hidden>Next text</button><button class="btn danger sm" id="delText">Delete text</button></div>
          </div>
          <p class="hint">Drag the text on the photo to move it.${d.pin ? " Keep it inside the dashed ring." : ""}</p>
        </div>
        <div class="ctrl btnrow"><button class="btn ghost sm" id="reset">Reset photo</button>${IT.photos.length > 1 ? `<button class="btn ghost sm" id="lookAll">Use this look on all</button>` : ""}</div>
        <div class="ctrl"><button class="btn ghost sm block" id="nextPhoto">Next photo</button></div>
        ${d.pin ? `<p class="hint">The dashed ring is the front of the pin. Anything outside it wraps around the edge.</p>` : ""}
      </div>
    </div>
    <div class="strip" id="strip" role="list"></div><div class="wiz-nav"><span></span><button class="btn" id="edDone">Done</button></div>`;
  const cv = $("#cv"), frame = $("#frame"), stage = $("#stage"), zoom = $("#zoom");
  let W = 300, H = 300, tab = opts.tab || "crop", ti = -1; // ti: selected text of the current photo
  const cur = () => IT.photos[IT.active], crop = () => cur().crop, txt = () => crop().texts?.[ti];
  const size = () => {
    const a = photoAspect(cur(), d);
    const avail = Math.min(stage.clientWidth - 36, 460), maxH = Math.min(window.innerHeight * .55, 460);
    W = avail; H = W / a; if (H > maxH) { H = maxH; W = H * a; }
    W = Math.round(W); H = Math.round(H);
  };
  $("#strip").innerHTML = IT.photos.map((ph, i) => d.sides || isFirst(IT.photos, i) ? `<button data-i="${i}" aria-label="${photoLabel(i, d, need)}" role="listitem"><canvas class="${d.circle ? "round" : ""}"></canvas>${ph.seen ? '<span class="seen"></span>' : ""}</button>` : "").join("");
  const thumbs = {}; $$("#strip button").forEach(b => thumbs[b.dataset.i] = $("canvas", b));
  const drawThumb = i => { if (!thumbs[i]) return; const [w, h] = thumbBox(photoAspect(IT.photos[i], d), 58); paint(thumbs[i], IT.photos[i], w, h); };
  IT.photos.forEach((_, i) => drawThumb(i));
  let raf = 0;
  const redraw = () => { cancelAnimationFrame(raf); raf = requestAnimationFrame(() => {
    paint(cv, cur(), W, H); drawThumb(IT.active);
    const dpi = photoDPI(cur(), d), low = $("#lowres"); // Live: zooming in lowers the print sharpness.
    low.hidden = dpi >= LOW_DPI;
    if (dpi < LOW_DPI) low.textContent = `Low resolution (about ${Math.round(dpi)} dpi): this photo may print blurry. Zoom out or use a larger original.`;
  }); };
  const loadFont = t => document.fonts?.load(fontCSS(t, 40)).then(redraw).catch(() => {}); // Canvas only uses fonts that are loaded.
  const pressed = (el, on) => el?.setAttribute("aria-pressed", String(!!on));
  const fmt = { b: v => `${v}%`, c: v => `${v}%`, s: v => `${v}%`, warm: v => v > 0 ? `+${v}` : `${v}`, tsize: v => `${v}`, top: v => `${v}%` };
  const syncOrient = () => $$("#orient button").forEach(b => pressed(b, !!crop().land === (b.dataset.o === "1")));
  const syncText = () => {
    const t = txt();
    $("#textEdit").hidden = !t; $("#nextText").hidden = (crop().texts || []).length < 2;
    if (!t) return;
    $("#tt").value = t.t; $("#tcount").textContent = `${t.t.length}/80`; $("#tfont").value = t.font; $("#tfont").style.fontFamily = `'${FONTS[t.font][0]}'`;
    $$("[data-ts]").forEach(b => pressed(b, t[b.dataset.ts]));
    $$("[data-tc]").forEach(b => pressed(b, b.dataset.tc === t.color)); $("#tcc").value = t.color;
    $("#tsize").value = Math.round(t.size * 100); $("#tsizev").textContent = Math.round(t.size * 100);
    $("#top").value = Math.round((t.op ?? 1) * 100); $("#topv").textContent = `${Math.round((t.op ?? 1) * 100)}%`;
  };
  const syncEdits = () => { // Put the current photo's edits into the controls.
    const c = crop(), val = { b: c.b ?? 100, c: c.c ?? 100, s: c.s ?? 100, warm: c.warm || 0 };
    Object.entries(val).forEach(([k, v]) => { const el = $("#" + k); if (el) { el.value = v; $(`#${k}v`).textContent = fmt[k](v); } });
    $("#fit").checked = !!c.fit; $("#bgs").hidden = !c.fit; $$("[data-bg]").forEach(b => pressed(b, b.dataset.bg === (c.bg || "#ffffff"))); $("#bgc").value = c.bg || "#ffffff";
    $$(".look").forEach(b => { pressed(b, (c.look || "none") === b.dataset.look); $("img", b).src = cur().src; });
    syncText();
  };
  const showTab = t => { tab = t; $$("#tabs button").forEach(b => pressed(b, b.dataset.tab === t)); $$(".adj-pane").forEach(el => { el.hidden = el.dataset.pane !== t; }); frame.classList.toggle("texting", t === "text"); };
  showTab(tab);
  $$("#tabs button").forEach(b => b.onclick = () => showTab(b.dataset.tab));
  // Crop / adjust controls.
  ["b", "c", "s", "warm"].forEach(k => { const el = $("#" + k); if (el) el.oninput = () => { crop()[k] = +el.value; $(`#${k}v`).textContent = fmt[k](+el.value); redraw(); }; });
  $("#fit").onchange = e => { crop().fit = e.target.checked; crop().zoom = 1; zoom.value = 1; syncEdits(); redraw(); };
  $$("[data-bg]").forEach(b => b.onclick = () => { crop().bg = b.dataset.bg; syncEdits(); redraw(); });
  $("#bgc").oninput = e => { crop().bg = e.target.value; syncEdits(); redraw(); };
  $$(".look").forEach(b => b.onclick = () => { crop().look = b.dataset.look; syncEdits(); redraw(); });
  $("#lookAll")?.addEventListener("click", () => {
    const { look, b, c, s, warm } = crop();
    IT.photos.forEach(ph => Object.assign(ph.crop, { look, b, c, s, warm }));
    IT.photos.forEach((_, i) => drawThumb(i));
    toast(`Filter and adjustments applied to all ${IT.photos.length} photos.`);
  });
  // Text controls.
  $("#addText").onclick = () => { const t = newText(); (crop().texts ||= []).push(t); ti = crop().texts.length - 1; syncText(); loadFont(t); redraw(); $("#tt").select(); };
  $("#tt").oninput = e => { txt().t = e.target.value; $("#tcount").textContent = `${e.target.value.length}/80`; redraw(); };
  $("#tfont").onchange = e => { txt().font = e.target.value; syncText(); loadFont(txt()); redraw(); };
  $$("[data-ts]").forEach(b => b.onclick = () => { const t = txt(); t[b.dataset.ts] = !t[b.dataset.ts]; syncText(); loadFont(t); redraw(); });
  $$("[data-tc]").forEach(b => b.onclick = () => { txt().color = b.dataset.tc; syncText(); redraw(); });
  $("#tcc").oninput = e => { txt().color = e.target.value; syncText(); redraw(); };
  $("#tsize").oninput = e => { txt().size = +e.target.value / 100; $("#tsizev").textContent = e.target.value; redraw(); };
  $("#top").oninput = e => { txt().op = +e.target.value / 100; $("#topv").textContent = `${e.target.value}%`; redraw(); };
  $("#delText").onclick = () => { crop().texts.splice(ti, 1); ti = crop().texts.length - 1; syncText(); redraw(); };
  $("#nextText").onclick = () => { ti = (ti + 1) % crop().texts.length; syncText(); };
  const select = i => {
    IT.active = i; const ph = cur();
    if (!ph.seen) { ph.seen = true; const b = $(`#strip [data-i="${i}"]`); if (!b.querySelector(".seen")) b.insertAdjacentHTML("beforeend", '<span class="seen"></span>'); }
    $$("#strip button").forEach(b => b.setAttribute("aria-current", +b.dataset.i === i));
    zoom.value = ph.crop.zoom;
    ti = (ph.crop.texts || []).length - 1;
    (ph.crop.texts || []).forEach(loadFont);
    $("#which").textContent = photoLabel(i, d, need);
    $("#nextPhoto").textContent = i < IT.photos.length - 1 ? "Next photo" : "Back to first photo";
    $(`#strip [data-i="${i}"]`).scrollIntoView({ block: "nearest", inline: "nearest" });
    syncOrient(); syncEdits(); size(); redraw();
  };
  select(IT.active);
  $$("#strip button").forEach(b => b.onclick = () => select(+b.dataset.i));
  $$("#orient button").forEach(b => b.onclick = () => { crop().land = b.dataset.o === "1"; syncOrient(); size(); redraw(); });
  zoom.oninput = () => { crop().zoom = +zoom.value; redraw(); };
  const setZ = dz => { crop().zoom = clamp(crop().zoom + dz, 1, 3); zoom.value = crop().zoom; redraw(); };
  $("#zin").onclick = () => setZ(.15); $("#zout").onclick = () => setZ(-.15);
  $("#reset").onclick = () => { cur().crop = freshCrop(crop().land); ti = -1; zoom.value = 1; syncEdits(); redraw(); };
  $("#nextPhoto").onclick = () => select((IT.active + 1) % IT.photos.length);
  // Dragging: moves the photo, or on the Text tab the text under the finger (picked by tapping it).
  const pts = new Map(); let pinch0 = 0, zoom0 = 1, dragText = null;
  const pick = e => {
    const r = cv.getBoundingClientRect(), x = e.clientX - r.left, y = e.clientY - r.top, ctx = cv.getContext("2d");
    const texts = crop().texts || [];
    for (let i = texts.length - 1; i >= 0; i--) { const b = textBox(ctx, texts[i], W, H); if (x >= b.x - 8 && x <= b.x + b.w + 8 && y >= b.y - 8 && y <= b.y + b.h + 8) return i; }
    return -1;
  };
  frame.addEventListener("pointerdown", e => {
    frame.setPointerCapture(e.pointerId); pts.set(e.pointerId, { x: e.clientX, y: e.clientY }); frame.classList.add("dragging");
    dragText = null;
    if (tab === "text") { const i = pick(e); if (i >= 0) { ti = i; dragText = txt(); syncText(); } }
    if (pts.size === 2) { const [p1, p2] = [...pts.values()]; pinch0 = Math.hypot(p1.x - p2.x, p1.y - p2.y); zoom0 = crop().zoom; }
  });
  frame.addEventListener("pointermove", e => {
    if (!pts.has(e.pointerId)) return;
    const prev = pts.get(e.pointerId); pts.set(e.pointerId, { x: e.clientX, y: e.clientY });
    if (dragText) { dragText.x = clamp(dragText.x + (e.clientX - prev.x) / W, 0, 1); dragText.y = clamp(dragText.y + (e.clientY - prev.y) / H, 0, 1); }
    else if (pts.size === 2) { const [p1, p2] = [...pts.values()]; const dd = Math.hypot(p1.x - p2.x, p1.y - p2.y); crop().zoom = clamp(zoom0 * dd / pinch0, 1, 3); zoom.value = crop().zoom; }
    else { crop().x += (e.clientX - prev.x) / W; crop().y += (e.clientY - prev.y) / H; }
    redraw();
  });
  const up = e => { pts.delete(e.pointerId); if (!pts.size) { frame.classList.remove("dragging"); dragText = null; } };
  frame.addEventListener("pointerup", up); frame.addEventListener("pointercancel", up);
  frame.addEventListener("wheel", e => { e.preventDefault(); setZ(-e.deltaY * .002); }, { passive: false });
  const onResize = () => { if (!document.body.contains(cv)) return window.removeEventListener("resize", onResize); size(); redraw(); };
  window.addEventListener("resize", onResize);
  $("#edDone", p).onclick = () => opts.onDone?.(); // Own id: the preview behind the pop-up already has a #next (Add to cart).
  if (tab === "text" && !(crop().texts || []).length) $("#addText").click(); // Text button: start with a text ready to type.
}

/* ---------- /cart/: cart and checkout ---------- */
const CHECKOUT = ["cart", "details", "pay", "receipt", "review"];
function renderCheckout() {
  if (!CART.length && S.step === 0) {
    app.innerHTML = `<div class="wrap"><div class="empty-cart"><h1>Your cart is empty</h1><p>${cartOK ? "Pick a product to get started." : "Your browser is blocking the storage the cart needs (private browsing can do this). Please use a normal window."}</p><a class="btn" href="${esc(TT.urls.order)}">Browse products</a></div></div>`;
    return;
  }
  app.innerHTML = `<div class="wiz"><div class="wrap">${stepperHTML(CHECKOUT)}
    <div class="wiz-grid with-aside"><div class="panel" id="panel"></div><aside class="aside">${summaryHTML()}</aside></div></div></div>`;
  ({ cart: stepCart, details: stepDetails, pay: stepPay, receipt: stepReceipt, review: stepReview })[CHECKOUT[S.step]]($("#panel"));
}
function summaryHTML() {
  const sh = shipping();
  return `<div class="summary"><h3>Order summary</h3>
    ${CART.map(it => `<div class="srow"><span>${esc(itemName(it))}<small>${itemLine(it)}</small></span><span>${peso(itemUnit(it) * pieces(it))}</span></div>`).join("")}
    <div class="srow sline"><span>Subtotal</span><span>${peso(cartSubtotal())}</span></div>
    ${COUPON ? `<div class="srow discount"><span>Discount (${esc(COUPON.code)})</span><span>${couponDiscount() ? "−" + peso(couponDiscount()) : `Spend ${peso(COUPON.min)} to use`}</span></div>` : ""}
    <div class="srow"><span>Shipping fee</span><span>${sh == null ? "Based on location" : peso(sh)}</span></div>
    <div class="srow stotal"><span>${sh == null ? "Total to pay now" : "Total"}</span><span>${peso(cartTotal())}</span></div>
    ${sh == null ? `<p class="hint" style="margin-top:8px">We'll message you the shipping fee for your area.</p>` : ""}</div>`;
}

/* Cart items, shared by the cart page and the slide-in cart (openDrawer). */
function cartItemsHTML() {
  return `<ul class="cart-list">${CART.map(it => {
    const d = PRODUCTS[it.product], unit = itemUnit(it), n = it.photos.length;
    // Photo items: the stepper prints the same design again (copies). No-photo items: it's the quantity.
    const count = d.per ? (it.copies || 1) : it.qty;
    return `<li class="cart-item" data-id="${esc(it.id)}">
      <div class="ci-art art ${n || d.photo ? "photo" : ""}">${n ? `<canvas class="${d.circle ? "round" : ""}"></canvas>` : productArt(it.product)}</div>
      <div class="ci-info"><b>${esc(d.name)}</b>
        <span>${d.per ? `${qtyText(it)} · custom design` : qtyText(it)}${it.color ? ` · ${esc(it.color)}` : ""}</span>
        ${n > 1 ? `<div class="ci-thumbs">${it.photos.slice(0, 6).map(() => `<canvas class="${d.circle ? "round" : ""}"></canvas>`).join("")}${n > 6 ? `<span>+${n - 6}</span>` : ""}</div>` : ""}
        <div class="ci-actions">
          <span class="qmini" title="${d.per ? "Copies of this design" : "Quantity"}"><button class="iconbtn" data-q="-1" aria-label="${d.per ? "Fewer copies" : "Fewer"}"${count <= 1 ? " disabled" : ""}>−</button><b>${count}</b><button class="iconbtn" data-q="1" aria-label="${d.per ? "More copies" : "More"}">+</button></span>
          <a class="iconlink" href="${esc(TT.urls.order)}?edit=${encodeURIComponent(it.id)}" aria-label="Edit ${esc(d.name)}" title="Edit design">${TOOL.pencil}</a>
          <button class="iconlink" data-rm aria-label="Remove ${esc(d.name)}" title="Remove">${TOOL.trash}</button>
        </div></div>
      <div class="ci-price"><b>${peso(unit * pieces(it))}</b><span>${pieces(it)} × ${peso(unit)}</span></div>
    </li>`;
  }).join("")}</ul>`;
}
function bindCartItems(root, rerender) {
  $$(".cart-item", root).forEach(li => {
    const it = CART.find(x => x.id === li.dataset.id), d = PRODUCTS[it.product];
    // Cropped thumbnails (first photo large, then a strip), drawn with the customer's edits.
    const draw = (cv, ph, size) => loadTextFonts([ph]).then(() => shrink(ph.file, 360)).then(r => { const [w, h] = thumbBox(photoAspect(ph, d), size); paint(cv, ph, w, h, r.img); }).catch(() => {});
    const main = $(".ci-art canvas", li);
    if (main) draw(main, it.photos[0], 72);
    $$(".ci-thumbs canvas", li).forEach((cv, i) => draw(cv, it.photos[i], 34));
    $("[data-rm]", li).onclick = async () => {
      if (!confirm(`Remove ${itemName(it)} from your cart?`)) return;
      await cartStore.remove(it.id).catch(() => {}); CART = CART.filter(x => x !== it); setCartCount(); rerender();
    };
    $$("[data-q]", li).forEach(b => b.onclick = async () => {
      if (d.per) it.copies = clamp((it.copies || 1) + +b.dataset.q, 1, 20);
      else it.qty = clamp(it.qty + +b.dataset.q, 1, d.max);
      await cartStore.put(it).catch(() => {}); rerender();
    });
  });
}
function couponHTML() {
  return `<form class="coupon" data-coupon>${COUPON
    ? `<span>Code <b>${esc(COUPON.code)}</b> applied.</span><button type="button" class="linkbtn" data-coupon-rm>Remove</button>`
    : `<label class="sr" for="couponIn">Promo code</label><input class="input" id="couponIn" autocomplete="off" maxlength="30" placeholder="Promo code"><button class="btn ghost sm">Apply</button>`}</form>`;
}
function bindCoupon(root, rerender) {
  const form = $("[data-coupon]", root); if (!form) return;
  form.onsubmit = async e => {
    e.preventDefault();
    const code = $("input", form).value.trim();
    if (!/^[A-Za-z0-9_-]{1,30}$/.test(code)) return toast("Enter the code exactly as you received it.");
    try { COUPON = await api("coupon/" + encodeURIComponent(code)); ls("tt-coupon", JSON.stringify(COUPON)); rerender(); toast("Promo code applied."); }
    catch (err) { toast(err.message); }
  };
  $("[data-coupon-rm]", form)?.addEventListener("click", () => { COUPON = null; ls("tt-coupon", ""); rerender(); });
}

/* Slide-in cart (any page): items, order note, promo code, subtotal, Checkout → /cart/#checkout. */
async function openDrawer() {
  if (!$("#drawer")) {
    await loadCart();
    const el = document.createElement("div"); el.id = "drawer"; el.className = "drawer-wrap";
    document.body.appendChild(el); document.body.style.overflow = "hidden";
    el.addEventListener("click", e => { if (e.target.closest("[data-close]")) closeDrawer(); });
    requestAnimationFrame(() => el.classList.add("open"));
  }
  const el = $("#drawer"), problems = cartProblems(), sub = cartSubtotal(), disc = couponDiscount();
  el.innerHTML = `<div class="drawer-bg" data-close></div>
    <aside class="drawer" role="dialog" aria-modal="true" aria-labelledby="drawerTitle">
      <header><h2 id="drawerTitle">Your cart</h2><button class="close" data-close aria-label="Close cart">×</button></header>
      <div class="drawer-body">${CART.length ? cartItemsHTML() + `
        <label class="note-label" for="orderNote">Order note (optional)</label>
        <textarea class="input" id="orderNote" rows="3" placeholder="Anything we should know about your order?">${esc(ls("tt-note") || "")}</textarea>` : `<p class="empty">Your cart is empty.</p>`}</div>
      <footer>
        ${couponHTML()}
        <div class="srow"><span>Subtotal</span><span>${peso(sub)}</span></div>
        ${disc ? `<div class="srow discount"><span>Discount (${esc(COUPON.code)})</span><span>−${peso(disc)}</span></div>` : COUPON ? `<p class="hint">Spend ${peso(COUPON.min)} to use ${esc(COUPON.code)}.</p>` : ""}
        <p class="hint">Shipping is calculated at checkout.</p>
        ${problems.length ? `<p class="note warn" role="alert">${esc(problems[0])}</p>` : ""}
        ${CART.length && !problems.length ? `<a class="btn block" href="${esc(TT.urls.cart)}#checkout">Checkout</a>` : `<button class="btn block" disabled>Checkout</button>`}
      </footer>
    </aside>`;
  bindCartItems(el, openDrawer);
  bindCoupon(el, openDrawer);
  $("#orderNote")?.addEventListener("input", e => { ls("tt-note", e.target.value); S.d.notes = e.target.value; });
  $(".close", el).focus();
}
function closeDrawer() {
  const el = $("#drawer"); if (!el) return;
  el.classList.remove("open"); document.body.style.overflow = "";
  setTimeout(() => el.remove(), 250);
  if (TT.view === "product" && $("#pdpPrice")) initProductPage(); // Prices may change with the cart.
}
document.addEventListener("keydown", e => { if (e.key === "Escape") closeDrawer(); });
$(".cart-link")?.addEventListener("click", e => { if (TT.view !== "cart") { e.preventDefault(); openDrawer(); } });

/* Step — cart (the /cart/ page) */
function stepCart(p) {
  const problems = cartProblems();
  p.innerHTML = `<h2>Your cart</h2><p class="sub">${CART.length} item${CART.length > 1 ? "s" : ""}. Change copies, edit or remove anything before you check out.</p>
    ${cartItemsHTML()}
    ${problems.length ? `<div class="note warn" role="alert">${problems.map(esc).join("<br>")}</div>` : ""}
    ${couponHTML()}
    ${navHTML("Checkout", [TT.urls.order, "Add another product"])}`;
  bindCartItems(p, render);
  bindCoupon(p, render);
  bindNav(() => cartProblems()[0] || "");
}

/* Step — details */
const FIELDS = [
  ["name", "Full name", "text", "name", "Enter your full name.", v => v.trim().length >= 2],
  ["phone", "Mobile number", "tel", "tel", "Use a PH mobile number like 0917 123 4567.", v => /^(09|\+639)\d{9}$/.test(v.replace(/[\s-]/g, ""))],
  ["email", "Email (optional, for your order confirmation)", "email", "email", "Check the email address.", v => !v.trim() || /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v.trim())],
  ["address", "House no., street, barangay", "text", "street-address", "Enter your street and barangay.", v => v.trim().length >= 6],
  ["city", "City / municipality", "text", "address-level2", "Enter your city.", v => v.trim().length >= 2],
  ["province", "Province", "text", "address-level1", "Enter your province.", v => v.trim().length >= 2],
  ["postal", "Postal code", "text", "postal-code", "Postal codes have 4 digits.", v => /^\d{4}$/.test(v.trim())]
];
function stepDetails(p) {
  const f = ([k, l, t, ac, msg], cls = "") => `<div class="field ${cls}" data-f="${k}"><label for="f-${k}">${l}</label><input id="f-${k}" type="${t}" autocomplete="${ac}" value="${esc(S.d[k])}" ${k === "postal" || k === "phone" ? 'inputmode="numeric"' : ""}><div class="msg">${msg}</div></div>`;
  p.innerHTML = `<h2>Where should we send it?</h2><p class="sub">${shipping() == null ? "We use your address to work out the shipping fee." : "We'll text you when your order ships."}</p>
    <div class="form">
      <h3>Contact</h3>${f(FIELDS[0], "full")}${f(FIELDS[1])}${f(FIELDS[2])}
      <h3>Shipping address</h3>${f(FIELDS[3], "full")}${f(FIELDS[4])}${f(FIELDS[5])}${f(FIELDS[6])}
      <div class="field full"><label for="f-notes">Notes for us (optional)</label><textarea id="f-notes" placeholder="Landmarks, gift message, anything we should know">${esc(S.d.notes)}</textarea></div>
    </div>${navHTML()}`;
  FIELDS.forEach(([k]) => $("#f-" + k).addEventListener("input", e => { S.d[k] = e.target.value; e.target.closest(".field").classList.remove("err"); }));
  $("#f-notes").oninput = e => { S.d.notes = e.target.value; ls("tt-note", e.target.value); };
  bindNav(() => {
    let first = null;
    FIELDS.forEach(([k, , , , , ok]) => { const bad = !ok(S.d[k]); $(`[data-f="${k}"]`).classList.toggle("err", bad); if (bad && !first) first = k; });
    if (first) { $("#f-" + first).focus(); return "Check the highlighted fields."; }
    return "";
  });
}

/* Step — pay */
function stepPay(p) {
  p.innerHTML = `<h2>Make your payment</h2><p class="sub">Send the exact amount to either account below.</p>
    <div class="amount"><span>${shipping() == null ? "Amount to pay now (items)" : "Total amount to pay"}</span><strong>${peso(cartTotal())}</strong></div>
    <div class="paygrid">
      <div class="pay"><div class="brand gcash">GCash</div>
        ${CONFIG.gcash.qr ? `<div class="qrimg"><img src="${esc(CONFIG.gcash.qr)}" alt="GCash QR code"></div>` : ""}
        <dl><dt>Account name</dt><dd>${esc(CONFIG.gcash.name)}</dd><dt>GCash number</dt><dd>${esc(CONFIG.gcash.number)}</dd></dl>
        <button class="btn ghost sm" data-copy="${esc(CONFIG.gcash.number.replace(/\s/g, ""))}">Copy number</button></div>
      <div class="pay"><div class="brand bpi">BPI</div>
        <dl><dt>Account name</dt><dd>${esc(CONFIG.bpi.name)}</dd><dt>Account number</dt><dd>${esc(CONFIG.bpi.account)}</dd></dl>
        <button class="btn ghost sm" data-copy="${esc(CONFIG.bpi.account.replace(/\s/g, ""))}">Copy account no.</button></div>
    </div>
    <div class="note">Take a screenshot of your receipt after paying. You'll upload it on the next step.${shipping() == null ? " We'll message you separately for the shipping fee." : ""}</div>
    ${navHTML("I've paid")}`;
  $$("[data-copy]").forEach(b => b.onclick = () => copy(b.dataset.copy));
  bindNav();
}

/* Step — receipt */
function stepReceipt(p) {
  p.innerHTML = `<h2>Upload your receipt</h2><p class="sub">A screenshot from GCash or your bank app is fine.</p>
    <div class="receipt-row">
      <div>
        <label class="drop" id="rdrop"><input type="file" accept="image/*" class="sr" id="rfile">${ICON.cloud}<b>${S.receipt ? "Replace receipt" : "Tap to upload receipt"}</b><span>JPG or PNG, up to 10 MB</span></label>
        <div class="form" style="margin-top:18px">
          <div class="field"><label for="pm">Paid with</label><select id="pm"><option${S.payMethod === "GCash" ? " selected" : ""}>GCash</option><option${S.payMethod === "BPI" ? " selected" : ""}>BPI</option></select></div>
          <div class="field"><label for="ref">Reference no. (optional)</label><input id="ref" value="${esc(S.payRef)}" inputmode="numeric"></div>
        </div>
      </div>
      <div class="receipt-prev" id="rprev">${S.receipt ? `<img src="${S.receipt}" alt="Your receipt">` : "Your receipt will show here"}</div>
    </div>${navHTML()}`;
  $("#pm").onchange = e => S.payMethod = e.target.value;
  $("#ref").oninput = e => S.payRef = e.target.value;
  const take = async f => {
    if (!f) return; if (f.size > 10 * 1024 * 1024) return toast("That file is over 10 MB. Try a screenshot instead.");
    try { S.receipt = (await shrink(f, 1400, .85)).src; $("#rprev").innerHTML = `<img src="${S.receipt}" alt="Your receipt">`; $("#rdrop b").textContent = "Replace receipt"; }
    catch (e) { toast("Couldn't open that file. Upload a JPG or PNG screenshot."); }
  };
  $("#rfile").onchange = e => take(e.target.files[0]);
  const dz = $("#rdrop");
  ["dragenter", "dragover"].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.add("over"); }));
  ["dragleave", "drop"].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.remove("over"); }));
  dz.addEventListener("drop", e => take(e.dataTransfer.files[0]));
  bindNav(() => S.receipt ? "" : "Upload your payment receipt to continue.");
}

/* Step — review + submit */
function stepReview(p) {
  const d = S.d, n = cartPhotos();
  p.innerHTML = `<h2>Review and submit</h2><p class="sub">Check everything once more. We start making your order after we confirm your payment.</p>
    <div class="review-sec"><h3>Items <button class="linkbtn" data-to="0">Edit</button></h3>
      ${CART.map(it => `<p>${esc(itemName(it))} · ${itemLine(it)}${it.photos.length ? ` · ${it.photos.length} photo${it.photos.length > 1 ? "s" : ""}` : ""}</p>`).join("")}
      ${n ? `<label class="hint" style="display:flex;gap:8px;align-items:flex-start;margin-top:10px"><input type="checkbox" id="hqc"${S.hq ? " checked" : ""}> <span>Also send my original full-quality photos (recommended; uses more mobile data)</span></label>` : ""}</div>
    <div class="review-sec"><h3>Deliver to <button class="linkbtn" data-to="1">Edit</button></h3>
      <p><b style="color:var(--ink)">${esc(d.name)}</b> · ${esc(d.phone)}${d.email ? " · " + esc(d.email) : ""}<br>${esc(d.address)}, ${esc(d.city)}, ${esc(d.province)} ${esc(d.postal)}</p></div>
    <div class="review-sec"><h3>Payment <button class="linkbtn" data-to="3">Edit</button></h3><p>${esc(S.payMethod)}${S.payRef ? " · Ref. " + esc(S.payRef) : ""} · receipt attached</p></div>
    ${navHTML("Submit order")}`;
  $("#hqc")?.addEventListener("change", e => { S.hq = e.target.checked; });
  $$("[data-to]").forEach(b => b.onclick = () => go(+b.dataset.to));
  bindNav(() => cartProblems()[0] || "", submitOrder);
}
async function submitOrder() {
  const btn = $("#next"); btn.disabled = true;
  try {
    const fd = new FormData();
    Object.entries({ payMethod: S.payMethod, payRef: S.payRef, coupon: COUPON?.code || "" }).forEach(([k, v]) => fd.append(k, v));
    Object.entries(S.d).forEach(([k, v]) => fd.append(`customer[${k}]`, v));
    fd.append("receipt", await (await fetch(S.receipt)).blob(), "receipt.jpg");
    const c = document.createElement("canvas"), ctx = c.getContext("2d"), total = cartPhotos();
    await loadTextFonts(CART.flatMap(it => it.photos)); // Text must print in its own font.
    let done = 0;
    for (const [n, it] of CART.entries()) {
      const d = PRODUCTS[it.product];
      fd.append(`items[${n}][product]`, it.product); fd.append(`items[${n}][color]`, it.color || ""); fd.append(`items[${n}][qty]`, it.qty); fd.append(`items[${n}][copies]`, it.copies || 1); if (it.feature) fd.append(`items[${n}][feature]`, "1");
      for (const ph of it.photos) {
        btn.innerHTML = `<span class="spinner"></span> Preparing photo ${++done} of ${total}…`;
        // Crop from the original's full resolution (crop values are relative, so framing matches the preview).
        const src = await createImageBitmap(ph.file).catch(async () => (await shrink(ph.file)).img);
        const [W, H] = outSize(ph, d); c.width = W; c.height = H; drawCrop(ctx, src, { ...ph.crop }, W, H);
        src.close?.();
        fd.append(`items[${n}][photos][]`, c.toDataURL("image/jpeg", .95)); // Text fields, not files: PHP caps file uploads at 20 per request by default.
      }
    }
    btn.innerHTML = `<span class="spinner"></span> Sending…`;
    const originals = S.hq ? CART.flatMap(it => it.photos.map(ph => ph.file)) : []; // Same order as the server's photo list.
    const order = await api("orders", { method: "POST", body: fd });
    ls("tt-last", order.id);
    await cartStore.clear().catch(() => {});
    CART = []; setCartCount(); COUPON = null; ls("tt-coupon", ""); ls("tt-note", "");
    renderDone(order, originals);
  } catch (e) {
    btn.disabled = false; btn.textContent = "Submit order";
    toast(e.message || "Your order couldn't be sent. Check your connection and try again.");
  }
}

function renderDone(o, originals = []) {
  app.innerHTML = `<div class="wrap"><div class="done">
    <div class="heart"><svg viewBox="0 0 92 92" width="92" height="92"><circle cx="46" cy="46" r="44" fill="var(--mint)"/><path d="M46 70S22 55.5 22 39.5A12 12 0 0 1 46 34a12 12 0 0 1 24 5.5C70 55.5 46 70 46 70z" fill="var(--rose)"/></svg></div>
    <h2>Thank you!</h2>
    <p>We've received your order. We'll check your payment and message you at <b style="color:var(--ink)">${esc(o.customer.phone)}</b>${o.shipping == null ? " with your shipping fee," : ""} once it's approved and again when it ships.</p>
    <div class="ordno"><span>Order number</span><strong>${o.id}</strong><button class="linkbtn" id="cp" style="margin-top:6px">Copy order number</button></div>
    ${o.customer.email ? `<p class="hint">We've emailed a confirmation to <b>${esc(o.customer.email)}</b>.</p>` : ""}
    ${originals.length ? `<p class="note" id="hq" role="status" style="margin:18px auto;max-width:520px"></p>` : ""}
    <div class="summary done-summary"><h3>Order summary</h3>
      ${(o.items || []).map(it => `<div class="srow"><span>${esc(it.name)}<small>${it.qty}${(it.copies || 1) > 1 ? ` × ${it.copies} copies` : ""} × ${peso(it.unit)}</small></span><span>${peso(it.subtotal)}</span></div>`).join("")}
      <div class="srow sline"><span>Subtotal</span><span>${peso(o.subtotal)}</span></div>
      ${o.discount ? `<div class="srow discount"><span>Discount (${esc(o.coupon)})</span><span>−${peso(o.discount)}</span></div>` : ""}
      <div class="srow"><span>Shipping fee</span><span>${o.shipping == null ? "We'll message you" : peso(o.shipping)}</span></div>
      <div class="srow stotal"><span>Paid</span><span>${peso(o.total)}</span></div>
      <div class="srow"><span>Payment</span><span>${esc(o.payMethod)}${o.payRef ? " · Ref. " + esc(o.payRef) : ""}</span></div>
      <div class="srow"><span>Deliver to</span><span>${esc(o.customer.name)}<small>${esc(o.customer.address)}, ${esc(o.customer.city)}, ${esc(o.customer.province)} ${esc(o.customer.postal)}</small></span></div>
    </div>
    <div class="btns"><a class="btn" href="${esc(TT.urls.track)}?order=${encodeURIComponent(o.id)}">Track my order</a><a class="btn ghost" href="${esc(TT.urls.home)}">Back to home</a></div>
  </div></div>`;
  $("#cp").onclick = () => copy(o.id);
  window.scrollTo(0, 0);
  if (originals.length) uploadOriginals(o, originals);
}
/* Send the untouched original files one by one (inc/orders.php: POST orders/{id}/originals).
   The order is already placed; this only adds full-quality copies for the shop. */
async function uploadOriginals(o, files) {
  const box = $("#hq"), say = html => { if (box) box.innerHTML = html; };
  const todo = files.map((f, i) => [f, i]).filter(([f]) => f && f.size <= TT.maxOriginal);
  const keepOpen = e => { e.preventDefault(); e.returnValue = ""; };
  window.addEventListener("beforeunload", keepOpen);
  let sent = 0;
  for (const [n, [f, i]] of todo.entries()) {
    say(`<span class="spinner"></span> Sending your full-quality photos: ${n + 1} of ${todo.length}. Please keep this page open.`);
    for (let tries = 0; tries < 3; tries++) {
      const fd = new FormData(); fd.append("key", o.uploadKey); fd.append("index", i); fd.append("file", f, f.name);
      try { await api(`orders/${encodeURIComponent(o.id)}/originals`, { method: "POST", body: fd }); sent++; break; }
      catch (e) { if (/expired|already/i.test(e.message)) break; await new Promise(r => setTimeout(r, 2000 * (tries + 1))); }
    }
  }
  window.removeEventListener("beforeunload", keepOpen);
  say(sent === files.length ? `All ${sent} full-quality photos sent. Thank you!` : `${sent} of ${files.length} full-quality photos sent. That's okay: we can print your order from the photos you cropped.`);
}

/* ---------- track ---------- */
const FLOW = [["pending", "Order received", "We're checking your payment."], ["approved", "Payment confirmed", "Your photos are queued for printing."], ["processing", "In production", "We're printing and putting your order together."], ["completed", "Shipped", "Your order is on its way."]];
async function renderTrack(pre) {
  const last = pre || ls("tt-last") || "";
  app.innerHTML = `<div class="wrap"><div class="track">
    <div class="sec-head"><h2>Track your order</h2><p>Enter the order number from your confirmation screen, like TT-2026-0924-0001.</p></div>
    <form id="tf"><input class="input" id="tid" placeholder="Order number" value="${esc(last)}" autocapitalize="characters" aria-label="Order number"><button class="btn">Track</button></form>
    <div id="tres"></div></div></div>`;
  const show = async id => {
    id = id.trim().toUpperCase(); if (!id) return;
    const o = await trackOrder(id), r = $("#tres");
    if (!o) { r.innerHTML = `<p class="note" style="margin-top:22px">No order ${esc(id)} found. Check the number, or message us on Facebook and we'll look it up.</p>`; return; }
    if (o.status === "rejected") { r.innerHTML = `<div class="note" style="margin-top:22px;background:var(--bad-bg)"><b>We couldn't confirm the payment for ${o.id}.</b><br>Message us with your receipt and we'll sort it out.</div>`; return; }
    const at = FLOW.findIndex(f => f[0] === o.status);
    r.innerHTML = `<div class="summary" style="margin-top:22px"><div class="srow"><span>Order</span><span><b>${o.id}</b></span></div><div class="srow"><span>Items</span><span>${esc(o.productName)}</span></div><div class="srow"><span>Total</span><span>${peso(o.total)}</span></div></div>
      <ol class="timeline">${FLOW.map((f, i) => `<li class="${i <= at ? "on" : ""}"><b>${f[1]}</b>${i === at ? f[2] : ""}</li>`).join("")}</ol>`;
  };
  $("#tf").onsubmit = e => { e.preventDefault(); show($("#tid").value); };
  if (last) show(last);
}

/* ---------- admin ---------- */
const A = { tab: "all", q: "" };
const STATUS_LABEL = { pending: "Pending", approved: "Approved", processing: "In production", completed: "Shipped", rejected: "Rejected" };
const fmtDate = t => new Date(t).toLocaleString("en-PH", { month: "short", day: "numeric", year: "numeric", hour: "numeric", minute: "2-digit" });
function renderAdmin() {
  app.innerHTML = `<div class="admin">
    <aside class="side"><a class="logo side-logo" href="${esc(TT.urls.home)}"><img class="brand-logo" src="${IMG.logo}" alt="Tales to Tiles"></a>
      <nav><button aria-current="true">Orders</button><button id="toShop">View shop</button><button id="logout">Log out</button></nav></aside>
    <div class="amain">
      <div class="ahead"><h1>Orders</h1><span style="color:var(--ink-2);font-size:14px">All orders placed on the website</span></div>
      <div class="stats" id="stats"></div>
      <div class="toolbar"><div class="tabs" id="tabs"></div><input class="input" id="q" placeholder="Search order no. or name" value="${esc(A.q)}"></div>
      <div class="tablebox"><table><thead><tr><th>Order #</th><th>Date</th><th>Customer</th><th>Product</th><th>Photos</th><th>Amount</th><th>Payment</th><th>Status</th></tr></thead><tbody id="rows"></tbody></table></div>
      <div class="cards" id="cards"></div>
    </div></div>`;
  $("#toShop").onclick = () => { location.href = TT.urls.home; };
  $("#logout").onclick = () => { location.href = TT.urls.logout; };
  $("#q").oninput = e => { A.q = e.target.value; drawOrders(); };
  drawOrders();
}
async function drawOrders() {
  const all = await allOrders().catch(e => (toast(e.message), []));
  const cnt = s => all.filter(o => o.status === s).length;
  $("#stats").innerHTML = [["To check", cnt("pending")], ["To make", cnt("approved") + cnt("processing")], ["Shipped", cnt("completed")], ["Sales", peso0(all.filter(o => o.status !== "rejected" && o.status !== "pending").reduce((a, o) => a + o.total, 0))]]
    .map(([l, v]) => `<div class="stat"><span>${l}</span><strong>${v}</strong></div>`).join("");
  const tabs = [["all", "All", all.length], ...Object.keys(STATUS_LABEL).map(k => [k, STATUS_LABEL[k], cnt(k)])];
  $("#tabs").innerHTML = tabs.map(([k, l, n]) => `<button class="tab" data-t="${k}" aria-pressed="${A.tab === k}">${l} (${n})</button>`).join("");
  $$("[data-t]").forEach(b => b.onclick = () => { A.tab = b.dataset.t; drawOrders(); });
  const q = A.q.trim().toLowerCase();
  const list = all.filter(o => (A.tab === "all" || o.status === A.tab) && (!q || o.id.toLowerCase().includes(q) || o.customer.name.toLowerCase().includes(q)));
  const badge = s => `<span class="badge b-${s}">${STATUS_LABEL[s]}</span>`;
  const empty = all.length ? "No orders match this filter." : "No orders yet. New orders appear here as soon as they're submitted.";
  $("#rows").innerHTML = list.length ? list.map(o => `<tr data-id="${o.id}" tabindex="0"><td><b>${o.id}</b></td><td>${fmtDate(o.createdAt)}</td><td>${esc(o.customer.name)}<small>${esc(o.customer.phone)}</small></td><td>${esc(o.productName)}</td><td>${o.photos?.length || 0}</td><td>${peso(o.total)}</td><td>${esc(o.payMethod)}</td><td>${badge(o.status)}</td></tr>`).join("") : `<tr><td colspan="8"><div class="empty">${empty}</div></td></tr>`;
  $("#cards").innerHTML = list.length ? list.map(o => `<button class="ocard" data-id="${o.id}"><div class="top"><b>${o.id}</b>${badge(o.status)}</div><p>${esc(o.customer.name)} · ${esc(o.productName)}</p><p>${fmtDate(o.createdAt)} · <b style="color:var(--ink)">${peso(o.total)}</b> via ${esc(o.payMethod)}</p></button>`).join("") : `<div class="empty">${empty}</div>`;
  $$("[data-id]").forEach(el => { el.onclick = () => openOrder(el.dataset.id); el.onkeydown = e => { if (e.key === "Enter") openOrder(el.dataset.id); }; });
}
function modal(html, wide) {
  closeModal();
  const m = document.createElement("div"); m.className = "modal"; m.id = "modal";
  m.innerHTML = `<div class="mbox ${wide ? "wide" : ""}" role="dialog" aria-modal="true">${html}</div>`;
  m.onclick = e => { if (e.target === m) closeModal(); };
  document.body.appendChild(m); document.body.style.overflow = "hidden";
  $$(".close", m).forEach(b => b.onclick = closeModal);
  return m;
}
function closeModal() { $("#modal")?.remove(); document.body.style.overflow = ""; drawTiles?.(); } // drawTiles: the photo editor pop-up edits the preview's photos.
document.addEventListener("keydown", e => { if (e.key === "Escape") closeModal(); });
async function setStatus(o, status) {
  try {
    await api("orders/" + encodeURIComponent(o.id), { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ status }) });
    toast(`${o.id} marked ${STATUS_LABEL[status].toLowerCase()}.`); drawOrders(); openOrder(o.id);
  } catch (e) { toast(e.message); }
}
async function openOrder(id) {
  const o = await getOrder(id); if (!o) return;
  const c = o.customer, items = o.items || [], canPrint = ["approved", "processing", "completed"].includes(o.status);
  const actions = {
    pending: `<button class="btn danger" data-s="rejected">Reject payment</button><button class="btn green" data-s="approved">Approve payment</button>`,
    approved: `<button class="btn" data-s="processing">Mark as in production</button>`,
    processing: `<button class="btn green" data-s="completed">Mark as shipped</button>`,
    completed: "",
    rejected: `<button class="btn ghost" data-s="pending">Move back to pending</button>`
  }[o.status];
  const itemPhotos = it => o.photos.slice(it.photoFrom, it.photoFrom + it.photoCount);
  const label = (it, i) => it.print?.sides ? `#${Math.floor(i / 2) + 1} ${i % 2 ? "back" : "front"}` : `${i + 1}`;
  const photosBox = it => {
    const ps = itemPhotos(it);
    return `<div class="dbox dwide"><h3>${esc(it.name)} × ${it.qty}${(it.copies || 1) > 1 ? ` · print each ${it.copies}×` : ""}${it.feature ? ` <span class="badge b-approved" title="The customer agreed to have this order featured on social media">OK to feature</span>` : ""}${ps.length && canPrint ? ` <button class="btn ghost sm" data-pp="${items.indexOf(it)}">Print layout</button>` : ""}</h3>${ps.length
      ? `<div class="dphotos">${ps.map((p, i) => { const g = it.photoFrom + i; return `<figure><img class="${it.print?.circle ? "round" : ""}" src="${esc(p)}" alt="Photo ${label(it, i)}"><figcaption>${label(it, i)}${o.originals?.[g] ? ` · <a href="${esc(o.originals[g])}" download>Original</a>` : ""}</figcaption></figure>`; }).join("")}</div>`
      : `<p style="color:var(--ink-2)">No photos for this item.</p>`}</div>`;
  };
  const m = modal(`<div class="mhead"><div><h2>${o.id}</h2><span class="badge b-${o.status}" style="margin-top:6px">${STATUS_LABEL[o.status]}</span></div><button class="close" aria-label="Close">×</button></div>
    <div class="dgrid">
      <div class="dbox"><h3>Customer</h3><p><b>${esc(c.name)}</b></p><p>${esc(c.phone)}${c.email ? "<br>" + esc(c.email) : ""}</p><p>${esc(c.address)}<br>${esc(c.city)}, ${esc(c.province)} ${esc(c.postal)}</p>${c.notes ? `<p style="color:var(--ink-2)">Note: ${esc(c.notes)}</p>` : ""}</div>
      <div class="dbox"><h3>Order</h3>
        ${items.map(it => `<div class="srow"><span>${esc(it.name)} × ${it.qty}${(it.copies || 1) > 1 ? ` <b>× ${it.copies} copies</b>` : ""}${it.unit ? ` <small>@ ${peso(it.unit)}</small>` : ""}</span><span>${peso(it.subtotal)}</span></div>`).join("")}
        <div class="srow"><span>Subtotal</span><span>${peso(o.subtotal)}</span></div>${o.discount ? `<div class="srow"><span>Discount (${esc(o.coupon)})</span><span>−${peso(o.discount)}</span></div>` : ""}<div class="srow"><span>Shipping</span><span>${o.shipping == null ? "To quote by location" : peso(o.shipping)}</span></div>
        <div class="srow stotal"><span>Paid</span><span>${peso(o.total)}</span></div><p style="margin-top:8px;color:var(--ink-2);font-size:13.5px">Placed ${fmtDate(o.createdAt)}</p>${o.driveUrl ? `<p style="margin-top:6px"><a href="${esc(o.driveUrl)}" target="_blank" rel="noopener">Open photos in Google Drive</a></p>` : ""}</div>
      <div class="dbox"><h3>Payment · ${esc(o.payMethod)}${o.payRef ? " · Ref. " + esc(o.payRef) : ""}</h3><img class="dreceipt" id="rc" src="${o.receipt}" alt="Payment receipt"></div>
      ${o.photos.length ? `<p class="dwide" style="color:var(--ink-2);font-size:13.5px;margin:0">Photos as cropped by the customer. Full-quality originals: ${Object.keys(o.originals || {}).length} of ${o.photos.length}</p>` : ""}
      ${items.map(photosBox).join("")}
    </div>
    <div class="mactions">${actions}</div>`);
  $$("[data-s]", m).forEach(b => b.onclick = () => setStatus(o, b.dataset.s));
  $("#rc", m).onclick = () => modal(`<div class="mhead"><h2>Receipt · ${o.id}</h2><button class="close" aria-label="Close">×</button></div><img src="${o.receipt}" alt="Receipt" style="width:100%;border-radius:10px">`);
  $$("[data-pp]", m).forEach(b => b.onclick = () => printPreview(o, items[+b.dataset.pp]));
}
async function buildSheets(def, photos) { // def: item.print, saved with the order (inc/orders.php).
  const mm = def.mm || [63.5, 63.5];
  const SW = 2480, SH = 3508, mar = 40, gap = 40, labelH = def.sides ? 64 : 0;
  const imgs = await Promise.all(photos.map(loadImg)), sheets = [];
  const tw = mmPx(mm[0]), th = mmPx(mm[1]);
  const cols = Math.max(1, Math.floor((SW - 2 * mar + gap) / (tw + gap))), rows = Math.max(1, Math.floor((SH - 2 * mar + gap) / (th + gap + labelH))), per = cols * rows;
  const gw = cols * tw + (cols - 1) * gap, gh = rows * (th + labelH) + (rows - 1) * gap, ox = (SW - gw) / 2, oy = (SH - gh) / 2;
  let x;
  imgs.forEach((im, i) => {
    if (i % per === 0) { const c = document.createElement("canvas"); c.width = SW; c.height = SH; x = c.getContext("2d"); x.fillStyle = "#fff"; x.fillRect(0, 0, SW, SH); sheets.push(c); }
    const k = i % per, cx = ox + (k % cols) * (tw + gap), cy = oy + Math.floor(k / cols) * (th + gap + labelH);
    x.save();
    if (def.circle) { x.beginPath(); x.arc(cx + tw / 2, cy + th / 2, tw / 2, 0, Math.PI * 2); x.clip(); }
    if (def.orient && im.width > im.height) { x.translate(cx + tw / 2, cy + th / 2); x.rotate(-Math.PI / 2); x.drawImage(im, -th / 2, -tw / 2, th, tw); }
    else x.drawImage(im, cx, cy, tw, th);
    x.restore();
    x.strokeStyle = "#bdbdbd"; x.lineWidth = 3; x.setLineDash([]);
    if (def.circle) { x.beginPath(); x.arc(cx + tw / 2, cy + th / 2, tw / 2 + 1.5, 0, Math.PI * 2); x.stroke(); }
    else x.strokeRect(cx - 1.5, cy - 1.5, tw + 3, th + 3);
    if (def.sides) { x.fillStyle = "#666"; x.font = "36px sans-serif"; x.textAlign = "center"; x.fillText(`#${Math.floor(i / 2) + 1} ${i % 2 ? "back" : "front"}`, cx + tw / 2, cy + th + 46); }
  });
  return sheets;
}
async function printPreview(o, it) {
  const m = modal(`<div class="mhead"><div><h2>Print preview · ${o.id} · ${esc(it.name)}</h2><p style="margin:4px 0 0;color:var(--ink-2);font-size:14px">A4 sheets at actual size, 300 dpi. Print at 100% / "Actual size".</p></div><button class="close" aria-label="Close">×</button></div>
    <div class="sheets" id="sheets"><p style="color:var(--ink-2)">Laying out sheets…</p></div>
    <div class="mactions"><button class="btn ghost" id="cx">Cancel</button><button class="btn ghost" id="dl">Download sheets (PNG)</button><button class="btn" id="pr">Print</button></div>`, true);
  $("#cx", m).onclick = closeModal;
  const sheets = await buildSheets(it.print || {}, Array.from({ length: it.copies || 1 }, () => o.photos.slice(it.photoFrom, it.photoFrom + it.photoCount)).flat()); // Copies: the whole set again (keeps front/back pairs together).
  $("#sheets", m).innerHTML = "";
  sheets.forEach((c, i) => { const f = document.createElement("figure"); f.appendChild(c); f.insertAdjacentHTML("beforeend", `<figcaption>Sheet ${i + 1} of ${sheets.length}</figcaption>`); $("#sheets", m).appendChild(f); });
  $("#dl", m).onclick = () => sheets.forEach((c, i) => {
    const a = document.createElement("a"); a.href = c.toDataURL("image/png"); a.download = `${o.id}-${it.product}-sheet-${i + 1}.png`; a.click();
  });
  $("#pr", m).onclick = () => {
    $("#printArea").innerHTML = sheets.map(c => `<img src="${c.toDataURL("image/jpeg", .95)}" alt="">`).join("");
    try { window.print(); } catch (e) { toast("Printing is blocked here. Download the sheets and print them instead."); }
  };
}

/* ---------- boot: page templates set TT.view ---------- */
const qs = new URLSearchParams(location.search);
showCartCount();
if (ls("tt-open-cart")) { ls("tt-open-cart", ""); openDrawer(); } // Just added from the builder.
if (TT.view === "product") loadCart().then(() => { PRODUCT_HTML = app.innerHTML; initProductPage(); });
else if (TT.view === "order" && (qs.has("product") || qs.has("edit"))) (async () => { // Otherwise the Order page is the server-built grid.
  await loadCart();
  const key = qs.get("product") ?? "", saved = CART.find(it => it.id === qs.get("edit"));
  if (saved) {
    app.innerHTML = `<div class="wrap empty-cart"><p><span class="spinner dark"></span> Loading your photos…</p></div>`;
    const open = {}; // Same id = same photo used several times: open it once and share it.
    const photos = await Promise.all(saved.photos.map(ph => open[ph.id] ??= shrink(ph.file).then(r => ({ ...ph, ...r })).catch(() => null)));
    IT = { ...saved, active: 0, photos: photos.filter(Boolean) };
    S.step = PRODUCTS[saved.product].per ? 1 : 0; // Straight to the photo preview.
    if (IT.photos.length < saved.photos.length) toast("Some photos couldn't be opened again. Please add them back.");
  } else if (Object.hasOwn(PRODUCTS, key)) {
    if (CART.length >= MAX_ITEMS) { toast(`Your cart is full (${MAX_ITEMS} items). Check out first, then start a new order.`); location.href = TT.urls.order; return; }
    IT = newItem(key);
  } else { location.href = TT.urls.order; return; } // Unknown product or cart item.
  render = renderBuilder; renderBuilder();
})();
else if (TT.view === "cart") loadCart().then(() => {
  render = renderCheckout;
  if (location.hash === "#checkout" && CART.length && !cartProblems().length) S.step = 1; // From the slide-in cart's Checkout button.
  renderCheckout();
});
else if (TT.view === "track") renderTrack(qs.get("order"));
else if (TT.view === "admin") renderAdmin();
