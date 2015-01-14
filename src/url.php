<?php

// There is no normalizing of URLs anymore. If some backend needs normalizing, it should be in the backend code...

if (!defined('ABSPATH')) exit();

class WPRO_Url {

	function __construct() {
		add_filter('upload_dir', array($this, 'upload_dir')); // Sets the paths and urls for uploads.
	}

	function attachmentUrlFromFilePath($file) {
		$baseurl = apply_filters('wpro_backend_retrieval_baseurl', '');
		return rtrim($baseurl, '/') . '/' . ltrim($this->blogRelativeUploadPath($file), '/');
	}

	function blogRelativeUploadPath($url) {
		$file = explode('/', $url);
		$parts = count($file);
		$file = rtrim(wpro()->options->get('wpro-fs-path'), '/') . '/' . $file[$parts - 3] . '/' . $file[$parts - 2] . '/' . $file[$parts - 1];
		return $file;
	}

	function upload_dir($data) {
		$log = wpro()->debug->logblock('WPRO_Url::upload_dir()');

		$backend = wpro()->backends->active_backend;
		if (is_null($backend)) return $log->logreturn($data);

		$baseurl = apply_filters('wpro_backend_retrieval_baseurl', $data['baseurl']);

		return $log->logreturn(array(
			'path' => wpro()->tmpdir->reqTmpDir() . $data['subdir'],
			'url' => $baseurl . $data['subdir'],
			'subdir' => $data['subdir'],
			'basedir' => wpro()->tmpdir->reqTmpDir(),
			'baseurl' => $baseurl,
			'error' => false
		));

	}

}
