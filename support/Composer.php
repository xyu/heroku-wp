<?php

namespace HerokuWP;

class Composer {

    public static function makeSymLinkTree( \Composer\Script\Event $event ) {

        if ( 'post-install-cmd' != $event->getName() ) {
            $event->getIO()->write( 'HerokuWP\\Composer::makeSymLinkTree must be run as post-install-cmd' );
            return;
        }

        self::_createSymLinks( 'public.composer', 'public.building', 1 );
        self::_createSymLinks( 'public', 'public.building', 1 );

    }

    private static function _createSymLinks( $src, $dest, $level ) {

        $handle  = opendir( $src );
        while ( false !== ( $filename = readdir( $handle ) ) ) {

            if ( in_array( $filename, array( '.', '..' ) ) ) {
                continue;
            }

            if ( is_dir( "$src/$filename" ) ) {
                if ( !is_dir( "$dest/$filename" ) ) {
                    self::_removeIfFileOrLink( "$dest/$filename" );
                    mkdir( "$dest/$filename", 0755 );
                }
                self::_createSymLinks( "$src/$filename", "$dest/$filename", $level + 1 );
                continue;
            }

            self::_removeIfFileOrLink( "$dest/$filename" );
            symlink( str_repeat( '../', $level ) . "$src/$filename", "$dest/$filename" );

        }

    }

    private static function _removeIfFileOrLink( $filename ) {

        if ( is_file( $filename ) || is_link( $filename ) ) {
            unlink( $filename );
        }

    }
}
