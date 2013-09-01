<?php
  $root = $argv[1];
?>
worker_processes  1;

events {
  worker_connections  1024;
}

http {
  include       mime.types;
  default_type  application/octet-stream;

  sendfile        on;
  keepalive_timeout  65;

  server {
    listen       8080;
    root         <?php echo $root ?>;

    if (!-e $request_filename) {
      rewrite  ^(.*)$  /index.php/$1  last;
    }

    location / {
      index  index.php;
    }

    location ~ \.php$ {      
      fastcgi_pass   127.0.0.1:9000;
      fastcgi_index  index.php;
      fastcgi_param  SCRIPT_FILENAME  <?php echo $root ?>$fastcgi_script_name;
      include        fastcgi_params;
    }
  }

  server {
    listen       443;

    ssl                  on;
    ssl_certificate      <?php echo $root ?>/test/20140901.pem;
    ssl_certificate_key  <?php echo $root ?>/test/20140901.pem;

    location / {
      root   <?php echo $root ?>;
      index  index.html;
    }
  }

}