<?php
defined('_JEXEC') or die();

JFormHelper::loadFieldClass('filelist');
class JFormFieldGetcertificate extends JFormFieldFileList {

	/**
	 * Element name
	 *
	 * @access    protected
	 * @var        string
	 */
	var $type = 'Getcertificate';


	protected function getOptions() {
		$options = array();
		//$folder = $this->directory;
        $folder = (string) $this->element['directory'];
		$safePath = VmConfig::get('forSale_path', '');
        $certificatePath = $safePath . $folder;
		$certificatePath = JPath::clean($certificatePath);
        // Is the path a folder?
		if (!is_dir($certificatePath)) {
			return '<span>' . vmText::sprintf('VMPAYMENT_KKB_CERTIFICATE_FOLDER_NOT_EXIST', $certificatePath) . '</span>';
		}
		$path = str_replace('/', DS, $certificatePath);


		// Prepend some default options based on field attributes.
		if (!$this->hideNone) {
			$options[] = JHtml::_('select.option', '-1', JText::alt('JOPTION_DO_NOT_USE', preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)));
		}

//		if (!$this->hideDefault) {
//			$options[] = JHtml::_('select.option', '', JText::alt('JOPTION_USE_DEFAULT', preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)));
//		}

		// Get a list of files in the search path with the given filter.
		$files = JFolder::files($path, $this->filter);

		// Build the options list from the list of files.
		if (is_array($files)) {
			foreach ($files as $file) {
				// Check to see if the file is in the exclude mask.
				if ($this->exclude) {
					if (preg_match(chr(1) . $this->exclude . chr(1), $file)) {
						continue;
					}
				}

				// If the extension is to be stripped, do it.
				if ($this->stripExt) {
					$file = JFile::stripExt($file);
				}

				$options[] = JHtml::_('select.option', $file, $file);
			}
		}

		return $options;
	}

}