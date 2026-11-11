<?php
/** Standalone regression checks: php tests/connect-provision-job.php */
namespace wpCloud\StatelessMedia { trait Singleton {} }
namespace {
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
 public $base_prefix='wp_', $rows=[], $race=false;
 function prepare($sql,...$args) { return [$sql,$args]; }
 function get_var($q) { return $this->rows[$q[1][0]] ?? null; }
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
function clear_job() {global $r,$c;$p=$r->getProperty('provision_job');$p->setAccessible(true);$p->setValue($c,null);}
function assert_true($v,$message) {if(!$v)throw new \RuntimeException($message);echo "PASS $message\n";}
function reply($call) {try{$call();}catch(Reply $r){return $r;}throw new \RuntimeException('Expected response');}
$_POST=['sm_gc_job'=>str_repeat('a',32)]; $_GET=[];
invoke('begin_provision_job');
assert_true(count($wpdb->rows)===1,'first submission acquires scope lock');
$x=reply(fn()=>invoke('begin_provision_job'));
assert_true($x->status===409,'duplicate running submission rejected');
$_POST['sm_gc_job']=str_repeat('b',32);
$x=reply(fn()=>invoke('begin_provision_job'));
assert_true($x->status===409,'different submission cannot overlap');
$x=reply(fn()=>invoke('ajax_provision_status'));
assert_true($x->data['id']===str_repeat('a',32),'reload discovers existing running attempt');
$GLOBALS['uid']=8;
$x=reply(fn()=>invoke('ajax_provision_status'));
assert_true($x->data===['status'=>'busy'],'another administrator cannot read attempt details');
$GLOBALS['uid']=7;
invoke('provision_job_write',['status'=>'complete','url'=>'/complete']); clear_job();
$_POST['sm_gc_job']=str_repeat('a',32);
$x=reply(fn()=>invoke('begin_provision_job'));
assert_true($x->status===409,'completed request cannot replay');
$_GET=['job'=>str_repeat('a',32)];
$x=reply(fn()=>invoke('ajax_provision_status'));
assert_true($x->data['status']==='complete','matching client recovers completed response');
$_GET=[];
$x=reply(fn()=>invoke('ajax_provision_status'));
assert_true($x->data['status']==='none','ordinary reload does not redirect to old completion');
$_POST['sm_gc_job']=str_repeat('b',32); invoke('begin_provision_job');
assert_true(json_decode(current($wpdb->rows),true)['id']===str_repeat('b',32),'new attempt can replace finished record');
$GLOBALS['blog']=2; invoke('begin_provision_job');
assert_true(count($wpdb->rows)===2,'site locks are isolated');
$GLOBALS['multi']=true; $GLOBALS['network']=true; invoke('begin_provision_job');
assert_true(count($wpdb->rows)===3,'network lock is separate from site locks');
clear_job(); $GLOBALS['blog']=3; $GLOBALS['network']=false;$wpdb->race=true;
$x=reply(fn()=>invoke('begin_provision_job'));
assert_true($x->status===409,'failed atomic acquisition stops provisioning');
$GLOBALS['allowed']=false;
$x=reply(fn()=>invoke('ajax_provision_status'));
assert_true($x->status===403,'status rejects unauthorized users');
$GLOBALS['allowed']=true;$GLOBALS['nonce_ok']=false;
try {invoke('ajax_provision_status');throw new \RuntimeException('Nonce bypass');}catch(\RuntimeException $e){assert_true($e->getMessage()==='bad nonce','status validates nonce');}
}
