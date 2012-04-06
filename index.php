<?php
/*
Plugin Name: Redirects
Description: Create redirects from old urls to the new.
Version: 1.0
Author: Loud Dog
Author URI: http://www.louddog.com
*/

// TODO: Delete options on deactivation

new LoudDog_Redirects;
class LoudDog_Redirects {
	var $slug = 'louddog_redirects';
	
	function __construct() {
		add_action('init', array($this,'redirect'), 1);
		add_action('admin_menu', array($this,'admin_menu'));
		
		if (isset($_POST[$this->slug.'_submit'])) {
			$this->save($_POST[$this->slug]);
		}
	}

	function admin_menu() {
		add_options_page(
			'Redirects',
			'Redirects',
			10,
			$this->slug,
			array($this, 'options')
		);
	}

	function options() {
		$redirects = get_option($this->slug);
		if (is_array($redirects)) ksort($redirects);
		else $redirects = array();
		
		?>
	
		<div class="wrap">
			<h2>Redirects</h2>
			
			<p>
				Looks like you've got
				<strong><?php echo count($redirects); ?></strong>
				<?php echo count($redirects) == 1 ? "redirect" : "redirects"; ?>.
			</p>
			
			<form method="post" action="options-general.php?page=<?=$this->slug?>" enctype="multipart/form-data">
				<table>
					<tr>
						<th>From</th>
						<th>To</th>
						<th>Delete</th>
					</tr>
					<tr>
						<td><small>example: /about.htm</small></td>
						<td><small>example: <?php echo get_option('home'); ?>/about/</small></td>
						<td><input type="checkbox" class="selectAll" /></td>
					</tr>

					<tr>
						<td><input type="text" name="<?=$this->slug?>[from][]" value="" style="width:30em" />&nbsp;&raquo;&nbsp;</td>
						<td><input type="text" name="<?=$this->slug?>[to][]" value="" style="width:30em;" /></td>
						<td><input type="hidden" name="<?=$this->slug?>[delete][]" value="" /></td>
					</tr>
					
					<tr>
						<td colspan="2"><hr /></td>
					</tr>

					<?php if (!empty($redirects)) foreach ($redirects as $from => $to) { ?>

						<tr>
							<td><input type="text" name="<?=$this->slug?>[from][]" value="<?=$from?>" style="width:30em" />&nbsp;&raquo;&nbsp;</td>
							<td><input type="text" name="<?=$this->slug?>[to][]" value="<?=$to?>" style="width:30em;" /></td>
							<td><input type="checkbox" name="<?=$this->slug?>[delete][]" /></td>
						</tr>

					<?php } ?>
					
				</table>
				
				<p>Or upload a .csv file full of 'em: <input type="file" name="<?=$this->slug?>_csv" /></p>

				<p class="submit"><input type="submit" name="<?=$this->slug?>_submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>
			</form>
		</div>
		
		<script>
			jQuery(function() {
				var $ = jQuery;
				
				$('input.selectAll').change(function() {
					$(this).parents('form').find('input:checkbox:not(.selectAll)').attr('checked', $(this).attr('checked'));
				});
			});
		</script>

		<?php
	}

	function save($data) {
		$redirects = array();

		foreach ($data['from'] as $i => $from) {
			if ($data['delete'][$i]) continue;
			
			$from = trim($from);
			$to = trim($data['to'][$i]);
			if (empty($from) || empty($to)) continue;
			$redirects[$from] = $to;
		}
		
		if (!empty($_FILES[$this->slug.'_csv']['tmp_name'])) {
			$csv = explode("\n", file_get_contents($_FILES[$this->slug.'_csv']['tmp_name']));
			foreach ($csv as $redirect) {
				list($from, $to) = explode(',', $redirect);
				if (empty($from) || empty($to)) continue;
				$redirects[$from] = $to;
			}
		}
		
		/*
		echo "<pre>";
		print_r($_POST);
		print_r($redirects);
		die;
		*/
		
		update_option($this->slug, $redirects);
	}

	function redirect() {
		$redirects = get_option($this->slug);
		extract(parse_url($_SERVER['REQUEST_URI']));

		if (is_array($redirects) && array_key_exists($path, $redirects)) {
			$to = $redirects[$path];
			if (!empty($query)) $to .= "?$query";

			header ('HTTP/1.1 301 Moved Permanently');
			header ('Location: '.$to);
			exit();
		}
	}
}