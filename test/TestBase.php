<?php
/**
 * 豆瓣查： 豆瓣影音书微信速查入口
 *
 * @author     Ian Li <i@techotaku.net>
 * @copyright  Ian Li <i@techotaku.net>, All rights reserved.
 * @link       http://dbcha.techotaku.net/
 */

  require __DIR__ . '/../config.php';

  /**
   * Test Base
   */
  abstract class DbChaTestBase extends PHPUnit_Framework_TestCase {
    protected $token;
    protected $timestamp;
    protected $nonce;
    protected $signature;

    protected $url;

    protected $toUser;
    protected $fromUser;
    protected $time;
    protected $msgid;

    protected $response;

    protected $CURL_OPTS = array(
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT        => 60,
                CURLOPT_USERAGENT      => 'dbcha.techotaku.net',
                );

    protected function setUp() {
      $this->response = array();
      $this->token = TOKEN;

      $this->timestamp = (string) time();
      $this->nonce = (string) rand(10000000, 99999999);
      $signatureArray = array($this->token, $this->timestamp, $this->nonce);
      sort($signatureArray);
      $this->signature = sha1(implode($signatureArray));

      $this->url = APP_URL_ROOT . '/wechat?';
      $this->url .= "timestamp=$this->timestamp";
      $this->url .= "&nonce=$this->nonce";
      $this->url .= "&signature=$this->signature";

      $this->toUser = 'mp.weixin';
      $this->fromUser = 'fromUser';
      $this->time = time();
      $this->msgid = '1234567890123456';
    }

    protected function removeTime($content) {
      return preg_replace('#<CreateTime>(.+)</CreateTime>#', '<CreateTime></CreateTime>', $content);
    }

    /**
     * make curl request
     *
     * @param string $url
     * @param string $type
     * @param array $header
     * @param array $data
     *
     * @return object
     */
    protected function curl($url, $type, $header, $data = array())
    {
        $opts = $this->CURL_OPTS;
        $opts[CURLOPT_URL] = $url;
        $opts[CURLOPT_CUSTOMREQUEST] = $type;
        $opts[CURLOPT_HTTPHEADER] = $header;
        if ($type == 'POST' || $type == 'PUT') {
            $opts[CURLOPT_POSTFIELDS] = $data;
        }

        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $result = 'CURL error: ' . curl_error($ch);
        }
        curl_close($ch);  
        return $result;
    }

    protected function makeTextMsg($param) {
      return "<xml>
  <ToUserName><![CDATA[$this->toUser]]></ToUserName>
  <FromUserName><![CDATA[$this->fromUser]]></FromUserName> 
  <CreateTime>$this->time</CreateTime>
  <MsgType><![CDATA[text]]></MsgType>
  <Content><![CDATA[$param]]></Content>
  <MsgId>$this->msgid</MsgId>
</xml>";
    }

    protected function makeLocationMsg($x, $y) {
      return "<xml>
  <ToUserName><![CDATA[$this->toUser]]></ToUserName>
  <FromUserName><![CDATA[$this->fromUser]]></FromUserName>
  <CreateTime>$this->time</CreateTime>
  <MsgType><![CDATA[location]]></MsgType>
  <Location_X>$x</Location_X>
  <Location_Y>$y</Location_Y>
  <Scale>20</Scale>
  <Label><![CDATA[位置信息]]></Label>
  <MsgId>$this->msgid</MsgId>
</xml>";
    }

    protected function makeUnknown($param) {
      return "<xml>
  <ToUserName><![CDATA[$this->toUser]]></ToUserName>
  <FromUserName><![CDATA[$this->fromUser]]></FromUserName>
  <CreateTime>$this->time</CreateTime>
  <MsgType><![CDATA[who-knowns]]></MsgType>
  <Unknown><![CDATA[$param]]></Unknown>
  <MsgId>$this->msgid</MsgId>
</xml>";
    }

    protected function makeEvent($event, $eventKey = '') {
      return "<xml>
  <ToUserName><![CDATA[$this->toUser]]></ToUserName>
  <FromUserName><![CDATA[$this->fromUser]]></FromUserName>
  <CreateTime>$this->time</CreateTime>
  <MsgType><![CDATA[event]]></MsgType>
  <Event><![CDATA[$event]]></Event>
  <EventKey><![CDATA[$eventKey]]></EventKey>
  <MsgId>$this->msgid</MsgId>
</xml>";
    }

  }
?>