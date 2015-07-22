Setup Plugin
-------------

This plugin requires PHP CLI.

### Install Plugin
```
#!bash

cd /usr/share/munin/plugins/
sudo wget -O php-fpm_ https://bitbucket.org/n2j7/munin-php-fpm-pools/raw/master/php-fpm_
sudo chmod +x php-fpm_

```

### Setup Graphs
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
sudo ln -s /usr/share/munin/plugins/php-fpm_ /etc/munin/plugins/php-fpm_poolname_connections

# alltogether
sudo ln -s /usr/share/munin/plugins/php-fpm_ /etc/munin/plugins/php-fpm_connections

```


Env variables:
```
phpbin - php fpm executable file name
phpmemwarn - warning level of php memory usage in Mb (based on 1024)
phpmemcrit - critical level of php memory usage in Mb (based on 1024)

```