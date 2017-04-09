Heroku WP
=========

This is a template for installing and running [WordPress](http://wordpress.org/) on [Heroku](http://www.heroku.com/) with a focus on speed and security while using the official Heroku stack.

The repository is built on top of the standard Heroku PHP buildpack so you don't need to trust some sketchy 3rd party s3 bucket.
* [NGINX](http://nginx.org) - Fast scalable webserver.
* [PHP 7](http://php.net) - Latest and greatest with performance on par with HHVM.
* [Composer](https://getcomposer.org) - A dependency manager to make installing and managing plugins easier.

Heroku WP uses the following addons:
* [MariaDB](https://mariadb.org) / [jawsdb-maria](https://elements.heroku.com/addons/jawsdb-maria) - A binary compatible MySQL replacement with even better performance.
* [Redis](http://redis.io) / [heroku-redis](https://elements.heroku.com/addons/heroku-redis) - An in-memory datastore for fast persistant object cache.
* [SendGrid](https://sendgrid.com) / [sendgrid](https://elements.heroku.com/addons/sendgrid) - SaaS email delivery service.
* [New Relic](https://newrelic.com) / [newrelic](https://elements.heroku.com/addons/newrelic) - SaaS application performance monitoring.

And optionally the following addons:
* [IronWorker](https://www.iron.io) / [iron_worker](https://elements.heroku.com/addons/iron_worker) - SaaS external jobs queue

In additon repository comes bundled with the following tools and must use plugins.
* [WP-CLI](http://wp-cli.org) - For simple management of your WP install.
* [Batcache](http://wordpress.org/plugins/batcache) - For full page output caching.
* [Redis Object Cache](http://wordpress.org/plugins/redis-cache) - For using Redis as a persistant, shared, object cache.
* [Secure DB Connection](http://wordpress.org/plugins/secure-db-connection) - For ensuring connections to the database are secure and encrypted.

Finally these plugins are pre-installed as they are highly recommended but not activated.
* [Authy Two Factor Auth](https://www.authy.com/products/wordpress)
* [Jetpack](http://jetpack.me/)
* [S3 Uploads](https://github.com/humanmade/S3-Uploads)
* [SendGrid](http://wordpress.org/plugins/sendgrid-email-delivery-simplified/)

WordPress and most included plugins are installed by Composer on build. To add new plugins or upgrade versions of plugins simply update the `composer.json` file and then generate the `composer.lock` file with the following command locally:

```bash
$ bin/composer update --ignore-platform-reqs
```

To add local plugins and themes, you can create ```plugins/``` and ```themes/``` folders inside `/public/wp-content` which upon deploy to Heroku will be copied on top of the standard WordPress install, themes, and plugins specified by Composer.

Installation
------------

Make sure you have the [Heroku Toolbelt](https://toolbelt.heroku.com/) installed and configured for your account. This provides the `heroku` CLI tool for creating and managing your Heroku apps.

Clone the repository from Github

    $ git clone https://github.com/xyu/heroku-wp.git

Run the included init script

    $ cd heroku-wp && bin/init.sh my-app-name

Use WP-CLI to install the DB and set intial settings

    $ heroku run wp core install \
        --url=my-app-name.herokuapp.com \
        --title="WordPress on Heroku" \
        --admin_user="admin" \
        --admin_password="correct-horse-battery-staple" \
        --admin_email="info@example.com"

Optional Installation
---------------------

Installing and configuring the items below are not essential to get a working WordPress install but will make your site more functional and secure.

### Sending Email

[SendGrid](http://wordpress.org/plugins/sendgrid-email-delivery-simplified) plugin is included in the repository and preconfigured to work with the [SendGrid addon](https://elements.heroku.com/addons/sendgrid) simply activate the plugin for better email delivery.

### Media Uploads

[S3 Uploads](https://github.com/humanmade/S3-Uploads) is a lightweight "drop-in" for storing WordPress uploads on [Amazon S3](http://aws.amazon.com/s3) instead of the local filesystem. If you want media uploads you must activate this plugin and configure a S3 bucket because the local filesystem for Heroku Dynos are ephemeral.

To activate this plugin:

1.  First set your S3 credentials via Heroku configs with AWS S3 path-style URLs. It's best practices to URL encode your AWS key and secret, (e.g. use `%2B` for `+` and `%2F` for `/`,) however non URL encoded values should still work even if they are invalid URLs.

    ```
    $ heroku config:set \
        AWS_S3_URL="s3://{AWS_KEY}:{AWS_SECRET}@s3.amazonaws.com/{AWS_BUCKET}"
    ```

    If you would like to set the optional region for your S3 bucket use the region-specific endpoint.

    ```
    $ heroku config:set \
        AWS_S3_URL="s3://{AWS_KEY}:{AWS_SECRET}@s3-{AWS_REGION}.amazonaws.com/{AWS_BUCKET}"
    ```

    For example, if your bucket is in the South America (São Paulo) region use:

    ```
    $ heroku config:set \
        AWS_S3_URL="s3://my-key:my-secret@s3-sa-east-1.amazonaws.com/my-bucket"
    ```

    If you would like to use a custom bucket URL either because you are proxying the requests or if you are using a domain for the bucket name you can do so by setting a custom url param:

    ```
    $ heroku config:set \
        AWS_S3_URL="s3://{AWS_KEY}:{AWS_SECRET}@s3-{AWS_REGION}.amazonaws.com/{AWS_BUCKET}?url={BUCKET_URL}"
    ```

    The `BUCKET_URL` should have a scheme attached, e.g.:

    ```
    $ heroku config:set \
        AWS_S3_URL="s3://my-key:my-secret@s3-sa-east-1.amazonaws.com/my-bucket?url=https://static.example.com"
    ```

2.  Then activate the plugin in WP Admin.

### Securing Your MySQL Connection (X509 auth or custom CAs only)

This repo already comes with both the ClearDB and Amazon RDS root CAs installed for secure DB connections. To turn on SSL simply set the `WP_DB_SSL` config. (We default to secured so this is already set to `ON` by `init.sh`.)

    $ heroku config:set \
        WP_DB_SSL="ON"

If you use another MySQL database and have a self signed cert you can add the self signed CA to the trusted store by committing it to `/support/mysql-certs` and setting the filenames or explicitly setting it in the ENV config itself:

    $ heroku config:set \
        MYSQL_SSL_CA="$(cat /path/to/server-ca.pem)"

In addition if your MySQL server requires X509 auth in addition to the username/password you can set the client cert and private key through the use of ENV vars like so (be sure to use RSA keys):

    $ heroku config:set \
        MYSQL_SSL_CERT="$(cat /path/to/client-cert.pem)" \
        MYSQL_SSL_KEY="$(cat /path/to/client-key.pem)"

### Offloaded WP Cron

WP Cron relies on visitors to the site to trigger scheduled jobs to run which can be a problem for lightly trafficked sites. Instead we can have an external jobs system (IronWorker) run WP Cron on schedule to provide consistency.

Just run the included init script to install an IronWorker task with an execution schedule of every 15 minutes.

    $ bin/init-ironworker.sh my-app-name

Usage
-----

Because a file cannot be written to Heroku's file system, updating and installing plugins or themes should be done locally and then pushed to Heroku. Even better would be to use Composer to install plugins so that version control and upgrading is simply a matter of editing the `composer.json` file and bumping the version number.

Internationalization
--------------------

In most cases you may want to have your WordPress blog in a language different than its default (US English). In that case all you need to do is download the .mo and .po files for your language from [wpcentral.io/internationalization](http://wpcentral.io/internationalization/) and place them in the
`languages` directory you'll create under `public/wp-content`. Then you should commit changes to your local branch and push them to your heroku remote. After that, you'll be able to select the new language from the WP admin panel.

Updating
--------

Updating your WordPress version is just a matter of merging the updates into
the branch created from the installation.

    $ git pull                                    # Get the latest updates
    $ git checkout {SLUG}                         # Checkout the site branch
    $ git merge origin/nginx-php7                 # Merge in latest
    $ bin/composer update --ignore-platform-reqs  # Update composer.lock file
    $ git push heroku {SLUG}:master               # Deploy to Heroku

After pushing changes update the WordPress database via WP-CLI:

    $ heroku run wp core update-db

WordPress will prompt for updating the database. After that you'll be good
to go.

Custom Domains
--------------

Heroku allows you to add custom domains to your site hosted with them.  To add your custom domain, enter in the follow commands.

    $ heroku domains:add www.example.com
    > Added www.example.com as a custom domain name to myapp.heroku.com

FAQ
---

#### Q. Help, nothing is showing up / I've polluted my cache!

One of the most common problems is if you make a DB change but still have stale cache refering to the old configs the easiest way to fix this is to use the included WP-CLI tool to flush your Redis cache:

    $ heroku run wp cache flush

#### Q. Why are you hacking Batcache?

PHP 7 support for Batcache has been merged into master however a new version has not been tagged yet. Also some bug fixes that help make sure caching headers are valid have not been merged in yet. Finally, displaying caching information in HTTP headers is a lot easier then HTML comments however it's a rather large change so I'm forking the plugin for now.

As with all external code you should trust but verify, here's the diff for the forked version against Automattic's head:

https://github.com/Automattic/batcache/compare/master...xyu:master

Running Locally
---------------

A Vagrant instance to run Heroku WP is included. To get up and running:
* Install Vagrant http://www.vagrantup.com/downloads
* Install VirtualBox https://www.virtualbox.org/wiki/Downloads

To make your life easier a Vagrant plugin can be used to manage the hosts file.

    $ vagrant plugin install vagrant-hostmanager

If you don't have vagrant-hostmanager installed you'll have to manually update
your hostfile.

Once installed `cd` into app root directory and run `$ vagrant up` (should start setting up virtual env. go grab some ☕, takes about 10 minutes)

Once Vagrant provisions the VM you will have Heroku WP running locally at `http://herokuwp.local/`. On first load, it should bring you to the WordPress install page. If the site is not accessible in the browser, you might need to add `192.168.50.100 herokuwp.local` to your hosts file manually.

As a convenience both the `/public` dir and `/composer.lock` file will be monitored by the VM. Any changes to either triggers a rebuild process which will result in `/public.built` (the web root) being updated. `/app/support` is also monitored by the VM, changes here will cause Nginx to reload with the new configs.

Connecting to MySQL on Vagrant Machine
--------------------------------------

In order to connect you will need to change the MySQL config to work with 0.0.0.0 IP address instead of localhost.
* SSH into the vm `$ vagrant ssh`
* Open the config file `$ sudo vim /etc/mysql/my.cnf`
* Change the IP address from 127.0.0.1 to 0.0.0.0

Then you can connect using SSH with the following parameters:
* SSH hostname: 127.0.0.1:2222
* SSH username: vagrant
* SSH password: vagrant
* MySQL hostname: 127.0.0.1
* MySQL port: 3306
* mysql user: herokuwp
* mysql password: password

If your computer goes to sleep and vagrant is suspended abruptly
----------------

Sometimes after `vagrant up` from an aborted state, the vm does not start correctly and the site is not accessible. When this happens force a re-provision of the machine with

    $ vagrant provision
