mkdir -p htdocs/opencart

tar xvfj setup/opencart20.tar.bz2 -C htdocs/opencart/
mysql --port=33063 -h 127.0.0.1 -u root -proot < setup/opencart20.sql

tar xvfj setup/opencart21.tar.bz2 -C htdocs/opencart/
mysql --port=33063 -h 127.0.0.1 -u root -proot < setup/opencart21.sql

tar xvfj setup/opencart22.tar.bz2 -C htdocs/opencart/
mysql --port=33063 -h 127.0.0.1 -u root -proot < setup/opencart22.sql
