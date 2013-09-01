cd ..
wget http://openresty.org/download/ngx_openresty-1.4.2.1.tar.gz
tar xzf ngx_openresty-1.4.2.1.tar.gz
cd ngx_openresty-1.4.2.1
./configure --prefix=$HOME/openresty --with-luajit
make
make install
cd ..
php dbcha/test/make-nginx-cfg.php `pwd`/dbcha > dbcha/test/nginx.conf
mv dbcha/test/nginx.conf ~/openresty/nginx/conf/nginx.conf
sudo apt-get install php5-fpm
/etc/init.d/php5-fpm start
sudo ~/openresty/nginx/sbin/nginx