Heroku WP
=========

This is a template for installing and running [WordPress](http://wordpress.org/) on [Heroku](http://www.heroku.com/) with a focus on speed and security while using the official Heroku stack.

The repository is built on top of the following technologies.
* [nginx](http://nginx.org) - For serving web content.
* [HHVM](http://hhvm.com) - A virtual machine designed to serve Hack and PHP.
* [MySQL](http://www.mysql.com) - Provided by the ClearDB add-on.
* [Memcached](http://memcached.org) - Provided by the MemCachier add-on.
* [Composer](https://getcomposer.org) - A dependency manager to make installing and managing plugins easier.

In additon repository comes bundled with the following plugins.
* [SASL object cache](https://github.com/xyu/SASL-object-cache) - For running with MemCachier add-on
* [Batcache](http://wordpress.org/plugins/batcache/) - For full page output caching
* [SSL Domain Alias](http://wordpress.stackexchange.com/questions/38902) - For sending SSLed traffic to a different domain (needed to send WP admin traffic to Heroku over SSL directly.)
* [Authy Two Factor Auth](https://www.authy.com/products/wordpress)
* [Jetpack](http://jetpack.me/)
* [SendGrid](http://wordpress.org/plugins/sendgrid-email-delivery-simplified/)
* [WP Read-Only](http://wordpress.org/extend/plugins/wpro/)

WordPress and most included plugins are installed by Composer on build. To add new plugins or upgrade versions of plugins simply update the `composer.json` file and then generate the `composer.lock` file with the following command locally:

```bash
$ composer update --ignore-platform-reqs
```

To customize the site simply place files into `/public` which upon deploy to Heroku will be copied on top of the standard WordPress install and plugins specified by Composer.

Installation
------------

Clone the repository from Github

    $ git clone git://github.com/xyu/heroku-wp.git

With the [Heroku gem](http://devcenter.heroku.com/articles/heroku-command), create your app

    $ cd heroku-wp
    $ heroku create
    > Creating strange-turtle-1234... done, stack is cedar
    > http://strange-turtle-1234.herokuapp.com/ | git@heroku.com:strange-turtle-1234.git
    > Git remote heroku added


Add a database to your app

    $ heroku addons:add cleardb:ignite
    > Adding cleardb:ignite on strange-turtle-1234... done, v2 (free)
    > Use `heroku addons:docs cleardb:ignite` to view documentation.

Add memcache to your app

    $ heroku addons:add memcachier:dev
    > Adding memcachier:dev on strange-turtle-1234... done, v3 (free)
    > MemCachier: added.  Your credentials may take up to 3 minutes to sync to our servers.
    > Use `heroku addons:docs memcachier:dev` to view documentation.

Add unique keys and salts to your Heroku config

    $ heroku config:set\
        WP_AUTH_KEY=`dd if=/dev/random bs=1 count=96 2>/dev/null | base64`\
        WP_SECURE_AUTH_KEY=`dd if=/dev/random bs=1 count=96 2>/dev/null | base64`\
        WP_LOGGED_IN_KEY=`dd if=/dev/random bs=1 count=96 2>/dev/null | base64`\
        WP_NONCE_KEY=`dd if=/dev/random bs=1 count=96 2>/dev/null | base64`\
        WP_AUTH_SALT=`dd if=/dev/random bs=1 count=96 2>/dev/null | base64`\
        WP_SECURE_AUTH_SALT=`dd if=/dev/random bs=1 count=96 2>/dev/null | base64`\
        WP_LOGGED_IN_SALT=`dd if=/dev/random bs=1 count=96 2>/dev/null | base64`\
        WP_NONCE_SALT=`dd if=/dev/random bs=1 count=96 2>/dev/null | base64`
    > Setting config vars and restarting strange-turtle-1234... done, v4
    > WP_AUTH_KEY:         ...
    > WP_AUTH_SALT:        ...
    > WP_LOGGED_IN_KEY:    ...
    > WP_LOGGED_IN_SALT:   ...
    > WP_NONCE_KEY:        ...
    > WP_NONCE_SALT:       ...
    > WP_SECURE_AUTH_KEY:  ...
    > WP_SECURE_AUTH_SALT: ...

Create a new production branch for your app

    $ git checkout -b production

Deploy to Heroku

    $ git push heroku production:master
    > Fetching repository, done.
    > Counting objects: 5, done.
    > Delta compression using up to 8 threads.
    > Compressing objects: 100% (3/3), done.
    > Writing objects: 100% (3/3), 650 bytes | 0 bytes/s, done.
    > Total 3 (delta 2), reused 0 (delta 0)
    >
    > -----> PHP app detected
    > -----> Detected request for HHVM 3.1.0 in composer.json.
    > -----> Setting up runtime environment...
    >        - HHVM 3.1.0
    >        - Apache 2.4.9
    >        - Nginx 1.4.6
    > -----> Installing dependencies...
    >        Composer version 1d8b627b57a5a0fe8ceaef25534d9da8bd7cb301 2014-06-28 18:45:20
    >        Loading composer repositories with package information
    >        Installing dependencies from lock file
    >          - Installing fancyguy/webroot-installer (1.1.0)
    >            Loading from cache
    >
    >          - Installing composer/installers (v1.0.14)
    >            Loading from cache
    >
    >          - Installing wordpress/wordpress (3.9.1)
    >            Loading from cache
    >
    >          - Installing wpackagist-plugin/authy-two-factor-authentication (2.5.4)
    >            Loading from cache
    >
    >          - Installing wpackagist-plugin/jetpack (3.0.2)
    >            Loading from cache
    >
    >          - Installing wpackagist-plugin/sendgrid-email-delivery-simplified (1.3.2)
    >            Loading from cache
    >
    >          - Installing wpackagist-plugin/wpro (1.0)
    >            Loading from cache
    >
    >        Generating optimized autoload files
    > -----> Building runtime environment...
    > -----> Discovering process types
    >        Procfile declares types -> web
    >
    > -----> Compressing... done, 61.1MB
    > -----> Launching... done, v70
    >        http://strange-turtle-1234.herokuapp.com/ deployed to Heroku
    >
    > To git@heroku:strange-turtle-1234.git
    > * [new branch]    production -> master

After deployment WordPress has a few more steps to setup and thats it!

Optional Installation
---------------------

Installing and configuring the items below are not essential to get a working WordPress install but will make your site more functional and secure.

### Securing Your Admin Dashboard

Heroku provides an SSL'ed endpoint to each app for free via the APP_NAME.herokuapp.com domain. To use this domain for all logged in sessions and to protect your login credentials simply set the SSL domain in the config vars. (Replace APP_NAME with the name of your Heroku app.)

    $ heroku config:set SSL_DOMAIN="APP_NAME.herokuapp.com"

### Securing Your MySQL Connection

By default WordPress will connect to the database unencrypted which is a potential problem for a cloud based installs where the database and application servers may transfer data over unsecured connections. ClearDB provides SSL keys and certs for the database that's setup and it's highly advisable to use them to secure your database connection.

1. Go to your [Heroku Dashboard](https://dashboard.heroku.com/) and click on your heroku-wp app.
2. Click on the "ClearDB MySQL Database" add-on.
3. Scroll to the bottom of the page and download the "ClearDB CA Certificate", "Client Certificate", and "Client Private Key" in the "PEM Format".
4. Generate Heroku compatible RSA keys from the key file downloaded:

    ```
    $ openssl rsa -in cleardb_id-key.pem -out cleardb_id-key.rsa.pem
    ```

5. Add the keys to the config vars of your app:

    ```
    $ heroku config:set \
        CLEARDB_SSL_CA="$(cat /path/to/cleardb-ca.pem)" \
        CLEARDB_SSL_CERT="$(cat /path/to/cleardb_id-cert.pem)" \
        CLEARDB_SSL_KEY="$(cat /path/to/cleardb_id-key.rsa.pem)"
    > Setting config vars and restarting strange-turtle-1234... done, v12
    > CLEARDB_SSL_CA:   ...
    > CLEARDB_SSL_CERT: ...
    > CLEARDB_SSL_KEY:  ...
    ```

### Media Uploads

[WP Read-Only](http://wordpress.org/extend/plugins/wpro/) plugin is included in the repository allowing the use of [S3](http://aws.amazon.com/s3/) for media uploads.

1. Activate the plugin under 'Plugins', if not already activated.
2. Input your Amazon S3 credentials in 'Settings'->'WPRO Settings'.

### Sending Email

[SendGrid](http://wordpress.org/plugins/sendgrid-email-delivery-simplified/) plugin is included in the repository allowing the use of [SendGrid](https://addons.heroku.com/sendgrid/) for emails.

Add SendGrid to your app

    $ heroku addons:add sendgrid:starter
    > Adding sendgrid:starter on strange-turtle-1234... done, v11 (free)
    > Use `heroku addons:docs sendgrid:starter` to view documentation.

Activate the plugin

Usage
-----

Because a file cannot be written to Heroku's file system, updating and installing plugins or themes should be done locally and then pushed to Heroku. Even better would be to use Composer to install plugins so that version control and upgrading is simply a matter of editing the `composer.json` file and bumping the version number.

Updating
--------

Updating your WordPress version is just a matter of merging the updates into
the branch created from the installation.

    $ git pull # Get the latest

Using the same branch name from our installation:

    $ git checkout production
    $ git merge master # Merge latest
    $ git push heroku production:master

WordPress needs to update the database. After push, navigate to:

    http://your-app-url.herokuapp.com/wp-admin

WordPress will prompt for updating the database. After that you'll be good
to go.

Custom Domains
--------------

Heroku allows you to add custom domains to your site hosted with them.  To add your custom domain, enter in the follow commands.

    $ heroku domains:add www.example.com
    > Added www.example.com as a custom domain name to myapp.heroku.com

Running Locally
---------------

A Vagrant instance to run Heroku WP is included to get up and running first [install Vagrant](https://docs.vagrantup.com/v2/installation/) then `vagrant up` and go grab some ☕.

Once Vagrant provisions the VM you will have Heroku WP running locally at `http://herokuwp.local/`.

As a convenience both the `/public` dir and `/composer.lock` file will be monitored by the VM. Any changes to either triggers a rebuild process which will result in `/public.built` (the web root) being updated.
