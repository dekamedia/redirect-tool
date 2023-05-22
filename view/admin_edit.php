<?php
	$form_id = ( isset($_GET['form_id']) && !empty($_GET['form_id']) ) ? wp_kses($_GET['form_id'], '') : 0;
	
	//get options
	$options = $this->get_campaign($form_id);
	
	$the_title = __('Create New Campaign', $this->textdomain);
	if( $options ){
		$the_title = __('Edit Campaign', $this->textdomain);
	}
		
	$campaign_title = ( isset($options['campaign_title']) ) ? $options['campaign_title'] : '';
	$campaign_slug = ( isset($options['campaign_slug']) ) ? $options['campaign_slug'] : '';
	$campaign_resource = ( isset($options['campaign_resource']) ) ? $options['campaign_resource'] : '';
	$campaign_message = ( isset($options['campaign_message']) ) ? $options['campaign_message'] : '';
	$campaign_redirect = ( isset($options['campaign_redirect']) ) ? $options['campaign_redirect'] : false;
	$campaign_delay = ( isset($options['campaign_delay']) ) ? $options['campaign_delay'] : 0;
	$campaign_current_theme_css = ( isset($options['campaign_current_theme_css']) ) ? $options['campaign_current_theme_css'] : 'no';
	
	$redirect_url = $redirect_weight = false;
	if($campaign_redirect){
		foreach($campaign_redirect as $i => $info){
			$redirect_url[] = $info[0];
			$redirect_weight[] = $info[1];
		}
	}
?>
	<div class="content-wrapper">
		<h2><?php echo $the_title; ?></h2>
		<p class="align-right">
			<a href="<?php echo $this->admin_url('action=logs');?>" class="button">Redirect Logs</a>
			<a href="<?php echo $this->admin_url();?>" class="button">Existing Campaign</a>
			<a href="<?php echo $this->admin_url('action=edit&form_id=0');?>" class="button bold">+ New Campaign</a>
		</p>
		<hr />
		
		<form method="post" action="admin-post.php">
			<input type="hidden" name="action" value="<?php echo $this->textdomain . '_campaign_submit';?>">
			<input type="hidden" name="form_id" value="<?php echo $form_id;?>">

			<table class="form-table">

				<tr valign="top">
				<th scope="row"><?php echo __('Name', $this->textdomain);?></th>
				<td><input type="text" class="regular-text" name="campaign_title" value="<?php echo stripslashes($campaign_title);?>" placeholder="name your campaign">
				</td>
				</tr>

				<tr valign="top">
				<th scope="row"><?php echo __('Slug', $this->textdomain);?></th>
				<td><input type="text" class="regular-text" name="campaign_slug" id="campaign_slug" value="<?php echo stripslashes($campaign_slug);?>" placeholder="type slug..">
				</td>
				</tr>

				<tr valign="top">
				<th scope="row"><?php echo __('Redirect', $this->textdomain);?></th>
				<td>
					<?php for($x = 0; $x < $this->num_url(); $x++): ?>
					<p>URL: <input type="text" class="regular-text" name="redirect_url[]" value="<?php echo ( isset($redirect_url[$x]) ) ? esc_attr($redirect_url[$x]) : ''; ?>"> 
					W: <input type="text" class="short-text" name="redirect_weight[]" value="<?php echo ( isset($redirect_weight[$x]) ) ? (int)$redirect_weight[$x] : ''; ?>"placeholder="weight factor (1-10)"></p>
					<?php endfor; ?>
				</td>
				</tr>

				<tr valign="top">
				<th scope="row"><?php echo __('Redirect Delay', $this->textdomain);?></th>
				<td><input type="text" class="regular-text" name="campaign_delay" style="width: 50px;" value="<?php echo (int)$campaign_delay;?>" placeholder="in second"> second(s)
				</td>
				</tr>
				
				<tr valign="top">
				<th scope="row"><?php echo __('Other resources to load at WP_HEAD (javascript, tracking system, facebook pixel, etc)', $this->textdomain);?></th>
				<td><textarea class="large-text code" rows="10" cols="5" name="campaign_resource"><?php echo stripslashes($campaign_resource);?></textarea>
				</td>
				</tr>
				
				<tr valign="top">
				<th scope="row"><?php echo __('Message', $this->textdomain);?></th>
				<td><?php wp_editor( stripslashes($campaign_message), 'editor_id', array('media_buttons' => false, 'textarea_name' => 'campaign_message', 'textarea_rows' => 10 ) );?>
				</td>
				</tr>
				
				<tr valign="top">
				<th scope="row"><?php echo __('Stylesheet', $this->textdomain);?></th>
				<td>
				<p><strong><?php echo __('Do you want to use current theme to display message?', $this->textdomain);?></strong></p>
				<label><input name="campaign_current_theme_css" type="radio" value="yes" <?php echo ( $campaign_current_theme_css == 'yes' ) ? 'checked' : '';?> /> <?php echo __('Yes, please use current theme stylesheet', $this->textdomain);?></label><br>
				<label><input name="campaign_current_theme_css" type="radio" value="no" <?php echo ( $campaign_current_theme_css == 'no' ) ? 'checked' : '';?> /> <?php echo __('No, use white background with dark font color (default)', $this->textdomain);?></label>
				</td>
				</tr>
				
				<tr valign="top">
				<th scope="row">&nbsp;</th>
				<td><?php submit_button('Save Changes', 'primary'); ?></td>
				</tr>					
			</table>

			
			
		</form>
	</div>
	
<style>
/* all */
::-webkit-input-placeholder { color:#DDD; font-style: italic; }
::-moz-placeholder { color:#DDD; font-style: italic; } /* firefox 19+ */
:-ms-input-placeholder { color:#DDD; font-style: italic; } /* ie */
input:-moz-placeholder { color:#DDD; font-style: italic; }
</style>