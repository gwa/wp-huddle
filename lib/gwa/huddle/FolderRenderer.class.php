<?php
namespace gwa\huddle;

class FolderRenderer
{
	private $_folder;
	private $_basefolder;
	private $_twig;

	public function __construct( \gwa\huddle\Folder $folder, $basefolder, \Twig_Environment $twig )
	{
		$this->_folder = $folder;
		$this->_basefolder = $basefolder;
		$this->_twig = $twig;
	}

	public function render( $template='folder.twig.html' )
	{
		return $this->_twig->render(
			$template,
			array(
				'folder' => $this->_folder,
				'parents' => $this->_folder->getParentsFromFolder($this->_basefolder),
				'basefolder' => $this->_basefolder
			)
		);
	}
}
