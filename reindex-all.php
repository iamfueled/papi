<?php

// when you get status "Processing" instead of "Ready" in your index management run    this script and it's working fine
 // change the index order as per your requirement currently it's 7 for Catalog Search Index  you can set as per your requirement

 require_once 'app/Mage.php';
 umask( 0 );
 Mage :: app( "default" );

 $indexingProcesses = Mage::getSingleton('index/indexer')->getProcessesCollection(); 
 foreach ($indexingProcesses as $process) {
  $process->reindexEverything();
}

?>