	<div class="content-wrapper">
		<h2><?php echo __('Redirect Logs', $this->textdomain); ?></h2>
		<p class="align-right">
			<a href="<?php echo $this->admin_url('');?>" class="button">Existing Campaign</a>
			<a href="<?php echo $this->admin_url('action=edit&form_id=0');?>" class="button bold">+ New Campaign</a>
		</p>
		<hr />
		
		<table class="widefat" cellspacing="0">
			<thead>
			<tr>
				<th scope="col" class="manage-column"><?php echo __('DATE', $this->textdomain);?></th>
				<th scope="col" class="manage-column"><?php echo __('IP ADDRESS', $this->textdomain);?></th>
				<th scope="col" class="manage-column"><?php echo __('SLUG', $this->textdomain);?></th>
				<th scope="col" class="manage-column"><?php echo __('REDIRECT', $this->textdomain);?></th>
				<th scope="col" class="manage-column"><?php echo __('REFERER', $this->textdomain);?></th>
			</tr>
			</thead>
			<tbody>
<?php		
			global $wpdb;
			$table_name = $this->table_logs; 
			
			$sql = "select * from {$table_name} order by id desc ";
			$query = $wpdb->get_results( $sql );
			if($query){

				$no = 0;
				foreach ($query as $line) {
					$info = json_decode($line->log_info, true);
					
					$tr_class = ($no % 2) ? 'alternate' : ''; $no++;
?>        
					<tr class="<?php echo $tr_class;?>">
						<td><?php echo date('M d, Y H:i:s', strtotime($line->time)); ?></td>
						<td><?php echo $line->ipaddress; ?></td>
						<td><?php echo $info['campaign_slug']; ?></td>
						<td><?php echo $info['redirect']; ?></td>
						<td><?php echo $info['referer']; ?></td>
					</tr>
<?php        
				}
			}
?>
			</tbody>
		</table>
		<p class="align-right"><a href="admin-post.php?action=<?php echo $this->textdomain . '_log_clear';?>&_wpnonce=<?php echo wp_create_nonce( $this->textdomain );?>" class="button red">Clear All Logs</a></p>
		
	</div>		