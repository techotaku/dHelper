<?php
/**
 * 豆瓣查： 豆瓣影音书微信速查入口
 *
 * @author     Ian Li <i@techotaku.net>
 * @copyright  Ian Li <i@techotaku.net>, All rights reserved.
 * @link       http://dbcha.techotaku.net/
 */

  require_once __DIR__ . '/TestBase.php';

  /**
   * Test Case
   */
  class DbChaTestCase extends DbChaTestBase {


    protected function setUp() {
      parent::setUp();
    }

    public function testEchoBack() {
      $this->url .= '&echostr=cvmoiehiogndjlsbvibiu';

      $response = $this->curl($this->url, 'GET', array());
      $this->assertEquals('cvmoiehiogndjlsbvibiu', $response);
    }

    public function testHelp() {
      $response = $this->curl($this->url, 'POST', array('Content-Type: text/plain'), $this->makeTextMsg('?'));
      $response = $this->removeTime($response);
      $expect = file_get_contents(__DIR__ . '/expect/help.output');

      $this->assertEquals($expect, $response);
    }

    public function testHelpMusic() {
      $response = $this->curl($this->url, 'POST', array('Content-Type: text/plain'), $this->makeTextMsg('m'));
      $response = $this->removeTime($response);
      $expect = file_get_contents(__DIR__ . '/expect/help.music.output');

      $this->assertEquals($expect, $response);
    }

    public function testHelpMovie() {
      $response = $this->curl($this->url, 'POST', array('Content-Type: text/plain'), $this->makeTextMsg('f'));
      $response = $this->removeTime($response);
      $expect = file_get_contents(__DIR__ . '/expect/help.movie.output');

      $this->assertEquals($expect, $response);
    }

    public function testHelpBook() {
      $response = $this->curl($this->url, 'POST', array('Content-Type: text/plain'), $this->makeTextMsg('b'));
      $response = $this->removeTime($response);
      $expect = file_get_contents(__DIR__ . '/expect/help.book.output');

      $this->assertEquals($expect, $response);
    }

    public function testMusic() {
      $response = $this->curl($this->url, 'POST', array('Content-Type: text/plain'), $this->makeTextMsg('m 空城'));
      $response = $this->removeTime($response);
      $expect = file_get_contents(__DIR__ . '/expect/music.output');

      $this->assertEquals($expect, $response);
    }

    public function testFilm() {
      $response = $this->curl($this->url, 'POST', array('Content-Type: text/plain'), $this->makeTextMsg('f 怪兽大学'));
      $response = $this->removeTime($response);
      $expect = file_get_contents(__DIR__ . '/expect/movie.output');

      $this->assertEquals($expect, $response);
    }

    public function testBook() {
      $response = $this->curl($this->url, 'POST', array('Content-Type: text/plain'), $this->makeTextMsg('b 宗教简史'));
      $response = $this->removeTime($response);
      $expect = file_get_contents(__DIR__ . '/expect/book.output');

      $this->assertEquals($expect, $response);
    }

  }
?>