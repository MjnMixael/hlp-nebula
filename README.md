# HLP-Nebula

HLP-Nebula is a FreeSpace 2 mod repository manager.

This project has two main goals :
* Provide a simple site where modders can upload their files.
* Make mod download and installation easier for players.

HLP-Nebula is mostly written in PHP, as a [Symfony2](http://symfony.com/) framework bundle.

## Dependencies

To run HLP-Nebula, the first thing you need is a working LAMP environment.
On Ubuntu, use command :
```
sudo apt-get install apache2 php5 mysql-server libapache2-mod-php5 php5-mysql php5-gd
```


## Install

1. If you have not done it yet, download the [latest version of HLP-Nebula](https://github.com/ngld/hlp-nebula).
2. Place the root folder inside your web server's document root.
3. Rename ```app/config/parameters.yml.dist``` to ```app/config/parameters.yml```
4. Edit ```app/config/parameters.yml``` modify it according to your system.
5. [Install Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)
6. Install the dependencies:
   ```bash
   composer install
   ```
7. Give the web server write access to ```app/cache```, ```app/logs``` and ```web/uploads```:
   ```bash
   sudo chown www-data -R app/cache app/logs web/uploads
   ```

8. Fill the database with the necessary tables:
   ```bash
   php app/console doctrine:schema:create --force
   ```

Now you can access the Nebula through the ```web/app.php``` file.
If you have Apache and mod_rewrite enabled, you should be able to access the ```web/``` folder directly.


## Quick start

1. Create the admin user with:
   ```bash
   php app/console fos:user:create <username> --super-admin
   ```

2. Now you can login and add your mods.

## Clients and JSON Specification

The generated JSON data is [explained in a post on HLP](http://www.hard-light.net/forums/index.php?topic=89434.0).
That post also contains a list of compatible clients at the bottom.


## Development

HLP-Nebula is in _active development_. It is still missing a lot of features.
The official [development thread](http://www.hard-light.net/forums/index.php?topic=86364) is found on [Hard Light Productions](http://www.hard-light.net), a FreeSpace 2 community.


## License

HLP-Nebula is licensed under the [European Union Public License, Version 1.1](LICENSE).
Symfony is licensed under the [MIT License](LICENSE).
