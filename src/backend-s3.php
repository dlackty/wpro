<?php

if (!defined('ABSPATH')) exit();

class WPRO_Backend_S3 {

	const NAME = 'Amazon S3';

	private $amazon_s3_endpoints = array(
		'US Standard (us-east-1)' => 's3.amazonaws.com',
		'US Standard, North Virginia endpoint (us-east-1)' => 's3-external-1.amazonaws.com',
		'US West, Oregon (us-west-2)' => 's3-us-west-2.amazonaws.com',
		'US West, North California (us-west-1)' => 's3-us-west-1.amazonaws.com',
		'EU, Ireland (eu-west-1)' => 's3-eu-west-1.amazonaws.com',
		'EU, Frankfurt (eu-central-1)' => 's3-eu-central-1.amazonaws.com',
		'Asia Pacific, Singapore (ap-southeast-1)' => 's3-ap-southeast-1.amazonaws.com',
		'Asia Pacific, Sydney (ap-southeast-2)' => 's3-ap-southeast-2.amazonaws.com',
		'Asia Pacific, Tokyo (ap-northeast-1)' => 's3-ap-northeast-1.amazonaws.com',
		'South America, Sao Paulo (sa-east-1)' => 's3-sa-east-1.amazonaws.com'
	);

	function activate() {
		$log = wpro()->debug->logblock('WPRO_Backend_S3::activate()');

		wpro()->options->register('wpro-aws-key');
		wpro()->options->register('wpro-aws-secret');
		wpro()->options->register('wpro-aws-bucket');
		wpro()->options->register('wpro-aws-virthost');
		wpro()->options->register('wpro-aws-endpoint');
		wpro()->options->register('wpro-aws-ssl');
		wpro()->options->register('wpro-aws-cloudfront');

		add_filter('wpro_backend_file_exists', array($this, 'file_exists'), 10, 2);
		add_filter('wpro_backend_store_file', array($this, 'store_file'));
		add_filter('wpro_backend_retrieval_baseurl', array($this, 'url'));

		return $log->logreturn(true);
	}

	function admin_form() {
		$log = wpro()->debug->logblock('WPRO_Backend_S3::admin_form()');

		?>
			<h3><?php echo(self::NAME); ?></h3>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">AWS Key</th>
					<td>
						<input type="text" name="wpro-aws-key" value="<?php echo(wpro()->options->get_option('wpro-aws-key')); ?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">AWS Secret</th>
					<td>
						<input type="text" name="wpro-aws-secret" value="<?php echo(wpro()->options->get_option('wpro-aws-secret')); ?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Endpoint</th>
					<td>
						<select name="wpro-aws-endpoint">
							<option>-</option>
							<?php
								foreach ($this->amazon_s3_endpoints as $endpoint_name => $endpoint_domain) {
									$selected = '';
									if ($endpoint_domain == wpro()->options->get_option('wpro-aws-endpoint')) {
										$selected = 'selected="selected"';
									}
									?><option value="<?php echo($endpoint_domain); ?>" <?php echo($selected); ?>><?php echo($endpoint_name); ?></option><?php
								}
							?>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Use SSL/HTTPS</th>
					<td>
						<input name="wpro-aws-ssl" id="wpro-aws-ssl" value="1" type="checkbox" <?php if (wpro()->options->get_option('wpro-aws-ssl')) echo('checked="checked"'); ?> />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">S3 Bucket</th>
					<td>
						<input type="text" name="wpro-aws-bucket" value="<?php echo(wpro()->options->get_option('wpro-aws-bucket')); ?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Use bucket name as virtual hostname</th>
					<td>
						<input name="wpro-aws-virthost" id="wpro-aws-virthost" value="1" type="checkbox" <?php if (wpro()->options->get_option('wpro-aws-virthost')) echo('checked="checked"'); ?> />
						<p class="description">
							Check this box if your bucket name is a valid domain name, and the domain is a CNAME alias for Amazon S3.
						</p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">CloudFront URL</th>
					<td>
						<input type="text" name="wpro-aws-cloudfront" value="<?php echo(wpro()->options->get_option('wpro-aws-cloudfront')); ?>" />
					</td>
				</tr>
			</table>
		<?php
		return $log->logreturn(true);
	}

	function admin_post() {
		$log = wpro()->debug->logblock('WPRO_Backend_S3::admin_post()');

		// The generic admin_post() in admin.php does not handle unchecked checkboxes.
		// Maybe that should be fixed in a more generic way... Until then:
		if (!isset($_POST['wpro-aws-ssl'])) {
			wpro()->options->set('wpro-aws-ssl', '');
		} else {
			wpro()->options->set('wpro-aws-ssl', '1');
		}
		if (!isset($_POST['wpro-aws-virthost'])) {
			wpro()->options->set('wpro-aws-virthost', '');
		} else {
			wpro()->options->set('wpro-aws-virthost', '1');
		}

		return $log->logreturn(true);
	}

	function file_exists($exists, $url) {
		$log = wpro()->debug->logblock('WPRO_Backend_S3::file_exists($exists, $url = "' . $url . '")');
		$bucket = wpro()->options->get_option('wpro-aws-bucket');
		$path = ltrim(parse_url($url, PHP_URL_PATH), '/');
		$log->log('$path = ' . var_export($path, true));

		return $log->logreturn(client()->getObjectInfo($bucket, $path, false));
	}

	function store_file($data) {
		$log = wpro()->debug->logblock('WPRO_Backend_S3::store_file($data)');
		$log->log('$data = ' . var_export($data, true));

		$file = $data['file'];
		$url = wpro()->url->relativePath($data['url']);
		$mime = $data['type'];

		if (!file_exists($file)) {
			$log->log('Error: File does not exist: ' . $file);
			return $log->logreturn(false);
		}

		$response = client()->putObjectFile($file, wpro()->options->get_option('wpro-aws-bucket'), $url, S3::ACL_PUBLIC_READ);

		if ($response) {
			return $log->logreturn($data);
		} else {
			return $log->logreturn(false);
		}
	}

	function deactivate() {
		$log = wpro()->debug->logblock('WPRO_Backend_S3::deactivate()');

		wpro()->options->deregister('wpro-aws-key');
		wpro()->options->deregister('wpro-aws-secret');
		wpro()->options->deregister('wpro-aws-bucket');
		wpro()->options->deregister('wpro-aws-virthost');
		wpro()->options->deregister('wpro-aws-endpoint');
		wpro()->options->deregister('wpro-aws-ssl');
		wpro()->options->deregister('wpro-aws-cloudfront');

		remove_filter('wpro_backend_file_exists', array($this, 'file_exists'));
		remove_filter('wpro_backend_handle_upload', array($this, 'handle_upload'));
		remove_filter('wpro_backend_retrieval_baseurl', array($this, 'url'));

		return $log->logreturn(true);
	}

	function url($value) {
		$log = wpro()->debug->logblock('WPRO_Backend_S3::url()');
		$protocol = 'http';
		if (wpro()->options->get('wpro-aws-ssl')) {
			$protocol = 'https';
		}

		if (strlen($cloudfront = trim(wpro()->options->get_option('wpro-aws-cloudfront'), '/'))) {
			$url = $protocol . '://' . $cloudfront . '/';
		} else if (wpro()->options->get_option('wpro-aws-virthost')) {
			$url = $protocol . '://' . wpro()->options->get('wpro-aws-bucket') . '/';
		} else {
			$url = $protocol . '://' . wpro()->options->get('wpro-aws-bucket') . '.' . wpro()->options->get('wpro-aws-endpoint') . '/';
		}

		if (strlen($folder = trim(wpro()->options->get_option('wpro-folder'), '/'))) {
			$url .= $folder;
		}

		return $log->logreturn($url);
	}
}

	function client() {
		$log = wpro()->debug->logblock('WPRO_Backend_S3::client()');
		$s3 = new S3(wpro()->options->get_option('wpro-aws-key'),
			wpro()->options->get_option('wpro-aws-secret'),
			wpro()->options->get('wpro-aws-ssl'),
			wpro()->options->get('wpro-aws-endpoint'));
		return $log->logreturn($s3);
	}

function wpro_setup_s3_backend() {
	wpro()->backends->register('WPRO_Backend_S3'); // Name of the class.
}
add_action('wpro_setup_backend', 'wpro_setup_s3_backend');

