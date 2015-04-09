<?php
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2015 Sangoma Technologies.
//
extract($request);
if ($itemid){
	//get details for this time condition
	$thisItem = pinsets_get($itemid);
	$description = isset($thisItem['description']) ? $thisItem['description'] : '';
	$addtocdr = isset($thisItem['addtocdr']) ? $thisItem['addtocdr'] : '';
	$passwords = isset($thisItem['passwords']) ? $thisItem['passwords'] : '';
	$deleteurl = '?display=pinsets&action=delete&itemid='.$thisItem['pinsets_id'];
}

?>
<h3><?php echo ($itemid ? _("Edit PIN Set") : _("New PIN Set")) ?></h3>
<form autocomplete="off" name="edit" action="" method="post" class="fpbx-submit" id="edit" data-fpbx-delete="<?php echo $deleteurl?>" onsubmit="return edit_onsubmit();">
	<input type="hidden" name="display" value="pinsets">
	<input type="hidden" name="view" value="form">
	<input type="hidden" name="action" value="<?php echo ($itemid ? 'edit' : 'add') ?>">
	<input type="hidden" name="account" value="<?php echo $itemid; ?>">
<!--PIN Set Description-->
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="description"><?php echo _("PIN Set Description") ?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="description"></i>
					</div>
					<div class="col-md-9">
						<input type="text" maxlength="50" class="form-control maxlen" id="description" name="description" value="<?php echo $description ?>">
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="description-help" class="help-block fpbx-help-block"><?php echo _("Please enter a description for this pinset")?></span>
		</div>
	</div>
</div>
<!--END PIN Set Description-->
<!--Record In CDR-->
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="addtocdr"><?php echo _("Record In CDR") ?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="addtocdr"></i>
					</div>
					<div class="col-md-9 radioset">
						<input type="radio" class="form-control" id="addtocdryes" name="addtocdr" value="1" <?php echo ($addtocdr == '1' ? 'CHECKED' : ''); ?>>
						<label for="addtocdryes"><?php echo _("Yes")?></label>
						<input type="radio" class="form-control" id="addtocdrno" name="addtocdr" value="0" <?php echo ($addtocdr == '1' ? '' : 'CHECKED'); ?>>
						<label for="addtocdrno"><?php echo _("No")?></label>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="addtocdr-help" class="help-block fpbx-help-block"><?php echo _("Select Yes, if you would like to record the PIN in the call detail records when used")?></span>
		</div>
	</div>
</div>
<!--END Record In CDR-->
<!--PIN List-->
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="passwords"><?php echo _("PIN List") ?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="passwords"></i>
					</div>
					<div class="col-md-9">
						<textarea rows=15 cols=20 name="passwords" class="form-control"><?php echo $passwords?></textarea>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="passwords-help" class="help-block fpbx-help-block"><?php echo _("Enter a list of one or more PINs.  One PIN per line.")?></span>
		</div>
	</div>
</div>
<!--END PIN List-->
</form>
