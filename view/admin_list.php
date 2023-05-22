	<div class="content-wrapper">
		<h2><?php echo __('Existing Campaigns', $this->textdomain); ?></h2>
		<p class="align-right">
			<a href="<?php echo $this->admin_url('action=logs');?>" class="button">Redirect Logs</a>
			<a href="<?php echo $this->admin_url('action=edit&form_id=0');?>" class="button bold">+ New Campaign</a>
		</p>
		<hr />
		
		<table class="widefat" cellspacing="0">
			<thead>
			<tr>
				<th scope="col" class="manage-column"><?php echo __('CREATE DATE', $this->textdomain);?></th>
				<th scope="col" class="manage-column"><?php echo __('TITLE', $this->textdomain);?></th>
				<th scope="col" class="manage-column"><?php echo __('SLUG', $this->textdomain);?></th>
				<th scope="col" class="manage-column"><?php echo __('REDIRECT', $this->textdomain);?></th>
				<th scope="col" class="manage-column"><?php echo __('ACTIONS', $this->textdomain);?></th>
			</tr>
			</thead>
			<tbody>
<?php		
	$list = $this->get_campaign();
	if($list){
		//$this->d($list);

		$no = 0;
		foreach ($list as $form_id => $line) {
			$campaign_redirect = '';
			$r = (array)$line['campaign_redirect'];
			foreach($r as $i => $info){
				$campaign_redirect .= $info[0] . ' (' . $info[1] . ')<br>';
			}
			
			$actions = '<a onclick="return confirm(\'Are you sure want to delete a campaign?\');" href="' . admin_url('admin-post.php?action=' . $this->textdomain . '_campaign_delete&form_id=' . esc_attr($form_id) . '&_wpnonce=' . wp_create_nonce( $this->textdomain ) ) . '">' . __('Delete', $this->textdomain) . '</a>';
			
			$actions .= ' | <a href="' . $this->_get_permalink_by_slug( wp_kses( stripslashes($line['campaign_slug']), '') ) . '" target="_blank">' . __('Test', $this->textdomain) . '</a>';
			
			$actions .= ' | <a href="' . $this->admin_url('action=edit&form_id=' . esc_attr($form_id) ) . '">' . __('Edit', $this->textdomain) . '</a>';
			
			$tr_class = ($no % 2) ? 'alternate' : ''; $no++;
?>        
			<tr class="<?php echo $tr_class;?>">
				<td><?php echo date('M d, Y H:i:s', strtotime($line['insert_date'])); ?></td>
				<td><?php echo wp_kses( stripslashes($line['campaign_title']), ''); ?></td>
				<td><?php echo wp_kses( stripslashes($line['campaign_slug']), ''); ?></td>
				<td><?php echo $campaign_redirect; ?></td>
				<td><?php echo $actions; ?></td>
			</tr>
<?php        
		}
	}
?>
			</tbody>
		</table>

	</div>