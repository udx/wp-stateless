<?php
/**
 * Standalone regression checks: php tests/connect-provision-job.php
 *
 * WordPress is replaced by the few functions below. Options live in arrays, WP_Upgrader's lock behaves like
 * core's (one claim per name, taken over once it is older than its timeout), and $wpdb only answers the three
 * named-lock functions, per connection. A request is one object of the class on one database connection.
 */
namespace wpCloud\StatelessMedia { trait Singleton {} }
namespace {
define('DB_NAME','test');
define('MINUTE_IN_SECONDS',60);
define('DAY_IN_SECONDS',86400);
function is_admin() { return true; }
function is_multisite() { return $GLOBALS['multi'] ?? false; }
function is_network_admin() { return $GLOBALS['network'] ?? false; }
function get_current_network_id() { return 1; }
function get_main_site_id() { return $GLOBALS['main_site'] ?? 1; }
function get_current_blog_id() { return $GLOBALS['blog'] ?? 1; }
function switch_to_blog($id) { $GLOBALS['blog_stack'][] = get_current_blog_id(); $GLOBALS['blog'] = $id; }
function restore_current_blog() { $GLOBALS['blog'] = array_pop($GLOBALS['blog_stack']); }
function get_current_user_id() { return $GLOBALS['uid'] ?? 7; }
function current_user_can($cap) { return $GLOBALS['allowed'] ?? true; }
function check_ajax_referer($action, $field) { if (!($GLOBALS['nonce_ok'] ?? true)) throw new \RuntimeException('bad nonce'); }
function sanitize_text_field($s) { return strip_tags($s); }
function wp_unslash($s) { return $s; }
function wp_json_encode($v) { return json_encode($v); }
function wp_generate_uuid4() { return sprintf('%08x-0000-4000-8000-%012x', mt_rand(), mt_rand()); }
function maybe_serialize($v) { return is_array($v) || is_object($v) ? serialize($v) : $v; }
function __($s, $domain = null) { return $s; }
function ud_get_stateless_media() { return (object) ['domain'=>'test']; }
function wp_die($s) { throw new \RuntimeException($s); }
function wp_send_json_error($data, $status = 200) { throw new Reply(false, $data, $status); }
function wp_send_json_success($data) { throw new Reply(true, $data, 200); }
function get_user_meta($uid, $key, $single = false) { return $GLOBALS['meta'][$uid][$key] ?? ''; }
function update_user_meta($uid, $key, $value) { $GLOBALS['meta'][$uid][$key] = $value; return true; }
function delete_user_meta($uid, $key) { unset($GLOBALS['meta'][$uid][$key]); return true; }
function wp_strip_all_tags($s) { return trim(strip_tags($s)); }
function add_query_arg($args, $url) { return $url . '?' . http_build_query($args); }
function admin_url($path = '') { return '/wp-admin/' . $path; }
function network_admin_url($path = '') { return '/wp-admin/network/' . $path; }
// The Options API, per site and per network. As in WordPress, a request answers repeated reads of an option from
// its own copy, so a change made by another request is only seen after wp_cache_delete().
function &request_cache() { $id = $GLOBALS['request'] ?? 0; if (!isset($GLOBALS['cache'][$id])) $GLOBALS['cache'][$id] = []; return $GLOBALS['cache'][$id]; }
function wp_cache_delete($key, $group = '') { $c = &request_cache(); unset($c[($group === 'site-options' ? 'n:' : 'o:' . get_current_blog_id() . ':') . $key]); return true; }
function get_option($k, $d = false) { $b = get_current_blog_id(); $c = &request_cache(); if (!array_key_exists("o:$b:$k", $c)) { $o = $GLOBALS['options'][$b] ?? []; if (!array_key_exists($k, $o)) return $d; $c["o:$b:$k"] = $o[$k]; } return $c["o:$b:$k"]; }
function update_option($k, $v, $autoload = null) { $b = get_current_blog_id(); $GLOBALS['options'][$b][$k] = $v; $c = &request_cache(); $c["o:$b:$k"] = $v; return true; }
function delete_option($k) { $b = get_current_blog_id(); $c = &request_cache(); unset($c["o:$b:$k"]); if (!array_key_exists($k, $GLOBALS['options'][$b] ?? [])) return false; unset($GLOBALS['options'][$b][$k]); return true; }
function get_network_option($n, $k, $d = false) { $c = &request_cache(); if (!array_key_exists("n:$n:$k", $c)) { $o = $GLOBALS['network_options'][$n] ?? []; if (!array_key_exists($k, $o)) return $d; $c["n:$n:$k"] = $o[$k]; } return $c["n:$n:$k"]; }
function update_network_option($n, $k, $v) { $GLOBALS['network_options'][$n][$k] = $v; $c = &request_cache(); $c["n:$n:$k"] = $v; return true; }
function delete_network_option($n, $k) { $c = &request_cache(); unset($c["n:$n:$k"]); if (!array_key_exists($k, $GLOBALS['network_options'][$n] ?? [])) return false; unset($GLOBALS['network_options'][$n][$k]); return true; }
class WP_Error { private $code, $message; function __construct($code = '', $message = '') { $this->code = $code; $this->message = $message; } function get_error_code() { return $this->code; } function get_error_message() { return $this->message; } }
// Core's lock: an option named "<name>.lock" holding the time it was taken.
class WP_Upgrader {
 static function create_lock($name, $timeout = null) {
  if (!empty($GLOBALS['claim_lost'])) return false; // another database node got there first
  $taken = $GLOBALS['options'][get_current_blog_id()][$name . '.lock'] ?? null; // core tries an INSERT IGNORE, straight at the database
  if ($taken && $taken > time() - $timeout) return false;
  return update_option($name . '.lock', time());
 }
 static function release_lock($name) { return delete_option($name . '.lock'); }
}
class Reply extends \RuntimeException { public $ok, $data, $status; function __construct($ok,$data,$status) {$this->ok=$ok;$this->data=$data;$this->status=$status;} }
class Database {
 // $locking: 'real' connection-owned locks, 'none' (functions unavailable), 'unverifiable' (GET_LOCK answers 1, ownership cannot be checked).
 public $base_prefix='wp_', $locks=[], $connection=1, $locking='real', $lock_requests=0;
 function prepare($sql,...$args) { return [$sql,$args]; }
 function suppress_errors($suppress=true) { return false; }
 function get_var($q) {
  [$sql,$a]=$q; $key=$a[0];
  if(strpos($sql,'SELECT GET_LOCK')===0) {if(!empty($GLOBALS['before_lock'])){$f=$GLOBALS['before_lock'];$GLOBALS['before_lock']=null;$f();}$this->lock_requests++;if($this->locking==='none')return null;if($this->locking==='unverifiable')return '1';if(isset($this->locks[$key]) && $this->locks[$key]!==$this->connection)return '0';$this->locks[$key]=$this->connection;return '1';}
  if(strpos($sql,'SELECT RELEASE_LOCK')===0) {if($this->locking!=='real')return null;if(($this->locks[$key]??null)!==$this->connection)return '0';unset($this->locks[$key]);return '1';}
  if(strpos($sql,'SELECT IS_USED_LOCK')===0) {if($this->locking!=='real')return null;return ($this->locks[$key]??null)===$this->connection?'1':'0';}
  throw new \RuntimeException('unexpected query: '.$sql);
 }
}
$wpdb=new Database;
require dirname(__DIR__).'/lib/classes/class-google-connect.php';
$r=new \ReflectionClass('wpCloud\\StatelessMedia\\GoogleConnect');
/** A request: its own object, on its own database connection. */
function request($connection) {global $r;static $seq=0;return [$r->newInstanceWithoutConstructor(),$connection,++$seq];}
function call($request,$name,...$args) {global $r,$wpdb;$before=[$wpdb->connection,$GLOBALS['request']??0];$wpdb->connection=$request[1];$GLOBALS['request']=$request[2];$m=$r->getMethod($name);$m->setAccessible(true);try{return $m->invokeArgs($request[0],$args);}finally{[$wpdb->connection,$GLOBALS['request']]=$before;}}
function reply($request,$name,...$args) {try{call($request,$name,...$args);}catch(Reply $x){return $x;}throw new \RuntimeException('Expected a response from '.$name);}
function stopped($request,$name,...$args) {try{call($request,$name,...$args);}catch(Reply $x){throw new \RuntimeException('Expected '.$name.' to stop');}catch(\RuntimeException $e){return $e->getMessage();}return '';}
/** The request ends normally: its shutdown handler runs. */
function finish($request) {call($request,'release_provision_worker');}
/** The process is killed: nothing of its own runs, the database just drops its connection and with it its lock. */
function kill($request) {global $wpdb;foreach($wpdb->locks as $name=>$owner)if($owner===$request[1])unset($wpdb->locks[$name]);}
function submit($request,$id) {$_POST=['sm_gc_job'=>$id];return call($request,'begin_provision_job');}
/** The setup ends the way the plugin ends it: the job is recorded as complete and the scope given back. */
function complete($request) {return reply($request,'done',['sm_gc'=>'provisioned']);}
/** A submission that is turned away; its request then ends like any other. */
function refused($request,$id) {$_POST=['sm_gc_job'=>$id];$x=reply($request,'begin_provision_job');finish($request);return $x;}
function stored($key,$blog=null) {return $GLOBALS['options'][$blog ?? get_current_blog_id()][$key] ?? null;}
function job($blog=null) {return $GLOBALS['options'][$blog ?? get_current_blog_id()]['sm_gc_job_site_'.($blog ?? get_current_blog_id())] ?? null;}
function claim($blog=null) {return $GLOBALS['options'][$blog ?? get_current_blog_id()]['sm_gc_job_site_'.($blog ?? get_current_blog_id()).'.lock'] ?? null;}
function assert_true($v,$message) {if(!$v)throw new \RuntimeException('FAILED: '.$message);echo "PASS $message\n";}
$A=str_repeat('a',32);$B=str_repeat('b',32);$C=str_repeat('c',32);$_GET=[];

// One setup at a time.
$w1=request(1);submit($w1,$A);
assert_true(job()['status']==='running' && job()['worker_lock']===true && claim(),'first submission takes the scope');
assert_true(refused(request(2),$A)->status===409,'duplicate running submission rejected');
assert_true(refused(request(2),$B)->status===409 && job()['id']===$A,'different submission cannot overlap');
$x=reply(request(2),'ajax_provision_status');
assert_true($x->data['id']===$A && $x->data['status']==='running','reload discovers existing running attempt');
$GLOBALS['uid']=8;$x=reply(request(2),'ajax_provision_status');$GLOBALS['uid']=7;
assert_true($x->data===['status'=>'busy'],'another administrator cannot read attempt details');
complete($w1);
assert_true(job()['status']==='complete' && claim()===null && $wpdb->locks===[],'finished setup gives the scope back');
$x=refused(request(2),$A);
assert_true($x->status===409 && strpos($x->data['message'],'already recorded')!==false,'completed request cannot replay');
$_GET=['job'=>$A];$x=reply(request(3),'ajax_provision_status');
assert_true($x->data['status']==='complete' && strpos($x->data['url'],'sm_gc=provisioned')!==false,'matching client recovers completed response');
$_GET=[];$x=reply(request(3),'ajax_provision_status');
assert_true($x->data['status']==='none','ordinary reload does not redirect to old completion');
$w2=request(4);submit($w2,$B);
assert_true(job()['id']===$B && job()['status']==='running','new attempt can replace finished record');
$GLOBALS['blog']=2;$w3=request(5);submit($w3,$A);
assert_true(job(1)['id']===$B && job(2)['id']===$A,'site scopes are isolated');
finish($w3);$GLOBALS['blog']=1;finish($w2);

// A worker that died is known at once where the database has locks, and its scope is free again.
$GLOBALS['blog']=3;$w=request(10);submit($w,$A);kill($w);
assert_true(job()['status']==='running' && claim(),'killed worker leaves its record and its claim behind');
$_GET=['job'=>$A];$x=reply(request(11),'ajax_provision_status');$_GET=[];
assert_true($x->data['status']==='interrupted' && claim()===null && $wpdb->locks===[],'dead worker becomes resumable without waiting for expiry');
$w=request(12);submit($w,$B);kill($w);
$d=request(14);
assert_true(call($d,'take_scope',2)===true && job()['id']===$B && job()['status']==='interrupted','disconnect that takes over from a dead worker records that it stopped');
call($d,'release_scope');
$w=request(15);submit($w,$A);kill($w);
assert_true(strpos(refused(request(16),$A)->data['message'],'already recorded')!==false && job()['status']==='interrupted','repeated request of a dead worker is not run again and is offered for resume');
$w=request(13);submit($w,$C);
assert_true(job()['id']===$C && job()['status']==='running','new worker takes over the scope of a dead one');
$wpdb->locks=[]; // this worker's database connection was replaced behind its back
assert_true(strpos(stopped($w,'assert_provision_lock'),'lost its database')!==false,'reconnected worker cannot continue after losing lock');
finish($w);
assert_true(claim()!==null,'worker that lost its lock leaves the claim alone: it may be another worker\'s by now');
$GLOBALS['options'][3]=[];
// A slow worker that still holds its lock is never declared dead by age.
$GLOBALS['blog']=4;$w=request(20);submit($w,$A);
$GLOBALS['options'][4]['sm_gc_job_site_4']['started']=time()-5000;
$_GET=['job'=>$A];$x=reply(request(21),'ajax_provision_status');$_GET=[];
assert_true($x->data['status']==='running','slow worker that still holds the lock stays running');
assert_true(refused(request(22),$B)->status===409,'and keeps its scope');
call($w,'assert_provision_lock');
assert_true(true,'and is not stopped by the clock');
finish($w);
// Where the claim cannot be made although the lock was free, the setup does not start.
$GLOBALS['blog']=5;$GLOBALS['claim_lost']=true;
assert_true(refused(request(30),$A)->status===409 && job()===null && $wpdb->locks===[],'failed claim stops the setup and frees the lock');
$GLOBALS['claim_lost']=false;

// A status check that arrives just as the setup finishes must not turn the finished job into an interrupted one:
// what it read before waiting for the lock is out of date by the time it has the lock.
$GLOBALS['blog']=12;$w=request(90);submit($w,$A);
$GLOBALS['before_lock']=function() use ($w) {complete($w);};
$_GET=['job'=>$A];$x=reply(request(91),'ajax_provision_status');$_GET=[];
assert_true($x->data['status']==='complete' && job()['status']==='complete','status check that crosses the end of a setup reports it as finished');
$GLOBALS['blog']=5;

// Status endpoint.
$GLOBALS['allowed']=false;
assert_true(reply(request(31),'ajax_provision_status')->status===403,'status rejects unauthorized users');
$GLOBALS['allowed']=true;$GLOBALS['nonce_ok']=false;
assert_true(stopped(request(31),'ajax_provision_status')==='bad nonce','status validates nonce');
$GLOBALS['nonce_ok']=true;
// An idle status poll must not touch the lock, or it could make a submission that arrives with it look like a duplicate.
$before=$wpdb->lock_requests;$x=reply(request(31),'ajax_provision_status');
assert_true($x->data['status']==='none' && $wpdb->lock_requests===$before,'idle status poll leaves the lock alone');
$w=request(32);submit($w,$A);complete($w);
$before=$wpdb->lock_requests;reply(request(31),'ajax_provision_status');
assert_true($wpdb->lock_requests===$before,'finished job is reported without taking the lock');

// Databases without connection-owned locks (SQLite, some clusters): the claim alone guards the scope and runs out with time.
$wpdb->locking='none';$GLOBALS['blog']=6;$w=request(40);submit($w,$A);
assert_true(job()['status']==='running' && job()['worker_lock']===false && claim(),'setup starts on a database without locks');
call($w,'assert_provision_lock');
assert_true(true,'lockless worker is not stopped by the ownership check');
assert_true(refused(request(41),$B)->status===409 && job()['id']===$A,'claim alone refuses an overlapping submission');
$_GET=['job'=>$A];$x=reply(request(42),'ajax_provision_status');
assert_true($x->data['status']==='running','silent lockless job is not declared dead early');
kill($w); // nothing tells the database apart from a slow worker here
$GLOBALS['options'][6]['sm_gc_job_site_6']['started']=time()-901;$GLOBALS['options'][6]['sm_gc_job_site_6.lock']=time()-901;
$x=reply(request(42),'ajax_provision_status');$_GET=[];
assert_true($x->data['status']==='interrupted','lockless job is resumable once its claim has run out');
$w=request(43);submit($w,$B);
assert_true(job()['id']===$B && job()['status']==='running','new worker takes over a claim that ran out');
// A worker whose scope was taken over stops before it touches Google again; one about to run out stops and hands the scope back.
$GLOBALS['options'][6]['sm_gc_job_site_6']['id']=$C;
assert_true(strpos(stopped($w,'assert_provision_lock'),'took over')!==false,'replaced lockless worker stops');
finish($w);
assert_true(job()['id']===$C && claim()!==null,'and leaves the record and the claim of its successor alone');
$GLOBALS['options'][6]=[];
$w=request(44);submit($w,$A);$GLOBALS['options'][6]['sm_gc_job_site_6']['started']=time()-(900-179);
assert_true(strpos(stopped($w,'assert_provision_lock'),'ran out of time')!==false,'lockless worker stops before its claim runs out');
finish($w);
assert_true(job()['status']==='interrupted' && claim()===null,'and hands the scope back for an immediate resume');
$wpdb->locking='unverifiable';$GLOBALS['blog']=7;$w=request(45);submit($w,$A);
assert_true(job()['worker_lock']===false,'lock that cannot be verified is not trusted');
finish($w);$wpdb->locking='real';

// A disconnect takes the scope the same way, with or without database locks, and gives it back when its request ends.
foreach (['real'=>8,'none'=>9] as $mode=>$blog) {
 $wpdb->locking=$mode;$GLOBALS['blog']=$blog;
 $d=request(50);
 assert_true(call($d,'take_scope',2)===true,"disconnect takes the scope ($mode locks)");
 assert_true(refused(request(51),$A)->status===409 && job()===null,"setup cannot start while a disconnect is under way ($mode locks)");
 assert_true(call(request(52),'take_scope',0)===false,"second disconnect is refused meanwhile ($mode locks)");
 call($d,'release_scope');
 $w=request(53);submit($w,$A);
 assert_true(job()['status']==='running',"setup starts once the disconnect is over ($mode locks)");
 assert_true(call(request(54),'take_scope',0)===false,"disconnect is refused while a setup runs ($mode locks)");
 finish($w);
}
$wpdb->locking='real';
// A disconnect is held to the same ownership check as a setup worker, and gives up only a claim that is still its own.
function since($request,$seconds) {global $r;$p=$r->getProperty('scope_since');$p->setAccessible(true);$p->setValue($request[0],time()-$seconds);}
$GLOBALS['blog']=13;$d=request(55);call($d,'take_scope',2);
call($d,'assert_provision_lock');
assert_true(true,'disconnect that holds its lock goes on');
$wpdb->locks=[]; // its database connection was replaced behind its back
assert_true(strpos(stopped($d,'assert_provision_lock'),'connection was lost')!==false,'disconnect that lost its lock stops');
call($d,'release_scope');
assert_true(claim()!==null,'and leaves the claim alone: it may be a setup\'s by now');
$GLOBALS['options'][13]=[];$wpdb->locking='none';
$d=request(56);call($d,'take_scope',2);call($d,'release_scope');
assert_true(claim()===null,'disconnect without database locks gives its claim back');
$d=request(57);call($d,'take_scope',2);since($d,901); // it was held up until its claim had run out
assert_true(strpos(stopped($d,'assert_provision_lock'),'connection was lost')!==false,'request whose claim ran out stops');
$d=request(58);call($d,'take_scope',2);since($d,901);$GLOBALS['options'][13]['sm_gc_job_site_13.lock']=time()-901;$w=request(59);submit($w,$A); // a setup took the claim over meanwhile
call($d,'release_scope');
assert_true(claim()!==null && job()['id']===$A,'request whose claim ran out does not give up the claim of the setup that took over');
finish($w);
$wpdb->locking='real';

// Where the records live: ordinary options of the site, network options for a network setup, the claim with the main site.
$GLOBALS['multi']=true;$GLOBALS['blog']=3;$GLOBALS['options'][3]=[];$w=request(60);submit($w,$A);
assert_true(isset($GLOBALS['options'][3]['sm_gc_job_site_3'],$GLOBALS['options'][3]['sm_gc_job_site_3.lock']),'site setup on a network keeps record and claim with that site');
finish($w);
$GLOBALS['network']=true;$GLOBALS['main_site']=4;$GLOBALS['options'][4]=[];$w=request(61);submit($w,$A);
assert_true(isset($GLOBALS['network_options'][1]['sm_gc_job_network_1']) && isset($GLOBALS['options'][4]['sm_gc_job_network_1.lock']) && !isset($GLOBALS['options'][3]['sm_gc_job_network_1.lock']) && get_current_blog_id()===3,'network setup keeps its record in network options and its claim with the main site');
assert_true(refused(request(62),$B)->status===409,'network scope refuses a second setup');
$GLOBALS['network']=false;$w2=request(63);submit($w2,$B);
assert_true($GLOBALS['options'][3]['sm_gc_job_site_3']['id']===$B,'network scope is separate from the site scopes');
finish($w2);$GLOBALS['network']=true;finish($w);
$GLOBALS['multi']=false;$GLOBALS['network']=false;$GLOBALS['main_site']=1;

// The outcome of an action stays on the screen until the next action: a failure with the steps before it, a finished setup with its steps.
$GLOBALS['blog']=10;$w=request(70);submit($w,$A);
call($w,'state_set','log',['Project ready','Stopped: That bucket name is taken'],600,7);
$x=reply($w,'fail','That bucket name is taken',true);
assert_true($x->ok && job()['status']==='failed' && claim()===null,'failed setup is recorded on its job and frees the scope');
[$log,$error]=call(request(71),'last_result',7);
assert_true($error==='That bucket name is taken' && count($log)===2,'failed setup shows its explanation and the steps before it');
[$log,$error]=call(request(71),'last_result',7);
assert_true($error==='That bucket name is taken' && count($log)===2,'reload keeps the explanation of a failed setup');
$w=request(72);submit($w,$B);call($w,'clear_result');
[$log,$error]=call($w,'last_result',7);
assert_true($log===false && $error===false,'next action clears the previous failure');
call($w,'state_set','error','A storage setup is still running.',600,7); // a disconnect refused in another tab meanwhile
call($w,'state_set','log',['Plugin configured'],600,7);
$x=reply($w,'done',['sm_gc'=>'provisioned']);
assert_true(job()['status']==='complete','finished setup is recorded on its job');
[$log,$error]=call(request(73),'last_result',7);
assert_true($error===false && $log===['Plugin configured'],'finished setup shows its steps without an earlier failure');
[$log,$error]=call(request(73),'last_result',7);
assert_true($error===false && $log===['Plugin configured'],'reload keeps the steps of a finished setup');
$w=request(74);submit($w,$C);$x=reply($w,'fail','A storage setup is still running.');
[$log,$error]=call(request(75),'last_result',7);
assert_true($error==='A storage setup is still running.' && $log===false,'failure of another action does not appear above the steps of a finished setup');
call(request(75),'state_set','log',['Stopped: an earlier setup'],600,7);
$w=request(76);submit($w,$A);$x=reply($w,'done',['sm_gc'=>'billing-ready']);
[$log,$error]=call(request(77),'last_result',7);
assert_true($log===false && $error===false,'action that ends well leaves neither a failure nor the steps of another action');
call(request(77),'state_set','error','Old failure',-1,7);
[$log,$error]=call(request(77),'last_result',7);
assert_true($error===false,'expired explanation is not shown');

// Keys an ended setup left behind: its own key when that never went live, otherwise the key it had already replaced.
$any=request(80);
$key=fn($id,$email='site@project.iam')=>['private_key_id'=>$id,'client_email'=>$email];
$saved=fn($issued,$previous)=>['bucket'=>'b','sa_email'=>'site@project.iam','key'=>json_encode($issued),'previous'=>$previous];
$left=call($any,'setup_leftover_keys',$saved($key('K1'),$key('K0')),$key('K0'));
assert_true(count($left)===1 && $left[0]['id']==='K1' && $left[0]['kind']==='unused','setup key that never went live is revoked; the key in use is not');
$left=call($any,'setup_leftover_keys',$saved($key('K1'),$key('K0')),$key('K1'));
assert_true(count($left)===1 && $left[0]['id']==='K0' && $left[0]['kind']==='replaced','key replaced by an interrupted setup is revoked');
$left=call($any,'setup_leftover_keys',$saved($key('K1'),$key('K0','hand-made@other.iam')),$key('K1'));
assert_true($left===[],'replaced key of another service account is left alone');
$left=call($any,'setup_leftover_keys',$saved($key('K1'),null),$key('K1'));
assert_true($left===[],'first setup that went live leaves nothing behind');
$left=call($any,'setup_leftover_keys',$saved($key('K1'),$key('K0')),$key('KX'));
assert_true(count($left)===1 && $left[0]['id']==='K1','key that was replaced by hand is not revoked on the setup\'s behalf');
assert_true(call($any,'setup_leftover_keys',null,$key('K1'))===[] && call($any,'setup_leftover_keys',['key'=>'not json'],$key('K1'))===[],'unreadable saved setup yields no keys');
// A resumed setup replaces the key the site is configured with now. Only when its own key already went live does it recover the key on record.
assert_true(call($any,'resumed_previous_key',$saved($key('K1'),null),$key('HAND','hand-made@other.iam'))['private_key_id']==='HAND','configuration entered by hand after a setup stopped is what the resumed setup protects');
assert_true(call($any,'resumed_previous_key',$saved($key('K1'),$key('K0')),$key('HAND'))['private_key_id']==='HAND','key changed by hand after a setup stopped replaces the one on record');
assert_true(call($any,'resumed_previous_key',$saved($key('K1'),$key('K0')),$key('K1'))['private_key_id']==='K0','setup whose key already went live recovers the key it replaced');
assert_true(call($any,'resumed_previous_key',$saved($key('K1'),null),$key('K1'))===null,'first setup whose key went live has no earlier key');
assert_true(call($any,'resumed_previous_key',$saved($key('K1'),$key('K0')),$key('K0'))['private_key_id']==='K0','unchanged configuration resumes as before');
assert_true(call($any,'resumed_previous_key',$saved($key('K1'),$key('K0')),null)===null,'configuration removed by hand leaves nothing to protect or revoke');
// A disconnect waits only for failures that pass.
$err=fn($code)=>new \WP_Error($code,'x');
assert_true(call($any,'worth_retrying',$err('http_request_failed')) && call($any,'worth_retrying',$err('google_503')) && call($any,'worth_retrying',$err('google_500')) && call($any,'worth_retrying',$err('google_429')),'network failures and busy answers from Google are worth another try');
assert_true(!call($any,'worth_retrying',$err('google_403')) && !call($any,'worth_retrying',$err('google_401')) && !call($any,'worth_retrying',$err('google_400')) && !call($any,'worth_retrying',$err('token')) && !call($any,'worth_retrying',$err('not_connected')) && !call($any,'worth_retrying',$err('broker')),'refusals and lost access are not');

// The sign-in attempt on record is an ordinary option; it is only removed while it still is the one that was read.
$GLOBALS['blog']=11;$token=str_repeat('t',32);
$first=['txn'=>str_repeat('1',32),'token'=>$token,'created'=>time()];$second=['txn'=>str_repeat('2',32),'token'=>$token,'created'=>time()];
update_option('sm_gc_funnel',$first);
assert_true(call($any,'funnel_state')===$first,'sign-in attempt on record is read back as saved');
update_option('sm_gc_funnel',$second);
assert_true(call($any,'forget_funnel',0,$first,true)===false && stored('sm_gc_funnel')===$second,'attempt saved meanwhile by another request is kept');
assert_true(call($any,'forget_funnel',0,$second,true)===true && stored('sm_gc_funnel')===null,'attempt that is still the one read is removed');
update_option('sm_gc_funnel',$first);$sync=request(95);$seen=call($sync,'funnel_state');
update_option('sm_gc_funnel',$second); // another request saves a newer attempt while the sync is still running
assert_true(call($sync,'forget_funnel',0,$seen,true)===false && stored('sm_gc_funnel')===$second,'long-running sync does not remove an attempt saved after it started');
delete_option('sm_gc_funnel');
update_option('sm_gc_funnel',['txn'=>'short','token'=>$token,'created'=>time()]);
assert_true(call($any,'funnel_state')===null && stored('sm_gc_funnel')===null,'malformed attempt is dropped when read');
update_option('sm_gc_funnel',['txn'=>str_repeat('3',32),'token'=>$token,'created'=>time()-90000]);
assert_true(call($any,'funnel_state')===null && stored('sm_gc_funnel')===null,'attempt older than a day is dropped when read');
update_option('sm_gc_funnel_u_7',$first);
assert_true(call($any,'funnel_state',0,7)===$first && call($any,'funnel_state')===null,'attempt of one administrator is separate from the one that connected the site');
assert_true(call($any,'forget_funnel',0,null,false,7)===true && stored('sm_gc_funnel_u_7')===null,'attempt is removed outright when asked');
$GLOBALS['multi']=true;update_network_option(1,'sm_gc_funnel',$first);
assert_true(call($any,'funnel_state',1)===$first && call($any,'forget_funnel',1,$first,true)===true && ($GLOBALS['network_options'][1]['sm_gc_funnel'] ?? null)===null,'network attempt lives in network options');
$GLOBALS['multi']=false;

// A bucket whose files are public or private together is made public by adding one reader; everything else stays.
// The reader role allows reading a file by its link and nothing else: the role that also lists the bucket is not used.
$GC='wpCloud\\StatelessMedia\\GoogleConnect';
$reader='roles/storage.legacyObjectReader';
$admin=['role'=>'roles/storage.admin','members'=>['serviceAccount:site@p.iam.gserviceaccount.com']];
$timed=['role'=>$reader,'members'=>['user:a@example.com'],'condition'=>['title'=>'until','expression'=>'request.time < timestamp("2030-01-01T00:00:00Z")']];
$opened=$GC::public_bindings([$admin,$timed]);
assert_true($GC::bucket_is_public($opened) && $opened[0]===$admin && $opened[1]===$timed && $opened[2]===['role'=>$reader,'members'=>['allUsers']],'making a bucket public adds one reader and keeps every other permission');
$opened=$GC::public_bindings([['role'=>$reader,'members'=>['group:team@example.com']]]);
assert_true(count($opened)===1 && $opened[0]['members']===['group:team@example.com','allUsers'],'an existing reader entry gains the public reader instead of a second entry');
$viewers=['role'=>'roles/storage.objectViewer','members'=>['group:team@example.com']];
$opened=$GC::public_bindings([$viewers]);
assert_true($opened[0]===$viewers && $opened[1]===['role'=>$reader,'members'=>['allUsers']],'the public reader is never added to a role that can also list the bucket');
$public=[['role'=>'roles/storage.objectViewer','members'=>['allUsers']]];
assert_true($GC::public_bindings($public)===$public,'a bucket that is already public is left as it is');
$conditional=['role'=>$reader,'members'=>['allUsers'],'condition'=>['title'=>'t','expression'=>'true']];
assert_true(!$GC::bucket_is_public([$conditional]) && $GC::bucket_is_public($GC::public_bindings([$conditional])) && $GC::public_bindings([$conditional])[0]===$conditional,'a conditional public entry does not count as public and is not edited');
assert_true($GC::public_bindings([])===[['role'=>$reader,'members'=>['allUsers']]],'a bucket without permissions on record gets the public reader');
// What a stopped setup reports goes by its code where it has one, so the label does not depend on the site's language.
$stop=fn($message,$code=0)=>new \Exception($message,$code);
assert_true(call($any,'provisioning_reason',$stop('x',1001))==='billing_missing' && call($any,'provisioning_reason',$stop('x',1002))==='public_access' && call($any,'provisioning_reason',$stop('x',1003))==='other','missing billing, a private bucket and a bucket of another project are reported by code');
assert_true(call($any,'provisioning_reason',$stop('Create bucket: backend error'))==='google_api' && call($any,'provisioning_reason',$stop('The bucket name x is already taken.'))==='bucket_taken','other stops keep their labels');
}
