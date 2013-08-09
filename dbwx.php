<?php
/**
  * WeChat Service
  */

//define your token
define("TOKEN", "{YOUR TOKEN HERE}");
define("DEBUG", 0);

//豆瓣应用public key
define("DKEY", "{Your Douban App Key}");
//豆瓣应用secret key
define("DSERCRET", "{Your Douban App Secret}");

require("DoubanOauth.php");

$wechatObj = new WeChat();

if($wechatObj->checkSignature()){
  if($_SERVER['REQUEST_METHOD']=='POST' || DEBUG==1) {
    $wechatObj->parseMsg();
  } else {
    $wechatObj->valid();
  } 
} else {
  echo "Invalid signature.";
}

class WeChat
{
  public function valid()
  {
    $echoStr = $_GET["echostr"];
    echo $echoStr;
    exit;
  }

  public function parseMsg()
  {         
    if (DEBUG==0)
    {
      $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
      if (!empty($postStr))
      {
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (!isset($postObj) || !isset($postObj->MsgType))
        {
          echo "Invalid input.";
          exit;
        }
        $fromUsername = $postObj->FromUserName;
        $toUsername = $postObj->ToUserName;
        $keyword = trim($postObj->Content);
        $type = trim($postObj->MsgType);
      }
    } else {
      $fromUsername = "user";
      $toUsername = "mp";
      $keyword = $_GET["msg"];
      $type = trim($_GET["type"]);
    }
    switch($type)
      {
      case "text":
        $this->response($keyword, $fromUsername, $toUsername);
        break;
      case "image":
        $this->writeTxtMessage($fromUsername, $toUsername, "Hi，我们已经记录了您的图片留言。\n发送\"帮助\"或者\"help\"又或者中英文问号可以获取帮助和可用指令列表哟。");
        break;
      case "location":
        $this->writeTxtMessage($fromUsername, $toUsername, "Hi，我们已经记录了您的地理位置留言。\n发送\"帮助\"或者\"help\"又或者中英文问号可以获取帮助和可用指令列表哟。");
        break;
      default:
        $this->writeTxtMessage($fromUsername, $toUsername, "Hi，我们已经记录了您的消息（".$type."）。\n发送\"帮助\"或者\"help\"又或者中英文问号可以获取帮助和可用指令列表哟。");
      }
  }

  private function response($input, $user, $mpaccount)
  {
    $defmsg = "欢迎关注豆瓣助手！\n发送\"帮助\"或者\"help\"或者中英文问号查看指令帮助。";
    $newmsg = "欢迎关注豆瓣助手！\n在这里，您可以很方便地从豆瓣检索指定的电影、音乐或图书。\n发送\"帮助\"或者\"help\"或者中英文问号查看指令帮助。\n本应用查询结果来自豆瓣（www.douban.com），感谢豆瓣网提供API服务。";
    $command = explode(" ", $input, 2);
    if (count($command) > 1)
    {
      $key = strtolower($command[0]);
      $param = strtolower($command[1]);
      switch ($key)
      {
      case "帮助":
      case "help":
      case "？":
      case "?":
          $this->cmdCmdHelp($param, $user, $mpaccount);
        break;
      case "电影":
      case "f":
          $this->cmdFilm($param, $user, $mpaccount);
        break;
      case "音乐":
      case "m":
          $this->cmdMusic($param, $user, $mpaccount);
        break;
      case "图书":
      case "b":
          $this->cmdBook($param, $user, $mpaccount);
        break;
      default:
          $this->writeTxtMessage($user, $mpaccount, $defmsg);
      }
    } else {
      $key = strtolower($command[0]);
      switch ($key)
      {
      case "帮助":
      case "help":
      case "？":
      case "?":
          $this->cmdHelp($user, $mpaccount);
        break;
      case "hello2bizuser":
          $this->writeTxtMessage($user, $mpaccount, $newmsg);
        break;
      default:
          $this->writeTxtMessage($user, $mpaccount, $defmsg);
      }
    }
  }
  
  private function cmdHelp($user, $mpaccount)
  {
    $helptxt = "指令说明：\n
类型 关键字\n
可用类型包括：电影、f；音乐、m；图书、b。
如\"电影 一代宗师\"，\"b 九州 天光云影\"。
本应用查询结果来自豆瓣（www.douban.com），感谢豆瓣网提供API服务。";
    $this->writeTxtMessage($user, $mpaccount, $helptxt);
  }

  private function cmdCmdHelp($cmd, $user, $mpaccount)
  {
      switch ($cmd)
    {
    case "f":
    case "电影":
        $helptxt = "指令说明：\n
电影 关键字
f 关键字\n
关键字可用空格隔开，助手将返回匹配的第一个结果。
如\"电影 一代宗师\"。";
        $this->writeTxtMessage($user, $mpaccount, $helptxt);
      break;
    case "m": 
    case "音乐":
        $helptxt = "指令说明：\n
音乐 关键字
m 关键字\n
关键字可用空格隔开，助手将返回匹配的第一个结果。
如\"音乐 江南\"。";
        $this->writeTxtMessage($user, $mpaccount, $helptxt);
      break;
    case "b":
    case "图书":
        $helptxt = "指令说明：\n
图书 关键字
b 关键字\n
关键字可用空格隔开，助手将返回匹配的第一个结果。
如\"图书 天光云影\"。";
        $this->writeTxtMessage($user, $mpaccount, $helptxt);
      break;
    default:
        $this->writeTxtMessage($user, $mpaccount, "暂时无法提供关于\"".$cmd."\"的帮助信息。");
    }     
  }

  private function cmdFilm($key, $user, $mpaccount)
  {
      $this->checkLimit($user, $mpaccount);
    
      $appConfig = array(
        'client_id' => DKEY,
        'secret' => DSERCRET,
        'redirect_uri' => 'http://nrfsf.com/dHelper/callback',
        'scope' => 'douban_basic_common,book_basic_r,movie_basic,movie_basic_r,music_basic_r',
        // 可选参数（默认为false），是否在header中发送accessToken。
        'need_permission' => false
        );
    
    $douban = new DoubanOauth($appConfig);
    $data = array(
            'q' => $key, 
            'start' => 0, 
            'count' => 1
            );
    $r = $douban->api('Movie.search.GET', $data)->makeRequest();
    
    $result = json_decode($r, TRUE);
    $this->checkResult($r, $result, $user, $mpaccount);
    
    if (isset($result["count"]) && $result["count"] == 1)
    {
        $textTpl = "电影：%s（%s）
评分：%s分 via %s 人
（豆瓣从搜索结果中屏蔽了较多内容，请点击下面链接查看详情）
豆瓣链接：%s";
         $titleArray = array($result["subjects"][0]["title"], $result["subjects"][0]["original_title"]);
         $resultStr = sprintf($textTpl,
         implode(" / ", $titleArray),
         $result["subjects"][0]["year"],
         $result["subjects"][0]["rating"]["average"],
         $result["subjects"][0]["rating"]["numRaters"],
         $result["subjects"][0]["alt"]
         );
      $this->writeTxtMessage($user, $mpaccount, $resultStr); 
    } else {
        $this->writeTxtMessage($user, $mpaccount, "未能找到与\"".$key."\"有关的电影。"); 
    }
  }
  
  private function cmdMusic($key, $user, $mpaccount)
  {
    $this->checkLimit($user, $mpaccount);
    
    $appConfig = array(
      'client_id' => DKEY,
      'secret' => DSERCRET,
      'redirect_uri' => 'http://nrfsf.com/dHelper/callback',
      'scope' => 'douban_basic_common,book_basic_r,movie_basic,movie_basic_r,music_basic_r',
      // 可选参数（默认为false），是否在header中发送accessToken。
      'need_permission' => false
      );
    
    $douban = new DoubanOauth($appConfig);
    $data = array(
            'q' => $key, 
            'start' => 0, 
            'count' => 1
            );
    $r = $douban->api('Music.search.GET', $data)->makeRequest();

    $result = json_decode($r, TRUE);
    $this->checkResult($r, $result, $user, $mpaccount);
    
    if (isset($result["count"]) && $result["count"] == 1)
    {
        $textTpl = "音乐：%s
又名：%s
评分：%s分 via %s 人
表演者：%s
版本特性：%s
发行时间：%s
出版者：%s
介质：%s
豆瓣链接：%s
曲目：%s";
             $resultStr = sprintf($textTpl,
           $result["musics"][0]["title"],
         $result["musics"][0]["alt_title"],
         $result["musics"][0]["rating"]["average"],
         $result["musics"][0]["rating"]["numRaters"],
         implode("，", $result["musics"][0]["attrs"]["singer"]),
         implode("，", $result["musics"][0]["attrs"]["version"]),
         implode("，", $result["musics"][0]["attrs"]["pubdate"]),
         implode("，", $result["musics"][0]["attrs"]["publisher"]),
         implode("，", $result["musics"][0]["attrs"]["media"]),
         $result["musics"][0]["alt"],
         implode("，", $result["musics"][0]["attrs"]["tracks"])
         );
      if (strlen($resultStr) >= 2048)
      {
          mb_internal_encoding("UTF-8");
          $resultStr = mb_substr($resultStr, 0, 600)."……";
      }  
      $this->writeTxtMessage($user, $mpaccount, $resultStr); 
    } else {
        $this->writeTxtMessage($user, $mpaccount, "未能找到与\"".$key."\"有关的音乐。"); 
    }
  }
  
  private function cmdBook($key, $user, $mpaccount)
  {
      $this->checkLimit($user, $mpaccount);
    
    $appConfig = array(
      'client_id' => DKEY,
      'secret' => DSERCRET,
      'redirect_uri' => 'http://nrfsf.com/dHelper/callback',
      'scope' => 'douban_basic_common,book_basic_r,movie_basic,movie_basic_r,music_basic_r',
      // 可选参数（默认为false），是否在header中发送accessToken。
      'need_permission' => false
      );
    
    $douban = new DoubanOauth($appConfig);
    $data = array(
            'q' => $key, 
            'start' => 0, 
            'count' => 1
            );
    $r = $douban->api('Book.search.GET', $data)->makeRequest();
    
    $result = json_decode($r, TRUE);
    $this->checkResult($r, $result, $user, $mpaccount);
    
    if (isset($result["count"]) && $result["count"] == 1)
    {
        $title = $result["books"][0]["title"];
      if (strlen($result["books"][0]["origin_title"]) > 0)
      {
         $title = $title." / ".$result["books"][0]["origin_title"];
      }
      $authorArray = array_merge($result["books"][0]["author"], $result["books"][0]["translator"]);
      $authorIntro = $result["books"][0]["author_intro"];
      if (strlen($authorIntro) == 0)
      {
          $authorIntro = "（暂无）";
      }
      $textTpl = "图书：%s
副标题：%s
ISBN：%s
评分：%s分 via %s 人
作者：%s
出版时间：%s
出版社：%s
页数：%s
装帧：%s
定价：%s
豆瓣链接：%s
图书简介：%s
作者简介：%s";
       $resultStr = sprintf($textTpl,
         $title,
         $result["books"][0]["subtitle"],
         $result["books"][0]["isbn13"],
         $result["books"][0]["rating"]["average"],
         $result["books"][0]["rating"]["numRaters"],
         implode("，", $authorArray), //作者
         $result["books"][0]["pubdate"],
         $result["books"][0]["publisher"],
         $result["books"][0]["pages"],
         $result["books"][0]["binding"],
         $result["books"][0]["price"],
         $result["books"][0]["alt"],
         $result["books"][0]["summary"],
         $authorIntro
         );
        if (strlen($resultStr) >= 2048)
      {
          mb_internal_encoding("UTF-8");
          $resultStr = mb_substr($resultStr, 0, 600)."……";
      }
      $this->writeTxtMessage($user, $mpaccount, $resultStr); 
    } else {
        $this->writeTxtMessage($user, $mpaccount, "未能找到与\"".$key."\"有关的图书。"); 
    }
  }

  private function checkResult($r, $result, $user, $mpaccount)
  {
    if (strncmp("CURL error:", $r, 10) == 0)
    {
      file_put_contents("./lasterror/".$user, $r);
      $this->writeTxtMessage($user, $mpaccount, "Oops! 向豆瓣发送搜索请求时发生错误。应用可能已经达到每分钟搜索次数限制。如果多次出现此提示，请联系管理员 i@techotaku.net。"); 
    }

    if (json_last_error() != JSON_ERROR_NONE)
    {
      file_put_contents("./lasterror/".$user, "JSON error: ".json_last_error());
      $this->writeTxtMessage($user, $mpaccount, "Oops! 解析搜索结果时发生错误。应用可能已经达到每分钟搜索次数限制。如果多次出现此提示，请联系管理员 i@techotaku.net。"); 
    }

    if (isset($result["error"]))
    {
      file_put_contents("./lasterror/".$user, "DOUBAN error: ".$result["error"]);
      $this->writeTxtMessage($user, $mpaccount, "Oops! 豆瓣返回了一条错误信息：".$result["error"]."。如果多次出现此提示，请联系管理员 i@techotaku.net。"); 
    }
  }
  
  private function checkLimit($user, $mpaccount)
  {
      if (!file_exists("./access/".$user))
    {
        file_put_contents("./access/".$user, time());
        return;
    } else {
        $last = file_get_contents("./access/".$user);
      if (intval($last) + 20 > intval(time()))
      {
          $this->writeTxtMessage($user, $mpaccount, "你动作太快啦！两次搜索的间隔时间不能少于20秒。"); 
      } else {
          file_put_contents("./access/".$user, time());
        return;
      }
    }
  }
  
  private function writeTxtMessage($user, $mpaccount, $txt)
  {
      $time = time();
        $textTpl = "
<xml>
  <ToUserName><![CDATA[%s]]></ToUserName>
  <FromUserName><![CDATA[%s]]></FromUserName>
  <CreateTime>%s</CreateTime>
  <MsgType>text</MsgType>
  <Content><![CDATA[%s]]></Content>
  <FuncFlag>0</FuncFlag>
</xml>";
        $resultStr = sprintf($textTpl, $user, $mpaccount, $time, $txt);
        echo $resultStr;
    exit;
  }

  private function writePicTxtMessage($user, $mpaccount, $title, $description, $picurl, $url)
  {
      $time = time();
        $textTpl = "
<xml>
  <ToUserName><![CDATA[%s]]></ToUserName>
  <FromUserName><![CDATA[%s]]></FromUserName>
  <CreateTime>%s</CreateTime>
  <MsgType>news</MsgType>
  <Content></Content>
  <ArticleCount>1</ArticleCount>
  <Articles>
    <item>
      <Title><![CDATA[%s]]></Title>
      <Description><![CDATA[%s]]></Description>
      <PicUrl><![CDATA[%s]]></PicUrl>
      <Url><![CDATA[%s]]></Url>
    </item>
  </Articles>
  <FuncFlag>0</FuncFlag>
</xml>";
        $resultStr = sprintf($textTpl, $user, $mpaccount, $time, $title, $description, $picurl, $url);
        echo $resultStr;
    exit;
  }
  
  public function checkSignature()
  {
      if (DEBUG==1)
    {
        return true;
    }
  
      if(!isset($_GET["signature"]) || !isset($_GET["timestamp"]) || !isset($_GET["nonce"]))
    {
        return false;
    }

    $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];  
    
    $token = TOKEN;
    $tmpArr = array($token, $timestamp, $nonce);
    sort($tmpArr);
    $tmpStr = implode( $tmpArr );
    $tmpStr = sha1( $tmpStr );
    
    if( $tmpStr == $signature ){
      return true;
    } else {
      return false;
    }
  }
}

?>