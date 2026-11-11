// Run with node tests/connect-provision-browser.cjs.
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = fs.readFileSync(__dirname + '/../static/scripts/stateless-connect.js', 'utf8').split('// Keep the document alive')[1];
async function scenario({formPresent=true, saved='', responses=[]}={}) {
  const requests=[], timers=[], listeners={}, store=new Map(saved ? [['stateless-provision:/wp-admin/upload.php:0',saved]]:[]);
  const node=()=>({hidden:false,setAttribute(){},append(){},scrollIntoView(){}});
  const form={action:{value:'sm_gc_provision'},getAttribute:()=>'/wp-admin/admin-post.php',querySelectorAll:()=>[],addEventListener:(event,fn)=>listeners[event]=fn};
  const location={pathname:'/wp-admin/upload.php',assign:url=>location.destination=url,reload:()=>location.reloaded=true};
  const context={URL,Uint8Array,crypto:require('node:crypto').webcrypto,location,sessionStorage:{getItem:k=>store.get(k),setItem:(k,v)=>store.set(k,v),removeItem:k=>store.delete(k)},document:{getElementById:()=>formPresent?form:null,createElement:node,querySelector:()=>({after(){}})},FormData:class{set(){}},fetch:async(url,options)=>{requests.push({url:String(url),options});return {ok:true,json:async()=>({success:true,data:responses.shift()||{status:'none'}})};}};
  context.window={smConnectPicker:{url:'https://example.test/wp-admin/admin-ajax.php?sm_gc_network=0',nonce:'nonce'},setTimeout:fn=>timers.push(fn)};
  vm.runInNewContext('// Keep the document alive'+source,context);
  await new Promise(setImmediate);
  return {requests,timers,listeners,location,store};
}
(async()=>{
  let s=await scenario();
  s.listeners.submit({preventDefault(){}});s.listeners.submit({preventDefault(){}});
  await new Promise(setImmediate);
  assert.equal(s.requests.filter(r=>r.options.method==='POST').length,1);
  assert.equal(s.requests.find(r=>r.options.method==='POST').url,'/wp-admin/admin-post.php');
  console.log('PASS one POST uses action attribute despite named action input');
  s=await scenario({formPresent:false,saved:'attempt',responses:[{id:'attempt',status:'complete',url:'/ready'}]});
  assert.equal(s.location.destination,'/ready');assert.equal(s.store.size,0);
  console.log('PASS completed attempt recovers after reload without storage form');
  s=await scenario({responses:[{status:'busy'},{status:'none'}]});
  await s.timers.shift()();
  assert.equal(s.location.reloaded,true);
  console.log('PASS other administrator completion refreshes blocked form');
})().catch(error=>{console.error(error);process.exitCode=1;});
