<?php 
require_once 'app/Mage.php';
umask( 0 );
Mage :: app( "default" );
$ver = Mage :: getVersion();
$userModel = Mage :: getModel( 'admin/user' );
$userModel -> setUserId( 0 );
Mage :: getSingleton( 'admin/session' ) -> setUser( $userModel );
echo "Refreshing cache...\n";
Mage :: app() -> cleanCache();
$enable = array();
foreach ( Mage :: helper( 'core' ) -> getCacheTypes() as $type => $label ) {
$enable[$type] = 1;
}
Mage :: app() -> saveUseCache( $enable );
echo "Cache refreshed";
?>