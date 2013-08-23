<?php
/**
 * 微信公众平台 PHP SDK
 *
 * @author NetPuter <netputer@gmail.com>
 */

  /**
   * 微信公众平台处理类
   */
  class Wechat {

    /**
     * Token 通信令牌
     *
     * @var string
     */
    private $token;

    /**
     * 构造函数。保存通信令牌。
     *
     * @param string $token 验证信息
     */
    public function __construct($token) {
      $this->token = $token;
    }

    /**
     * 解析并返回请求对象。
     * 若请求签名无效或无请求数据，则返回NULL。
     * 若此次请求是否为验证请求，则自动处理并返回NULL。
     *
     * @param string $token 验证信息
     */
    public function getRequest() {
      if (!$this->validateSignature($this->token)) {
        return NULL;
      }
      // 网址接入验证
      if (isset($_GET['echostr'])) {          
        echo $_GET['echostr'];
        return NULL;
      }
      if (!isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
        return NULL;
      }
        
      try {
        $xml = (array) simplexml_load_string($GLOBALS['HTTP_RAW_POST_DATA'], 'SimpleXMLElement', LIBXML_NOCDATA);
        // 将数组键名转换为小写，提高健壮性，减少因大小写不同而出现的问题
        $xml = array_change_key_case($xml, CASE_LOWER);
        // 返回解析后的Request
        return new WechatRequest($xml);
      } catch (Exception $e) {
        return NULL;
      }

    }

    /**
     * 验证此次请求的签名信息
     *
     * @param  string $token 验证信息
     * @return boolean
     */
    private function validateSignature($token) {
      if ( ! (isset($_GET['signature']) && isset($_GET['timestamp']) && isset($_GET['nonce']))) {
        return FALSE;
      }
      
      $signature = $_GET['signature'];
      $timestamp = $_GET['timestamp'];
      $nonce = $_GET['nonce'];

      $signatureArray = array($token, $timestamp, $nonce);
      sort($signatureArray);

      return sha1(implode($signatureArray)) == $signature;
    }

  }

  /**
   * 微信公众平台请求信息类
   */
  class WechatRequest {

    /**
     * 以数组的形式保存微信服务器每次发来的请求
     *
     * @var array
     */
    private $request;

    /**
     * 初始化，判断此次请求是否为验证请求，并以数组形式保存
     *
     * @param array $request 解析后的请求信息
     */
    public function __construct($request) {
      $this->request = $request;
    }

    /**
     * 获取本次请求中的参数，不区分大小
     *
     * @param  string $key 参数名
     * @return mixed
     */
    public function item($key) {
      $key = strtolower($key);

      if (isset($this->request[$key])) {
        return $this->request[$key];
      }

      return NULL;
    }

    
    /**
     * 获取请求的类型
     *
     * @return string
     */
    public function getRequestType() {

      switch ($this->item('msgtype')) {

        case 'event':
          switch ($this->item('event')) {

            case 'subscribe':
              return WechatRequestType::subscribe;
              break;
            case 'unsubscribe':
              return WechatRequestType::unsubscribe;
              break;
          }
          break;

        case 'text':
          return WechatRequestType::text;
          break;

        case 'image':
          return WechatRequestType::image;
          break;

        case 'location':
          return WechatRequestType::location;
          break;

        case 'link':
          return WechatRequestType::link;
          break;

        default:
          return WechatRequestType::unknown;
          break;
      }
    }

    /**
     * 回复文本消息
     *
     * @param  string  $content  消息内容
     * @param  integer $funcFlag 默认为0，设为1时星标刚才收到的消息
     * @return void
     */
    public function responseText($content, $funcFlag = 0) {
      exit(new WechatTextResponse($this->item('fromusername'), $this->item('tousername'), $content, $funcFlag));
    }

    /**
     * 回复音乐消息
     *
     * @param  string  $title       音乐标题
     * @param  string  $description 音乐描述
     * @param  string  $musicUrl    音乐链接
     * @param  string  $hqMusicUrl  高质量音乐链接，Wi-Fi 环境下优先使用
     * @param  integer $funcFlag    默认为0，设为1时星标刚才收到的消息
     * @return void
     */
    public function responseMusic($title, $description, $musicUrl, $hqMusicUrl, $funcFlag = 0) {
      exit(new WechatMusicResponse($this->item('fromusername'), $this->item('tousername'), $title, $description, $musicUrl, $hqMusicUrl, $funcFlag));
    }

    /**
     * 回复图文消息
     * @param  array   $items    由单条图文消息类型 NewsResponseItem() 组成的数组
     * @param  integer $funcFlag 默认为0，设为1时星标刚才收到的消息
     * @return void
     */
    public function responseNews($items, $funcFlag = 0) {
      exit(new WechatNewsResponse($this->item('fromusername'), $this->item('tousername'), $items, $funcFlag));
    }
  }

  /**
   * 请求消息类型
   */
  class WechatRequestType {
    const text = 'text';
    const image = 'image';
    const location = 'location';
    const link = 'link';
    const subscribe = 'subscribe';
    const unsubscribe = 'unsubscribe';
    const unknown = 'unknown';
  }

  /**
   * 用于回复的基本消息类型
   */
  abstract class WechatResponse {

    protected $toUserName;
    protected $fromUserName;
    protected $funcFlag;
    protected $template;

    public function __construct($toUserName, $fromUserName, $funcFlag) {
      $this->toUserName = $toUserName;
      $this->fromUserName = $fromUserName;
      $this->funcFlag = $funcFlag;
    }

    abstract public function __toString();

  }

  /**
   * 用于回复的文本消息类型
   */
  class WechatTextResponse extends WechatResponse {

    protected $content;

    public function __construct($toUserName, $fromUserName, $content, $funcFlag = 0) {
      parent::__construct($toUserName, $fromUserName, $funcFlag);

      $this->content = $content;
      $this->template = <<<XML
<xml>
  <ToUserName><![CDATA[%s]]></ToUserName>
  <FromUserName><![CDATA[%s]]></FromUserName>
  <CreateTime>%s</CreateTime>
  <MsgType><![CDATA[text]]></MsgType>
  <Content><![CDATA[%s]]></Content>
  <FuncFlag>%s<FuncFlag>
</xml>
XML;
    }

    public function __toString() {
      return sprintf($this->template,
        $this->toUserName,
        $this->fromUserName,
        time(),
        $this->content,
        $this->funcFlag
      );
    }

  }

  /**
   * 用于回复的音乐消息类型
   */
  class WechatMusicResponse extends WechatResponse {

    protected $title;
    protected $description;
    protected $musicUrl;
    protected $hqMusicUrl;

    public function __construct($toUserName, $fromUserName, $title, $description, $musicUrl, $hqMusicUrl, $funcFlag) {
      parent::__construct($toUserName, $fromUserName, $funcFlag);

      $this->title = $title;
      $this->description = $description;
      $this->musicUrl = $musicUrl;
      $this->hqMusicUrl = $hqMusicUrl;
      $this->template = <<<XML
<xml>
  <ToUserName><![CDATA[%s]]></ToUserName>
  <FromUserName><![CDATA[%s]]></FromUserName>
  <CreateTime>%s</CreateTime>
  <MsgType><![CDATA[music]]></MsgType>
  <Music>
    <Title><![CDATA[%s]]></Title>
    <Description><![CDATA[%s]]></Description>
    <MusicUrl><![CDATA[%s]]></MusicUrl>
    <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
  </Music>
  <FuncFlag>%s<FuncFlag>
</xml>
XML;
    }

    public function __toString() {
      return sprintf($this->template,
        $this->toUserName,
        $this->fromUserName,
        time(),
        $this->title,
        $this->description,
        $this->musicUrl,
        $this->hqMusicUrl,
        $this->funcFlag
      );
    }

  }

  /**
   * 用于回复的图文消息类型
   */
  class WechatNewsResponse extends WechatResponse {

    protected $items = array();

    public function __construct($toUserName, $fromUserName, $items, $funcFlag) {
      parent::__construct($toUserName, $fromUserName, $funcFlag);

      $this->items = $items;
      $this->template = <<<XML
<xml>
  <ToUserName><![CDATA[%s]]></ToUserName>
  <FromUserName><![CDATA[%s]]></FromUserName>
  <CreateTime>%s</CreateTime>
  <MsgType><![CDATA[news]]></MsgType>
  <ArticleCount>%s</ArticleCount>
  <Articles>
    %s
  </Articles>
  <FuncFlag>%s<FuncFlag>
</xml>
XML;
    }

    public function __toString() {
      return sprintf($this->template,
        $this->toUserName,
        $this->fromUserName,
        time(),
        count($this->items),
        implode($this->items),
        $this->funcFlag
      );
    }

  }

  /**
   * 单条图文消息类型
   */
  class WechatNewsResponseItem {

    protected $title;
    protected $description;
    protected $picUrl;
    protected $url;
    protected $template;

    public function __construct($title, $description, $picUrl, $url) {
      $this->title = $title;
      $this->description = $description;
      $this->picUrl = $picUrl;
      $this->url = $url;
      $this->template = <<<XML
<item>
  <Title><![CDATA[%s]]></Title>
  <Description><![CDATA[%s]]></Description>
  <PicUrl><![CDATA[%s]]></PicUrl>
  <Url><![CDATA[%s]]></Url>
</item>
XML;
    }

    public function __toString() {
      return sprintf($this->template,
        $this->title,
        $this->description,
        $this->picUrl,
        $this->url
      );
    }

  }
