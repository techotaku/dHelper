<?php

/**
 * Minimal sample router
 */
class Router {
  public $controller = '';
  public $action = '';
  public $params = '';
  public $querystring = '';

  const defaultController = 'Welcome';
  const defaultAction = 'index';

  public function __construct() {
    $uri = explode('?', ltrim($_SERVER['REQUEST_URI'], '/'), 2);
    $route_uri = explode('/', $uri[0], 3);

    $this->controller = ucfirst(strtolower($route_uri[0]));
    if (isset($route_uri[1])) $this->action = strtolower($route_uri[1]);
    if (isset($route_uri[2])) $this->params = $route_uri[2];
    if (isset($uri[1])) $this->querystring = $uri[1];

    if ($this->controller == '') $this->controller = self::defaultController;
    if ($this->action == '') $this->action = self::defaultAction;
  }
}

/**
 * Minimal sample base controller
 */
class Controller {
  protected $route;

  public static function getController($route) {
    $controller = $route->controller . 'Controller';
    if (class_exists($controller)) {
      return new $controller($route);
    } else {
      exit('Specified controller <b><i>'.$route->controller.'</i></b> not found.');
    }    
  }

  public function __construct($route) {
    $this->route = $route;
  }

  public function run() {
    $this->before();
    $method = $this->route->action;
    if (method_exists($this, $method)) {
      $this->$method();
    } else {
      exit('Specified action <b><i>'.$this->route->action.'</i></b> not found.');
    }  
    $this->after();
  }

  protected function before() {
  }

  protected function after() {
  }
}

/**
 * Welcome sample controller
 */
class WelcomeController extends Controller {
  protected $tpl;
  
  protected function before() {
    parent::before();

    require_once './lib/Savant3.php';
    $this->tpl = new Savant3();
  }

  protected function index() {
    $this->tpl->display('./tpl/index.tpl.php');
  }
}

/**
 * Wechat sample controller
 */
class WechatController extends Controller {
  protected $req;

  protected function before() {
    parent::before();

    require_once './lib/Wechat.php';
    require_once './config.php';

    $wechat = new Wechat(TOKEN);
    $this->req = $wechat->getRequest();

    if ($this->req != NULL) {

      switch ($this->req->getRequestType()) {
        case WechatRequestType::unknown:
          $this->route->action = 'unknown';
          break;
        case WechatRequestType::subscribe:
          $this->route->action = 'subscribe';
          break;
        case WechatRequestType::unsubscribe:
          $this->route->action = 'unsubscribe';
          break;
        case WechatRequestType::text:
          $this->route->action = 'text';
          break;
        case WechatRequestType::image:
          $this->route->action = 'image';
          break;
        case WechatRequestType::location:
          $this->route->action = 'location';
          break;
        case WechatRequestType::link:
          $this->route->action = 'link';
          break;
        default:
          $this->route->action = 'index';
          break;
      }
    } else {
      $this->route->action = 'index';
    }
    
  }

  protected function index() {
    echo 'Wechat service works.';
  }

  protected function unknown() {
    $this->req->responseText('我们已经记录了您发送的消息[' . $this->req->item('msgtype') . ']。');
  }

  protected function subscribe() {
    $this->req->responseText('欢迎关注【豆瓣查】微信公众账号！回复 帮助 或者 help 或者中英文问号可获得帮助信息。');
  }

  protected function unsubscribe() {
    // 「悄悄的我走了，正如我悄悄的来；我挥一挥衣袖，不带走一片云彩。」
  }

  protected function text() {
    $this->req->responseText('收到了文字消息：' . $this->req->item('content'));
  }

  protected function image() {
    $picurl = $this->req->item('picurl');
    $items = array(
        new WechatNewsResponseItem('标题一', '这是一篇非常短的图文消息。', $picurl, $picurl),
        new WechatNewsResponseItem('标题二', '非常短的图文消息二。', $picurl, $picurl),
    );
    $this->req->responseNews($items);
  }

  protected function location() {
    $this->req->responseText('收到了位置消息：' . $this->req->item('location_x') . '，' . $this->req->item('location_y'));
  }

  protected function link() {
    $this->req->responseText('收到了链接：' . $this->req->item('url'));
  }
}

$route = new Router;
$controller = Controller::getController($route);
$controller->run();

?>