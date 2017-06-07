<?php
/*
Plugin Name: Redirects
Description: Create redirects from old urls to the new.
Version: 1.2
Author: Loud Dog
Author URI: http://www.louddog.com
*/

new LoudDog_Redirects;
class LoudDog_Redirects {
	var $slug = 'louddog_redirects';
	
	function __construct() {
		add_action('init', array($this,'redirect'), 1);
		add_action('admin_menu', array($this,'admin_menu'));
		add_action('admin_init', array($this,'save'));
	}

	function admin_menu() {
		add_options_page(
			'Redirects',
			'Redirects',
			'administrator',
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
				<strong><?php echo count($redirects) ?></strong>
				<?php echo count($redirects) == 1 ? "redirect" : "redirects" ?>.
			</p>
			
			<h3>New</h3>
			<form method="post" action="options-general.php?page=<?php echo $this->slug ?>" enctype="multipart/form-data">
				<table>
					<tr>
						<th>From</th>
						<th>To</th>
					</tr>
					<tr>
						<td><input type="text" name="<?php echo $this->slug ?>[from][new]" style="width:30em" id="redir_from_input" />&nbsp;&raquo;&nbsp;</td>
						<td><input type="text" name="<?php echo $this->slug ?>[to][new]" style="width:30em;" id="redir_to_input" /></td>
					</tr>
					<tr>
						<td><small>example: /about.htm</small></td>
						<td><small>example: <?php echo get_option('home'); ?>/about/</small></td>
					</tr>
				</table>

				<p>Or upload a .csv file full of 'em: <input type="file" name="<?php echo $this->slug ?>_csv" /></p>

				<p class="submit"><input type="submit" name="<?php echo $this->slug ?>_submit" class="button-primary" value="<?php _e('Save') ?>" id="redir_save_btn" /></p>
				
				<script>
					var saveBtn = document.getElementById('redir_save_btn');
					saveBtn.disabled = true;
					
					var fromInput = document.getElementById('redir_from_input');
					var toInput = document.getElementById('redir_to_input');
					
					function enableBtn(){
						//Only enable the save button if the inputs have a different value.
						saveBtn.disabled = fromInput.value === toInput.value;
					}
					
					//Respond to input change events
					if(fromInput.addEventListener) {
						fromInput.addEventListener('keyup', enableBtn);
						toInput.addEventListener('keyup', enableBtn);
					} else if(fromInput.attachEvent) {
						fromInput.attachEvent('onkeyup', enableBtn);
						toInput.attachEvent('onkeyup', enableBtn);
					} else {
						//Can't respond to input events so just enable the button all the time.
						saveBtn.disabled = false;
					}
				</script>
			</form>

			<?php if (!empty($redirects)) { ?>
				<h3>Existing</h3>
				<form method="post" action="options-general.php?page=<?php echo $this->slug ?>" enctype="multipart/form-data">
					<table>
						<tr>
							<th>From</th>
							<th>To</th>
						</tr>
						<?php foreach ($redirects as $from => $to) { ?>
							<tr>
								<td><input type="text" name="<?php echo $this->slug ?>[from][]" value="<?php echo $from ?>" style="width:30em" />&nbsp;&raquo;&nbsp;</td>
								<td><input type="text" name="<?php echo $this->slug ?>[to][]" value="<?php echo $to ?>" style="width:30em;" /></td>
								<td><a href="options-general.php?page=<?php echo $this->slug ?>&<?php echo $this->slug ?>[delete]=<?php echo urlencode($from) ?>">delete</a></td>
							</tr>
						<?php } ?>
					</table>

					<p class="submit"><input type="submit" name="<?php echo $this->slug ?>_submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>
				</form>
			<?php } ?>
		</div>

		<?php
	}

	function save() {
		if (!isset($_REQUEST[$this->slug]) && empty($_FILES[$this->slug.'_csv'])) return;

		$data = $_REQUEST[$this->slug];
		$redirects = get_option($this->slug);

		if (!empty($_FILES[$this->slug.'_csv']['tmp_name'])) {
			$csv = fopen($_FILES[$this->slug.'_csv']['tmp_name'], 'r');
			while ($redirect = fgetcsv($csv)) {
				array_walk($redirect, 'trim');
				list($from, $to) = $redirect;
				if (empty($from) || empty($to)) continue;
				$redirects[$from] = $to;
			}
		} else if (isset($data['delete'])) {
			unset($redirects[$data['delete']]);
		} else if (isset($data['from']['new'])) {
			$from = trim($data['from']['new']);
			$to = trim($data['to']['new']);
			$redirects[$from] = $to;
		} else {
			$redirects = array();
			$changes = array_combine($data['from'], $data['to']);
			foreach ($changes as $from => $to) {
				$from = trim($from);
				$to = trim($to);
				$redirects[$from] = $to;
			}
		}

		$processed = array();
		foreach ($redirects as $from => $to) {
			if (!preg_match("/^\//", $from)) {
				$from = "/$from";
			}

			if (!preg_match("/^https?:\/\/|\//", $to)) {
				$to = preg_match("/\.(com|net|org)/", $to)
					? "http://$to"
					: "/$to";
			}

			$processed[$from] = $to;
		}
		$redirects = $processed;

		update_option($this->slug, $redirects);

		wp_redirect("options-general.php?page=$this->slug");
		exit;
	}

	function redirect() {
		$redirects = get_option( $this->slug );
		if ( is_array( $redirects ) ) {
			$url = parse_url( $_SERVER['REQUEST_URI'] );

			if ( isset( $redirects[$url['path']] ) ) {
				$to = $redirects[$url['path']];
				$query = $url['query'];
				if ( !empty( $query ) ) $to .= "?$query";
				wp_redirect( $to, 301 );
				exit;
			}
		}
	}
}
