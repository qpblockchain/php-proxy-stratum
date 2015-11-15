<?php
class Stratum {
  private $s = array();
  private $p = array();
  private $o = array();

  public function __construct() {
    $this->o = array(NULL);
    $this->p = array(socket_create(AF_INET, SOCK_STREAM, SOL_TCP));
    socket_set_option($this->p[0], SOL_SOCKET, SO_REUSEADDR, 1);
    socket_bind($this->p[0], 0, 8033) || die('ERROR: Could not bind to address.');
    socket_listen($this->p[0]);
    $this->s = array(socket_create(AF_INET, SOCK_STREAM, SOL_TCP));
    socket_set_option($this->s[0], SOL_SOCKET, SO_REUSEADDR, 1);
    socket_bind($this->s[0], 0, 3333) || die('ERROR: Could not bind to address.');
    socket_listen($this->s[0]);
    set_time_limit(0);
    for (;;) {
      $r = array_merge($this->s, array_filter($this->p));
      if (socket_select($r, $w = NULL, $e = NULL, 0)) $this->x($r);
    }
  }

  public function __destruct() {
    socket_close($this->p[0]);
    socket_close($this->s[0]);
  }

  private function x($r) {
    if (in_array($this->p[0], $r)) {
      $k = socket_accept($this->p[0]);
      socket_getpeername($k , $a);
      if ($a=='127.0.0.1')
        while ($a = @socket_read($k, 2048, PHP_NORMAL_READ))
          socket_write($k, $this->h($a)."\n");
      unset($r[array_search($this->p[0], $r)]);
    }
    if (in_array($this->s[0], $r)) {
      if (($k = count($this->s))<9999) {
        $this->p[] = NULL;
        $this->o[] = new U();
        $this->s[] = socket_accept($this->s[0]);
        $this->l('connected, total: '.$k.'.');
      } else $this->l('ignored, too many.');
      unset($r[array_search($this->s[0], $r)]);
    }
    foreach($r as $_r) {
      $k = ($_k = array_search($_r, $this->s)) ?: array_search($_r, $this->p);
      $_d = $this->o[$k]->d(@socket_read($_r, 2048, PHP_NORMAL_READ));
      if ($_d === FALSE) $this->k($k, $_k.'lost');
      else if ($_k === FALSE) {
        if ($this->s[$k]) {
          $d = json_decode($_d);
          if (isset($d->id) && $d->id && $d->id == $this->o[$k]->s[0]) {
            if (isset($d->result) && isset($d->result[1]) && $d->result[1]) {
              $this->l($k.' gets extranonce ["'.$d->result[1].'", '.$d->result[2].'].');
              socket_write($this->s[$k], '{"params":["'.$d->result[1].'",'.$d->result[2].'],"method":"mining.set_extranonce","id":null}'."\n");
            }
          } else if(!isset($d->method) || $d->method!='client.show_message') {
            $this->l($k.' gets: '.$_d);
            socket_write($this->s[$k], $_d);
          }
        } else $this->k($k, 'lost before server');
      } else {
        $this->l($k.' says: '.$_d);
        if (($d = json_decode($_d)) && isset($d->method)) {
          if ($d->method == 'mining.subscribe') {
            $this->l($k.' gets subscription '.$d->id.'.');
            socket_write($this->s[$k], '{"id":'.$d->id.',"result":[[["mining.set_difficulty","1"],["mining.notify","1"]],"00",4],"error":null}'."\n");
            if (!$this->p[$k]) {
              $this->o[$k]->v = (isset($d->params) && isset($d->params[0]) && $d->params[0]) ? $d->params[0] : 'unknown';
              $this->o[$k]->s = array($d->id, $_d);
            }
          } else if ($d->method == 'mining.authorize') {
            $this->l($k.' gets authorization '.$d->id.'.');
            socket_write($this->s[$k], '{"error":null,"id":'.$d->id.',"result":true}'."\n");
            if (isset($d->params) && isset($d->params[0]) && $d->params[0]) {
              $this->o[$k]->u = $d->params[0];
              if (!$this->p[$k]) {
                $this->p[$k] = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
                if (!($p = $this->o[$k]->c($this->p[$k]))) $this->k($k, 'lost pools');
                else if ($this->o[$k]->s) {
                  $this->l($k.' connected to '.$p[0].':'.$p[1].' as '.$p[2].'.');
                  $this->l('server '.$k.' gets '.$this->o[$k]->s[1]);
                  socket_write($this->p[$k], $this->o[$k]->s[1]);
                  $this->l('server '.$k.' gets '.$this->o[$k]->d($_d));
                  socket_write($this->p[$k], $this->o[$k]->d($_d));
                } else $this->k($k, 'lost subscribe.');
              }
            } else $this->k($k, 'unkown.');
          } else if ($this->p[$k]) {
            $this->l('server '.$k.' gets '.$_d);
            socket_write($this->p[$k], $_d);
          } else $this->k($k, 'lost server');
        } else $this->k($k, 'said garbage');
      }
    }
  }

  private function k($k, $m) {
    unset($this->s[$k], $this->p[$k], $this->o[$k]);
    $this->s = array_values($this->s);
    $this->p = array_values($this->p);
    $this->o = array_values($this->o);
    $this->l($k.' '.$m.', killed.');
  }

  private function h($h) {
    $this->l('HTTP says '.$h);
    $d = array('result'=>NULL);
    if (is_object($h = @json_decode($h)) && isset($h->method))
      switch($h->method) {
        case 'wtfisconnected':
          foreach($this->o as $k => $o) {
            if (!$o) continue;
            if ($o->u)
              $d['result'][] = $o->u.' is fuckin connected with '.$o->v.' to '.$o->P[0].' as '.$o->P[2].'.';
            else $d['result'][] = $k.' is zombie.';
          }
          break;
        case 'switchpool':
          foreach($this->o as $k => $o) {
            if (!$o || $o->u!=$h->params[0]) continue;
            socket_close($this->p[$k]);
            $this->p[$k] = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if (!($p = $o->c($this->p[$k], $h->params[1]))) $this->k($k, 'lost pools');
            else if ($o->s) {
              $this->l($k.' connected to '.$p[0].':'.$p[1].' as '.$p[2].'.');
              $this->l('server '.$k.' gets '.$o->s[1]);
              socket_write($this->p[$k], $o->s[1]);
              $this->l('server '.$k.' gets '.$o->d('{"id": '.($o->s[0]+1).', "method": "mining.authorize", "params": ["'.$p[2].'", "x"]}'));
              socket_write($this->p[$k], $o->d('{"id": '.($o->s[0]+1).', "method": "mining.authorize", "params": ["'.$p[2].'", "x"]}'."\n"));
            } else $this->k($k, 'lost subscribe.');
          }
          break;
      }
    return json_encode($d);
  }

  private function l($m) {
    print date('H:i:s') .': Client '.$m.(strpos($m, "\n")===FALSE ? PHP_EOL : NULL);
  }
}

class U {
  public $v = NULL;
  public $s = NULL;
  public $P = NULL;
  private $p = NULL;

  public function __set($k, $v) {
    $this->$k = $v;
    if ($k=='u') {
      $this->P = array('solo.ckpool.org', 3333, '1CArLeSkmBT1BkkcADtNrHoLSgHVhBcesk');
      $this->p = array(
        'analpaper.3' => array(
          'p' => array(
            array('eu.stratum.bitcoin.cz', 3333, 'analpaper.0'),
            array('stratum.f2pool.com', 3333, 'analpaper.0')
          )
        )
      );
    }
  }

  public function c($p, $_k = 0) {
    if (!isset($this->u)) return FALSE;
    if (isset($this->p[$this->u]) && isset($this->p[$this->u]['p']))
      foreach($this->p[$this->u]['p'] as $k => $_p)
        if ($k<$_k) continue;
        else if (socket_connect($p, $_p[0], $_p[1])) return $this->P = $_p;
    if (socket_connect($p, $this->P[0], $this->P[1])) return $this->P;
    return FALSE;
  }
  public function d($d) {
    return ($d && isset($this->u)) ? strtr($d, array($this->u => $this->P[2])) : $d;
  }
}

new Stratum();