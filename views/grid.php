<?php
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2015 Sangoma Technologies.
//

$pinsets = pinsets_list();
$lrows = '';
foreach ($pinsets as $pinset) {
	$lrows .= '<tr>';
	$lrows .= '<td>';
	$lrows .= $pinset['description'];
	$lrows .= '</td>';
	$lrows .= '<td>';
	$lrows .= '<a href="?display=pinsets&view=form&itemid='.$pinset['pinsets_id'].'"><i class="fa fa-edit"></i></a>&nbsp;';
	$lrows .= '<a href="?display=pinsets&action=delete&itemid='.$pinset['pinsets_id'].'"><i class="fa fa-trash"></i></a>';
	$lrows .= '</td>';
	$lrows .= '</tr>';
}

?>
<table class="table table-striped">
	<thead>
		<th><?php echo _("PIN Set")?></th>
		<th><?php echo _("Actions")?></th>
	</thead>
	<tbody>
		<?php echo $lrows ?>
	</tbody>
</table>
