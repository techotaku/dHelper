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

  protected function index() {
    echo file_get_contents('./Welcome_index.html');
  }
}

/**
 * Wechat sample controller
 */
class WechatController extends Controller {
  const resError = "\n如此消息频繁出现，请联系管理员 i@techotaku.net 。";
  const resSubscribe = "欢迎关注【豆瓣查】微信公众账号！
在这里，您可以很方便地从豆瓣检索指定的电影、音乐或图书。
回复\"帮助\"或者\"help\"或者中英文问号查看帮助信息。";
  const resDefault = "欢迎使用【豆瓣查】！
回复\"帮助\"或者\"help\"或者中英文问号查看帮助信息。";
  const resHelp = "指令说明：\n
类型 关键字\n
可用类型包括：电影、f；音乐、m；图书、b。
如\"电影 一代宗师\"，\"b 九州 天光云影\"。
本应用查询结果来自豆瓣（www.douban.com），感谢豆瓣网提供API服务。";
  const resHelpFilm = "指令说明：\n
电影 关键字
f 关键字\n
关键字可用空格隔开，至多返回匹配的前十个结果。
如\"电影 一代宗师\"。";
  const resHelpMusic = "指令说明：\n
音乐 关键字
m 关键字\n
关键字可用空格隔开，至多返回匹配的前十个结果。
如\"音乐 江南\"。";
  const resHelpBook = "指令说明：\n
图书 关键字
b 关键字\n
关键字可用空格隔开，至多返回匹配的前十个结果。
如\"图书 天光云影\"。";

  protected $wechat;

  protected function before() {
    parent::before();

    require_once './lib/Wechat.php';
    require_once './config.php';

    $this->wechat = new Wechat(TOKEN, DEBUG);

    if ($this->wechat->isValid()) {

      switch ($this->wechat->getRequestType()) {
        case WechatRequest::text:
          $this->route->action = 'text';
          break;
        case WechatRequest::subscribe:
          $this->route->action = 'subscribe';
          break;
        case WechatRequest::unsubscribe:
        case WechatRequest::voice:
        case WechatRequest::image:
        case WechatRequest::location:
        case WechatRequest::link:
        case WechatRequest::unknown:
          $this->route->action = 'def';
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

  protected function def() {
    $this->wechat->sendResponse(WechatResponse::text, self::resDefault);
  }

  protected function subscribe() {
    $this->wechat->sendResponse(WechatResponse::text, self::resSubscribe);
  }

  protected function text() {
    try {

      $command = explode(' ', $this->wechat->getRequest('content'), 2);
      if (count($command) > 1)
      {
        $key = strtolower($command[0]);
        $param = strtolower($command[1]);
        switch ($key)
        {
          case '帮助':
          case 'help':
          case '？':
          case '?':
            switch ($param) {
              case '电影':
              case 'f':
                $this->wechat->sendResponse(WechatResponse::text, self::resHelpFilm);
                break;
              case '音乐':
              case 'm':
                $this->wechat->sendResponse(WechatResponse::text, self::resHelpMusic);
                break;
              case '图书':
              case 'b':
                $this->wechat->sendResponse(WechatResponse::text, self::resHelpBook);
                break;
              default:
                $this->wechat->sendResponse(WechatResponse::text, '暂无与"' . $param . '"相关的帮助信息。');
                break;
            }
            break;
          case '电影':
          case 'f':
            $this->search('movie', $param);
            break;
          case '音乐':
          case 'm':
            $this->search('music', $param);
            break;
          case '图书':
          case 'b':
            $this->search('book', $param);
            break;
          default:
            $this->default();
            break;
        }
      } else {
        $key = strtolower($command[0]);
        switch ($key)
        {
          case '帮助':
          case 'help':
          case '？':
          case '?':
            $this->wechat->sendResponse(WechatResponse::text, self::resHelp);
            break;
          case '电影':
          case 'f':
            $this->wechat->sendResponse(WechatResponse::text, self::resHelpFilm);
            break;
          case '音乐':
          case 'm':
            $this->wechat->sendResponse(WechatResponse::text, self::resHelpMusic);
            break;
          case '图书':
          case 'b':
            $this->wechat->sendResponse(WechatResponse::text, self::resHelpBook);
            break;
          default:
            $this->default();
            break;
        }
      }
      
    } catch (Exception $ex) {
      if (DEBUG) {
        $template = "
处理指令时发送异常

%s
文件： %s
行号： %s
";
        $content = sprintf($template, $ex->getMessage(), $ex->getFile(), $ex->getLine());
        $this->wechat->sendResponse(WechatResponse::text, $content);
      }
    }

  }

  private function search($api, $param) {
    $api = ucfirst(strtolower($api));

    if (in_array($api, array('Movie', 'Music', 'Book'))) {

      require_once './lib/DoubanOauth.php';
      $appConfig = array(
        'client_id' => DKEY,
        'secret' => DSERCRET,
        'redirect_uri' => 'http://dbcha.techotaku.net/douban/callback',
        'scope' => 'douban_basic_common,book_basic_r,movie_basic,movie_basic_r,music_basic_r',
        'need_permission' => false
        );

      $douban = new DoubanOauth($appConfig);
      $data = array(
        'q' => $param, 
        'start' => 0, 
        'count' => 10
        );
      $r = $douban->api($api . '.search.GET', $data)->makeRequest();

      if (strncmp('CURL error:', $r, 10) == 0) {
        $this->wechat->sendResponse(WechatResponse::text, 'Oops! 向豆瓣发送搜索请求时发生错误：' . $r . self::resError);
      }

      $result = json_decode($r, TRUE);

      if (json_last_error() != JSON_ERROR_NONE) {
        $this->wechat->sendResponse(WechatResponse::text, 'Oops! 应用可能已经达到每分钟搜索次数限制。解析豆瓣搜索结果时发生错误：' . json_last_error() . $r . self::resError);
      }

      if (isset($result['error'])) {
        $this->wechat->sendResponse(WechatResponse::text, 'Oops! 豆瓣返回了一条错误信息：' . $result['error'] . self::resError);
      }

      if (isset($result['count']) && $result['count'] <= 0) {
        $this->wechat->sendResponse(WechatResponse::text, '没有找到与"' . $param . '"有关的豆瓣条目。');
      }

      $items = array();
      switch ($api) {
        case 'Movie':
          $items = $result['subjects'];
          break;
        case 'Music':
          $items = $result['musics'];
          break;
        case 'Book':
          $items = $result['books'];
          break;
      }

      $news = array();
      foreach ($items as $item) {
        $picurl = '';
        if (isset($item['images'])) {
          $picurl = $item['images']['large'];
        } elseif (isset($item['image'])) {
          $picurl = $item['image'];
        }

        array_push($news, new WechatNewsResponseItem($item['title'], '', $picurl, $item['alt']));
      }
      $this->wechat->sendResponse(WechatResponse::news, $news);

    } else {
      $this->wechat->sendResponse(WechatResponse::text, '暂不支持的豆瓣Api：' . $param);
    }
  }

}

$route = new Router;
$controller = Controller::getController($route);
$controller->run();

?>