<?php

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class class_smush {

	public $surl = 'http://www.smushit.com/ysmush.it/ws.php?img=';
	public $burl;
	public $bdir;

	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry   = $registry;
		$this->DB         = $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		
		$this->burl = $this->settings['upload_url'].'/';
		$this->bdir = str_replace( '&#46;', '.', $this->settings['upload_dir'] ).'/';
	}
	
	public function smushallatach() {
		$this->DB->build(
			array(
				'select' => 'attach_id,attach_ext,attach_location,attach_thumb_location',
				'from' => 'attachments',
				'where' => "smushed=0 AND attach_is_image=1",
			)
		);
		$attchs = $this->DB->execute();
		
		//check all images
		while($r = $this->DB->fetch($attchs)) {
			//send image for smush
			$file = $this->_dosmush($r['attach_location']);
			//send thumbnails
			$thumb = $this->_dosmush($r['attach_thumb_location']);
			
			//send for save in DB
			$send = $this->updatedb($file,$thumb,$r['attach_id']);
			
			//Dont sleep if send is false
			if(!$send)
				continue;

			sleep(0.2);
		}
		
		return;
	}
	
	public function _dosmush($url, $slp=0.2) {
		/* Get the file managemnet class */
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classFileManagement.php', 'classFileManagement' );
		$query = new $classToLoad();
		$query->timeout = 20;
		
		$surl = $this->surl . urlencode( $this->burl . $url );
		$smushedpic = json_decode($query->getFileContents($surl));
		
		$idir = $this->bdir . $url;
		
		if(is_file( $idir ) && $smushedpic->error) {
			return 'smushed';
		}
		
		if(!$smushedpic->dest or $smushedpic->error or $smushedpic->dest_size > $smushedpic->src_size) {
			return false;
		}
		
		$response = $query->getFileContents( $smushedpic->dest );
		if(!$response or (is_writable($idir) === false)) {
			return false;
		}
		
		$return['name'] = $url;
		$oldextention = IPSText::getFileExtension( $this->burl . $url );
		$fileExtension = IPSText::getFileExtension( $smushedpic->dest );
		if($fileExtension != $oldextention) {
			if ( ! @rename( $idir, $idir . '.' . $fileExtension ) ) {
				return false;
			}
			$idir = $idir . '.' . $fileExtension;
			$renamed = true;
			$return['name'] = $url.'.'.$fileExtension;
			$return['ext'] = $fileExtension;
		}
		
		if(!file_put_contents( $idir , $response)) {
			if($renamed) {
				@rename( $idir, $this->bdir . $url );
			}
			return false;
		}
		
		$return['saved'] = $smushedpic->src_size-$smushedpic->dest_size;
		$return['newsize'] = $smushedpic->dest_size;
		sleep($slp);
		return $return;
	}
	
	public function smushone($id) {
		$attach = $this->DB->buildAndFetch(array(
			'select' => 'attach_id,attach_ext,attach_location,attach_thumb_location,attach_is_image',
			'from' => 'attachments',
			'where' => "attach_id=".$id,
		));
		if(!$attach['attach_is_image']) {
			return false;
		}

		$file = $this->_dosmush($attach['attach_location'],0);
		$thumb = $this->_dosmush($attach['attach_thumb_location'],0);
		
		return $this->updatedb($file,$thumb,$id);
	}
	
	public function updatedb($file,$thumb,$id) {
		//skip if both of files is false
		if(!$file && !$thumb) 
			return false;
		
		$save = array();
		
		//already smushed
		if($file=='smushed') {
			$save['smushed'] = 1;
		}
		
		if( $file != 'smushed' ) {
			$save['attach_location'] = $file['name'];
			$save['attach_filesize'] = $file['newsize'];
			$save['smushedsize'] = $file['saved'];
			$save['smushed'] = 1;
			if($file['ext']) {
				$save['attach_ext'] = $file['ext'];
			}
		}
		
		if( $thumb != 'smushed') {
			$save['attach_thumb_location'] = $thumb['name'];
			$save['smushed'] = 1;
		}
		
		if( !count($save) )
			return false;
		
		$this->DB->update('attachments', $save, 'attach_id='. intval($id) );
		
		return true;
	}
}