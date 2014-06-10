<?php

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_forums_smushit_smush extends ipsCommand
{
	protected $html;
	public $class_smush;
	
	public function doExecute( ipsRegistry $registry ) 
	{
		$this->registry->class_localization->loadLanguageFile( array( 'admin_feelpost' ) );
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_smush' );
		
		$this->lang->loadLanguageFile( array( 'admin_smush' ) );
		
		$this->html->form_code    = 'module=smushit&amp;section=smush&amp;';
		$this->html->form_code_js = 'module=smushit&amp;section=smush&amp;';
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_smush.php', 'class_smush' );
		$this->class_smush = new $classToLoad( $registry );
		
		switch($this->request['do']) {
			case 'smushall':
				$this->class_smush->smushallatach();
				$this->registry->output->global_message = $this->lang->words['smush_smushedcomplete'];
				$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->html->form_code  );
			break;
		}
		
		$this->ginformation();
		
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->html_main .= $this->html->main();
		$this->registry->output->sendOutput();
	}
	
	public function ginformation() {
		$count = $this->DB->buildAndFetch( array( 
			'select' => 'COUNT(*) as cnt',
			'from'   => 'attachments',
			'where'  => "smushed=0 AND attach_is_image=1"
		));
		$this->lang->words['smush_information'] = sprintf($this->lang->words['smush_information'],$count['cnt'],$count['cnt']*3/60);
		
		if(!$count['cnt']) {
			$this->lang->words['smush_information'] = $this->lang->words['smush_anyimages'];
		}
		
		$smushed = $this->DB->buildAndFetch(
			array(
				'select' => 'SUM(smushedsize) as sum, COUNT(*) as count',
				'from' => 'attachments',
				'where' => "smushed=1 AND attach_is_image=1",
			)
		);
		
		$smushed['sum'] = IPSLib::sizeFormat(intval($smushed['sum']));
		$this->lang->words['smush_smushed'] = sprintf($this->lang->words['smush_smushed'],$smushed['count'],$smushed['sum']);
	}
}