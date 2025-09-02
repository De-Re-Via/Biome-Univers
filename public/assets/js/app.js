// helpers
const $  = (s, r=document) => r.querySelector(s);
const $$ = (s, r=document) => Array.from(r.querySelectorAll(s));

// refs principales
const panels        = $$('.tab-panel');
const videoUnivers  = $('#vid-univers');
const videoExplorer = $('#vid-explorer');
const authModal     = $('#authModal');
const aboutModal    = $('#aboutModal');
const legalModal    = $('#legalModal');
const cancelBtn     = $('#auth-cancel-btn'); // bouton "Annuler" de la modale

// état global session
window.__CURRENT_USER__ = window.__CURRENT_USER__ || null;

/* ======================= Onglets ======================= */
function activateTab(name){
  const map = { univers: $('#tab-univers'), explorer: $('#tab-explorer') };
  const btnU = $('#tab-univers-btn');
  const btnE = $('#tab-explorer-btn');

  Object.entries(map).forEach(([k, el]) => el?.classList.toggle('active', k === name));
  btnU?.classList.toggle('active', name === 'univers');
  btnE?.classList.toggle('active', name === 'explorer');

  try {
    if (name === 'univers') { videoExplorer?.pause(); videoUnivers?.play(); }
    else                   { videoUnivers?.pause();  videoExplorer?.play(); }
  } catch {}
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// assure présence .tabs-bar + .user-slot dans les deux onglets
function ensureBarsAndSlots(){
  ['univers', 'explorer'].forEach(name => {
    const panel = document.getElementById(`tab-${name}`);
    if (!panel) return;

    const wrap = panel.querySelector('.tabs-wrap');
    const nav  = panel.querySelector('.tabs');
    if (!wrap || !nav) return;

    // 1) .tabs-bar
    let bar = wrap.querySelector('.tabs-bar');
    if (!bar) {
      bar = document.createElement('div');
      bar.className = 'tabs-bar';
      const underline = wrap.querySelector('.tabs-underline');
      wrap.insertBefore(bar, underline || wrap.firstChild);
    }
    if (nav.parentElement !== bar) bar.appendChild(nav);

    // 2) .user-slot
    if (!bar.querySelector('.user-slot')) {
      const slot = document.createElement('div');
      slot.className = 'user-slot';
      slot.setAttribute('aria-live', 'polite');
      bar.insertBefore(slot, bar.firstChild);
    }
  });
}
function getUserSlots(){
  return document.querySelectorAll('#tab-univers .user-slot, #tab-explorer .user-slot');
}

function setTab(name){
  // verrou : explorer interdit si pas connecté
  if (name === 'explorer' && !window.__CURRENT_USER__) {
    authModal?.showModal();
    activateTab('univers');
    return;
  }
  activateTab(name);
  ensureBarsAndSlots();
  renderUserSlot(window.__CURRENT_USER__);
}

// ✅ DÉLÉGATION de clic : fonctionne pour les 2 barres (IDs dupliqués safe)
document.addEventListener('click', (e) => {
  const btn = e.target.closest('.tabs .tab');
  if (!btn) return;
  const target = btn.dataset.tab;
  if (!target) return;
  e.preventDefault();
  setTab(target);
});

/* ======================= Footer modaux ======================= */
$$('[data-open="about"]').forEach(el => el.addEventListener('click', ()=>aboutModal.showModal()));
$$('[data-open="legal"]').forEach(el => el.addEventListener('click', ()=>legalModal.showModal()));
$$('[data-close="about"]').forEach(el => el.addEventListener('click', ()=>aboutModal.close()));
$$('[data-close="legal"]').forEach(el => el.addEventListener('click', ()=>legalModal.close()));

/* ======================= Démo jauges ======================= */
function demoGauges(rootId){
  const root = document.getElementById(rootId); if (!root) return;
  root.querySelectorAll('.gauge').forEach(g => {
    const val = Math.floor(40 + Math.random()*40);
    g.querySelector('i').style.width = val + '%';
    g.querySelector('em').textContent = val;
  });
}
demoGauges('gauges-world');
demoGauges('gauges-player');

/* ======================= Auth (login/register) ======================= */
const authForm    = authModal?.querySelector('form');
const loginBtn    = authForm?.querySelector('button[value="login"]');
const registerBtn = authForm?.querySelector('button[value="register"]');

function fields() {
  return {
    name:     authForm?.querySelector('input[name="name"]')?.value.trim() || "",
    email:    authForm?.querySelector('input[name="email"]')?.value.trim() || "",
    password: authForm?.querySelector('input[name="password"]')?.value || ""
  };
}
async function callAuth(endpoint, body){
  const res = await fetch(`api/auth/${endpoint}`, {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify(body)
  });
  const txt = await res.text();
  let json; try { json = JSON.parse(txt); } catch { json = { ok:false, error: txt || 'Réponse invalide' }; }
  if (!res.ok) throw new Error(json.error || 'Erreur réseau');
  return json;
}

loginBtn?.addEventListener('click', async (e) => {
  e.preventDefault();
  const { email, password } = fields();
  try {
    const r = await callAuth('login', { email, password });
    if (r.ok) {
      renderUserSlot(r.user);
      alert(`Connecté : ${r.user.name} (${r.user.role})`);
      authModal.close();
      activateTab('explorer'); // aller sur Explorer après succès
    } else {
      alert(r.error || 'Connexion impossible');
    }
  } catch (err) { alert(err.message || 'Connexion impossible'); }
});

registerBtn?.addEventListener('click', async (e) => {
  e.preventDefault();
  const { name, email, password } = fields();
  try {
    const r = await callAuth('register', { name, email, password });
    if (r.ok) {
      renderUserSlot(r.user);
      alert(`Bienvenue ${r.user.name} ! Compte créé.`);
      authModal.close();
      activateTab('explorer'); // aller sur Explorer après succès
    } else {
      alert(r.error || 'Inscription impossible');
    }
  } catch (err) { alert(err.message || 'Inscription impossible'); }
});

/* ======================= User slot (nom + déconnexion) ======================= */
function escapeHtml(s){
  return String(s).replace(/[&<>"']/g, m => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[m]));
}
function roleLabel(role){
  return role === 'explorer_master' ? 'ExplorerMaster'
       : role === 'admin' ? 'Admin'
       : 'Explorer';
}
function renderUserSlot(user){
  window.__CURRENT_USER__ = user || null;
  ensureBarsAndSlots();

  const slots = getUserSlots();
  slots.forEach(slot => {
    if (!user) { slot.innerHTML = ''; return; }
    slot.innerHTML = `
      <span class="id">
        <span class="hello neon">Welcome,</span>
        <span class="role neon">${roleLabel(user.role)}</span>
        <span class="name neon">${escapeHtml(user.name)}</span>
      </span>
      <button class="tab logout-btn">Déconnexion</button>
    `;
  });

  // ✅ Déconnexion : on vide la session ET on revient sur Univers (sans rouvrir la modale)
  $$('#tab-univers .logout-btn, #tab-explorer .logout-btn').forEach(btn => {
    btn.onclick = async () => {
      try {
        await fetch('api/auth/logout', { method:'POST' });
        renderUserSlot(null);
        authModal?.close();
        activateTab('univers');      // redirection Univers
      } catch { alert('Déconnexion impossible'); }
    };
  });
}

/* ======================= Modale : Annuler / ESC ======================= */
if (cancelBtn){
  cancelBtn.type = 'button';
  cancelBtn.addEventListener('click', () => {
    authModal?.close();
    activateTab('univers');
  });
}

// empêche ESC/backdrop de laisser Explorer actif sans user
authModal?.addEventListener('cancel', (e) => {
  if (!window.__CURRENT_USER__) {
    e.preventDefault();
    authModal.close();
    activateTab('univers');
  }
});
authModal?.addEventListener('close', () => {
  const explorerActif = $('#tab-explorer')?.classList.contains('active');
  if (!window.__CURRENT_USER__ && explorerActif) activateTab('univers');
});

/* ======================= Init ======================= */
ensureBarsAndSlots();
(async function initUser(){
  try {
    const r = await fetch('api/auth/me', { method:'GET' });
    const j = await r.json();
    renderUserSlot(j.user || null);
  } catch { renderUserSlot(null); }
})();
