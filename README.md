Setup Plugin
-------------

This plugin requires PHP CLI.

### Complete setup

All:
```
wget -O /usr/share/munin/plugins/php-fpm_ https://raw.githubusercontent.com/acki/munin-php-fpm-pools/refs/heads/master/php-fpm_
chmod +x /usr/share/munin/plugins/php-fpm_
ln -s /usr/share/munin/plugins/php-fpm_ /etc/munin/plugins/php-fpm_average
ln -s /usr/share/munin/plugins/php-fpm_ /etc/munin/plugins/php-fpm_connections
ln -s /usr/share/munin/plugins/php-fpm_ /etc/munin/plugins/php-fpm_memory
ln -s /usr/share/munin/plugins/php-fpm_ /etc/munin/plugins/php-fpm_processes
ln -s /usr/share/munin/plugins/php-fpm_ /etc/munin/plugins/php-fpm_status
```

### Install Plugin
```
#!bash

cd /usr/share/munin/plugins/
sudo wget -O php-fpm_ https://raw.githubusercontent.com/acki/munin-php-fpm-pools/refs/heads/master/php-fpm_
sudo chmod +x php-fpm_

```

### Setup Graphs

All:
```
ln -s /usr/share/munin/plugins/php-fpm_ /etc/munin/plugins/php-fpm_average
ln -s /usr/share/munin/plugins/php-fpm_ /etc/munin/plugins/php-fpm_connections
ln -s /usr/share/munin/plugins/php-fpm_ /etc/munin/plugins/php-fpm_memory
ln -s /usr/share/munin/plugins/php-fpm_ /etc/munin/plugins/php-fpm_processes
ln -s /usr/share/munin/plugins/php-fpm_ /etc/munin/plugins/php-fpm_status
```

Average process memory:
```
#!bash

# per pool
sudo ln -s /usr/share/munin/plugins/php-fpm_ /etc/munin/plugins/php-fpm_poolname_average

# alltogether
sudo ln -s /usr/share/munin/plugins/php-fpm_ /etc/munin/plugins/php-fpm_average

```

Connections:
```
#!bash

# per pool
sudo ln -s /usr/share/munin/plugins/php-fpm_ /etc/munin/plugins/php-fpm_poolname_connections

# alltogether
sudo ln -s /usr/share/munin/plugins/php-fpm_ /etc/munin/plugins/php-fpm_connections

```

Memory usage:
```
#!bash

# per pool
sudo ln -s /usr/share/munin/plugins/php-fpm_ /etc/munin/plugins/php-fpm_poolname_memory

# alltogether
sudo ln -s /usr/share/munin/plugins/php-fpm_ /etc/munin/plugins/php-fpm_memory

```

Processes count:
```
#!bash

# per pool
sudo ln -s /usr/share/munin/plugins/php-fpm_ /etc/munin/plugins/php-fpm_poolname_processes

# alltogether
sudo ln -s /usr/share/munin/plugins/php-fpm_ /etc/munin/plugins/php-fpm_processes

```

Connection statuses count:
```
#!bash

# per pool
sudo ln -s /usr/share/munin/plugins/php-fpm_ /etc/munin/plugins/php-fpm_poolname_status

# alltogether
sudo ln -s /usr/share/munin/plugins/php-fpm_ /etc/munin/plugins/php-fpm_status

```


Env variables:
```
phpbin - php fpm executable file name [php-fpm by default]
phpmemwarn - warning level of php memory usage in Mb (based on 1024) [100 by default]
phpmemcrit - critical level of php memory usage in Mb (based on 1024) [200 by default]
fpmpoolscount - the number of defined pools
fpmpool_X_name - the name of X pool (starts from 0) in list [a-zA-Z0-9_] (the same as in symlink)
fpmpool_X_url - the url for getting stats info (in json format) of X pool (starts from 0)

```
