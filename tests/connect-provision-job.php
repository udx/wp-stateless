<?php
/** Standalone regression checks: php tests/connect-provision-job.php */
namespace wpCloud\StatelessMedia { trait Singleton {} }
namespace {
define('DB_NAME','test');
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
class Reply extends \RuntimeException { public $ok, $data, $status; function __construct($ok,$data,$status) {$this->ok=$ok;$this->data=$data;$this->status=$status;} }
class Database {
 public $base_prefix='wp_', $rows=[], $race=false, $locks=[], $connection=1;
 function prepare($sql,...$args) { return [$sql,$args]; }
 function get_var($q) {
  [$sql,$a]=$q; $key=$a[0];
  if(strpos($sql,'SELECT GET_LOCK')===0) {if(isset($this->locks[$key]) && $this->locks[$key]!==$this->connection)return '0';$this->locks[$key]=$this->connection;return '1';}
  if(strpos($sql,'SELECT RELEASE_LOCK')===0) {if(($this->locks[$key]??null)!==$this->connection)return '0';unset($this->locks[$key]);return '1';}
  if(strpos($sql,'SELECT IS_USED_LOCK')===0)return ($this->locks[$key]??null)===$this->connection?'1':'0';
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
}
