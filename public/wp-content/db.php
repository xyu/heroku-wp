<?php

class wpdb_ssl extends wpdb {

  /**
  * Connect to and select database.
  *
  * If $allow_bail is false, the lack of database connection will need
  * to be handled manually.
  *
  * @since 3.0.0
  * @since 3.9.0 $allow_bail parameter added.
  *
  * @param bool $allow_bail Optional. Allows the function to bail. Default true.
  * @return bool True with a successful connection, false on failure.
  */
  public function db_connect( $allow_bail = true ) {

    $this->is_mysql = true;

    /*
    * Deprecated in 3.9+ when using MySQLi. No equivalent
    * $new_link parameter exists for mysqli_* functions.
    */
    $new_link = defined( 'MYSQL_NEW_LINK' ) ? MYSQL_NEW_LINK : true;
    $client_flags = defined( 'MYSQL_CLIENT_FLAGS' ) ? MYSQL_CLIENT_FLAGS : 0;

    if ( $this->use_mysqli ) {
      $this->dbh = mysqli_init();

      // mysqli_real_connect doesn't support the host param including a port or socket
      // like mysql_connect does. This duplicates how mysql_connect detects a port and/or socket file.
      $port = null;
      $socket = null;
      $host = $this->dbhost;
      $port_or_socket = strstr( $host, ':' );
      if ( ! empty( $port_or_socket ) ) {
        $host = substr( $host, 0, strpos( $host, ':' ) );
        $port_or_socket = substr( $port_or_socket, 1 );
        if ( 0 !== strpos( $port_or_socket, '/' ) ) {
          $port = intval( $port_or_socket );
          $maybe_socket = strstr( $port_or_socket, ':' );
          if ( ! empty( $maybe_socket ) ) {
            $socket = substr( $maybe_socket, 1 );
          }
        } else {
          $socket = $port_or_socket;
        }
      }

      // Set SSL certs if we want to use secure DB connections
      if ( $client_flags & MYSQLI_CLIENT_SSL ) {
        $ssl_key = ( defined( 'MYSQL_SSL_KEY' ) && is_file( MYSQL_SSL_KEY ) ) ? MYSQL_SSL_KEY : null;
        $ssl_cert = ( defined( 'MYSQL_SSL_CERT' ) && is_file( MYSQL_SSL_CERT ) ) ? MYSQL_SSL_CERT : null;
        $ssl_ca = ( defined( 'MYSQL_SSL_CA' ) && is_file( MYSQL_SSL_CA ) ) ? MYSQL_SSL_CA : null;
        $ssl_capath = ( defined( 'MYSQL_SSL_CA_PATH' ) && is_dir( MYSQL_SSL_CA_PATH ) ) ? MYSQL_SSL_CA_PATH : null;
        $ssl_cipher = defined( 'MYSQL_SSL_CIPHER' ) ? MYSQL_SSL_CIPHER : null;

        mysqli_ssl_set( $this->dbh, $ssl_key, $ssl_cert, $ssl_ca, $ssl_capath, $ssl_cipher );
      }

      if ( WP_DEBUG ) {
        mysqli_real_connect( $this->dbh, $host, $this->dbuser, $this->dbpassword, null, $port, $socket, $client_flags );
      } else {
        @mysqli_real_connect( $this->dbh, $host, $this->dbuser, $this->dbpassword, null, $port, $socket, $client_flags );
      }

      if ( $this->dbh->connect_errno ) {
        $this->dbh = null;

        /* It's possible ext/mysqli is misconfigured. Fall back to ext/mysql if:
        *  - We haven't previously connected, and
        *  - WP_USE_EXT_MYSQL isn't set to false, and
        *  - ext/mysql is loaded.
        */
        $attempt_fallback = true;

        if ( $this->has_connected ) {
          $attempt_fallback = false;
        } else if ( defined( 'WP_USE_EXT_MYSQL' ) && ! WP_USE_EXT_MYSQL ) {
          $attempt_fallback = false;
        } else if ( ! function_exists( 'mysql_connect' ) ) {
          $attempt_fallback = false;
        }

        if ( $attempt_fallback ) {
          $this->use_mysqli = false;
          $this->db_connect();
        }
      }
    } else {
      if ( WP_DEBUG ) {
        $this->dbh = mysql_connect( $this->dbhost, $this->dbuser, $this->dbpassword, $new_link, $client_flags );
      } else {
        $this->dbh = @mysql_connect( $this->dbhost, $this->dbuser, $this->dbpassword, $new_link, $client_flags );
      }
    }

    if ( ! $this->dbh && $allow_bail ) {
      wp_load_translations_early();

      // Load custom DB error template, if present.
      if ( file_exists( WP_CONTENT_DIR . '/db-error.php' ) ) {
        require_once( WP_CONTENT_DIR . '/db-error.php' );
        die();
      }

      $this->bail( sprintf( __( "
      <h1>Error establishing a database connection</h1>
      <p>This either means that the username and password information in your <code>wp-config.php</code> file is incorrect or we can't contact the database server at <code>%s</code>. This could mean your host's database server is down.</p>
      <ul>
        <li>Are you sure you have the correct username and password?</li>
        <li>Are you sure that you have typed the correct hostname?</li>
        <li>Are you sure that the database server is running?</li>
      </ul>
      <p>If you're unsure what these terms mean you should probably contact your host. If you still need help you can always visit the <a href='https://wordpress.org/support/'>WordPress Support Forums</a>.</p>
      " ), htmlspecialchars( $this->dbhost, ENT_QUOTES ) ), 'db_connect_fail' );

      return false;
    } else if ( $this->dbh ) {
      $this->has_connected = true;
      $this->set_charset( $this->dbh );
      $this->set_sql_mode();
      $this->ready = true;
      $this->select( $this->dbname, $this->dbh );

      return true;
    }

    return false;
  }

}

$wpdb = new wpdb_ssl( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
