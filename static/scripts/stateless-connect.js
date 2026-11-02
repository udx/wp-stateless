(function () {
  'use strict';
  const form = document.getElementById('sm-gc-provision');
  if (!form) return;
  const draft = window.smConnectPicker.draft;
  if (draft) Object.entries(draft).forEach(([key,value]) => {
    const field = form.elements.namedItem(key);
    if (field && !(key === 'mode' && form.querySelector('[name=mode][value=cdn]').disabled)) field.value = value;
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
      status.textContent = buckets.length ? buckets.length + ' buckets loaded.' + (next ? ' Load more to search additional buckets.' : '') : 'No buckets found in this project. Create a new bucket or enter a known name below.';
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
    form.querySelectorAll('.sm-gc-new-bucket').forEach(r => r.hidden = !newBucket);
    document.getElementById('new_project_name').required = isNew;
    picker.hidden = newBucket;
    billing.hidden = isNew || !project.value; billingLink.hidden = isNew || !project.value;
    const currentChoice = newBucket ? 'new' : 'existing';
    if (currentChoice !== bucketChoice) { names[bucketChoice] = name.value; name.value = names[currentChoice]; bucketChoice = currentChoice; renderBuckets(); }
    go.value = newBucket ? 'Create bucket and connect this site' : 'Connect this bucket';
    go.disabled = !project.value || isNew && !form.querySelector('[name=billing_account]').value;
    document.getElementById('sm-bucket-help').textContent = newBucket ? 'Names must be globally unique. Use lowercase letters, numbers, dashes or underscores.' : 'Choose above, or enter the exact name if you cannot list buckets. Its location stays unchanged.';
    if (project.value && !newBucket && loadedProject !== project.value) load(false);
  }
  async function changeProject(clearName) {
    generation++; loading = false; buckets = []; next = ''; loadedProject = ''; search.value = '';
    if (clearName) { names.existing = ''; if (bucketChoice === 'existing') name.value = ''; }
    renderBuckets(); sync();
    if (!project.value || project.value === '__new__') return;
    const version = generation;
    billing.textContent = 'Checking project billing…';
    try {
      const data = await request('sm_gc_project_billing', {project:project.value});
      if (version !== generation) return;
      billing.textContent = data.enabled ? 'Billing is enabled for this project' + (data.account ? ' (' + data.account.replace('billingAccounts/', '') + ')' : '') + '.' : 'Billing is not enabled. Enable it in Google Cloud before creating storage.';
    } catch(error) { if (version === generation) billing.textContent = error.message; }
  }
  project.addEventListener('change', () => changeProject(true));
  form.querySelectorAll('[name=bucket_choice]').forEach(c => c.addEventListener('change',sync));
  select.addEventListener('change', () => { if (select.value) name.value = select.value; renderBuckets(); });
  name.addEventListener('input',renderBuckets);
  search.addEventListener('input',renderBuckets);
  more.addEventListener('click', () => load(true)); retry.addEventListener('click', () => load(!!next));
  form.addEventListener('submit', () => { go.value = 'Connecting storage, please wait…'; go.classList.add('disabled'); });
  changeProject(false);
})();
