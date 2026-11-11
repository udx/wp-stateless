<?php
/** Standalone regression checks: php tests/connect-provision-job.php */
namespace wpCloud\StatelessMedia { trait Singleton {} }
namespace {
define('DB_NAME','test');
define('MINUTE_IN_SECONDS',60);
function is_admin() { return true; }
function is_multisite() { return $GLOBALS['multi'] ?? false; }
function is_network_admin() { return $GLOBALS['network'] ?? false; }
function get_current_network_id() { return 1; }
function get_current_blog_id() { return $GLOBALS['blog'] ?? 1; }
function get_current_user_id() { return $GLOBALS['uid'] ?? 7; }
function current_user_can($cap) { return $GLOBALS['allowed'] ?? true; }
function check_ajax_referer($action, $field) { if (!($GLOBALS['nonce_ok'] ?? true)) throw new \RuntimeException('bad nonce'); }
function sanitize_text_field($s) { return strip_tags($s); }
function wp_unslash($s) { return $s; }
function wp_json_encode($v) { return json_encode($v); }
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
class Reply extends \RuntimeException { public $ok, $data, $status; function __construct($ok,$data,$status) {$this->ok=$ok;$this->data=$data;$this->status=$status;} }
class Database {
 // $locking: 'real' connection-owned locks, 'none' (functions unavailable), 'unverifiable' (GET_LOCK answers 1, ownership cannot be checked).
 public $base_prefix='wp_', $rows=[], $race=false, $locks=[], $connection=1, $locking='real', $lock_requests=0;
 function prepare($sql,...$args) { return [$sql,$args]; }
 function suppress_errors($suppress=true) { return false; }
 function get_var($q) {
  [$sql,$a]=$q; $key=$a[0];
  if(strpos($sql,'SELECT GET_LOCK')===0) {$this->lock_requests++;if($this->locking==='none')return null;if($this->locking==='unverifiable')return '1';if(isset($this->locks[$key]) && $this->locks[$key]!==$this->connection)return '0';$this->locks[$key]=$this->connection;return '1';}
  if(strpos($sql,'SELECT RELEASE_LOCK')===0) {if($this->locking!=='real')return null;if(($this->locks[$key]??null)!==$this->connection)return '0';unset($this->locks[$key]);return '1';}
  if(strpos($sql,'SELECT IS_USED_LOCK')===0) {if($this->locking!=='real')return null;return ($this->locks[$key]??null)===$this->connection?'1':'0';}
  return $this->rows[$key] ?? null;
 }
 function query($q) {
  [$sql,$a]=$q;
  if ($this->race) return 0;
  if (strpos($sql,'INSERT IGNORE')===0) {if(isset($this->rows[$a[0]]))return 0;$this->rows[$a[0]]=$a[1];return 1;}
  if (($this->rows[$a[1]]??null)!==$a[2])return 0;
  $this->rows[$a[1]]=$a[0];return 1;
 }
}
$wpdb=new Database;
require dirname(__DIR__).'/lib/classes/class-google-connect.php';
$r=new \ReflectionClass('wpCloud\\StatelessMedia\\GoogleConnect');
$c=$r->newInstanceWithoutConstructor();
function invoke($name,...$args) {global $r,$c;$m=$r->getMethod($name);$m->setAccessible(true);return $m->invokeArgs($c,$args);}
function clear_job() {global $r,$c;invoke('release_provision_lock');$p=$r->getProperty('provision_job');$p->setAccessible(true);$p->setValue($c,null);}
function assert_true($v,$message) {if(!$v)throw new \RuntimeException($message);echo "PASS $message\n";}
function reply($call) {try{$call();}catch(Reply $r){return $r;}throw new \RuntimeException('Expected response');}
$_POST=['sm_gc_job'=>str_repeat('a',32)]; $_GET=[];
invoke('begin_provision_job');
assert_true(count($wpdb->rows)===1,'first submission acquires scope lock');
$wpdb->connection=2;$x=reply(fn()=>invoke('begin_provision_job'));$wpdb->connection=1;
assert_true($x->status===409,'duplicate running submission rejected');
$_POST['sm_gc_job']=str_repeat('b',32);
$wpdb->connection=2;$x=reply(fn()=>invoke('begin_provision_job'));$wpdb->connection=1;
assert_true($x->status===409,'different submission cannot overlap');
$wpdb->connection=2; $x=reply(fn()=>invoke('ajax_provision_status')); $wpdb->connection=1;
assert_true($x->data['id']===str_repeat('a',32),'reload discovers existing running attempt');
$GLOBALS['uid']=8;
$wpdb->connection=2; $x=reply(fn()=>invoke('ajax_provision_status')); $wpdb->connection=1;
assert_true($x->data===['status'=>'busy'],'another administrator cannot read attempt details');
$GLOBALS['uid']=7;
invoke('provision_job_write',['status'=>'complete','url'=>'/complete']); clear_job();
$_POST['sm_gc_job']=str_repeat('a',32);
$wpdb->connection=2;$x=reply(fn()=>invoke('begin_provision_job'));$wpdb->connection=1;
assert_true($x->status===409,'completed request cannot replay');$wpdb->connection=2;invoke('release_provision_lock');$wpdb->connection=1;
$_GET=['job'=>str_repeat('a',32)];
$wpdb->connection=2; $x=reply(fn()=>invoke('ajax_provision_status')); $wpdb->connection=1;
assert_true($x->data['status']==='complete','matching client recovers completed response');
$_GET=[];
$wpdb->connection=2; $x=reply(fn()=>invoke('ajax_provision_status')); $wpdb->connection=1;
assert_true($x->data['status']==='none','ordinary reload does not redirect to old completion');
$_POST['sm_gc_job']=str_repeat('b',32); invoke('begin_provision_job');
assert_true(json_decode(current($wpdb->rows),true)['id']===str_repeat('b',32),'new attempt can replace finished record');
$GLOBALS['blog']=2; invoke('begin_provision_job');
assert_true(count($wpdb->rows)===2,'site locks are isolated');
$GLOBALS['multi']=true; $GLOBALS['network']=true; invoke('begin_provision_job');
assert_true(count($wpdb->rows)===3,'network lock is separate from site locks');
clear_job(); $GLOBALS['blog']=3; $GLOBALS['network']=false;$wpdb->race=true;
$wpdb->connection=2;$x=reply(fn()=>invoke('begin_provision_job'));$wpdb->connection=1;
assert_true($x->status===409,'failed atomic acquisition stops provisioning');
$wpdb->race=false;$GLOBALS['blog']=4;$_POST['sm_gc_job']=str_repeat('c',32);invoke('begin_provision_job');
$wpdb->locks=[]; // A killed process closes its MySQL session.
clear_job();
$_GET=['job'=>str_repeat('c',32)];
$wpdb->connection=2;$x=reply(fn()=>invoke('ajax_provision_status'));$wpdb->connection=1;
assert_true($x->data['status']==='interrupted','dead worker becomes resumable without waiting for expiry');
$_POST['sm_gc_job']=str_repeat('d',32);invoke('begin_provision_job');
assert_true(json_decode($wpdb->rows['sm_gc_job_site_4'],true)['status']==='running','new worker acquires abandoned scope');
$wpdb->locks=[];
try {invoke('assert_provision_lock');throw new \RuntimeException('lost lock accepted');}catch(\RuntimeException $e){assert_true(strpos($e->getMessage(),'lost its database')!==false,'reconnected worker cannot continue after losing lock');}
clear_job();
$GLOBALS['allowed']=false;
$wpdb->connection=2; $x=reply(fn()=>invoke('ajax_provision_status')); $wpdb->connection=1;
assert_true($x->status===403,'status rejects unauthorized users');
$GLOBALS['allowed']=true;$GLOBALS['nonce_ok']=false;
try {invoke('ajax_provision_status');throw new \RuntimeException('Nonce bypass');}catch(\RuntimeException $e){assert_true($e->getMessage()==='bad nonce','status validates nonce');}
$GLOBALS['nonce_ok']=true;
// An idle status poll must not touch the lock, or it could make a submission that arrives with it look like a duplicate.
$GLOBALS['blog']=5;$_GET=[];$before=$wpdb->lock_requests;
$wpdb->connection=2;$x=reply(fn()=>invoke('ajax_provision_status'));$wpdb->connection=1;
assert_true($x->data['status']==='none' && $wpdb->lock_requests===$before,'idle status poll leaves the lock alone');
$_POST['sm_gc_job']=str_repeat('f',32);invoke('begin_provision_job');invoke('provision_job_write',['status'=>'complete','url'=>'/done']);clear_job();
$before=$wpdb->lock_requests;
$wpdb->connection=2;$x=reply(fn()=>invoke('ajax_provision_status'));$wpdb->connection=1;
assert_true($wpdb->lock_requests===$before,'finished job is reported without taking the lock');
// Databases without connection-owned locks (SQLite, some clusters) fall back to the job record.
$wpdb->locking='none';$GLOBALS['blog']=6;$_POST['sm_gc_job']=str_repeat('1',32);invoke('begin_provision_job');
$row=json_decode($wpdb->rows['sm_gc_job_site_6'],true);
assert_true($row['status']==='running' && $row['worker_lock']===false,'setup starts on a database without locks');
invoke('assert_provision_lock');
assert_true(true,'lockless worker is not stopped by the ownership check');
clear_job();
$_POST['sm_gc_job']=str_repeat('2',32);$x=reply(fn()=>invoke('begin_provision_job'));
assert_true($x->status===409 && json_decode($wpdb->rows['sm_gc_job_site_6'],true)['id']===str_repeat('1',32),'job record alone refuses an overlapping submission');
$_GET=['job'=>str_repeat('1',32)];$x=reply(fn()=>invoke('ajax_provision_status'));
assert_true($x->data['status']==='running','silent lockless job is not declared dead early');
$row['updated']=time()-901;$wpdb->rows['sm_gc_job_site_6']=json_encode($row);
$x=reply(fn()=>invoke('ajax_provision_status'));
assert_true($x->data['status']==='interrupted','lockless job resumes after the stale period');
$_POST['sm_gc_job']=str_repeat('3',32);invoke('begin_provision_job');
assert_true(json_decode($wpdb->rows['sm_gc_job_site_6'],true)['id']===str_repeat('3',32),'new worker takes over a stale lockless job');
clear_job();
$wpdb->locking='unverifiable';$GLOBALS['blog']=7;$_POST['sm_gc_job']=str_repeat('4',32);invoke('begin_provision_job');
assert_true(json_decode($wpdb->rows['sm_gc_job_site_7'],true)['worker_lock']===false,'lock that cannot be verified is not trusted');
clear_job();
// Without locks the job record is the guard: a live worker keeps it fresh, a replaced worker stops.
$wpdb->locking='none';$GLOBALS['blog']=9;$_POST['sm_gc_job']=str_repeat('6',32);invoke('begin_provision_job');
$row=json_decode($wpdb->rows['sm_gc_job_site_9'],true);$row['updated']=time()-120;$wpdb->rows['sm_gc_job_site_9']=json_encode($row);
invoke('assert_provision_lock');
assert_true(time()-json_decode($wpdb->rows['sm_gc_job_site_9'],true)['updated']<5,'live lockless worker refreshes its record');
$row=json_decode($wpdb->rows['sm_gc_job_site_9'],true);$row['id']=str_repeat('7',32);$wpdb->rows['sm_gc_job_site_9']=json_encode($row);
try {invoke('assert_provision_lock');throw new \RuntimeException('replaced worker continued');}catch(\RuntimeException $e){assert_true(strpos($e->getMessage(),'took over')!==false,'replaced lockless worker stops before touching Google again');}
clear_job();$wpdb->locking='real';
// A worker that held a real lock is never declared dead by age while the lock is still held.
$GLOBALS['blog']=8;$_POST['sm_gc_job']=str_repeat('5',32);invoke('begin_provision_job');
$row=json_decode($wpdb->rows['sm_gc_job_site_8'],true);$row['updated']=time()-5000;$wpdb->rows['sm_gc_job_site_8']=json_encode($row);
$_GET=['job'=>str_repeat('5',32)];
$wpdb->connection=2;$x=reply(fn()=>invoke('ajax_provision_status'));$wpdb->connection=1;
assert_true($x->data['status']==='running','slow worker that still holds the lock stays running');
clear_job();
// A failed setup keeps its explanation on the screen until the next action; the log of a finished setup is shown once.
$GLOBALS['blog']=10;$_GET=[];$_POST=['sm_gc_job'=>str_repeat('8',32)];invoke('begin_provision_job');
invoke('state_set','log',['Project ready','Stopped: That bucket name is taken'],600,7);
$x=reply(fn()=>invoke('fail','That bucket name is taken'));
assert_true($x->ok && json_decode($wpdb->rows['sm_gc_job_site_10'],true)['status']==='failed','failed setup is recorded on its job');
clear_job();
[$log,$error]=invoke('take_result',7);
assert_true($error==='That bucket name is taken' && count($log)===2,'failed setup shows its explanation and the steps before it');
[$log,$error]=invoke('take_result',7);
assert_true($error==='That bucket name is taken' && count($log)===2,'reload keeps the explanation of a failed setup');
$_POST['sm_gc_job']=str_repeat('9',32);invoke('begin_provision_job');invoke('clear_result');
[$log,$error]=invoke('take_result',7);
assert_true($log===false && $error===false,'next action clears the previous failure');
invoke('state_set','error','A storage setup is still running.',600,7); // a disconnect refused in another tab meanwhile
invoke('state_set','log',['Plugin configured'],600,7);
$x=reply(fn()=>invoke('done',['sm_gc'=>'provisioned']));
assert_true(json_decode($wpdb->rows['sm_gc_job_site_10'],true)['status']==='complete','finished setup is recorded on its job');
clear_job();
[$log,$error]=invoke('take_result',7);
assert_true($error===false && $log===['Plugin configured'],'finished setup shows its log without an earlier failure');
[$log,$error]=invoke('take_result',7);
assert_true($log===false && $error===false,'log of a finished setup is shown once');
invoke('state_set','error','Old failure',-1,7);
[$log,$error]=invoke('take_result',7);
assert_true($error===false,'expired explanation is not shown');
// Keys an ended setup left behind: its own key when that never went live, otherwise the key it had already replaced.
$key=fn($id,$email='site@project.iam')=>['private_key_id'=>$id,'client_email'=>$email];
$saved=fn($issued,$previous)=>['bucket'=>'b','sa_email'=>'site@project.iam','key'=>json_encode($issued),'previous'=>$previous];
$left=invoke('setup_leftover_keys',$saved($key('K1'),$key('K0')),$key('K0'));
assert_true(count($left)===1 && $left[0]['id']==='K1' && $left[0]['kind']==='unused','setup key that never went live is revoked; the key in use is not');
$left=invoke('setup_leftover_keys',$saved($key('K1'),$key('K0')),$key('K1'));
assert_true(count($left)===1 && $left[0]['id']==='K0' && $left[0]['kind']==='replaced','key replaced by an interrupted setup is revoked');
$left=invoke('setup_leftover_keys',$saved($key('K1'),$key('K0','hand-made@other.iam')),$key('K1'));
assert_true($left===[],'replaced key of another service account is left alone');
$left=invoke('setup_leftover_keys',$saved($key('K1'),null),$key('K1'));
assert_true($left===[],'first setup that went live leaves nothing behind');
$left=invoke('setup_leftover_keys',$saved($key('K1'),$key('K0')),$key('KX'));
assert_true(count($left)===1 && $left[0]['id']==='K1','key that was replaced by hand is not revoked on the setup\'s behalf');
assert_true(invoke('setup_leftover_keys',null,$key('K1'))===[] && invoke('setup_leftover_keys',['key'=>'not json'],$key('K1'))===[],'unreadable saved setup yields no keys');
}
