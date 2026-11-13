(function () {
  'use strict';
  const form = document.getElementById('sm-gc-provision');
  if (!form) return;
  const draft = window.smConnectPicker.draft;
  if (draft) Object.entries(draft).forEach(([key,value]) => {
    const field = form.elements.namedItem(key);
    if (!field) return;
    if (key === 'mode') {
      // Restore the mode only when it is an editable radio group. A locked mode is a hidden input and a
      // required mode leaves the other radios disabled; both must keep what the server rendered.
      const radios = Array.from(form.querySelectorAll('[name=mode][type=radio]'));
      if (!radios.length || radios.some(r => r.disabled)) return;
    }
    field.value = value;
  });
  const project = document.getElementById('sm_gc_project');
  const regionSelect = document.getElementById('location');
  const regionGroups = Array.from(regionSelect.querySelectorAll('optgroup')).map(g => g.cloneNode(true));
  const rememberedRegions = {};
  let regionType = (regionGroups.find(g => Array.from(g.children).some(o => o.value === regionSelect.value)) || regionGroups[0]).dataset.type;
  function syncRegions(type) {
    rememberedRegions[regionType] = regionSelect.value;
    regionType = type;
    regionSelect.replaceChildren(regionGroups.find(g => g.dataset.type === type).cloneNode(true));
    if (rememberedRegions[type]) regionSelect.value = rememberedRegions[type];
    form.querySelector('[name=location_type][value=' + type + ']').checked = true;
  }
  syncRegions(regionType);
  form.querySelectorAll('[name=location_type]').forEach(r => r.addEventListener('change', () => syncRegions(r.value)));
  const picker = document.getElementById('sm-bucket-picker');
  const select = document.getElementById('sm-existing-bucket');
  const search = document.getElementById('sm-bucket-search');
  const name = document.getElementById('sm-bucket-name');
  const status = document.getElementById('sm-bucket-status');
  const more = document.getElementById('sm-bucket-more');
  const retry = document.getElementById('sm-bucket-retry');
  const location = document.getElementById('sm-bucket-location');
  const go = document.getElementById('sm-gc-go');
  const access = document.getElementById('sm-bucket-access');
  const makePublic = document.getElementById('sm-bucket-public');
  let bucketChoice = form.querySelector('[name=bucket_choice]:checked').value;
  const names = {new: bucketChoice === 'new' ? name.value : '', existing: bucketChoice === 'existing' ? name.value : ''};
  let buckets = [], next = '', generation = 0, loadedProject = '', loading = false;
  const billing = document.createElement('p');
  billing.className = 'description'; billing.setAttribute('role', 'status');
  project.parentNode.appendChild(billing);
  const billingLink = document.createElement('a');
  billingLink.textContent = 'Manage billing in Google Cloud'; billingLink.href = 'https://console.cloud.google.com/billing'; billingLink.target = '_blank'; billingLink.rel = 'noopener';
  billing.after(billingLink);
  function filterSelect(input, target) {
    const options = Array.from(target.options).map(o => o.cloneNode(true));
    input.addEventListener('input', function () {
      const selected = target.value, query = input.value.trim().toLowerCase();
      target.replaceChildren();
      options.filter(o => o.value === selected || o.value === '__new__' || o.text.toLowerCase().includes(query)).forEach(o => target.appendChild(o.cloneNode(true)));
      target.value = selected;
    });
  }
  form.querySelectorAll('[data-filter]').forEach(input => filterSelect(input, document.getElementById(input.dataset.filter)));
  async function request(action, params) {
    const url = new URL(window.smConnectPicker.url, window.location.href);
    Object.entries(Object.assign({action, nonce: window.smConnectPicker.nonce}, params)).forEach(([k,v]) => url.searchParams.set(k,v));
    const fallback = 'Unable to load Google Cloud data. Please try again.';
    let response, json;
    try {
      response = await fetch(url, {credentials:'same-origin'});
      json = await response.json();
    } catch (error) {
      throw new Error(fallback);
    }
    if (!response.ok || !json || !json.success || !json.data || typeof json.data !== 'object') {
      const message = json && json.data && json.data.message;
      throw new Error(typeof message === 'string' && message ? message : fallback);
    }
    return json.data;
  }
  function renderBuckets() {
    const query = search.value.trim().toLowerCase();
    select.replaceChildren(new Option('Choose a bucket', ''));
    buckets.filter(b => b.name.toLowerCase().includes(query) || b.name === name.value).forEach(b => select.add(new Option(b.name, b.name)));
    select.value = buckets.some(b => b.name === name.value) ? name.value : '';
    const bucket = buckets.find(b => b.name === name.value);
    location.textContent = bucket ? 'Location: ' + bucket.location + ' · ' + bucket.locationType + '. Existing buckets keep their current location.' : '';
    syncAccess();
  }
  // The choice to make a bucket public is offered for an existing bucket that is known to use uniform access,
  // or that the last attempt found private, and only when media would be served from it.
  function syncAccess() {
    const existing = form.querySelector('[name=bucket_choice]:checked').value === 'existing';
    const mode = form.querySelector('[name=mode]:checked') || form.querySelector('[name=mode]');
    const bucket = buckets.find(b => b.name === name.value);
    const offered = existing && !!name.value && (!mode || mode.value !== 'backup') && (bucket ? !!bucket.uniform : access.dataset.requiredFor === name.value);
    access.hidden = !offered;
    makePublic.disabled = !offered;
  }
  async function load(append) {
    if (loading) return;
    const version = generation, id = project.value;
    loading = true; retry.hidden = true; more.hidden = true;
    status.textContent = 'Loading buckets…';
    try {
      const data = await request('sm_gc_buckets', {project:id, page_token:append ? next : ''});
      if (version !== generation) return;
      buckets = append ? buckets.concat(data.buckets) : data.buckets;
      buckets = Array.from(new Map(buckets.map(b => [b.name,b])).values());
      next = data.next; loadedProject = id;
      renderBuckets(); more.hidden = !next;
      status.textContent = buckets.length ? (buckets.length === 1 ? '1 bucket loaded.' : buckets.length + ' buckets loaded.') + (next ? ' Load more to search additional buckets.' : '') : 'No buckets found in this project. Create a new bucket or enter a known name below.';
    } catch (error) {
      if (version !== generation) return;
      status.textContent = error.message + ' You can also enter a known bucket name below.'; retry.hidden = false;
    } finally { if (version === generation) loading = false; }
  }
  function sync() {
    const isNew = project.value === '__new__';
    const existing = form.querySelector('[name=bucket_choice][value=existing]');
    existing.disabled = isNew || !project.value;
    if (isNew) form.querySelector('[name=bucket_choice][value=new]').checked = true;
    const newBucket = form.querySelector('[name=bucket_choice]:checked').value === 'new';
    form.querySelectorAll('.sm-gc-new-project').forEach(r => r.hidden = !isNew);
    // A hidden billing selector must not submit a value: billing is only chosen for a project created here.
    form.querySelectorAll('select[name=billing_account]').forEach(b => b.disabled = !isNew);
    form.querySelectorAll('.sm-gc-new-bucket').forEach(r => r.hidden = !newBucket);
    document.getElementById('new_project_name').required = isNew;
    document.getElementById('new_project_name').disabled = !isNew;
    picker.hidden = newBucket;
    billing.hidden = isNew || !project.value; billingLink.hidden = isNew || !project.value;
    const currentChoice = newBucket ? 'new' : 'existing';
    if (currentChoice !== bucketChoice) { names[bucketChoice] = name.value; name.value = names[currentChoice]; bucketChoice = currentChoice; renderBuckets(); }
    go.value = newBucket ? 'Create my storage' : 'Connect this bucket';
    go.disabled = !project.value || isNew && !form.querySelector('[name=billing_account]').value;
    document.getElementById('sm-bucket-help').textContent = newBucket ? 'Names must be globally unique. Use lowercase letters, numbers, dashes or underscores.' : 'Choose above, or enter the exact name if you cannot list buckets. Its location stays unchanged.';
    syncAccess();
    if (project.value && !newBucket && loadedProject !== project.value) load(false);
  }
  async function changeProject(clearName) {
    generation++; loading = false; buckets = []; next = ''; loadedProject = ''; search.value = '';
    if (clearName) { names.existing = ''; if (bucketChoice === 'existing') name.value = ''; }
    renderBuckets(); sync();
    if (!project.value || project.value === '__new__') return;
    const version = generation;
    // A project this setup asked Google to create cannot be queried yet; submitting resumes it.
    const chosen = project.selectedOptions[0];
    if (chosen && chosen.dataset.pending) { billing.textContent = 'Google is still creating this project. Continue to finish the saved setup.'; return; }
    billing.textContent = 'Checking project billing…';
    try {
      const data = await request('sm_gc_project_billing', {project:project.value});
      if (version !== generation) return;
      billing.textContent = data.enabled ? 'Billing is enabled for this project' + (data.account ? ' (' + data.account.replace('billingAccounts/', '') + ')' : '') + '.' : 'Billing is not enabled. Enable it in Google Cloud before creating storage.';
    } catch(error) { if (version === generation) billing.textContent = error.message; }
  }
  project.addEventListener('change', () => changeProject(true));
  form.querySelectorAll('[name=bucket_choice]').forEach(c => c.addEventListener('change',sync));
  form.querySelectorAll('[name=mode]').forEach(m => m.addEventListener('change',syncAccess));
  // The Create button depends on a billing account for new projects; re-evaluate when one is picked.
  form.querySelectorAll('[name=billing_account]').forEach(b => b.addEventListener('change',sync));
  select.addEventListener('change', () => { if (select.value) name.value = select.value; renderBuckets(); });
  name.addEventListener('input',renderBuckets);
  search.addEventListener('input',renderBuckets);
  more.addEventListener('click', () => load(true)); retry.addEventListener('click', () => load(!!next));
  changeProject(false);
})();

// Keep the document alive while provisioning runs. A proxy timeout is not a failed setup.
(function () {
  'use strict';
  const form = document.getElementById('sm-gc-provision');
  const config = window.smConnectPicker;
  if (!config) return;
  const storageKey = 'stateless-provision:' + location.pathname + ':' + new URL(config.url).searchParams.get('sm_gc_network');
  let job = '', polling = false, misses = 0;
  try { job = sessionStorage.getItem(storageKey) || ''; } catch (_) {}
  const panel = document.createElement('section');
  panel.className = 'sm-connect-provision-progress card';
  panel.hidden = true;
  panel.setAttribute('role', 'status');
  panel.setAttribute('aria-live', 'polite');
  const title = document.createElement('h2');
  title.textContent = 'Connecting your storage';
  const message = document.createElement('p');
  const help = document.createElement('p');
  help.textContent = 'Google can take a few minutes. You can reload this page without starting over.';
  panel.append(title, message, help);
  document.querySelector('.sm-connect-steps').after(panel);
  function remember(value) {
    job = value;
    try { if (value) sessionStorage.setItem(storageKey, value); else sessionStorage.removeItem(storageKey); } catch (_) {}
  }
  function show(text) {
    panel.hidden = false;
    message.textContent = text;
    if (form) form.querySelectorAll('input[type=submit], button[type=submit]').forEach(button => { button.disabled = true; });
  }
  async function poll() {
    if (polling) return;
    polling = true;
    try {
      const url = new URL(config.url);
      url.searchParams.set('action', 'sm_gc_provision_status');
      url.searchParams.set('nonce', config.nonce);
      if (job) url.searchParams.set('job', job);
      const response = await fetch(url, {credentials: 'same-origin', cache: 'no-store'});
      if (!response.ok) throw new Error('Status unavailable');
      const result = await response.json();
      if (!result.success) throw new Error('Status unavailable');
      const data = result.data;
      if (data.status === 'none') {
        if (!job) {
          if (!panel.hidden) location.reload();
          return;
        }
        misses++;
        show('Waiting for the setup request to reach WordPress. Do not submit it again.');
        if (misses > 20) {
          show('WordPress has not confirmed this request. Reload to review your choices before trying again.');
          remember('');
          return;
        }
      } else if (data.status === 'busy') {
        show('Another administrator is connecting storage. Waiting for that setup to finish.');
      } else {
        misses = 0;
        remember(data.id);
        if (data.status === 'complete' || data.status === 'failed') {
          remember('');
          // A reload can land after the setup failed: this page then already shows the explanation, so there
          // is nothing to navigate to.
          if (data.status === 'failed' && panel.hidden && document.querySelector('.sm-connect-result')) return;
          location.assign(data.url);
          return;
        }
        if (data.status === 'interrupted') {
          show(data.message);
          help.textContent = 'Your completed steps are saved. Review them before continuing.';
          const resume = document.createElement('button');
          resume.type = 'button';
          resume.className = 'button button-primary';
          resume.textContent = 'Resume setup';
          resume.addEventListener('click', () => location.reload());
          panel.append(resume);
          remember('');
          return;
        }
        show(data.message);
      }
    } catch (_) {
      if (!job && panel.hidden) return;
      show('Still checking your setup. The connection was interrupted; your setup request has not been repeated.');
    } finally { polling = false; }
    window.setTimeout(poll, 2000);
  }
  if (form) form.addEventListener('submit', event => {
    event.preventDefault();
    if (job || !panel.hidden) return;
    const bytes = new Uint8Array(16);
    crypto.getRandomValues(bytes);
    remember(Array.from(bytes, b => b.toString(16).padStart(2, '0')).join(''));
    const body = new FormData(form);
    body.set('sm_gc_job', job);
    show('Starting your Google Cloud setup.');
    panel.scrollIntoView({block: 'center', behavior: 'smooth'});
    // Do not retry this POST. Poll the persisted result even if the proxy drops its response.
    fetch(form.getAttribute('action'), {method: 'POST', body, credentials: 'same-origin'}).catch(() => {});
    poll();
  });
  poll();
})();
