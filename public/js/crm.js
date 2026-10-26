'use strict';
/* Small progressive enhancements on top of server-rendered pages */
const $ = (s, r = document) => r.querySelector(s), $$ = (s, r = document) => [...r.querySelectorAll(s)];
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));

/* ---------- toast ---------- */
function toast(m, k) {
  const t = $('#toast'); if (!t) return;
  t.textContent = m; t.className = k === 'err' ? 'err' : ''; t.style.display = 'block';
  clearTimeout(toast._t); toast._t = setTimeout(() => t.style.display = 'none', k === 'err' ? 5000 : 2600);
}

/* ---------- modals ---------- */
const openModal = sel => { const m = $(sel); if (m) { m.hidden = false; const f = $('textarea,input:not([type=hidden]),select', m); if (f) f.focus(); } };
const closeModals = () => $$('.ov').forEach(m => m.hidden = true);

document.addEventListener('click', e => {
  if (!e.target.closest('#newwrap')) { const m = $('#newmenu'); if (m) m.style.display = 'none'; }
  if (!e.target.closest('#gsw')) { const r = $('#gsr'); if (r) r.style.display = 'none'; }

  const el = e.target.closest('[data-act]'); if (!el) return;
  const act = el.dataset.act;
  if (act === 'newmenu') { const m = $('#newmenu'); m.style.display = m.style.display === 'block' ? 'none' : 'block'; }
  else if (act === 'modal') { e.preventDefault(); openModal(el.dataset.target); }
  else if (act === 'close') { e.preventDefault(); closeModals(); }
  else if (act === 'go') { if (!e.target.closest('a,button,form')) location.href = el.dataset.href; }
  else if (act === 'pw') {
    e.preventDefault();
    const inp = $('input', el.closest('.pw')), show = inp.type === 'password';
    inp.type = show ? 'text' : 'password';
    el.classList.toggle('on', show);
    el.title = el.ariaLabel = show ? 'Hide password' : 'Show password';
  }
  else if (act === 'soc-add') { e.preventDefault(); const tpl = $('#soc-tpl'); $('#soc').insertAdjacentHTML('beforeend', tpl.innerHTML.replaceAll('__i__', Date.now())); }
  else if (act === 'soc-del') { e.preventDefault(); el.closest('.soc-row').remove(); }
  else if (act === 'fu-done') {
    e.preventDefault();
    const m = $('#fuDone'); $('form', m).action = el.dataset.url;
    $('#fuDoneInfo').innerHTML = esc(el.dataset.info);
    $('[name=outcome]', m).value = ''; $('[name=next_due]', m).value = ''; $('[name=next_purpose]', m).value = '';
    openModal('#fuDone');
  }
});

/* confirm before destructive / important submits */
document.addEventListener('submit', e => {
  const msg = e.target.dataset.confirm;
  if (msg && !confirm(msg)) e.preventDefault();
});

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModals(); });

/* ---------- filters auto-submit ---------- */
$$('form.filters').forEach(f => {
  let t;
  f.addEventListener('change', e => { if (e.target.type !== 'search') f.submit(); });
  f.addEventListener('input', e => { if (e.target.type === 'search') { clearTimeout(t); t = setTimeout(() => f.submit(), 450); } });
});
/* keep the cursor at the end of the search box after the page reloads */
$$('form.filters input[type=search]').forEach(i => { if (i.value) { i.focus(); i.setSelectionRange(i.value.length, i.value.length); } });

/* ---------- global search ---------- */
const gs = $('#gs');
if (gs) {
  let t;
  gs.addEventListener('input', () => {
    clearTimeout(t);
    const q = gs.value.trim(), box = $('#gsr');
    if (!q) { box.style.display = 'none'; return; }
    t = setTimeout(async () => {
      try {
        const r = await fetch(gs.dataset.url + '?q=' + encodeURIComponent(q), {headers: {Accept: 'application/json'}});
        const rows = await r.json();
        box.innerHTML = rows.length
          ? rows.map(c => `<div data-act="go" data-href="${esc(c.url)}"><b>${esc(c.name)}</b> <small>${esc(c.type)} · ${esc(c.contact || '')} ${esc(c.phone || '')}</small></div>`).join('')
          : '<div><small>No matches</small></div>';
        box.style.display = 'block';
      } catch (err) { console.error(err); }
    }, 200);
  });
}

/* ---------- dependent selects ---------- */
document.addEventListener('change', e => {
  const t = e.target, form = t.closest('form'); if (!form) return;

  // service category -> service list
  if (t.name === 'category' && t.tagName === 'SELECT' && form.dataset.services) {
    const svc = JSON.parse(form.dataset.services)[t.value] || [], s = $('[name=service]', form);
    s.innerHTML = '<option value="">Select…</option>' + svc.map(v => `<option>${esc(v)}</option>`).join('');
  }

  // company -> its inquiries, and default the agent to the company's agent
  if (t.name === 'company_id' && form.dataset.companies) {
    const c = JSON.parse(form.dataset.companies)[t.value];
    const inq = $('[name=inquiry_id]', form);
    if (inq) inq.innerHTML = '<option value="">— none —</option>' + (c ? c.inquiries : []).map(i => `<option value="${i.id}">${esc(i.label)}</option>`).join('');
    const ag = $('[name=agent]', form);
    if (ag && c && c.agent && [...ag.options].some(o => o.value === c.agent)) ag.value = c.agent;
    const pr = $('[name=proposal_id]', form);
    if (pr) pr.innerHTML = '<option value="">— none —</option>' + (c ? c.proposals : []).map(p => `<option value="${p.id}">${esc(p.label)}</option>`).join('');
  }

  // new invoice: picking a proposal copies its lines and terms (only into an empty invoice)
  if (t.name === 'proposal_id' && form.hasAttribute('data-newinv')) {
    const c = JSON.parse(form.dataset.companies)[$('[name=company_id]', form).value];
    const p = c && c.proposals.find(x => String(x.id) === t.value);
    if (p && !readItems(form).length) {
      $('#its', form).innerHTML = '';
      p.items.forEach(i => addItemRow(form, i));
      $('[name=currency]', form).value = p.currency;
      $('[name=tax_pct]', form).value = p.tax_pct;
      $('[name=discount]', form).value = p.discount;
      if (!$('[name=terms]', form).value && p.terms) $('[name=terms]', form).value = p.terms;
      if (!$('[name=project]', form).value && p.project) $('[name=project]', form).value = p.project;
    }
  }

  if (t.name === 'invoice_id' && form.hasAttribute('data-pay')) payHint(form, true);
  if (form.hasAttribute('data-items')) recalc(form);
});

/* ---------- line items editor (proposals + invoices) ---------- */
const num = v => { const n = parseFloat(v); return isFinite(n) ? n : 0; };
const fmt = (n, cur) => { try { return new Intl.NumberFormat('en-GB', {style: 'currency', currency: cur}).format(n); } catch (e) { return cur + ' ' + n.toFixed(2); } };
const readItems = form => $$('.it-row', form).filter(r => $('.i-s', r).value.trim() || $('.i-d', r).value.trim() || $('.i-p', r).value);

function addItemRow(form, i = {}) {
  const tpl = $('#it-tpl', form).innerHTML.replaceAll('__i__', Date.now() + Math.floor(Math.random() * 1000));
  $('#its', form).insertAdjacentHTML('beforeend', tpl);
  const row = $('#its', form).lastElementChild;
  $('.i-s', row).value = i.service || ''; $('.i-d', row).value = i.desc || '';
  $('.i-q', row).value = i.qty ?? 1; $('.i-p', row).value = i.price ?? '';
}

function recalc(form) {
  const box = $('#tot', form); if (!box) return;
  let sub = 0;
  $$('.it-row', form).forEach(r => { const a = num($('.i-q', r).value) * num($('.i-p', r).value); sub += a; $('.amt', r).textContent = a.toFixed(2); });
  const cur = ($('[name=currency]', form) || {}).value || 'GBP';
  const disc = Math.min(Math.max(num(($('[name=discount]', form) || {}).value), 0), sub);
  const tax = (sub - disc) * num(($('[name=tax_pct]', form) || {}).value) / 100;
  const total = sub - disc + tax;
  let html = `<div>Subtotal <b>${fmt(sub, cur)}</b></div>`
    + (disc ? `<div>Discount <b>− ${fmt(disc, cur)}</b></div>` : '')
    + (tax ? `<div>Tax <b>${fmt(tax, cur)}</b></div>` : '')
    + `<div class="gt">Total <b>${fmt(total, cur)}</b></div>`;
  if (box.dataset.paid !== undefined) {
    const paid = num(box.dataset.paid);
    html += `<div>Paid <b>${fmt(paid, cur)}</b></div><div><b>Balance due</b> <b>${fmt(Math.max(0, total - paid), cur)}</b></div>`;
  }
  box.innerHTML = html;
}

document.addEventListener('input', e => {
  const form = e.target.closest && e.target.closest('form[data-items]');
  if (form) recalc(form);
});
document.addEventListener('click', e => {
  const el = e.target.closest('[data-act="it-add"],[data-act="it-del"]'); if (!el) return;
  const form = el.closest('form');
  if (el.dataset.act === 'it-add') addItemRow(form);
  else if ($$('.it-row', form).length > 1) el.closest('tr').remove();
  recalc(form);
});
$$('form[data-items]').forEach(recalc);

/* ---------- payment form: balance hint, and fill the balance as the amount ---------- */
function payHint(form, fillAmount) {
  const inv = JSON.parse(form.dataset.invoices)[$('[name=invoice_id]', form).value];
  $('#payhint', form).textContent = inv
    ? `Invoice total ${fmt(inv.total, inv.currency)} · already paid ${fmt(inv.paid, inv.currency)} · balance ${fmt(inv.balance, inv.currency)} (${inv.currency})`
    : '';
  if (inv && fillAmount) $('[name=amount]', form).value = inv.balance || '';
}
$$('form[data-pay]').forEach(f => payHint(f, false));

/* ---------- flash message from the server ---------- */
const flash = $('#flash');
if (flash && flash.dataset.msg) toast(flash.dataset.msg, flash.dataset.kind);
