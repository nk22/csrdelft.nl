<?php
class MededelingContent extends SimpleHTML{
	private $mededeling;

	public function __construct(Mededeling $mededeling){
		$this->mededeling=$mededeling;

	}

	public function view(){
		$ranken=Mededeling::getRanks();

		$content=new Smarty_csr();
		define( 'NIEUWS_ROOT', '/actueel/mededelingen/');

		$content->assign('mededeling', $this->mededeling);
		$content->assign('melding', $this->getMelding());
		$content->assign('nieuws_root', NIEUWS_ROOT);
		$content->assign('ranken', $ranken);

		$content->display('mededelingen/mededeling.tpl');
	}
}
?>