<?php /* $Id */
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2015 Sangoma Technologies.
//
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
$helptext = _("PIN Sets are used to manage lists of PINs that can be used to access restricted features such as Outbound Routes. The PIN can also be added to the CDR record's 'accountcode' field.");
$heading = _("Pin Sets");

$request = $_REQUEST;
$request['view'] = !empty($request['view']) ? $request['view'] : '';
switch ($request['view']) {
	case 'form':
		$content = load_view(__DIR__.'/views/form.php', array('request' => $request));
	break;
	default:
		$content = load_view(__DIR__.'/views/grid.php', array('request' => $request));
	break;
}

?>
<div class="container-fluid">
	<h1><?php $heading?></h1>
	<div class="well well-info">
		<?php echo $helptext?>
	</div>
	<div class = "display full-border">
		<div class="row">
			<div class="col-sm-9">
				<div class="fpbx-container">
					<div class="display full-border">
						<?php echo $content ?>
					</div>
				</div>
			</div>
			<div class="col-sm-3 hidden-xs bootnav">
				<div class="list-group">
					<?php echo load_view(__DIR__.'/views/bootnav.php', array('request' => $request))?>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript" src="/admin/modules/pinsets/assets/js/pinsets.js"></script>
